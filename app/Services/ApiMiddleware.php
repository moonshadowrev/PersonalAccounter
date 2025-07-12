<?php

require_once __DIR__ . '/../Models/ApiKey.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Config.php';

class ApiMiddleware {
    
    private static $apiKeyModel = null;
    
    /**
     * Initialize API middleware
     */
    public static function init($database) {
        self::$apiKeyModel = new ApiKey($database);
    }
    
    /**
     * API Authentication middleware
     */
    public static function authenticate($request) {
        AppLogger::debug('API Authentication started', [
            'uri' => $request['uri'] ?? 'unknown',
            'method' => $request['method'] ?? 'unknown'
        ]);
        
        // Get API key from Authorization header or query parameter
        $apiKey = self::extractApiKey($request);
        
        if (!$apiKey) {
            AppLogger::warning('API authentication failed - no API key provided');
            return self::unauthorizedResponse('API key is required');
        }
        
        AppLogger::debug('API Key extracted, validating...', [
            'key_prefix' => substr($apiKey, 0, 8),
            'key_length' => strlen($apiKey)
        ]);
        
        // Validate API key
        $keyData = self::$apiKeyModel->validateApiKey($apiKey);
        
        if (!$keyData) {
            AppLogger::warning('API key validation failed', [
                'key_prefix' => substr($apiKey, 0, 8)
            ]);
            // Record failed attempt if we can identify the key
            self::recordFailedAttempt($apiKey);
            return self::unauthorizedResponse('Invalid API key');
        }
        
        AppLogger::debug('API key validated successfully', [
            'key_id' => $keyData['id'],
            'user_id' => $keyData['user_id'],
            'key_name' => $keyData['name']
        ]);
        
        // Check rate limit
        if (!self::$apiKeyModel->checkRateLimit($keyData['id'])) {
            AppLogger::warning('API rate limit exceeded', [
                'key_id' => $keyData['id']
            ]);
            return self::rateLimitResponse();
        }
        
        // Record successful usage
        self::$apiKeyModel->recordUsage($keyData['id']);
        
        // Store API key data in request for later use
        $request['api_key'] = $keyData;
        
        AppLogger::debug('API authentication completed successfully');
        
        return true;
    }
    
    /**
     * Check API permission middleware
     */
    public static function checkPermission($permission) {
        return function($request) use ($permission) {
            if (!isset($request['api_key'])) {
                return self::unauthorizedResponse('API key not found in request');
            }
            
            if (!self::$apiKeyModel->hasPermission($request['api_key'], $permission)) {
                return self::forbiddenResponse("Permission '{$permission}' is required");
            }
            
            return true;
        };
    }
    
    /**
     * API CORS middleware
     */
    public static function cors($request) {
        // Set CORS headers for API
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        return true;
    }
    
    /**
     * API Security headers middleware
     */
    public static function securityHeaders($request) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        return true;
    }
    
    /**
     * Extract API key from request
     */
    private static function extractApiKey($request) {
        // Log headers for debugging
        AppLogger::debug('API Key extraction - Headers received', [
            'headers' => $request['headers'],
            'header_keys' => array_keys($request['headers']),
            'uri' => $request['uri'] ?? 'unknown'
        ]);
        
        // Check Authorization header (Bearer token)
        if (isset($request['headers']['Authorization'])) {
            $authHeader = $request['headers']['Authorization'];
            if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                AppLogger::debug('API Key found in Authorization header (Bearer)');
                return $matches[1];
            }
        }
        
        // Check X-API-Key header (case-insensitive)
        foreach ($request['headers'] as $name => $value) {
            if (strtolower($name) === 'x-api-key') {
                AppLogger::debug('API Key found in X-API-Key header', [
                    'header_name' => $name,
                    'key_prefix' => substr($value, 0, 8)
                ]);
                return $value;
            }
        }
        
        // Check query parameter (less secure, but sometimes necessary)
        if (isset($request['query']['api_key'])) {
            AppLogger::debug('API Key found in query parameter');
            return $request['query']['api_key'];
        }
        
        AppLogger::warning('No API key found in request', [
            'uri' => $request['uri'] ?? 'unknown',
            'headers' => array_keys($request['headers'])
        ]);
        
        return null;
    }
    
    /**
     * Record failed API key attempt
     */
    private static function recordFailedAttempt($rawKey) {
        if (empty($rawKey) || !str_starts_with($rawKey, 'ak_')) {
            return;
        }
        
        $hashedKey = hash('sha256', $rawKey);
        $keyPrefix = substr($rawKey, 0, 8);
        
        // Find the key even if it's invalid to record the attempt
        $apiKey = self::$apiKeyModel->db->get('api_keys', 'id', [
            'api_key' => $hashedKey,
            'api_key_prefix' => $keyPrefix
        ]);
        
        if ($apiKey) {
            self::$apiKeyModel->recordFailedAttempt($apiKey);
        }
    }
    
    /**
     * Return unauthorized response
     */
    private static function unauthorizedResponse($message = 'Unauthorized') {
        http_response_code(401);
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
            'timestamp' => date('c')
        ]);
        exit();
    }
    
    /**
     * Return forbidden response
     */
    private static function forbiddenResponse($message = 'Forbidden') {
        http_response_code(403);
        echo json_encode([
            'error' => 'Forbidden',
            'message' => $message,
            'timestamp' => date('c')
        ]);
        exit();
    }
    
    /**
     * Return rate limit response
     */
    private static function rateLimitResponse() {
        http_response_code(429);
        echo json_encode([
            'error' => 'Rate Limit Exceeded',
            'message' => 'Too many requests. Please try again later.',
            'timestamp' => date('c')
        ]);
        exit();
    }
    
    /**
     * Handle API middleware stack
     */
    public static function handle($middlewares, $request) {
        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $result = $middleware($request);
            } elseif (method_exists(self::class, $middleware)) {
                $result = self::$middleware($request);
            } else {
                continue;
            }
            
            if ($result !== true) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get current API request information
     */
    public static function getCurrentRequest() {
        return [
            'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'method' => $_SERVER['REQUEST_METHOD'],
            'query' => $_GET,
            'body' => self::getRequestBody(),
            'headers' => getallheaders() ?: []
        ];
    }
    
    /**
     * Get request body (handles JSON)
     */
    private static function getRequestBody() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = file_get_contents('php://input');
            return json_decode($rawBody, true) ?: [];
        }
        
        return $_POST;
    }
} 