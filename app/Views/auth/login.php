<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="login-box">
    <h2>Accounting Panel</h2>
    <form action="/login" method="POST">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit">Login</button>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 