<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php
// Add basic security headers
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
// Basic CSP - can be enhanced as needed
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self'");
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5/dark.css">
    <?php if (isset($view_data['load_datatable']) && $view_data['load_datatable']): ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.css">
    <?php endif; ?>
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/responsive.css" rel="stylesheet">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.7.1.js"></script>
</head>
<body>

<?php 
function is_active($path) {
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path === '/') {
        return $current_path === '/' ? 'active' : '';
    }
    return strpos($current_path, $path) === 0 ? 'active' : '';
}

// Security helper functions
function h($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function safe_url($url) {
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}
?>

<?php if (isset($_SESSION['user'])): ?>
<div class="app-container">
    <div class="sidebar">
        <div class="sidebar-header">
            Accounting Panel
            <button class="close-sidebar"><i class="fas fa-times"></i></button>
        </div>
        
        <!-- Main Navigation -->
        <div class="sidebar-nav">
            <a href="/" class="<?= is_active('/') ?>">
                <i class="fa-fw fas fa-tachometer-alt"></i> 
                <span>Dashboard</span>
            </a>
            
            <!-- Expenses Section -->
            <div class="nav-section">
                <div class="nav-section-header">Expenses</div>
                <a href="/expenses" class="<?= is_active('/expenses') ?>">
                    <i class="fa-fw fas fa-receipt"></i> 
                    <span>All Expenses</span>
                </a>
                <a href="/categories" class="<?= is_active('/categories') ?>">
                    <i class="fa-fw fas fa-tags"></i> 
                    <span>Categories</span>
                </a>
                <a href="/tags" class="<?= is_active('/tags') ?>">
                    <i class="fa-fw fas fa-tag"></i> 
                    <span>Tags</span>
                </a>
            </div>
            
            <!-- Payment Methods Section -->
            <div class="nav-section">
                <div class="nav-section-header">Payment Methods</div>
                <a href="/credit-cards" class="<?= is_active('/credit-cards') ?>">
                    <i class="fa-fw fas fa-credit-card"></i> 
                    <span>Credit Cards</span>
                </a>
                <a href="/bank-accounts" class="<?= is_active('/bank-accounts') ?>">
                    <i class="fa-fw fas fa-university"></i> 
                    <span>Bank Accounts</span>
                </a>
                <a href="/crypto-wallets" class="<?= is_active('/crypto-wallets') ?>">
                    <i class="fa-fw fab fa-bitcoin"></i> 
                    <span>Crypto Wallets</span>
                </a>
            </div>
            
            <!-- Other Sections -->
            <a href="/subscriptions" class="<?= is_active('/subscriptions') ?>">
                <i class="fa-fw fas fa-sync-alt"></i> 
                <span>Subscriptions</span>
            </a>
            <a href="/reports" class="<?= is_active('/reports') ?>">
                <i class="fa-fw fas fa-chart-bar"></i> 
                <span>Reports</span>
            </a>
            <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'superadmin'): ?>
            <a href="/users" class="<?= is_active('/users') ?>">
                <i class="fa-fw fas fa-users-cog"></i> 
                <span>Admins</span>
            </a>
            <?php endif; ?>
        </div>
        
        <!-- User Section at Bottom -->
        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'User'); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars(ucfirst($_SESSION['user']['role'] ?? 'admin')); ?></div>
                </div>
            </div>
            
            <div class="user-actions">
                <a href="/profile/edit" class="<?= is_active('/profile/edit') ?>">
                    <i class="fa-fw fas fa-user-edit"></i> 
                    <span>Profile</span>
                </a>
                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'superadmin'): ?>
                <a href="/profile/api-keys" class="<?= is_active('/profile/api-keys') ?>">
                    <i class="fa-fw fas fa-key"></i> 
                    <span>API Keys</span>
                </a>
                <?php endif; ?>
                <a href="/logout" class="logout-link">
                    <i class="fa-fw fas fa-sign-out-alt"></i> 
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    <div class="overlay"></div>

    <div class="main-content">
        <div class="header-bar">
            <button class="mobile-toggle"><i class="fas fa-bars"></i></button>
        </div>

<?php else: ?>
<div class="login-wrapper">
<?php endif; ?>

<!-- Include Flash Messages -->
<?php require_once __DIR__ . '/flash_messages.php'; ?>

</body>
</html> 