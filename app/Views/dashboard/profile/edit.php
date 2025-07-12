<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Profile</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Update Profile Details</h4>
                    
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

                    <form action="/profile/update" method="POST" class="profile-form">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" autocomplete="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" autocomplete="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" disabled id="role" name="role" class="form-control" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="member_since" class="form-label">Member Since</label>
                                    <input type="text" disabled id="member_since" name="member_since" class="form-control" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password (required to save changes)</label>
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password" class="form-control" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" class="form-control">
                                    <small class="form-text text-muted">Minimum 6 characters required</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" class="form-control">
                                    <div id="password-match-feedback" class="form-text"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 profile-actions">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
    <br>
    <!-- Two-Factor Authentication Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Two-Factor Authentication</h4>
                    <p class="card-title-desc">Add an extra layer of security to your account by enabling two-factor authentication.</p>
                    
                    <?php if ($user['two_factor_enabled']): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-shield-alt me-2"></i>
                            Two-factor authentication is <strong>enabled</strong> for your account.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6>Backup Codes</h6>
                                    <p class="text-muted">Generate new backup codes if you've lost access to your authenticator app.</p>
                                    <form action="/profile/2fa/regenerate-backup-codes" method="POST" style="display: inline;">
                                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                                        <button type="submit" class="btn btn-info btn-sm">Regenerate Backup Codes</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6>Disable 2FA</h6>
                                    <p class="text-muted">Disable two-factor authentication for your account.</p>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#disable2FAModal">
                                        Disable 2FA
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Two-factor authentication is <strong>disabled</strong> for your account.
                        </div>
                        
                        <div class="mb-3">
                            <p>Secure your account by enabling two-factor authentication. You'll need to install an authenticator app like Google Authenticator or Authy on your mobile device.</p>
                            <a href="/profile/2fa/setup" class="btn btn-success">
                                <i class="fas fa-shield-alt me-2"></i>Enable Two-Factor Authentication
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->
<br>
<!-- Disable 2FA Modal -->
<div class="modal fade" id="disable2FAModal" tabindex="-1" aria-labelledby="disable2FAModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="disable2FAModalLabel">Disable Two-Factor Authentication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/profile/2fa/disable" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Disabling two-factor authentication will make your account less secure.
                    </div>
                    <div class="mb-3">
                        <label for="disable_current_password" class="form-label">Current Password</label>
                        <input type="password" id="disable_current_password" name="current_password" class="form-control" required>
                        <small class="form-text text-muted">Enter your current password to confirm this action.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Disable 2FA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const feedback = document.getElementById('password-match-feedback');
    const form = document.querySelector('form');
    
    function validatePasswords() {
        const newPassword = newPasswordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (newPassword === '' && confirmPassword === '') {
            feedback.textContent = '';
            feedback.className = 'form-text';
            return true;
        }
        
        if (newPassword.length > 0 && newPassword.length < 6) {
            feedback.textContent = 'Password must be at least 6 characters long';
            feedback.className = 'form-text text-danger';
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            feedback.textContent = 'Passwords do not match';
            feedback.className = 'form-text text-danger';
            return false;
        }
        
        if (newPassword === confirmPassword && newPassword.length >= 6) {
            feedback.textContent = 'Passwords match ✓';
            feedback.className = 'form-text text-success';
            return true;
        }
        
        return true;
    }
    
    newPasswordField.addEventListener('input', validatePasswords);
    confirmPasswordField.addEventListener('input', validatePasswords);
    
    form.addEventListener('submit', function(e) {
        if (!validatePasswords()) {
            e.preventDefault();
            showDarkAlert({
                title: 'Validation Error',
                text: 'Please fix the password validation errors before submitting.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 