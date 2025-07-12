<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="text-center">
    <h1 class="display-1">503</h1>
    <h2>Service Unavailable</h2>
    <p class="lead">
        The service is temporarily unavailable due to maintenance or high load.
    </p>
    <p>
        Please try again in a few minutes. We apologize for any inconvenience.
    </p>
    
    <div class="mt-4">
        <a href="/" class="btn btn-primary">
            <i class="fas fa-home"></i> Go to Homepage
        </a>
        <button onclick="location.reload()" class="btn btn-secondary">
            <i class="fas fa-redo"></i> Try Again
        </button>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 