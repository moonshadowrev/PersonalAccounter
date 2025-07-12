<?php

if (!defined('APP_RAN')) {
    die('Direct access not allowed');
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load core services
require_once __DIR__ . '/../app/Services/Config.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Services/Middleware.php';
require_once __DIR__ . '/../app/Services/ErrorHandler.php';

try {
    // Initialize configuration
    Config::load();
    
    // Set timezone
    date_default_timezone_set(Config::get('timezone', 'UTC'));
    
    // Initialize logging
    AppLogger::init();
    
    // Log application startup
    AppLogger::info('Application starting', [
        'env' => Config::get('env'),
        'debug' => Config::get('debug'),
        'php_version' => PHP_VERSION
    ]);
    
    // Initialize middleware
    Middleware::init();
    
    // Initialize database connection
    $dbConfig = Config::get('database');
    $database = new Medoo\Medoo([
        'type' => $dbConfig['type'],
        'host' => $dbConfig['host'],
        'database' => $dbConfig['database'],
        'username' => $dbConfig['username'],
        'password' => $dbConfig['password'],
        'charset' => $dbConfig['charset'],
        'collation' => $dbConfig['collation'],
        'port' => $dbConfig['port']
    ]);
    
    // Test database connection
    try {
        $database->query('SELECT 1');
        AppLogger::info('Database connection established');
    } catch (Exception $e) {
        AppLogger::critical('Database connection failed', ['error' => $e->getMessage()]);
        
        // Handle database connection errors gracefully
        if (isset($errorController)) {
            $errorController->databaseError('Database connection failed: ' . $e->getMessage());
        } else {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    // Initialize router
    $router = new Bramus\Router\Router();
    
    // Set up global error handling
    require_once __DIR__ . '/../app/Controllers/ErrorController.php';
    $errorController = new ErrorController();
    
    // Initialize error handler
    ErrorHandler::init($errorController);
    
    // Configure PHP error handling
    ini_set('display_errors', Config::get('debug') ? '1' : '0');
    ini_set('log_errors', '1');
    
    // Set up error handlers
    set_exception_handler(function($exception) use ($errorController) {
        AppLogger::critical('Uncaught exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Use the improved error handling
        $errorController->handleException($exception);
    });
    
    set_error_handler(function($severity, $message, $file, $line) use ($errorController) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorMessage = "{$message} in {$file} on line {$line}";
        
        AppLogger::error('PHP Error', [
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line
        ]);
        
        // Only show errors for fatal errors, warnings will be logged but not displayed
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $errorController->serverError($errorMessage);
        }
        
        return true;
    });
    
    register_shutdown_function(function() use ($errorController) {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorMessage = "{$error['message']} in {$error['file']} on line {$error['line']}";
            
            AppLogger::critical('Fatal error', [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);
            
            // Create a mock exception for consistent error handling
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            
            $errorController->handleException($exception);
        }
    });
    
    AppLogger::info('Application bootstrap completed successfully');
    
    // Return initialized services
    return [
        'router' => $router,
        'database' => $database,
        'errorController' => $errorController,
        'logger' => AppLogger::getLogger(),
        'config' => Config::class,
        'middleware' => Middleware::class
    ];
    
} catch (Exception $e) {
    // Handle bootstrap errors
    $errorMessage = 'Bootstrap Error: ' . $e->getMessage();
    
    // Try to log the error if possible
    if (class_exists('AppLogger') && AppLogger::isInitialized()) {
        AppLogger::critical('Bootstrap failed', ['error' => $e->getMessage()]);
    } else {
        error_log($errorMessage);
    }
    
    // Display error based on environment
    http_response_code(500);
    
    if (php_sapi_name() === 'cli') {
        echo "Bootstrap Error: Application could not be started. Check logs for details.\n";
    } else {
        echo "<h1>Application Error</h1><p>The application could not be started. Please check the logs.</p>";
    }
    
    // Log detailed error information instead of displaying it
    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG']) {
        error_log("Debug - Bootstrap Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    }
    
    exit(1);
} 