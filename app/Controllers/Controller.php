<?php

require_once __DIR__ . '/../Services/FlashMessage.php';

class Controller {
    protected function view($view, $data = []) {
        // Ensure CSRF token is available in all views
        if (!isset($data['csrf_token'])) {
            $data['csrf_token'] = $this->getCsrfToken();
        }
        
        // Make flash messages available in all views
        $data['flash_messages'] = FlashMessage::getAll();
        
        extract($data);
        $view_data = $data;
        require_once __DIR__ . "/../Views/{$view}.php";
    }
    
    /**
     * Get or generate CSRF token
     */
    protected function getCsrfToken() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate token if not exists
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['_token'];
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCsrfToken() {
        $sessionToken = $_SESSION['_token'] ?? '';
        $postToken = $_POST['_token'] ?? '';
        
        if (empty($sessionToken) || empty($postToken) || !hash_equals($sessionToken, $postToken)) {
            AppLogger::warning('CSRF token validation failed', [
                'session_token_exists' => !empty($sessionToken),
                'post_token_exists' => !empty($postToken),
                'session_id' => session_id(),
                'session_token_length' => strlen($sessionToken),
                'post_token_length' => strlen($postToken),
                'session_token_preview' => substr($sessionToken, 0, 8) . '...',
                'post_token_preview' => substr($postToken, 0, 8) . '...',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
            return false;
        }
        
        AppLogger::debug('CSRF token validation successful', [
            'session_id' => session_id(),
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        
        return true;
    }
} 