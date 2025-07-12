<?php

class FlashMessage {
    
    /**
     * Set a flash message
     */
    public static function set($type, $message) {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Set a success message
     */
    public static function success($message) {
        self::set('success', $message);
    }
    
    /**
     * Set an error message
     */
    public static function error($message) {
        self::set('error', $message);
    }
    
    /**
     * Set a warning message
     */
    public static function warning($message) {
        self::set('warning', $message);
    }
    
    /**
     * Set an info message
     */
    public static function info($message) {
        self::set('info', $message);
    }
    
    /**
     * Get all flash messages and clear them
     */
    public static function getAll() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        
        return $messages;
    }
    
    /**
     * Check if there are any flash messages
     */
    public static function has() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return !empty($_SESSION['flash_messages']);
    }
    
    /**
     * Clear all flash messages
     */
    public static function clear() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['flash_messages']);
    }
} 