<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">API Key Created Successfully</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/profile/edit">Profile</a></li>
                    <li class="breadcrumb-item"><a href="/profile/api-keys">API Keys</a></li>
                    <li class="breadcrumb-item active">Created</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-success" role="alert">
                <h4 class="alert-heading"><i class="fas fa-check-circle"></i> API Key Created!</h4>
                <p>Your new API key "<strong><?= htmlspecialchars($apiKey['name']) ?></strong>" has been created successfully.</p>
                <a href="/profile/api-keys" class="btn btn-outline-success">
                    <i class="fas fa-arrow-left"></i> Back to API Keys
                </a>
            </div>
        </div>
    </div>

    <div class="row">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exclamation-triangle"></i> Important Security Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning" role="alert">
                                <strong>⚠️ This is the only time you will see this API key!</strong><br>
                                Make sure to copy and store it securely. You will not be able to view it again.
                            </div>

                            <div class="mb-4">
                                <label for="apiKey" class="form-label"><strong>Your API Key:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" id="apiKey" 
                                           value="<?= htmlspecialchars($apiKey['raw_key']) ?>" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Key Details:</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Name:</strong> <?= htmlspecialchars($apiKey['name']) ?></li>
                                        <li><strong>Key ID:</strong> <?= $apiKey['id'] ?></li>
                                        <li><strong>Prefix:</strong> <code><?= htmlspecialchars($apiKey['prefix']) ?>...</code></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Security Best Practices:</h6>
                                    <ul>
                                        <li>Store this key in a secure location</li>
                                        <li>Never commit it to version control</li>
                                        <li>Use environment variables in production</li>
                                        <li>Rotate keys regularly</li>
                                        <li>Monitor API usage for anomalies</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">How to Use Your API Key</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Authentication Methods</h6>
                                    <p>Include your API key in requests using one of these methods:</p>
                                    
                                    <h6 class="mt-3">1. Authorization Header (Recommended)</h6>
                                    <pre class="bg-dark text-light p-2 rounded border"><code>Authorization: Bearer <?= htmlspecialchars($apiKey['raw_key']) ?></code></pre>
                                    
                                    <h6 class="mt-3">2. X-API-Key Header</h6>
                                    <pre class="bg-dark text-light p-2 rounded border"><code>X-API-Key: <?= htmlspecialchars($apiKey['raw_key']) ?></code></pre>
                                    
                                    <h6 class="mt-3">3. Query Parameter (Less Secure)</h6>
                                    <pre class="bg-dark text-light p-2 rounded border"><code>?api_key=<?= htmlspecialchars($apiKey['raw_key']) ?></code></pre>
                                </div>
                                <div class="col-md-6">
                                    <h6>Example Usage</h6>
                                    <p>Here's how to make a request to the API:</p>
                                    
                                    <h6 class="mt-3">cURL Example</h6>
                                    <pre class="bg-dark text-light p-2 rounded border small"><code>curl -H "Authorization: Bearer <?= htmlspecialchars($apiKey['raw_key']) ?>" \
     <?= $_ENV['APP_URL'] ?? 'http://localhost' ?>/api/v1/users</code></pre>
                                    
                                    <h6 class="mt-3">JavaScript Example</h6>
                                    <pre class="bg-dark text-light p-2 rounded border small"><code>fetch('<?= $_ENV['APP_URL'] ?? 'http://localhost' ?>/api/v1/users', {
  headers: {
    'Authorization': 'Bearer <?= htmlspecialchars($apiKey['raw_key']) ?>',
    'Content-Type': 'application/json'
  }
})</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-center">
                    <a href="/profile/api-keys" class="btn btn-primary btn-lg">
                        <i class="fas fa-key"></i> Manage API Keys
                    </a>
                    <?php if (Config::get('debug', false)): ?>
                        <a href="/api/docs/ui" target="_blank" class="btn btn-outline-info btn-lg ms-2">
                            <i class="fas fa-book"></i> View API Documentation
                        </a>
                    <?php endif; ?>
                </div>
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<script>
// SweetAlert2 dark theme function
function showDarkAlert(options) {
    const defaultOptions = {
        background: '#2d3748',
        color: '#ffffff',
        confirmButtonColor: '#4299e1',
        customClass: {
            popup: 'swal-dark-popup'
        }
    };
    
    Swal.fire({...defaultOptions, ...options});
}

function copyApiKey() {
    const apiKeyInput = document.getElementById('apiKey');
    apiKeyInput.select();
    apiKeyInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Show feedback
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    } catch (err) {
        console.error('Failed to copy API key:', err);
                        showDarkAlert({
                    title: 'Copy Failed',
                    text: 'Failed to copy API key. Please copy it manually.',
                    icon: 'warning'
                });
    }
}

// Auto-select the API key on page load for easy copying
document.addEventListener('DOMContentLoaded', function() {
    const apiKeyInput = document.getElementById('apiKey');
    apiKeyInput.focus();
    apiKeyInput.select();
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 