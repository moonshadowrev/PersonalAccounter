<?php

class ErrorHandler
{
    private static $errorController = null;
    
    /**
     * Initialize the error handler with error controller
     */
    public static function init($errorController)
    {
        self::$errorController = $errorController;
    }
    
    /**
     * Wrap database operations to catch and handle errors gracefully
     */
    public static function wrapDatabaseOperation($callback, $fallbackValue = null)
    {
        try {
            return $callback();
        } catch (PDOException $e) {
            // Log the database error
            AppLogger::error('Database operation failed', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Check if this is a missing table error
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                self::handleMissingTable($e);
            } else {
                self::handleDatabaseError($e);
            }
            
            return $fallbackValue;
        } catch (Exception $e) {
            // Log general errors
            AppLogger::error('Operation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (self::$errorController) {
                self::$errorController->serverError($e->getMessage(), $e);
            }
            
            return $fallbackValue;
        }
    }
    
    /**
     * Handle missing table errors specifically
     */
    private static function handleMissingTable($exception)
    {
        $message = $exception->getMessage();
        
        // Extract table name from error message
        preg_match("/Table '.*\.(\w+)' doesn't exist/", $message, $matches);
        $tableName = $matches[1] ?? 'unknown';
        
        AppLogger::critical('Missing database table', [
            'table' => $tableName,
            'message' => $message
        ]);
        
        if (self::$errorController) {
            $isDebug = Config::get('debug', false);
            $env = Config::get('env', 'production');
            
            if ($isDebug || $env === 'development') {
                $debugMessage = "Database table '{$tableName}' doesn't exist. Please run migrations: php control migrate run";
                self::$errorController->databaseError($debugMessage);
            } else {
                self::$errorController->databaseError('Database configuration error. Please contact support.');
            }
        }
    }
    
    /**
     * Handle general database errors
     */
    private static function handleDatabaseError($exception)
    {
        if (self::$errorController) {
            self::$errorController->databaseError($exception->getMessage());
        }
    }
    
    /**
     * Check if request is AJAX
     */
    public static function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Handle AJAX errors
     */
    public static function handleAjaxError($message, $code = 500)
    {
        if (self::$errorController) {
            self::$errorController->ajaxError($message, $code);
        } else {
            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => true,
                'message' => 'An error occurred'
            ]);
            exit();
        }
    }
    
    /**
     * Wrap any operation with error handling
     */
    public static function wrap($callback, $fallbackValue = null)
    {
        try {
            return $callback();
        } catch (Exception $e) {
            AppLogger::error('Wrapped operation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (self::isAjaxRequest()) {
                self::handleAjaxError($e->getMessage());
            } else if (self::$errorController) {
                self::$errorController->handleException($e);
            }
            
            return $fallbackValue;
        }
    }
} 