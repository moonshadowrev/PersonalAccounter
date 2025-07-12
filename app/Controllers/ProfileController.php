<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/ApiKey.php';
require_once __DIR__ . '/../Services/TwoFactorService.php';

class ProfileController extends Controller {

    private $userModel;
    private $apiKeyModel;
    private $twoFactorService;

    public function __construct($db) {
        $this->userModel = new User($db);
        $this->apiKeyModel = new ApiKey($db);
        $this->twoFactorService = new TwoFactorService();
    }

    private function checkAuthentication() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit();
        }
    }
    
    private function checkSuperAdminAccess() {
        $this->checkAuthentication();
        
        // Check if user is superadmin
        if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'superadmin') {
            FlashMessage::error('Access denied. API key management is restricted to super administrators.');
            header('Location: /');
            exit();
        }
    }

    public function edit() {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            FlashMessage::error('User profile not found.');
            header('Location: /');
            exit();
        }
        
        $this->view('dashboard/profile/edit', ['user' => $user]);
    }

    public function update() {
        $this->checkAuthentication();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /profile/edit');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        // Get current user data for password verification
        $currentUser = $this->userModel->find($userId);
        if (!$currentUser) {
            FlashMessage::error('User profile not found.');
            header('Location: /profile/edit');
            exit();
        }
        
        // Debug logging
        AppLogger::info('Profile update attempt', [
            'user_id' => $userId,
            'has_new_password' => !empty($_POST['new_password']),
            'has_current_password' => !empty($_POST['current_password']),
            'post_data_keys' => array_keys($_POST)
        ]);
        
        // Basic validation
        if (empty($_POST['name']) || empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            FlashMessage::error('Please fill in all required fields with valid information.');
            header('Location: /profile/edit');
            exit();
        }
        
        // Verify current password (required for any profile changes)
        if (empty($_POST['current_password'])) {
            FlashMessage::error('Current password is required to save changes.');
            header('Location: /profile/edit');
            exit();
        }
        
        if (!password_verify($_POST['current_password'], $currentUser['password'])) {
            AppLogger::warning('Profile update failed - incorrect current password', [
                'user_id' => $userId,
                'email' => $currentUser['email']
            ]);
            FlashMessage::error('Current password is incorrect.');
            header('Location: /profile/edit');
            exit();
        }

        // Check if email is already taken by another user
        $existingUser = $this->userModel->findByEmail($_POST['email']);
        if ($existingUser && $existingUser['id'] != $userId) {
            FlashMessage::error('This email address is already in use by another account.');
            header('Location: /profile/edit');
            exit();
        }
        
        $data = [
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email'])
        ];
        
        // Handle password change if new password is provided
        if (!empty($_POST['new_password'])) {
            if (strlen($_POST['new_password']) < 6) {
                FlashMessage::error('New password must be at least 6 characters long.');
                header('Location: /profile/edit');
                exit();
            }
            
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                FlashMessage::error('New password confirmation does not match.');
                header('Location: /profile/edit');
                exit();
            }
            
            // Check if new password is different from current password
            if (password_verify($_POST['new_password'], $currentUser['password'])) {
                FlashMessage::error('New password must be different from your current password.');
                header('Location: /profile/edit');
                exit();
            }
            
            $data['password'] = $_POST['new_password']; // Model handles hashing
            
            AppLogger::info('Password change requested', [
                'user_id' => $userId,
                'email' => $currentUser['email']
            ]);
        }
        
        try {
            $updateResult = $this->userModel->update($userId, $data);
            
            AppLogger::info('Profile update database result', [
                'user_id' => $userId,
                'update_result' => $updateResult,
                'data_keys' => array_keys($data),
                'has_password_change' => isset($data['password'])
            ]);
            
            // Update session data
            $_SESSION['user']['name'] = $data['name'];
            $_SESSION['user']['email'] = $data['email'];
            
            if (isset($data['password'])) {
                AppLogger::info('Password updated successfully', [
                    'user_id' => $userId,
                    'email' => $data['email']
                ]);
                FlashMessage::success('Profile and password updated successfully!');
            } else {
                FlashMessage::success('Profile updated successfully!');
            }
        } catch (Exception $e) {
            AppLogger::error('Profile update failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            FlashMessage::error('Failed to update profile. Please try again.');
        }
        
        header('Location: /profile/edit');
        exit();
    }
    
    public function setup2FA() {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            FlashMessage::error('User profile not found.');
            header('Location: /profile/edit');
            exit();
        }
        
        // Generate new secret if not exists or if user wants to reset
        $secret = $this->twoFactorService->generateSecretKey();
        $qrCodeImage = $this->twoFactorService->getQRCodeImage($user, $secret);
        
        $this->view('dashboard/profile/2fa-setup', [
            'user' => $user,
            'secret' => $secret,
            'qrCodeImage' => $qrCodeImage
        ]);
    }
    
    public function enable2FA() {
        $this->checkAuthentication();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /profile/2fa/setup');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $secret = trim($_POST['secret'] ?? '');
        $code = trim($_POST['code'] ?? '');
        
        if (empty($secret) || empty($code)) {
            FlashMessage::error('Please provide both secret and verification code.');
            header('Location: /profile/2fa/setup');
            exit();
        }
        
        // Verify the code
        if (!$this->twoFactorService->verifyCode($secret, $code)) {
            FlashMessage::error('Invalid verification code. Please try again.');
            header('Location: /profile/2fa/setup');
            exit();
        }
        
        // Generate backup codes
        $backupCodes = $this->twoFactorService->generateBackupCodes();
        
        // Enable 2FA for user
        try {
            $this->userModel->enable2FA($userId, $secret, $backupCodes);
            
            AppLogger::info('2FA enabled for user', [
                'user_id' => $userId,
                'email' => $_SESSION['user']['email']
            ]);
            
            // Show backup codes to user
            $this->view('dashboard/profile/2fa-backup-codes', [
                'backupCodes' => $backupCodes
            ]);
            
        } catch (Exception $e) {
            AppLogger::error('Failed to enable 2FA', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to enable 2FA. Please try again.');
            header('Location: /profile/2fa/setup');
            exit();
        }
    }
    
    public function disable2FA() {
        $this->checkAuthentication();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /profile/edit');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $currentPassword = trim($_POST['current_password'] ?? '');
        
        if (empty($currentPassword)) {
            FlashMessage::error('Current password is required to disable 2FA.');
            header('Location: /profile/edit');
            exit();
        }
        
        // Verify current password
        $user = $this->userModel->find($userId);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            FlashMessage::error('Current password is incorrect.');
            header('Location: /profile/edit');
            exit();
        }
        
        try {
            $this->userModel->disable2FA($userId);
            
            AppLogger::info('2FA disabled for user', [
                'user_id' => $userId,
                'email' => $user['email']
            ]);
            
            FlashMessage::success('Two-factor authentication has been disabled.');
        } catch (Exception $e) {
            AppLogger::error('Failed to disable 2FA', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to disable 2FA. Please try again.');
        }
        
        header('Location: /profile/edit');
        exit();
    }
    
    public function regenerateBackupCodes() {
        $this->checkAuthentication();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /profile/edit');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $user = $this->userModel->find($userId);
        
        if (!$user || !$user['two_factor_enabled']) {
            FlashMessage::error('2FA is not enabled for your account.');
            header('Location: /profile/edit');
            exit();
        }
        
        // Generate new backup codes
        $backupCodes = $this->twoFactorService->generateBackupCodes();
        
        try {
            $this->userModel->updateBackupCodes($userId, json_encode($backupCodes));
            
            AppLogger::info('Backup codes regenerated for user', [
                'user_id' => $userId,
                'email' => $user['email']
            ]);
            
            // Show new backup codes to user
            $this->view('dashboard/profile/2fa-backup-codes', [
                'backupCodes' => $backupCodes,
                'regenerated' => true
            ]);
            
        } catch (Exception $e) {
            AppLogger::error('Failed to regenerate backup codes', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to regenerate backup codes. Please try again.');
            header('Location: /profile/edit');
            exit();
        }
    }
    
    // API Key Management Methods
    
    public function apiKeys() {
        $this->checkSuperAdminAccess();
        
        $userId = $_SESSION['user']['id'];
        $apiKeys = $this->apiKeyModel->getUserApiKeys($userId);
        
        $this->view('dashboard/profile/api-keys', [
            'apiKeys' => $apiKeys
        ]);
    }
    
    public function createApiKey() {
        $this->checkSuperAdminAccess();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /profile/api-keys');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $name = trim($_POST['name'] ?? '');
        $permissions = $_POST['permissions'] ?? [];
        $rateLimitPerMinute = (int)($_POST['rate_limit_per_minute'] ?? 60);
        $expiresAt = trim($_POST['expires_at'] ?? '');
        
        // Validate input
        if (empty($name)) {
            FlashMessage::error('API key name is required.');
            header('Location: /profile/api-keys');
            exit();
        }
        
        if ($rateLimitPerMinute < 1 || $rateLimitPerMinute > 1000) {
            FlashMessage::error('Rate limit must be between 1 and 1000 requests per minute.');
            header('Location: /profile/api-keys');
            exit();
        }
        
        $expiresAtFormatted = null;
        if (!empty($expiresAt)) {
            $expiresAtTimestamp = strtotime($expiresAt);
            if ($expiresAtTimestamp === false || $expiresAtTimestamp <= time()) {
                FlashMessage::error('Expiration date must be a valid future date.');
                header('Location: /profile/api-keys');
                exit();
            }
            $expiresAtFormatted = date('Y-m-d H:i:s', $expiresAtTimestamp);
        }
        
        try {
            $result = $this->apiKeyModel->generateApiKey(
                $name,
                $userId,
                !empty($permissions) ? $permissions : null,
                $rateLimitPerMinute,
                $expiresAtFormatted
            );
            
            if ($result) {
                AppLogger::info('API key created', [
                    'user_id' => $userId,
                    'api_key_id' => $result['id'],
                    'name' => $name
                ]);
                
                // Show the API key only once
                $this->view('dashboard/profile/api-key-created', [
                    'apiKey' => $result
                ]);
                return;
            } else {
                FlashMessage::error('Failed to create API key. Please try again.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to create API key', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to create API key. Please try again.');
        }
        
        header('Location: /profile/api-keys');
        exit();
    }
    
    public function deleteApiKey($keyId) {
        $this->checkSuperAdminAccess();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /profile/api-keys');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        try {
            $result = $this->apiKeyModel->deleteKey($keyId, $userId);
            
            if ($result && $result->rowCount() > 0) {
                AppLogger::info('API key deleted permanently', [
                    'user_id' => $userId,
                    'api_key_id' => $keyId
                ]);
                FlashMessage::success('API key has been permanently deleted.');
            } else {
                FlashMessage::error('API key not found or could not be deleted.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to delete API key', [
                'user_id' => $userId,
                'api_key_id' => $keyId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to delete API key. Please try again.');
        }
        
        header('Location: /profile/api-keys');
        exit();
    }
} 