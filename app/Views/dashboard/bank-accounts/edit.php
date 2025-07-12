<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Bank Account</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/bank-accounts">Bank Accounts</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Edit Bank Account Details</h4>
                    <form action="/bank-accounts/<?php echo $account['id']; ?>" method="POST" class="bank-account-form">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        <input type="hidden" name="_method" value="PUT">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($account['name']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" id="bank_name" name="bank_name" class="form-control" required value="<?php echo htmlspecialchars($account['bank_name']); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_type" class="form-label">Account Type</label>
                                    <select id="account_type" name="account_type" class="form-control">
                                        <?php foreach ($accountTypes as $key => $type): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $account['account_type'] === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <input type="text" id="currency" name="currency" class="form-control" maxlength="3" pattern="[A-Z]{3}" value="<?php echo htmlspecialchars($account['currency']); ?>">
                                    <div class="form-text">Enter 3-letter currency code (e.g., USD, EUR, GBP).</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" class="form-control" disabled value="**** **** <?php echo htmlspecialchars($account['account_number_last4']); ?>">
                            <div class="form-text">Account number cannot be changed for security reasons.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="routing_number" class="form-label">Routing/Sort Code</label>
                            <input type="text" id="routing_number" name="routing_number" class="form-control" pattern="[0-9]{6,15}" value="<?php echo htmlspecialchars($account['routing_number'] ?? ''); ?>">
                            <div class="form-text">Routing number, sort code, or bank code (6-15 digits, optional).</div>
                        </div>
                        
                        <!-- International Banking Fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="country_code" class="form-label">Country</label>
                                    <input type="text" id="country_code" name="country_code" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($account['country_code'] ?? ''); ?>" placeholder="United States, Germany, etc.">
                                    <div class="form-text">Country where the bank account is located.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="swift_bic" class="form-label">SWIFT/BIC Code</label>
                                    <input type="text" id="swift_bic" name="swift_bic" class="form-control" maxlength="11" value="<?php echo htmlspecialchars($account['swift_bic'] ?? ''); ?>" placeholder="DEUTDEFFXXX">
                                    <div class="form-text">SWIFT/BIC code for international transfers (optional).</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="iban" class="form-label">IBAN</label>
                            <input type="text" id="iban" name="iban" class="form-control" maxlength="34" value="<?php echo htmlspecialchars($account['iban'] ?? ''); ?>" placeholder="DE89 3704 0044 0532 0130 00">
                            <div class="form-text">International Bank Account Number (optional, mainly for European banks).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($account['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mt-4 bank-account-actions">
                            <button type="submit" class="btn btn-primary">Update Account</button>
                            <a href="/bank-accounts" class="btn btn-secondary">Cancel</a>
                            <button type="button" class="btn btn-danger float-end" onclick="confirmDelete()">Delete Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="delete-form" action="/bank-accounts/<?php echo $account['id']; ?>/delete" method="POST" style="display: none;">
    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
</form>

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

function confirmDelete() {
    showDarkAlert({
        title: 'Delete Bank Account',
        text: 'Are you sure you want to delete this bank account? This action cannot be undone and will remove it from all associated expenses.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e74a3b'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form').submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 