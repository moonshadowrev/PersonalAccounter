<?php

if (!defined('APP_RAN')) {
    die('Direct access not allowed');
}

// Load controllers
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/DashboardController.php';
require_once __DIR__ . '/../Controllers/CreditCardController.php';
require_once __DIR__ . '/../Controllers/SubscriptionController.php';
require_once __DIR__ . '/../Controllers/ReportController.php';
require_once __DIR__ . '/../Controllers/UserController.php';
require_once __DIR__ . '/../Controllers/ProfileController.php';
require_once __DIR__ . '/../Controllers/CategoryController.php';
require_once __DIR__ . '/../Controllers/TagController.php';
require_once __DIR__ . '/../Controllers/BankAccountController.php';
require_once __DIR__ . '/../Controllers/CryptoWalletController.php';
require_once __DIR__ . '/../Controllers/ExpenseController.php';

// Instantiate controllers
$authController = new AuthController($database);
$dashboardController = new DashboardController($database);
$creditCardController = new CreditCardController($database);
$subscriptionController = new SubscriptionController($database);
$reportController = new ReportController($database);
$userController = new UserController($database);
$profileController = new ProfileController($database);
$categoryController = new CategoryController($database);
$tagController = new TagController($database);
$bankAccountController = new BankAccountController($database);
$cryptoWalletController = new CryptoWalletController($database);
$expenseController = new ExpenseController($database);

