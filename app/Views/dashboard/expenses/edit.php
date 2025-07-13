<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Expense</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/expenses">Expenses</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Edit Expense Details</h4>
                    <p class="card-title-desc">Update the expense information below.</p>
                    
                    <form action="/expenses/<?php echo $expense['id']; ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        <input type="hidden" name="_method" value="PUT">
                        
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control" required value="<?php echo htmlspecialchars($expense['title']); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                                    <input type="date" id="expense_date" name="expense_date" class="form-control" required value="<?php echo htmlspecialchars($expense['expense_date']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($expense['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Amount and Tax -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" id="amount" name="amount" class="form-control" required min="0" step="0.01" value="<?php echo htmlspecialchars($expense['amount']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <select id="currency" name="currency" class="form-control">
                                        <?php foreach ($currencies as $code => $name): ?>
                                        <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $expense['currency'] === $code ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($code . ' - ' . $name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                    <input type="number" id="tax_rate" name="tax_rate" class="form-control" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($expense['tax_rate'] ?? '0'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tax and Total Display -->
                        <div class="row" id="tax-display" style="<?php echo ($expense['tax_rate'] ?? 0) > 0 ? 'display: flex;' : 'display: none;'; ?>">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tax Amount</label>
                                    <div class="form-control-plaintext" id="tax-amount">$<?php echo number_format($expense['tax_amount'] ?? 0, 2); ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Total Amount</label>
                                    <div class="form-control-plaintext font-weight-bold" id="total-amount">$<?php echo number_format(($expense['amount'] + ($expense['tax_amount'] ?? 0)), 2); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category and Tags -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select id="category_id" name="category_id" class="form-control">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo ($expense['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tag_ids" class="form-label">Tags</label>
                                    <select id="tag_ids" name="tag_ids[]" class="form-control" multiple>
                                        <?php foreach ($tags as $tag): ?>
                                        <option value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $selectedTagIds ?? []) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple tags</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Method (Read-only in edit) -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <input type="text" class="form-control" disabled value="<?php echo ucfirst(str_replace('_', ' ', $expense['payment_method_type'] ?? 'N/A')); ?>">
                                    <div class="form-text">Payment method cannot be changed after creation.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Source</label>
                                    <?php
                                    // Display payment source name based on type
                                    $paymentSourceName = 'N/A';
                                    switch($expense['payment_method_type']) {
                                        case 'credit_card':
                                            $paymentSourceName = $expense['credit_card_name'] ?? 'Credit Card';
                                            break;
                                        case 'bank_account':
                                            $paymentSourceName = $expense['bank_account_name'] ?? 'Bank Account';
                                            break;
                                        case 'crypto_wallet':
                                            $paymentSourceName = $expense['crypto_wallet_name'] ?? 'Crypto Wallet';
                                            break;
                                    }
                                    ?>
                                    <input type="text" class="form-control" disabled value="<?php echo htmlspecialchars($paymentSourceName); ?>">
                                    <div class="form-text">Payment source cannot be changed after creation.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="pending" <?php echo $expense['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $expense['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $expense['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Attachments -->
                        <?php if (!empty($expense['attachments'])): 
                            $attachments = json_decode($expense['attachments'], true);
                            if (is_array($attachments) && !empty($attachments)): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Attachments</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($attachments as $attachment): ?>
                                <div class="d-flex align-items-center border rounded p-2">
                                    <i class="fas fa-paperclip text-muted me-2"></i>
                                    <a href="/uploads/expenses/<?php echo htmlspecialchars($expense['user_id']); ?>/<?php echo htmlspecialchars(basename($attachment)); ?>" 
                                       class="text-decoration-none me-2" 
                                       download="<?php echo basename($attachment); ?>">
                                        <?php echo htmlspecialchars(basename($attachment)); ?>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; endif; ?>
                        
                        <!-- New File Attachment -->
                        <div class="mb-3">
                            <label for="attachment" class="form-label">
                                <?php echo (!empty($expense['attachments']) && $expense['attachments'] !== 'null') ? 'Replace Attachment' : 'Add Attachment'; ?>
                            </label>
                            <input type="file" id="attachment" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx">
                            <small class="form-text text-muted">
                                <?php echo (!empty($expense['attachments']) && $expense['attachments'] !== 'null') ? 'Leave empty to keep current attachments' : 'Upload receipt or supporting document (max 10MB)'; ?>
                            </small>
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($expense['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Expense</button>
                            <a href="/expenses/<?php echo $expense['id']; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="button" class="btn btn-danger float-end" onclick="confirmDelete()">Delete Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete form -->
<form id="delete-form" action="/expenses/<?php echo $expense['id']; ?>/delete" method="POST" style="display: none;">
    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calculate tax and total
    function calculateAmounts() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
        
        const taxAmount = (taxRate > 0) ? (amount * taxRate / 100) : 0;
        const totalAmount = amount + taxAmount;
        
        if (taxRate > 0 || amount > 0) {
            document.getElementById('tax-display').style.display = 'flex';
            document.getElementById('tax-amount').textContent = '$' + taxAmount.toFixed(2);
            document.getElementById('total-amount').textContent = '$' + totalAmount.toFixed(2);
        } else {
            document.getElementById('tax-display').style.display = 'none';
        }
    }
    
    document.getElementById('amount').addEventListener('input', calculateAmounts);
    document.getElementById('tax_rate').addEventListener('input', calculateAmounts);
    
    // Initial calculation
    calculateAmounts();
});

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
        title: 'Delete Expense',
        text: 'Are you sure you want to delete this expense? This action cannot be undone.',
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