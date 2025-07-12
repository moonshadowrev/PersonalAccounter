<?php

require_once __DIR__ . '/Controller.php';

class ErrorController extends Controller {
    public function __construct() {
        // No longer need logger injection - using static AppLogger
    }

    /**
     * Handle 404 Not Found errors
     */
    public function notFound() {
        http_response_code(404);
        
        AppLogger::warning('404 Not Found', [
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'none',
            'ip' => $this->getClientIp()
        ]);
        
        $this->clearOutputBuffer();
        $this->view('errors/404');
        exit();
    }

    /**
     * Handle 500 Internal Server errors
     */
    public function serverError($message = 'An unexpected error occurred.', $exception = null) {
        http_response_code(500);
        
        // Log the detailed error message
        $logData = [
            'message' => $message,
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => $this->getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Add exception details if provided
        if ($exception instanceof Throwable) {
            $logData['exception'] = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }
        
        AppLogger::error('Server Error', $logData);
        
        $this->clearOutputBuffer();
        
        // Determine what to show based on environment
        $isDebug = Config::get('debug', false);
        $env = Config::get('env', 'production');
        
        if ($isDebug || $env === 'development') {
            // Show detailed error in development
            $this->view('errors/500-debug', [
                'message' => $message,
                'exception' => $exception,
                'env' => $env
            ]);
        } else {
            // Show generic error in production
            $this->view('errors/500', [
                'message' => 'We are currently experiencing some technical difficulties. Please try again later.'
            ]);
        }
        
        exit();
    }

    /**
     * Handle 403 Forbidden errors
     */
    public function forbidden($message = 'Access denied.') {
        http_response_code(403);
        
        AppLogger::warning('403 Forbidden', [
            'message' => $message,
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'session_id' => session_id(),
            'ip' => $this->getClientIp()
        ]);
        
        $this->clearOutputBuffer();
        $this->view('errors/403', ['message' => $message]);
        exit();
    }

    /**
     * Handle database connection errors
     */
    public function databaseError($message = 'Database connection failed.') {
        http_response_code(503);
        
        AppLogger::critical('Database Error', [
            'message' => $message,
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'ip' => $this->getClientIp()
        ]);
        
        $this->clearOutputBuffer();
        
        $isDebug = Config::get('debug', false);
        $env = Config::get('env', 'production');
        
        if ($isDebug || $env === 'development') {
            $this->view('errors/503-debug', ['message' => $message]);
        } else {
            $this->view('errors/503', [
                'message' => 'The service is temporarily unavailable. Please try again later.'
            ]);
        }
        
        exit();
    }

    /**
     * Handle maintenance mode
     */
    public function maintenance() {
        http_response_code(503);
        
        AppLogger::info('Maintenance mode accessed', [
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'ip' => $this->getClientIp()
        ]);
        
        $this->clearOutputBuffer();
        $this->view('errors/maintenance');
        exit();
    }

    /**
     * Handle general application errors with proper logging
     */
    public function handleException($exception) {
        // Determine error type and handle accordingly
        if ($exception instanceof PDOException) {
            $this->databaseError($exception->getMessage());
        } else {
            $this->serverError($exception->getMessage(), $exception);
        }
    }

    /**
     * Clear any existing output buffer
     */
    private function clearOutputBuffer() {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Handle AJAX errors
     */
    public function ajaxError($message = 'An error occurred', $code = 500) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        AppLogger::error('AJAX Error', [
            'message' => $message,
            'code' => $code,
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'ip' => $this->getClientIp()
        ]);
        
        $this->clearOutputBuffer();
        
        $isDebug = Config::get('debug', false);
        $env = Config::get('env', 'production');
        
        $response = [
            'success' => false,
            'error' => true,
            'message' => ($isDebug || $env === 'development') ? $message : 'An error occurred'
        ];
        
        echo json_encode($response);
        exit();
    }
} 