<?php

if (!defined('APP_RAN')) {
    die('Direct access not allowed');
}

// Load API controllers
require_once __DIR__ . '/../Controllers/Api/ApiController.php';
require_once __DIR__ . '/../Controllers/Api/Schemas.php';
require_once __DIR__ . '/../Controllers/Api/UsersApiController.php';
require_once __DIR__ . '/../Controllers/Api/TransactionsApiController.php';
require_once __DIR__ . '/../Controllers/Api/CreditCardsApiController.php';
require_once __DIR__ . '/../Controllers/Api/SubscriptionsApiController.php';
require_once __DIR__ . '/../Controllers/Api/ExpensesApiController.php';
require_once __DIR__ . '/../Controllers/Api/CategoriesApiController.php';
require_once __DIR__ . '/../Controllers/Api/TagsApiController.php';
require_once __DIR__ . '/../Controllers/Api/BankAccountsApiController.php';
require_once __DIR__ . '/../Controllers/Api/CryptoWalletsApiController.php';
require_once __DIR__ . '/../Controllers/Api/ReportsApiController.php';
require_once __DIR__ . '/../Services/ApiMiddleware.php';

// Initialize API middleware
ApiMiddleware::init($database);

// Instantiate API controllers
$usersApiController = new UsersApiController($database);
$transactionsApiController = new TransactionsApiController($database);
$creditCardsApiController = new CreditCardsApiController($database);
$subscriptionsApiController = new SubscriptionsApiController($database);
$expensesApiController = new ExpensesApiController($database);
$categoriesApiController = new CategoriesApiController($database);
$tagsApiController = new TagsApiController($database);
$bankAccountsApiController = new BankAccountsApiController($database);
$cryptoWalletsApiController = new CryptoWalletsApiController($database);
$reportsApiController = new ReportsApiController($database);

// API Health Check (no authentication required)
$router->get('/api/health', function() {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
});

// API middleware for all API v1 routes (excluding health check)
$router->before('GET|POST|PUT|DELETE|OPTIONS', '/api/v1/.*', function() {
    $request = ApiMiddleware::getCurrentRequest();
    
    AppLogger::info('API Request received', [
        'method' => $request['method'],
        'uri' => $request['uri'],
        'user_agent' => $request['headers']['User-Agent'] ?? 'unknown'
    ]);
    
    // Handle API middleware
    $middlewares = ['cors', 'securityHeaders', 'authenticate'];
    $result = ApiMiddleware::handle($middlewares, $request);
    
    if (!$result) {
        return false;
    }
    
    // Store request data globally for controllers to access
    $GLOBALS['api_request'] = $request;
    
    return true;
});

// API v1 Routes

// Users API
$router->get('/api/v1/users', function() use ($usersApiController) {
    $usersApiController->index();
});

$router->get('/api/v1/users/(\d+)', function($id) use ($usersApiController) {
    $usersApiController->show($id);
});

$router->post('/api/v1/users', function() use ($usersApiController) {
    $usersApiController->store();
});

$router->put('/api/v1/users/(\d+)', function($id) use ($usersApiController) {
    $usersApiController->update($id);
});

$router->delete('/api/v1/users/(\d+)', function($id) use ($usersApiController) {
    $usersApiController->delete($id);
});

// Transactions API
$router->get('/api/v1/transactions', function() use ($transactionsApiController) {
    $transactionsApiController->index();
});

$router->get('/api/v1/transactions/(\d+)', function($id) use ($transactionsApiController) {
    $transactionsApiController->show($id);
});

$router->post('/api/v1/transactions', function() use ($transactionsApiController) {
    $transactionsApiController->store();
});

$router->put('/api/v1/transactions/(\d+)', function($id) use ($transactionsApiController) {
    $transactionsApiController->update($id);
});

$router->delete('/api/v1/transactions/(\d+)', function($id) use ($transactionsApiController) {
    $transactionsApiController->delete($id);
});

// Credit Cards API
$router->get('/api/v1/credit-cards', function() use ($creditCardsApiController) {
    $creditCardsApiController->index();
});

$router->get('/api/v1/credit-cards/(\d+)', function($id) use ($creditCardsApiController) {
    $creditCardsApiController->show($id);
});

