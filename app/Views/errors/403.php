<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="text-center">
    <h1 class="display-1">403</h1>
    <h2>Access Forbidden</h2>
    <p class="lead">
        <?= htmlspecialchars($message ?? 'You do not have permission to access this resource.') ?>
    </p>
    <p>
        If you believe this is an error, please contact the administrator.
    </p>
    
    <div class="mt-4">
        <a href="/" class="btn btn-primary">
            <i class="fas fa-home"></i> Go to Homepage
        </a>
        <a href="/login" class="btn btn-secondary">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>