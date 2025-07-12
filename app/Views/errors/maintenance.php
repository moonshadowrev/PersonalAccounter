<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="text-center">
    <h1 class="display-1">🔧</h1>
    <h2>Under Maintenance</h2>
    <p class="lead">
        We are currently performing scheduled maintenance to improve our services.
    </p>
    <p>
        We'll be back online shortly. Thank you for your patience!
    </p>
    
    <div class="mt-4">
        <button onclick="location.reload()" class="btn btn-primary">
            <i class="fas fa-redo"></i> Check Again
        </button>
    </div>
    
    <div class="mt-5">
        <small class="text-muted">
            Estimated completion: <span id="maintenance-time"><?= date('Y-m-d H:i') ?></span>
        </small>
    </div>
</div>

<script>
// Auto-refresh every 30 seconds during maintenance
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 