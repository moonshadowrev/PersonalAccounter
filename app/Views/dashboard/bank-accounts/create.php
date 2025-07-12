<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Add Bank Account</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/bank-accounts">Bank Accounts</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">New Bank Account Details</h4>
                    <p class="card-title-desc">Fill out the form below to add a new bank account.</p>
                    
                    <form action="/bank-accounts" method="POST" id="bank-account-form" class="bank-account-form">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required maxlength="100" placeholder="My Business Account">
                            <div class="form-text">A friendly name to identify this account.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" id="bank_name" name="bank_name" class="form-control" required maxlength="100" placeholder="Chase Bank">
                            <div class="form-text">The name of your bank or financial institution.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_type" class="form-label">Account Type</label>
                                    <select id="account_type" name="account_type" class="form-control">
                                        <?php foreach ($accountTypes as $key => $type): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <input type="text" id="currency" name="currency" class="form-control" maxlength="3" placeholder="USD" value="USD" pattern="[A-Z]{3}">
                                    <div class="form-text">Enter 3-letter currency code (e.g., USD, EUR, GBP).</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                                    <input type="text" id="account_number" name="account_number" class="form-control" required pattern="[0-9]{8,17}" placeholder="12345678901234567">
                                    <div class="form-text">8-17 digit account number (only last 4 digits will be stored).</div>
                                    <div id="account-validation" class="mt-1"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="routing_number" class="form-label">Routing/Sort Code</label>
                                    <input type="text" id="routing_number" name="routing_number" class="form-control" pattern="[0-9]{6,15}" placeholder="123456789">
                                    <div class="form-text">Routing number, sort code, or bank code (6-15 digits, optional).</div>
                                    <div id="routing-validation" class="mt-1"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- International Banking Fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="country_code" class="form-label">Country</label>
                                    <input type="text" id="country_code" name="country_code" class="form-control" maxlength="50" placeholder="United States, Germany, etc.">
                                    <div class="form-text">Country where the bank account is located.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="swift_bic" class="form-label">SWIFT/BIC Code</label>
                                    <input type="text" id="swift_bic" name="swift_bic" class="form-control" maxlength="11" placeholder="DEUTDEFFXXX">
                                    <div class="form-text">SWIFT/BIC code for international transfers (optional).</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="iban" class="form-label">IBAN</label>
                            <input type="text" id="iban" name="iban" class="form-control" maxlength="34" placeholder="DE89 3704 0044 0532 0130 00">
                            <div class="form-text">International Bank Account Number (optional, mainly for European banks).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000" placeholder="Additional notes about this account"></textarea>
                        </div>
                        
                        <div class="mt-4 bank-account-actions">
                            <button type="submit" class="btn btn-primary">Add Bank Account</button>
                            <a href="/bank-accounts" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const accountNumberInput = document.getElementById('account_number');
    const routingNumberInput = document.getElementById('routing_number');
    const accountValidation = document.getElementById('account-validation');
    const routingValidation = document.getElementById('routing-validation');
    
    function validateInputs() {
        const accountNumber = accountNumberInput.value.trim();
        const routingNumber = routingNumberInput.value.trim();
        
        if (accountNumber || routingNumber) {
            fetch('/bank-accounts/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': '<?php echo htmlspecialchars($csrf_token ?? ''); ?>'
                },
                body: new URLSearchParams({
                    account_number: accountNumber,
                    routing_number: routingNumber,
                    _token: '<?php echo htmlspecialchars($csrf_token ?? ''); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                // Account number validation
                if (accountNumber) {
                    if (data.account_valid) {
                        accountValidation.innerHTML = '<small class="text-success">✓ Valid account number format</small>';
                        accountNumberInput.classList.remove('is-invalid');
                        accountNumberInput.classList.add('is-valid');
                    } else {
                        accountValidation.innerHTML = '<small class="text-danger">✗ Invalid account number format</small>';
                        accountNumberInput.classList.remove('is-valid');
                        accountNumberInput.classList.add('is-invalid');
                    }
                } else {
                    accountValidation.innerHTML = '';
                    accountNumberInput.classList.remove('is-valid', 'is-invalid');
                }
                
                // Routing number validation
                if (routingNumber) {
                    if (data.routing_valid) {
                        routingValidation.innerHTML = '<small class="text-success">✓ Valid routing number format</small>';
                        routingNumberInput.classList.remove('is-invalid');
                        routingNumberInput.classList.add('is-valid');
                    } else {
                        routingValidation.innerHTML = '<small class="text-danger">✗ Invalid routing number format</small>';
                        routingNumberInput.classList.remove('is-valid');
                        routingNumberInput.classList.add('is-invalid');
                    }
                } else {
                    routingValidation.innerHTML = '';
                    routingNumberInput.classList.remove('is-valid', 'is-invalid');
                }
            })
            .catch(error => {
                console.error('Validation error:', error);
            });
        } else {
            accountValidation.innerHTML = '';
            routingValidation.innerHTML = '';
            accountNumberInput.classList.remove('is-valid', 'is-invalid');
            routingNumberInput.classList.remove('is-valid', 'is-invalid');
        }
    }
    
    // Real-time validation
    let validationTimeout;
    accountNumberInput.addEventListener('input', function() {
        clearTimeout(validationTimeout);
        validationTimeout = setTimeout(validateInputs, 500);
    });
    
    routingNumberInput.addEventListener('input', function() {
        clearTimeout(validationTimeout);
        validationTimeout = setTimeout(validateInputs, 500);
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 