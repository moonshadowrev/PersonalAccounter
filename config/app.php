<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'domain' => $_ENV['APP_DOMAIN'] ?? 'localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'type' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'database' => $_ENV['DB_NAME'] ?? '',
        'username' => $_ENV['DB_USER'] ?? '',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_general_ci',
        'port' => (int)($_ENV['DB_PORT'] ?? 3306)
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    */
    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 0),
        'path' => '/',
        'domain' => $_ENV['APP_DOMAIN'] ?? '',
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'httponly' => true,
        'samesite' => $_ENV['SESSION_SAMESITE'] ?? 'Lax'
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login_attempts_limit' => (int)($_ENV['LOGIN_ATTEMPTS_LIMIT'] ?? 5),
        'login_attempts_timeout' => (int)($_ENV['LOGIN_ATTEMPTS_TIMEOUT'] ?? 300),
        'session_key' => 'user'
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'default' => $_ENV['LOG_CHANNEL'] ?? 'file',
        'channels' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../logs/app.log',
                'level' => $_ENV['LOG_LEVEL'] ?? (($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'debug' : 'warning'),
                'max_files' => (int)($_ENV['LOG_MAX_FILES'] ?? 5)
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'global' => [
            'cors',
            'security_headers'
        ],
        'web' => [
            // Session is now handled explicitly first, removed from here
            // 'csrf' is now handled per-route basis
        ],
        'auth' => [
            'authenticate'
        ],
        'guest' => [
            'redirect_if_authenticated'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'max_failed_attempts' => (int)($_ENV['API_MAX_FAILED_ATTEMPTS'] ?? 5),
        'block_duration' => (int)($_ENV['API_BLOCK_DURATION'] ?? 300), // 5 minutes
        'default_rate_limit' => (int)($_ENV['API_DEFAULT_RATE_LIMIT'] ?? 60),
        'max_rate_limit' => (int)($_ENV['API_MAX_RATE_LIMIT'] ?? 1000)
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'public' => [
            '/login',
            '/2fa/verify',
            '/css/',
            '/js/',
            '/favicon.ico',
            '/api/health'
        ],
        'guest_only' => [
            '/login'
        ]
    ]
]; 