// Global middleware for all routes
$router->before('GET|POST', '/.*', function() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    AppLogger::info('Request received', [
        'method' => $method,
        'uri' => $uri,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Handle middleware for this route
    $result = Middleware::handleRoute($uri, $method);
    
    AppLogger::info('Web Middleware result', [
        'uri' => $uri,
        'result' => $result ? 'passed' : 'failed',
        'session_id' => session_id(), // Now safe to call after middleware
        'session_data_exists' => !empty($_SESSION),
        'session_user_exists' => isset($_SESSION['user'])
    ]);
    
    if (!$result) {
        // Middleware handled the response (redirect, error, etc.)
        return false;
    }
    
    return true;
});


// Public routes (no authentication required)
$router->get('/login', function() use ($authController) {
    $authController->showLoginForm();
});

$router->post('/login', function() use ($authController) {
    $authController->login();
});

// 2FA Routes
$router->get('/2fa/verify', function() use ($authController) {
    $authController->show2FAForm();
});

$router->post('/2fa/verify', function() use ($authController) {
    $authController->verify2FA();
});

// Protected routes (authentication required)
$router->get('/logout', function() use ($authController) {
    $authController->logout();
});

$router->get('/', function() use ($dashboardController) {
    $dashboardController->index();
});

// AJAX Routes
$router->get('/ajax/stats', function() use ($dashboardController) {
    $dashboardController->ajaxStats();
});

// Credit Card Routes
$router->get('/credit-cards', function() use ($creditCardController) {
    $creditCardController->index();
});

$router->get('/credit-cards/create', function() use ($creditCardController) {
    $creditCardController->create();
});

$router->post('/credit-cards', function() use ($creditCardController) {
    $creditCardController->store();
});

$router->get('/credit-cards/(\d+)/edit', function($id) use ($creditCardController) {
    $creditCardController->edit($id);
});

$router->post('/credit-cards/(\d+)', function($id) use ($creditCardController) {
    $creditCardController->update($id);
});

$router->post('/credit-cards/(\d+)/delete', function($id) use ($creditCardController) {
    $creditCardController->delete($id);
});

// Subscription Routes
$router->get('/subscriptions', function() use ($subscriptionController) {
    $subscriptionController->index();
});

$router->get('/subscriptions/create', function() use ($subscriptionController) {
    $subscriptionController->create();
});

$router->post('/subscriptions', function() use ($subscriptionController) {
    $subscriptionController->store();
});

$router->get('/subscriptions/(\d+)/edit', function($id) use ($subscriptionController) {
    $subscriptionController->edit($id);
});

$router->post('/subscriptions/(\d+)', function($id) use ($subscriptionController) {
    $subscriptionController->update($id);
});

$router->post('/subscriptions/(\d+)/delete', function($id) use ($subscriptionController) {
    $subscriptionController->delete($id);
});

// Report Routes
$router->get('/reports', function() use ($reportController) {
    $reportController->index();
});

$router->get('/reports/export', function() use ($reportController) {
    $reportController->export();
});

// User Management Routes
$router->get('/users', function() use ($userController) {
    $userController->index();
});

$router->get('/users/create', function() use ($userController) {
    $userController->create();
});

$router->post('/users', function() use ($userController) {
    $userController->store();
});

$router->get('/users/(\d+)/edit', function($id) use ($userController) {
    $userController->edit($id);
});

$router->post('/users/(\d+)', function($id) use ($userController) {
    $userController->update($id);
});

$router->post('/users/(\d+)/delete', function($id) use ($userController) {
    $userController->delete($id);
});

// Category Routes
$router->get('/categories', function() use ($categoryController) {
    $categoryController->index();
});

$router->get('/categories/create', function() use ($categoryController) {
    $categoryController->create();
});

$router->post('/categories', function() use ($categoryController) {
    $categoryController->store();
});

$router->get('/categories/(\d+)/edit', function($id) use ($categoryController) {
    $categoryController->edit($id);
});

$router->post('/categories/(\d+)', function($id) use ($categoryController) {
    $categoryController->update($id);
});

$router->post('/categories/(\d+)/delete', function($id) use ($categoryController) {
    $categoryController->delete($id);
});

$router->post('/categories/create-defaults', function() use ($categoryController) {
    $categoryController->createDefaults();
});

$router->get('/categories/ajax/list', function() use ($categoryController) {
    $categoryController->ajaxList();
});

$router->get('/categories/search', function() use ($categoryController) {
    $categoryController->search();
});

$router->post('/categories/quick-create', function() use ($categoryController) {
    $categoryController->quickCreate();
});

$router->get('/categories/popular', function() use ($categoryController) {
    $categoryController->popular();
});

// Tag Routes
$router->get('/tags', function() use ($tagController) {
    $tagController->index();
});

$router->get('/tags/create', function() use ($tagController) {
    $tagController->create();
});

$router->post('/tags', function() use ($tagController) {
    $tagController->store();
});

$router->get('/tags/(\d+)/edit', function($id) use ($tagController) {
    $tagController->edit($id);
});

$router->post('/tags/(\d+)', function($id) use ($tagController) {
    $tagController->update($id);
});

$router->post('/tags/(\d+)/delete', function($id) use ($tagController) {
    $tagController->delete($id);
});

$router->post('/tags/create-defaults', function() use ($tagController) {
    $tagController->createDefaults();
});

$router->get('/tags/ajax/list', function() use ($tagController) {
    $tagController->ajaxList();
});

$router->get('/tags/search', function() use ($tagController) {
    $tagController->search();
});

$router->post('/tags/quick-create', function() use ($tagController) {
    $tagController->quickCreate();
});

$router->get('/tags/popular', function() use ($tagController) {
    $tagController->popular();
});

// Bank Account Routes
$router->get('/bank-accounts', function() use ($bankAccountController) {
    $bankAccountController->index();
});

$router->get('/bank-accounts/create', function() use ($bankAccountController) {
    $bankAccountController->create();
});

$router->post('/bank-accounts', function() use ($bankAccountController) {
    $bankAccountController->store();
});

$router->get('/bank-accounts/(\d+)/edit', function($id) use ($bankAccountController) {
    $bankAccountController->edit($id);
});

$router->post('/bank-accounts/(\d+)', function($id) use ($bankAccountController) {
    $bankAccountController->update($id);
});

$router->post('/bank-accounts/(\d+)/delete', function($id) use ($bankAccountController) {
    $bankAccountController->delete($id);
});

$router->get('/bank-accounts/ajax/list', function() use ($bankAccountController) {
    $bankAccountController->ajaxList();
});

$router->get('/bank-accounts/(\d+)/details', function($id) use ($bankAccountController) {
    $bankAccountController->details($id);
});

$router->get('/bank-accounts/search', function() use ($bankAccountController) {
    $bankAccountController->search();
});

$router->get('/bank-accounts/by-currency/(\w+)', function($currency) use ($bankAccountController) {
    $bankAccountController->byCurrency($currency);
});

$router->post('/bank-accounts/validate', function() use ($bankAccountController) {
    $bankAccountController->validateAccount();
});

// Crypto Wallet Routes
$router->get('/crypto-wallets', function() use ($cryptoWalletController) {
    $cryptoWalletController->index();
});

$router->get('/crypto-wallets/create', function() use ($cryptoWalletController) {
    $cryptoWalletController->create();
});

$router->post('/crypto-wallets', function() use ($cryptoWalletController) {
    $cryptoWalletController->store();
});

$router->get('/crypto-wallets/(\d+)/edit', function($id) use ($cryptoWalletController) {
    $cryptoWalletController->edit($id);
});

$router->post('/crypto-wallets/(\d+)', function($id) use ($cryptoWalletController) {
    $cryptoWalletController->update($id);
});

$router->post('/crypto-wallets/(\d+)/delete', function($id) use ($cryptoWalletController) {
    $cryptoWalletController->delete($id);
});

$router->get('/crypto-wallets/ajax/list', function() use ($cryptoWalletController) {
    $cryptoWalletController->ajaxList();
});

$router->get('/crypto-wallets/(\d+)/details', function($id) use ($cryptoWalletController) {
    $cryptoWalletController->details($id);
});

$router->get('/crypto-wallets/search', function() use ($cryptoWalletController) {
    $cryptoWalletController->search();
});

$router->get('/crypto-wallets/by-currency/(\w+)', function($currency) use ($cryptoWalletController) {
    $cryptoWalletController->byCurrency($currency);
});

$router->get('/crypto-wallets/by-network/(\w+)', function($network) use ($cryptoWalletController) {
    $cryptoWalletController->byNetwork($network);
});

$router->get('/crypto-wallets/currencies-for-network/(\w+)', function($network) use ($cryptoWalletController) {
    $cryptoWalletController->getCurrenciesForNetwork($network);
});

$router->get('/crypto-wallets/networks-for-currency/(\w+)', function($currency) use ($cryptoWalletController) {
    $cryptoWalletController->getNetworksForCurrency($currency);
});

$router->post('/crypto-wallets/validate-address', function() use ($cryptoWalletController) {
    $cryptoWalletController->validateAddress();
});

// Expense Routes
$router->get('/expenses', function() use ($expenseController) {
    $expenseController->index();
});

$router->get('/expenses/create', function() use ($expenseController) {
    $expenseController->create();
});

$router->post('/expenses', function() use ($expenseController) {
    $expenseController->store();
});

$router->get('/expenses/(\d+)', function($id) use ($expenseController) {
    $expenseController->show($id);
});

$router->get('/expenses/(\d+)/edit', function($id) use ($expenseController) {
    $expenseController->edit($id);
});

$router->post('/expenses/(\d+)', function($id) use ($expenseController) {
    $expenseController->update($id);
});

$router->post('/expenses/(\d+)/delete', function($id) use ($expenseController) {
    $expenseController->delete($id);
});

$router->get('/expenses/import', function() use ($expenseController) {
    $expenseController->import();
});

$router->post('/expenses/import', function() use ($expenseController) {
    $expenseController->processImport();
});

$router->get('/expenses/export', function() use ($expenseController) {
    $expenseController->export();
});

$router->post('/expenses/(\d+)/approve', function($id) use ($expenseController) {
    $expenseController->approve($id);
});

$router->post('/expenses/(\d+)/reject', function($id) use ($expenseController) {
    $expenseController->reject($id);
});

$router->get('/expenses/analytics', function() use ($expenseController) {
    $expenseController->analytics();
});

$router->get('/expenses/(\d+)/download-attachment', function($id) use ($expenseController) {
    $expenseController->downloadAttachment($id);
});

$router->get('/expenses/download-template', function() use ($expenseController) {
    $expenseController->downloadTemplate();
});

// Profile Routes
$router->get('/profile/edit', function() use ($profileController) {
    $profileController->edit();
});

$router->post('/profile/update', function() use ($profileController) {
    $profileController->update();
});

// 2FA Profile Routes
$router->get('/profile/2fa/setup', function() use ($profileController) {
    $profileController->setup2FA();
});

$router->post('/profile/2fa/enable', function() use ($profileController) {
    $profileController->enable2FA();
});

$router->post('/profile/2fa/disable', function() use ($profileController) {
    $profileController->disable2FA();
});

$router->post('/profile/2fa/regenerate-backup-codes', function() use ($profileController) {
    $profileController->regenerateBackupCodes();
});

// API Key Management Routes
$router->get('/profile/api-keys', function() use ($profileController) {
    $profileController->apiKeys();
});

$router->post('/profile/api-keys/create', function() use ($profileController) {
    $profileController->createApiKey();
});

$router->post('/profile/api-keys/(\d+)/delete', function($keyId) use ($profileController) {
    $profileController->deleteApiKey($keyId);
});

// Include API routes
require_once __DIR__ . '/api.php';

// 404 Handler
$router->set404(function() use ($errorController) {
    AppLogger::warning('404 Not Found', [
        'uri' => $_SERVER['REQUEST_URI'],
        'method' => $_SERVER['REQUEST_METHOD'],
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
    ]);
    
    $errorController->notFound();
});