$router->post('/api/v1/credit-cards', function() use ($creditCardsApiController) {
    $creditCardsApiController->store();
});

$router->put('/api/v1/credit-cards/(\d+)', function($id) use ($creditCardsApiController) {
    $creditCardsApiController->update($id);
});

$router->delete('/api/v1/credit-cards/(\d+)', function($id) use ($creditCardsApiController) {
    $creditCardsApiController->delete($id);
});

// Subscriptions API
$router->get('/api/v1/subscriptions', function() use ($subscriptionsApiController) {
    $subscriptionsApiController->index();
});

$router->get('/api/v1/subscriptions/(\d+)', function($id) use ($subscriptionsApiController) {
    $subscriptionsApiController->show($id);
});

$router->post('/api/v1/subscriptions', function() use ($subscriptionsApiController) {
    $subscriptionsApiController->store();
});

$router->put('/api/v1/subscriptions/(\d+)', function($id) use ($subscriptionsApiController) {
    $subscriptionsApiController->update($id);
});

$router->delete('/api/v1/subscriptions/(\d+)', function($id) use ($subscriptionsApiController) {
    $subscriptionsApiController->delete($id);
});

// Expenses API
$router->get('/api/v1/expenses', function() use ($expensesApiController) {
    $expensesApiController->index();
});

$router->get('/api/v1/expenses/(\d+)', function($id) use ($expensesApiController) {
    $expensesApiController->show($id);
});

$router->post('/api/v1/expenses', function() use ($expensesApiController) {
    $expensesApiController->store();
});

$router->put('/api/v1/expenses/(\d+)', function($id) use ($expensesApiController) {
    $expensesApiController->update($id);
});

$router->delete('/api/v1/expenses/(\d+)', function($id) use ($expensesApiController) {
    $expensesApiController->delete($id);
});

$router->post('/api/v1/expenses/(\d+)/approve', function($id) use ($expensesApiController) {
    $expensesApiController->approve($id);
});

$router->post('/api/v1/expenses/(\d+)/reject', function($id) use ($expensesApiController) {
    $expensesApiController->reject($id);
});

// Categories API
$router->get('/api/v1/categories', function() use ($categoriesApiController) {
    $categoriesApiController->index();
});

$router->get('/api/v1/categories/(\d+)', function($id) use ($categoriesApiController) {
    $categoriesApiController->show($id);
});

$router->post('/api/v1/categories', function() use ($categoriesApiController) {
    $categoriesApiController->store();
});

$router->put('/api/v1/categories/(\d+)', function($id) use ($categoriesApiController) {
    $categoriesApiController->update($id);
});

$router->delete('/api/v1/categories/(\d+)', function($id) use ($categoriesApiController) {
    $categoriesApiController->delete($id);
});

$router->get('/api/v1/categories/popular', function() use ($categoriesApiController) {
    $categoriesApiController->popular();
});

// Tags API
$router->get('/api/v1/tags', function() use ($tagsApiController) {
    $tagsApiController->index();
});

$router->get('/api/v1/tags/(\d+)', function($id) use ($tagsApiController) {
    $tagsApiController->show($id);
});

$router->post('/api/v1/tags', function() use ($tagsApiController) {
    $tagsApiController->store();
});

$router->put('/api/v1/tags/(\d+)', function($id) use ($tagsApiController) {
    $tagsApiController->update($id);
});

$router->delete('/api/v1/tags/(\d+)', function($id) use ($tagsApiController) {
    $tagsApiController->delete($id);
});

$router->get('/api/v1/tags/popular', function() use ($tagsApiController) {
    $tagsApiController->popular();
});

// Bank Accounts API
$router->get('/api/v1/bank-accounts', function() use ($bankAccountsApiController) {
    $bankAccountsApiController->index();
});

$router->get('/api/v1/bank-accounts/(\d+)', function($id) use ($bankAccountsApiController) {
    $bankAccountsApiController->show($id);
});

$router->post('/api/v1/bank-accounts', function() use ($bankAccountsApiController) {
    $bankAccountsApiController->store();
});

$router->put('/api/v1/bank-accounts/(\d+)', function($id) use ($bankAccountsApiController) {
    $bankAccountsApiController->update($id);
});

