<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="login-wrapper">
    <div class="login-box">
        <h2><i class="fas fa-shield-alt me-2"></i>Two-Factor Authentication</h2>
        <p class="text-center text-muted mb-4">Enter the 6-digit code from your authenticator app or use a backup code.</p>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="/2fa/verify" method="POST">
            <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
            
            <div class="form-group">
                <label for="code">Authentication Code</label>
                <input type="text" id="code" name="code" class="form-control text-center" 
                       placeholder="000000" maxlength="8" autocomplete="one-time-code" 
                       style="font-size: 1.5rem; letter-spacing: 0.5rem;" required autofocus>
                <small class="form-text text-muted mt-2">
                    Enter the 6-digit code from your authenticator app, or an 8-character backup code.
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Verify & Sign In
            </button>
        </form>
        
        <div class="text-center mt-3">
            <a href="/login" class="text-muted">
                <i class="fas fa-arrow-left me-1"></i>Back to Login
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('code');
    
    // Auto-format input (remove spaces, limit length)
    codeInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').toUpperCase();
        
        // Limit to 8 characters for backup codes or 6 for regular codes
        if (value.length > 8) {
            value = value.substring(0, 8);
        }
        
        e.target.value = value;
    });
    
    // Auto-submit when 6 digits are entered (for regular 2FA codes)
    codeInput.addEventListener('input', function(e) {
        if (e.target.value.length === 6 && /^\d{6}$/.test(e.target.value)) {
            // Small delay to allow user to see the complete code
            setTimeout(() => {
                e.target.form.submit();
            }, 500);
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 