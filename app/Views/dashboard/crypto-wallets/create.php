<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Add Crypto Wallet</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/crypto-wallets">Crypto Wallets</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">New Crypto Wallet Details</h4>
                    <p class="card-title-desc">Fill out the form below to add a new crypto wallet.</p>
                    
                    <form action="/crypto-wallets" method="POST" class="crypto-wallet-form">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Wallet Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required maxlength="100" placeholder="My USDT Wallet">
                            <div class="form-text">A friendly name to identify this wallet.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="network" class="form-label">Network <span class="text-danger">*</span></label>
                                    <select id="network" name="network" class="form-control" required>
                                        <option value="">Select Network</option>
                                        <?php foreach ($networks as $key => $name): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                    <input type="text" id="currency" name="currency" class="form-control" required maxlength="10" placeholder="USDT, BTC, ETH, TRX, etc.">
                                    <div class="form-text">Enter the currency symbol (e.g., USDT, BTC, ETH, TRX).</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Wallet Address <span class="text-danger">*</span></label>
                            <input type="text" id="address" name="address" class="form-control" required placeholder="Enter wallet address">
                            <div id="address-validation" class="mt-1"></div>
                            <div class="form-text">Enter the complete wallet address for this network.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000" placeholder="Additional notes about this wallet"></textarea>
                        </div>
                        
                        <div class="mt-4 crypto-wallet-actions">
                            <button type="submit" class="btn btn-primary">Add Crypto Wallet</button>
                            <a href="/crypto-wallets" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const networkSelect = document.getElementById('network');
    const addressInput = document.getElementById('address');
    const addressValidation = document.getElementById('address-validation');
    
    // Reset address validation when network changes
    networkSelect.addEventListener('change', function() {
        validateAddress();
    });
    
    // Validate address
    function validateAddress() {
        const address = addressInput.value.trim();
        const network = networkSelect.value;
        
        if (address && network) {
            fetch('/crypto-wallets/validate-address', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': '<?php echo htmlspecialchars($csrf_token ?? ''); ?>'
                },
                body: new URLSearchParams({
                    address: address,
                    network: network,
                    _token: '<?php echo htmlspecialchars($csrf_token ?? ''); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.valid) {
                        addressValidation.innerHTML = '<small class="text-success">✓ Valid address format</small>';
                        addressInput.classList.remove('is-invalid');
                        addressInput.classList.add('is-valid');
                    } else {
                        addressValidation.innerHTML = '<small class="text-danger">✗ Invalid address format for this network</small>';
                        addressInput.classList.remove('is-valid');
                        addressInput.classList.add('is-invalid');
                    }
                }
            })
            .catch(error => {
                console.error('Address validation error:', error);
            });
        } else {
            addressValidation.innerHTML = '';
            addressInput.classList.remove('is-valid', 'is-invalid');
        }
    }
    
    // Real-time address validation
    let validationTimeout;
    addressInput.addEventListener('input', function() {
        clearTimeout(validationTimeout);
        validationTimeout = setTimeout(validateAddress, 500);
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 