$router->delete('/api/v1/bank-accounts/(\d+)', function($id) use ($bankAccountsApiController) {
    $bankAccountsApiController->delete($id);
});

$router->get('/api/v1/bank-accounts/by-currency/(\w+)', function($currency) use ($bankAccountsApiController) {
    $bankAccountsApiController->byCurrency($currency);
});

// Crypto Wallets API
$router->get('/api/v1/crypto-wallets', function() use ($cryptoWalletsApiController) {
    $cryptoWalletsApiController->index();
});

$router->get('/api/v1/crypto-wallets/(\d+)', function($id) use ($cryptoWalletsApiController) {
    $cryptoWalletsApiController->show($id);
});

$router->post('/api/v1/crypto-wallets', function() use ($cryptoWalletsApiController) {
    $cryptoWalletsApiController->store();
});

$router->put('/api/v1/crypto-wallets/(\d+)', function($id) use ($cryptoWalletsApiController) {
    $cryptoWalletsApiController->update($id);
});

$router->delete('/api/v1/crypto-wallets/(\d+)', function($id) use ($cryptoWalletsApiController) {
    $cryptoWalletsApiController->delete($id);
});

$router->get('/api/v1/crypto-wallets/by-currency/(\w+)', function($currency) use ($cryptoWalletsApiController) {
    $cryptoWalletsApiController->byCurrency($currency);
});

$router->get('/api/v1/crypto-wallets/by-network/(\w+)', function($network) use ($cryptoWalletsApiController) {
    $cryptoWalletsApiController->byNetwork($network);
});

// Reports API
$router->get('/api/v1/reports/dashboard', function() use ($reportsApiController) {
    $reportsApiController->dashboard();
});

$router->get('/api/v1/reports/expenses', function() use ($reportsApiController) {
    $reportsApiController->expenses();
});

$router->get('/api/v1/reports/subscriptions', function() use ($reportsApiController) {
    $reportsApiController->subscriptions();
});

$router->get('/api/v1/reports/export', function() use ($reportsApiController) {
    $reportsApiController->export();
});

// API Documentation (only in debug mode)
$router->get('/api/docs', function() {
    // Check if debug mode is enabled
    $isDebug = Config::get('debug', false) || ($_ENV['APP_ENV'] ?? 'production') === 'development';
    
    if (!$isDebug) {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found', 'message' => 'API documentation is not available in production mode']);
        exit();
    }
    
    try {
        // Generate Swagger documentation
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        // Set up OpenAPI generator with enhanced configuration
        $openapi = \OpenApi\Generator::scan([
            __DIR__ . '/../Controllers/Api/',
        ], [
            'exclude' => [],
            'pattern' => '*.php',
            'bootstrap' => null,
            'validate' => false // Disable validation for now to prevent errors
        ]);
        
        // Enhance the OpenAPI spec with additional information
        $openapi->info->title = 'Accounting Panel API';
        $openapi->info->version = '1.0.0';
        $openapi->info->description = 'API for Accounting Panel - Manage users, subscriptions, credit cards, transactions, expenses, and more.';
        
        // Add server configuration
        $baseUrl = ($_ENV['APP_URL'] ?? 'http://localhost');
        $openapi->servers = [
            new \OpenApi\Annotations\Server([
                'url' => $baseUrl,
                'description' => 'API Server'
            ])
        ];
        
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        
        echo $openapi->toJson(JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        AppLogger::error('Swagger generation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal Server Error',
            'message' => 'Failed to generate API documentation: ' . $e->getMessage(),
            'debug' => $isDebug ? $e->getTraceAsString() : null
        ], JSON_PRETTY_PRINT);
    }
});

// Swagger UI (only in debug mode)
$router->get('/api/docs/ui', function() {
    // Check if debug mode is enabled
    $isDebug = Config::get('debug', false) || ($_ENV['APP_ENV'] ?? 'production') === 'development';
    
    if (!$isDebug) {
        http_response_code(404);
        echo '<h1>404 Not Found</h1><p>API documentation is not available in production mode.</p>';
        exit();
    }
    
    $swaggerUiHtml = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Panel API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "/api/docs",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>';
    
    echo $swaggerUiHtml;
});

 