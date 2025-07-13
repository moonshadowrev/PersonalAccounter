<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Expenses</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item active">Expenses</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="col-auto">
                            <i class="fas fa-receipt text-primary fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Expenses</div>
                            <div class="h5 mb-0"><?php echo number_format($stats['total_count'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign text-success fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Amount</div>
                            <div class="h5 mb-0">$<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="col-auto">
                            <i class="fas fa-clock text-warning fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Pending</div>
                            <div class="h5 mb-0"><?php echo number_format($stats['pending_count'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="col-auto">
                            <i class="fas fa-check text-info fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Approved</div>
                            <div class="h5 mb-0"><?php echo number_format($stats['approved_count'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="card-title">All Expenses</h4>
                        <div class="d-flex gap-2">
                            <a href="/expenses/import" class="btn btn-success">
                                <i class="fas fa-file-excel me-2"></i>Import Excel
                            </a>
                            <a href="/expenses/export" class="btn btn-info">
                                <i class="fas fa-download me-2"></i>Export
                            </a>
                            <a href="/expenses/create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Expense
                            </a>
                        </div>
                    </div>

                    <table id="expenses-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Creator</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($expense['id']); ?></td>
                            <td>
                                <a href="/expenses/<?php echo htmlspecialchars($expense['id']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($expense['title']); ?>
                                </a>
                                <?php if (!empty($expense['attachments'])): ?>
                                    <i class="fas fa-paperclip text-muted ms-1" title="Has attachment"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($expense['category_name'])): ?>
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($expense['category_color'] ?? '#6c757d'); ?>; color: white;">
                                        <?php echo htmlspecialchars($expense['category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">No category</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($expense['currency']); ?> <?php echo number_format(($expense['amount'] + ($expense['tax_amount'] ?? 0)), 2); ?></strong>
                                <?php if (($expense['tax_amount'] ?? 0) > 0): ?>
                                    <br><small class="text-muted">Tax: <?php echo number_format($expense['tax_amount'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger'
                                ];
                                $statusColor = $statusColors[$expense['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo htmlspecialchars($statusColor); ?>"><?php echo htmlspecialchars(ucfirst($expense['status'])); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $expense['payment_method_type'] ?? 'N/A'))); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($expense['creator_name'] ?? 'Unknown'); ?></td>
                            <td>
                                <a href="/expenses/<?php echo htmlspecialchars($expense['id']); ?>" class="btn btn-sm btn-secondary">View</a>
                                <a href="/expenses/<?php echo htmlspecialchars($expense['id']); ?>/edit" class="btn btn-sm btn-info">Edit</a>
                                <?php if ($expense['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="approveExpense(<?php echo htmlspecialchars($expense['id']); ?>)">Approve</button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="rejectExpense(<?php echo htmlspecialchars($expense['id']); ?>)">Reject</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-danger delete-btn" onclick="deleteExpense(<?php echo htmlspecialchars($expense['id']); ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- end col -->
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

// Approve expense function
function approveExpense(expenseId) {
    showDarkAlert({
        title: 'Approve Expense?',
        text: 'This will approve the expense and generate a transaction.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, approve it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
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
    showDarkAlert({
        title: 'Reject Expense?',
        text: 'This will reject the expense and remove any associated transaction.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reject it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
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

// Delete expense function
function deleteExpense(expenseId) {
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
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/expenses/${expenseId}/delete`;
            
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