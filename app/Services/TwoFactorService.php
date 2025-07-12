<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PragmaRX\Google2FA\Google2FA;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class TwoFactorService {
    
    private $google2fa;
    
    public function __construct() {
        $this->google2fa = new Google2FA();
    }
    
    /**
     * Generate a new secret key for 2FA
     */
    public function generateSecretKey() {
        return $this->google2fa->generateSecretKey();
    }
    
    /**
     * Generate QR code URL for Google Authenticator
     */
    public function getQRCodeUrl($user, $secret) {
        $companyName = 'Accounting Panel';
        $companyEmail = $user['email'];
        
        return $this->google2fa->getQRCodeUrl(
            $companyName,
            $companyEmail,
            $secret
        );
    }
    
    /**
     * Generate QR code image as base64 data URL
     */
    public function getQRCodeImage($user, $secret) {
        $qrCodeUrl = $this->getQRCodeUrl($user, $secret);
        
        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrCodeUrl)
                ->size(200)
                ->margin(10)
                ->build();
            
            return 'data:image/png;base64,' . base64_encode($result->getString());
        } catch (Exception $e) {
            // Fallback: return a simple text-based representation
            AppLogger::error('QR Code generation failed', [
                'error' => $e->getMessage(),
                'user_email' => $user['email']
            ]);
            
            // Return a placeholder image or the URL itself
            return 'data:text/plain;base64,' . base64_encode($qrCodeUrl);
        }
    }
    
    /**
     * Verify a 2FA code
     */
    public function verifyCode($secret, $code) {
        return $this->google2fa->verifyKey($secret, $code);
    }
    
    /**
     * Generate backup codes
     */
    public function generateBackupCodes($count = 8) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }
        return $codes;
    }
    
    /**
     * Verify backup code
     */
    public function verifyBackupCode($backupCodes, $code) {
        if (empty($backupCodes)) {
            return false;
        }
        
        $codes = json_decode($backupCodes, true);
        if (!is_array($codes)) {
            return false;
        }
        
        $code = strtoupper(trim($code));
        $index = array_search($code, $codes);
        
        if ($index !== false) {
            // Remove used backup code
            unset($codes[$index]);
            return [
                'valid' => true,
                'remaining_codes' => json_encode(array_values($codes))
            ];
        }
        
        return ['valid' => false, 'remaining_codes' => $backupCodes];
    }
} 