<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Two-Factor Authentication Backup Codes</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/profile/edit">Profile</a></li>
                    <li class="breadcrumb-item active">Backup Codes</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?php if (isset($regenerated) && $regenerated): ?>
                        <h4 class="card-title text-success">
                            <i class="fas fa-check-circle me-2"></i>New Backup Codes Generated
                        </h4>
                        <p class="card-title-desc">Your old backup codes have been replaced with these new ones.</p>
                    <?php else: ?>
                        <h4 class="card-title text-success">
                            <i class="fas fa-check-circle me-2"></i>Two-Factor Authentication Enabled
                        </h4>
                        <p class="card-title-desc">Congratulations! Your account is now protected with two-factor authentication.</p>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Save these backup codes in a secure location. Each code can only be used once and will allow you to access your account if you lose your authenticator device.
                    </div>

                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-key me-2"></i>Your Backup Codes
                                    </h5>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyBackupCodes()">
                                        <i class="fas fa-copy me-1"></i>Copy All
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row backup-codes-container" id="backup-codes-container">
                                        <?php foreach ($backupCodes as $index => $code): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="backup-code-item p-2 bg-dark rounded border border-secondary">
                                                    <code class="text-light fw-bold"><?php echo htmlspecialchars($code); ?></code>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-outline-light btn-sm" onclick="printBackupCodes()">
                                            <i class="fas fa-print me-1"></i>Print Codes
                                        </button>
                                        <button type="button" class="btn btn-outline-light btn-sm" onclick="downloadBackupCodes()">
                                            <i class="fas fa-download me-1"></i>Download as Text
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-8 mx-auto">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>How to use backup codes:</h6>
                                <ul class="mb-0">
                                    <li>Each backup code can only be used once</li>
                                    <li>Use them when you don't have access to your authenticator app</li>
                                    <li>Enter a backup code in place of the 6-digit authenticator code</li>
                                    <li>Generate new codes if you're running low</li>
                                </ul>
                            </div>
                            
                            <div class="text-center">
                                <a href="/profile/edit" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<style>
.backup-code-item {
    background-color: #2d3748 !important;
    border: 1px solid #4a5568 !important;
    transition: all 0.2s ease;
}

.backup-code-item:hover {
    background-color: #374151 !important;
    border-color: #6b7280 !important;
}

.backup-code-item code {
    color: #e2e8f0 !important;
    background: transparent !important;
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    letter-spacing: 0.1rem;
}

.card-header {
    background-color: #374151 !important;
    border-bottom: 1px solid #4b5563 !important;
}

.btn-outline-light:hover {
    background-color: #f8f9fa;
    color: #212529;
}
</style>

<script>
function copyBackupCodes() {
    const codes = <?php echo json_encode($backupCodes); ?>;
    const codesText = codes.join('\n');
    
    navigator.clipboard.writeText(codesText).then(function() {
        showAlert('Backup codes copied to clipboard!', 'success');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = codesText;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showAlert('Backup codes copied to clipboard!', 'success');
    });
}

function printBackupCodes() {
    const codes = <?php echo json_encode($backupCodes); ?>;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
        <head>
            <title>2FA Backup Codes - Accounting Panel</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #333; }
                .code { 
                    font-family: monospace; 
                    font-size: 16px; 
                    font-weight: bold; 
                    padding: 5px; 
                    margin: 5px 0;
                    background: #f5f5f5;
                    border: 1px solid #ddd;
                    display: inline-block;
                    width: 120px;
                    text-align: center;
                }
                .warning { 
                    color: #d63384; 
                    font-weight: bold; 
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <h1>Two-Factor Authentication Backup Codes</h1>
            <p><strong>Account:</strong> ${<?php echo json_encode($_SESSION['user']['email']); ?>}</p>
            <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
            
            <div class="warning">
                ⚠️ IMPORTANT: Keep these codes secure and private. Each code can only be used once.
            </div>
            
            <h2>Backup Codes:</h2>
            ${codes.map(code => '<div class="code">' + code + '</div>').join('')}
            
            <div class="warning">
                Store these codes in a secure location separate from your authenticator device.
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

function downloadBackupCodes() {
    const codes = <?php echo json_encode($backupCodes); ?>;
    const userEmail = <?php echo json_encode($_SESSION['user']['email']); ?>;
    const timestamp = new Date().toISOString().split('T')[0];
    
    const content = `Two-Factor Authentication Backup Codes
Account: ${userEmail}
Generated: ${new Date().toLocaleString()}

IMPORTANT: Keep these codes secure and private. Each code can only be used once.

Backup Codes:
${codes.map((code, index) => `${index + 1}. ${code}`).join('\n')}

Instructions:
- Use these codes when you don't have access to your authenticator app
- Enter a backup code in place of the 6-digit authenticator code
- Each code can only be used once
- Generate new codes if you're running low

Store these codes in a secure location separate from your authenticator device.
`;
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `2fa-backup-codes-${timestamp}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showAlert('Backup codes downloaded successfully!', 'success');
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1050; max-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 