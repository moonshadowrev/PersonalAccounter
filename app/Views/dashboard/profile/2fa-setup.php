<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Enable Two-Factor Authentication</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/profile/edit">Profile</a></li>
                    <li class="breadcrumb-item active">2FA Setup</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Setup Two-Factor Authentication</h4>
                    <p class="card-title-desc">Follow these steps to secure your account with two-factor authentication.</p>
                    
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

                    <div class="row tfa-setup">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h5><span class="badge bg-primary me-2">1</span>Install Authenticator App</h5>
                                <p>Download and install an authenticator app on your mobile device:</p>
                                <ul>
                                    <li><strong>Google Authenticator</strong> (iOS/Android)</li>
                                    <li><strong>Authy</strong> (iOS/Android/Desktop)</li>
                                    <li><strong>Microsoft Authenticator</strong> (iOS/Android)</li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h5><span class="badge bg-primary me-2">2</span>Scan QR Code</h5>
                                <p>Open your authenticator app and scan this QR code:</p>
                                <div class="text-center p-3 bg-dark rounded">
                                    <img src="<?php echo htmlspecialchars($qrCodeImage); ?>" alt="2FA QR Code" class="img-fluid">
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Can't scan?</strong> Manually enter this secret key in your app:<br>
                                        <code class="bg-dark text-light p-2 rounded d-inline-block mt-1"><?php echo htmlspecialchars($secret); ?></code>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h5><span class="badge bg-primary me-2">3</span>Verify Setup</h5>
                                <p>Enter the 6-digit code from your authenticator app to complete setup:</p>
                                
                                <form action="/profile/2fa/enable" method="POST">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                                    <input type="hidden" name="secret" value="<?php echo htmlspecialchars($secret); ?>">
                                    
                                    <div class="mb-3">
                                        <label for="code" class="form-label">Verification Code</label>
                                        <input type="text" id="code" name="code" class="form-control text-center" 
                                               placeholder="000000" maxlength="6" pattern="\d{6}" 
                                               style="font-size: 1.5rem; letter-spacing: 0.5rem;" required autofocus>
                                        <small class="form-text text-muted">Enter the 6-digit code from your authenticator app</small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-shield-alt me-2"></i>Enable Two-Factor Authentication
                                        </button>
                                        <a href="/profile/edit" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Important:</strong> After enabling 2FA, you'll receive backup codes that can be used to access your account if you lose your authenticator device. Make sure to save them in a secure location.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('code');
    
    // Auto-format input (only allow digits, limit to 6 characters)
    codeInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length > 6) {
            value = value.substring(0, 6);
        }
        
        e.target.value = value;
    });
    
    // Auto-submit when 6 digits are entered
    codeInput.addEventListener('input', function(e) {
        if (e.target.value.length === 6) {
            // Small delay to allow user to see the complete code
            setTimeout(() => {
                e.target.form.submit();
            }, 500);
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 