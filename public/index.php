<?php

define('APP_RAN', true);

try {
    // Bootstrap the application
    $services = require_once __DIR__ . '/../bootstrap/app.php';
    
    // Extract services
    $router = $services['router'];
    $database = $services['database'];
    $errorController = $services['errorController'];
    $logger = $services['logger'];
    
    // Load routes
    require_once __DIR__ . '/../app/Routes/web.php';
    require_once __DIR__ . '/../app/Routes/api.php';
    
    // Run the router
    $router->run();
    
} catch (Exception $e) {
    // Handle any uncaught exceptions during routing
    
    // Log the error if possible
    if (class_exists('AppLogger') && method_exists('AppLogger', 'critical')) {
        AppLogger::critical('Application error in index.php', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        error_log('Application error: ' . $e->getMessage());
    }
    
    // Use the error controller if available
    if (isset($errorController)) {
        $errorController->handleException($e);
    } else {
        // Fallback error handling if error controller is not available
        http_response_code(500);
        
        $isDebug = (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG']) || 
                   (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development');
        
        echo "<h1>Application Error</h1>";
        echo "<p>An error occurred while processing your request. Please try again later.</p>";
        
        // Log detailed error information instead of displaying it
        if ($isDebug) {
            error_log("Debug - Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }
} 