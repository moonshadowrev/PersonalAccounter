<?php

class Middleware
{
    private static $middlewareStack = [];
    private static $currentRequest = null;

    /**
     * Register middleware
     */
    public static function register($name, $callback)
    {
        self::$middlewareStack[$name] = $callback;
    }

    /**
     * Run middleware stack
     */
    public static function run($middlewareNames, $request = null)
    {
        self::$currentRequest = $request ?? self::getCurrentRequest();
        
        foreach ($middlewareNames as $name) {
            if (isset(self::$middlewareStack[$name])) {
                $result = call_user_func(self::$middlewareStack[$name], self::$currentRequest);
                
                // If middleware returns false, stop execution
                if ($result === false) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Get current request information
     */
    private static function getCurrentRequest()
    {
        return [
            'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'method' => $_SERVER['REQUEST_METHOD'],
            'query' => $_GET,
            'body' => $_POST,
            'headers' => getallheaders() ?: [],
            'session' => $_SESSION ?? []
        ];
    }

    /**
     * Initialize default middleware
     */
    public static function init()
    {
        // Session Middleware - MUST be first to ensure session is available
        self::register('session', function($request) {
            if (session_status() === PHP_SESSION_NONE) {
                $sessionConfig = Config::get('session');
                
                session_set_cookie_params([
                    'lifetime' => $sessionConfig['lifetime'],
                    'path' => $sessionConfig['path'],
                    'domain' => $sessionConfig['domain'],
                    'secure' => $sessionConfig['secure'],
                    'httponly' => $sessionConfig['httponly'],
                    'samesite' => $sessionConfig['samesite']
                ]);
                
                session_start();
                
                AppLogger::debug('Session started', [
                    'session_id' => session_id(),
                    'uri' => $request['uri'],
                    'method' => $request['method'],
                    'session_config' => $sessionConfig
                ]);
                
                // Generate CSRF token if not exists (should be done early)
                if (!isset($_SESSION['_token'])) {
                    $_SESSION['_token'] = bin2hex(random_bytes(32));
                    AppLogger::debug('Generated new CSRF token', [
                        'session_id' => session_id(),
                        'token_preview' => substr($_SESSION['_token'], 0, 8) . '...'
                    ]);
                } else {
                    AppLogger::debug('Using existing CSRF token', [
                        'session_id' => session_id(),
                        'token_preview' => substr($_SESSION['_token'], 0, 8) . '...'
                    ]);
                }
            } else {
                AppLogger::debug('Session already started', [
                    'session_id' => session_id(),
                    'uri' => $request['uri'],
                    'method' => $request['method']
                ]);
            }
            return true;
        });

        // CORS Middleware
        self::register('cors', function($request) {
            if (Config::get('env') === 'development') {
                $allowedOrigins = ['http://localhost:3000', 'http://localhost:8000', 'http://127.0.0.1:8000' , $_ENV['APP_URL']];
                $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
                
                if (in_array($origin, $allowedOrigins)) {
                    header("Access-Control-Allow-Origin: {$origin}");
                    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization');
                    header('Access-Control-Allow-Credentials: true');
                }
            }
            return true;
        });

        // Security Headers Middleware
        self::register('security_headers', function($request) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            if (Config::get('session.secure')) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
            
            return true;
        });

        // Authentication Middleware
        self::register('authenticate', function($request) {
            $sessionKey = Config::get('auth.session_key');
            
            // Check if user is authenticated
            if (!isset($_SESSION[$sessionKey])) {
                AppLogger::warning('Authentication required', [
                    'uri' => $request['uri'],
                    'session_id' => session_id(),
                    'session_data' => $_SESSION
                ]);
                
                self::redirect('/login');
                return false;
            }
            
            AppLogger::debug('Authentication passed', [
                'user' => $_SESSION[$sessionKey]['email'] ?? 'unknown',
                'uri' => $request['uri']
            ]);
            
            return true;
        });

        // Guest Middleware (redirect if authenticated)
        self::register('redirect_if_authenticated', function($request) {
            $sessionKey = Config::get('auth.session_key');
            
            if (isset($_SESSION[$sessionKey])) {
                AppLogger::debug('User already authenticated, redirecting to dashboard', [
                    'user' => $_SESSION[$sessionKey]['email'] ?? 'unknown',
                    'uri' => $request['uri']
                ]);
                
                self::redirect('/');
                return false;
            }
            
            return true;
        });

        // CSRF Protection Middleware
        self::register('csrf', function($request) {
            // Only do basic logging here - let the controller handle validation
            if ($request['method'] === 'POST') {
                $token = $request['body']['_token'] ?? '';
                $sessionToken = $_SESSION['_token'] ?? '';
                
                AppLogger::debug('CSRF token check', [
                    'uri' => $request['uri'],
                    'session_id' => session_id(),
                    'session_token_exists' => !empty($sessionToken),
                    'post_token_exists' => !empty($token),
                    'session_token_length' => strlen($sessionToken),
                    'post_token_length' => strlen($token)
                ]);
            }
            
            return true;
        });
    }

    /**
     * Check if route is public
     */
    public static function isPublicRoute($uri)
    {
        $publicRoutes = Config::get('routes.public', []);
        
        foreach ($publicRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if route is guest only
     */
    public static function isGuestOnlyRoute($uri)
    {
        $guestRoutes = Config::get('routes.guest_only', []);
        
        foreach ($guestRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Safe redirect to prevent loops
     */
    public static function redirect($url, $statusCode = 302)
    {
        // Prevent redirect loops
        $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $targetUri = parse_url($url, PHP_URL_PATH);
        
        if ($currentUri === $targetUri) {
            AppLogger::error('Redirect loop detected', [
                'current_uri' => $currentUri,
                'target_uri' => $targetUri,
                'session_id' => session_id()
            ]);
            return;
        }

        // Log the redirect
        AppLogger::info('Redirecting', [
            'from' => $currentUri,
            'to' => $targetUri,
            'status_code' => $statusCode
        ]);

        header("Location: {$url}", true, $statusCode);
        exit();
    }

    /**
     * Handle middleware for specific route
     */
    public static function handleRoute($uri, $method = 'GET')
    {
        $request = [
            'uri' => $uri,
            'method' => $method,
            'query' => $_GET,
            'body' => $_POST,
            'headers' => getallheaders() ?: [],
            'session' => $_SESSION ?? []
        ];

        // ALWAYS start session first - this is critical
        if (!self::run(['session'], $request)) {
            return false;
        }

        // Run global middleware
        $globalMiddleware = Config::get('middleware.global', []);
        if (!self::run($globalMiddleware, $request)) {
            return false;
        }

        // Check route-specific middleware
        if (self::isGuestOnlyRoute($uri)) {
            // Guest-only routes (like login) should redirect if authenticated
            $guestMiddleware = Config::get('middleware.guest', []);
            if (!self::run($guestMiddleware, $request)) {
                return false;
            }
            
            // If it's also a POST request, run CSRF middleware
            if ($method === 'POST') {
                return self::run(['csrf'], $request);
            }
            return true;
        }

        if (self::isPublicRoute($uri)) {
            // Public routes don't need authentication but may need CSRF for POST
            AppLogger::debug('Public route accessed', ['uri' => $uri]);
            if ($method === 'POST') {
                return self::run(['csrf'], $request);
            }
            return true;
        }

        // Protected routes need authentication and CSRF for POST
        $authMiddleware = Config::get('middleware.auth', []);
        if (!self::run($authMiddleware, $request)) {
            return false;
        }

        // Add CSRF protection for POST requests
        if ($method === 'POST') {
            return self::run(['csrf'], $request);
        }

        return true;
    }
} 