<?php

class Config
{
    private static $config = null;
    private static $loaded = false;

    /**
     * Load configuration from files
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }

        // Load environment variables
        self::loadEnvironment();
        
        // Validate required environment variables
        self::validateEnvironment();
        
        // Load configuration files
        self::$config = require __DIR__ . '/../../config/app.php';
        
        self::$loaded = true;
    }

    /**
     * Get configuration value using dot notation
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set configuration value
     */
    public static function set($key, $value)
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }

        $config = $value;
    }

    /**
     * Get all configuration
     */
    public static function all()
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * Load environment variables
     */
    private static function loadEnvironment()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        try {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {
            self::handleConfigError(
                'Environment Configuration Error',
                'The .env file is missing. Please copy .env.example to .env and configure your environment variables.'
            );
        }
    }

    /**
     * Validate required environment variables
     */
    private static function validateEnvironment()
    {
        $required = [
            'DB_HOST',
            'DB_NAME', 
            'DB_USER',
            'DB_PASS',
            'APP_DOMAIN'
        ];

        $missing = [];
        foreach ($required as $key) {
            if (!isset($_ENV[$key]) || empty($_ENV[$key])) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            self::handleConfigError(
                'Environment Validation Error',
                'The following required environment variables are missing or empty: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Handle configuration errors
     */
    private static function handleConfigError($title, $message)
    {
        http_response_code(500);
        
        if (php_sapi_name() === 'cli') {
            echo "ERROR: {$title}\n{$message}\n";
        } else {
            echo "<h1>{$title}</h1><p>{$message}</p>";
        }
        
        exit(1);
    }
} 