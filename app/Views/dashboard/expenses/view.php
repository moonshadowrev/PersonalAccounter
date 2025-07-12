<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Expense Details</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/expenses">Expenses</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($expense['title']); ?></li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="card-title mb-2"><?php echo htmlspecialchars($expense['title']); ?></h4>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger'
                            ];
                            $statusColor = $statusColors[$expense['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusColor; ?> fs-6"><?php echo ucfirst($expense['status']); ?></span>
                        </div>
                        <div class="btn-group" role="group">
                            <a href="/expenses/<?php echo $expense['id']; ?>/edit" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <?php if ($expense['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="approveExpense(<?php echo $expense['id']; ?>)">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="rejectExpense(<?php echo $expense['id']; ?>)">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            <?php endif; ?>
                            <a href="/expenses" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($expense['description'])): ?>
                    <div class="mb-4">
                        <h6 class="text-muted">Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($expense['description'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Amount Information -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <h6 class="text-muted">Amount</h6>
                            <h5 class="text-primary"><?php echo htmlspecialchars($expense['currency']); ?> <?php echo number_format($expense['amount'], 2); ?></h5>
                        </div>
                        <?php if ($expense['tax_amount'] > 0): ?>
                        <div class="col-md-4">
                            <h6 class="text-muted">Tax (<?php echo number_format($expense['tax_rate'], 2); ?>%)</h6>
                            <h6><?php echo htmlspecialchars($expense['currency']); ?> <?php echo number_format($expense['tax_amount'], 2); ?></h6>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <h6 class="text-muted">Total Amount</h6>
                            <h4 class="text-success"><?php echo htmlspecialchars($expense['currency']); ?> <?php echo number_format(($expense['amount'] + ($expense['tax_amount'] ?? 0)), 2); ?></h4>
                        </div>
                    </div>

                    <!-- Category and Tags -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Category</h6>
                            <?php if ($category): ?>
                                <span class="badge fs-6" style="background-color: <?php echo htmlspecialchars($category['color']); ?>; color: white;">
                                    <?php if (!empty($category['icon'])): ?>
                                        <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">No category assigned</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Tags</h6>
                            <?php if (!empty($tags)): ?>
                                <?php foreach ($tags as $tag): ?>
                                    <span class="badge fs-6 me-1" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: white;">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">No tags assigned</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="text-muted">Payment Method</h6>
                            <span class="badge bg-info fs-6"><?php echo ucfirst(str_replace('_', ' ', $expense['payment_method_type'] ?? 'N/A')); ?></span>
                            
                            <?php
                            // Display payment source name based on type
                            $paymentSourceName = 'Unknown';
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
                            <span class="ms-2 text-muted">(<?php echo htmlspecialchars($paymentSourceName); ?>)</span>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <?php if (!empty($expense['attachments'])): ?>
                    <div class="mb-4">
                        <h6 class="text-muted">Attachments</h6>
                        <?php 
                        $attachments = json_decode($expense['attachments'], true);
                        if (is_array($attachments) && !empty($attachments)): ?>
                            <div class="row">
                                <?php foreach ($attachments as $attachment): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border">
                                            <?php 
                                            // Fix file path - attachment already contains the full filename
                                            $filePath = '/uploads/expenses/' . $expense['user_id'] . '/' . basename($attachment);
                                            $fileExtension = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                                            $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                            $displayName = basename($attachment);
                                            ?>
                                            
                                            <?php if ($isImage): ?>
                                                <img src="<?php echo htmlspecialchars($filePath); ?>" 
                                                     class="card-img-top" 
                                                     style="height: 150px; object-fit: cover; cursor: pointer;"
                                                     onclick="showImageModal('<?php echo htmlspecialchars($filePath); ?>', '<?php echo htmlspecialchars($displayName); ?>')"
                                                     alt="<?php echo htmlspecialchars($displayName); ?>">
                                            <?php else: ?>
                                                <div class="card-img-top d-flex align-items-center justify-content-center bg-dark" style="height: 150px;">
                                                    <div class="text-center">
                                                        <i class="fas fa-file-alt fa-3x text-muted mb-2"></i>
                                                        <div class="small text-muted"><?php echo strtoupper($fileExtension); ?></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($displayName); ?>">
                                                        <?php echo htmlspecialchars($displayName); ?>
                                                    </small>
                                                    <div>
                                                        <a href="<?php echo htmlspecialchars($filePath); ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           download="<?php echo htmlspecialchars($displayName); ?>"
                                                           title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <?php if ($isImage): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-secondary" 
                                                                    onclick="showImageModal('<?php echo htmlspecialchars($filePath); ?>', '<?php echo htmlspecialchars($displayName); ?>')"
                                                                    title="View Full Size">
                                                                <i class="fas fa-search-plus"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">No attachments uploaded</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Notes -->
                    <?php if (!empty($expense['notes'])): ?>
                    <div class="mb-4">
                        <h6 class="text-muted">Notes</h6>
                        <p><?php echo nl2br(htmlspecialchars($expense['notes'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Additional Information -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="text-muted">Created by</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($expense['creator_name'] ?? 'Unknown'); ?></p>
                            <small class="text-muted"><?php echo htmlspecialchars($expense['creator_email'] ?? ''); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Dates Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Important Dates</h6>
                    <div class="mb-3">
                        <small class="text-muted">Expense Date</small>
                        <div class="fw-bold"><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></div>
                    </div>
                    <?php if (!empty($expense['due_date'])): ?>
                    <div class="mb-3">
                        <small class="text-muted">Due Date</small>
                        <div class="fw-bold"><?php echo date('M j, Y', strtotime($expense['due_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <small class="text-muted">Created</small>
                        <div><?php echo date('M j, Y g:i A', strtotime($expense['created_at'])); ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Last Updated</small>
                        <div><?php echo date('M j, Y g:i A', strtotime($expense['updated_at'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Transaction Information -->
            <?php if ($transaction): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Transaction Details</h6>
                    <div class="mb-2">
                        <small class="text-muted">Transaction ID</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($transaction['reference_number']); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Status</small>
                        <div>
                            <?php
                            $transactionStatusColors = [
                                'pending' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger'
                            ];
                            $transactionStatusColor = $transactionStatusColors[$transaction['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $transactionStatusColor; ?>"><?php echo ucfirst($transaction['status']); ?></span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Transaction Date</small>
                        <div><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Amount</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($transaction['currency']); ?> <?php echo number_format($transaction['amount'], 2); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light" id="imageModalLabel">Image Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="Preview">
            </div>
            <div class="modal-footer border-secondary">
                <a id="modalDownload" href="" download class="btn btn-primary">
                    <i class="fas fa-download"></i> Download
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Image modal functionality
function showImageModal(imagePath, fileName) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    const modalImage = document.getElementById('modalImage');
    const modalDownload = document.getElementById('modalDownload');
    const modalTitle = document.getElementById('imageModalLabel');
    
    modalImage.src = imagePath;
    modalDownload.href = imagePath;
    modalDownload.download = fileName;
    modalTitle.textContent = fileName;
    
    modal.show();
}

// Approve expense function
function approveExpense(expenseId) {
    Swal.fire({
        title: 'Approve Expense?',
        text: 'This will approve the expense and generate a transaction.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, approve it!',
        cancelButtonText: 'Cancel',
        background: 'var(--bs-dark)',
        color: 'var(--bs-light)'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/expenses/${expenseId}/approve`;
            
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '<?php echo htmlspecialchars($csrf_token ?? ''); ?>';
            
            form.appendChild(tokenInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Reject expense function
function rejectExpense(expenseId) {
    Swal.fire({
        title: 'Reject Expense?',
        text: 'This will reject the expense and remove any associated transaction.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reject it!',
        cancelButtonText: 'Cancel',
        background: 'var(--bs-dark)',
        color: 'var(--bs-light)'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/expenses/${expenseId}/reject`;
            
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '<?php echo htmlspecialchars($csrf_token ?? ''); ?>';
            
            form.appendChild(tokenInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 