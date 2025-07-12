<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Services/TwoFactorService.php';

class AuthController extends Controller {

    private $userModel;
    private $twoFactorService;

    public function __construct($db) {
        $this->userModel = new User($db);
        $this->twoFactorService = new TwoFactorService();
    }

    public function showLoginForm() {
        // CSRF token will be automatically added by base Controller
        $this->view('auth/login');
    }

    public function login() {
        // Validate CSRF token using base controller method
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Security token mismatch. Please try again.');
            $this->view('auth/login');
            return;
        }

        if ($this->isRateLimited()) {
            FlashMessage::error('Too many login attempts. Please try again later.');
            $this->view('auth/login');
            return;
        }

        // Validate inputs
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->recordLoginAttempt(); // Record as a failed attempt
            FlashMessage::error('Please enter valid email and password.');
            $this->view('auth/login');
            return;
        }

        // Mitigate timing attacks and verify credentials
        $user = $this->userModel->findByEmail($email);

        // If user exists, use their hash. If not, use a dummy hash.
        // This ensures password_verify runs every time, making timing attacks harder.
        $correctHash = $user ? $user['password'] : password_hash('dummy_password_for_timing_attack_mitigation', PASSWORD_DEFAULT);

        if (password_verify($password, $correctHash) && $user) {
            // Check if 2FA is enabled
            if ($user['two_factor_enabled']) {
                // Store user data temporarily for 2FA verification
                $_SESSION['2fa_user_id'] = $user['id'];
                $_SESSION['2fa_user_data'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                
                AppLogger::info('2FA required for user', [
                    'user_id' => $user['id'],
                    'email' => $user['email']
                ]);
                
                header('Location: /2fa/verify');
                exit();
            }
            
            // Regular login without 2FA
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            unset($_SESSION['login_attempts']);
            
            AppLogger::info('User logged in successfully', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);
            
            FlashMessage::success('Welcome back, ' . htmlspecialchars($user['name']) . '!');
            header('Location: /');
            exit();
        } else {
            $this->recordLoginAttempt();
            AppLogger::warning('Failed login attempt', [
                'email' => $email,
                'user_exists' => $user ? true : false
            ]);
            FlashMessage::error('Invalid email or password. Please try again.');
            $this->view('auth/login');
        }
    }

    public function logout() {
        $userName = $_SESSION['user']['name'] ?? 'User';
        
        // Unset all session values
        $_SESSION = [];

        // Invalidate the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
        
        // Start a new session to show the flash message
        session_start();
        FlashMessage::success('You have been logged out successfully. See you soon, ' . htmlspecialchars($userName) . '!');

        header('Location: /login');
        exit();
    }

    private function isRateLimited() {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }

        // Remove old attempts
        $timeout = Config::get('auth.login_attempts_timeout', 300);
        $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function ($timestamp) use ($timeout) {
            return $timestamp > (time() - $timeout);
        });

        $limit = Config::get('auth.login_attempts_limit', 5);
        return count($_SESSION['login_attempts']) >= $limit;
    }

    private function recordLoginAttempt() {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        $_SESSION['login_attempts'][] = time();
    }
    
    public function show2FAForm() {
        // Check if user is in 2FA verification state
        if (!isset($_SESSION['2fa_user_id'])) {
            header('Location: /login');
            exit();
        }
        
        $this->view('auth/2fa-verify');
    }
    
    public function verify2FA() {
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Security token mismatch. Please try again.');
            $this->view('auth/2fa-verify');
            return;
        }
        
        // Check if user is in 2FA verification state
        if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_user_data'])) {
            FlashMessage::error('Invalid 2FA session. Please login again.');
            header('Location: /login');
            exit();
        }
        
        $userId = $_SESSION['2fa_user_id'];
        $userData = $_SESSION['2fa_user_data'];
        $code = trim($_POST['code'] ?? '');
        
        if (empty($code)) {
            FlashMessage::error('Please enter the 2FA code.');
            $this->view('auth/2fa-verify');
            return;
        }
        
        // Get user data to verify 2FA
        $user = $this->userModel->find($userId);
        if (!$user || !$user['two_factor_enabled']) {
            FlashMessage::error('2FA is not properly configured. Please contact support.');
            header('Location: /login');
            exit();
        }
        
        $isValid = false;
        
        // First try to verify as regular 2FA code
        if ($this->twoFactorService->verifyCode($user['two_factor_secret'], $code)) {
            $isValid = true;
        } else {
            // Try to verify as backup code
            $backupResult = $this->twoFactorService->verifyBackupCode($user['two_factor_backup_codes'], $code);
            if ($backupResult['valid']) {
                $isValid = true;
                // Update remaining backup codes
                $this->userModel->updateBackupCodes($userId, $backupResult['remaining_codes']);
                
                AppLogger::info('Backup code used for 2FA', [
                    'user_id' => $userId,
                    'email' => $user['email']
                ]);
            }
        }
        
        if ($isValid) {
            // Complete login process
            session_regenerate_id(true);
            $_SESSION['user'] = $userData;
            
            // Clean up 2FA session data
            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_user_data']);
            unset($_SESSION['login_attempts']);
            
            AppLogger::info('User logged in successfully with 2FA', [
                'user_id' => $userId,
                'email' => $user['email'],
                'role' => $user['role']
            ]);
            
            FlashMessage::success('Welcome back, ' . htmlspecialchars($userData['name']) . '!');
            header('Location: /');
            exit();
        } else {
            $this->recordLoginAttempt();
            AppLogger::warning('Failed 2FA verification', [
                'user_id' => $userId,
                'email' => $user['email']
            ]);
            FlashMessage::error('Invalid 2FA code. Please try again.');
            $this->view('auth/2fa-verify');
        }
    }
} 