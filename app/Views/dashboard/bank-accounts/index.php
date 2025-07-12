<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Bank Accounts</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item active">Bank Accounts</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="card-title">All Bank Accounts</h4>
                        <a href="/bank-accounts/create" class="btn btn-primary">Add New Account</a>
                    </div>

                    <table id="bank-accounts-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Bank</th>
                                <th>Type</th>
                                <th>Account Number</th>
                                <th>Currency</th>
                                <th>Owner</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bankAccounts as $account): ?>
                        <tr>
                            <td><?php echo $account['id']; ?></td>
                            <td><?php echo htmlspecialchars($account['name']); ?></td>
                            <td><?php echo htmlspecialchars($account['bank_name']); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo ucfirst(str_replace('_', ' ', $account['account_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">**** **** <?php echo htmlspecialchars($account['account_number_last4']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($account['currency']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($account['user_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo date('M j, Y', strtotime($account['created_at'])); ?></td>
                            <td>
                                <a href="/bank-accounts/<?php echo $account['id']; ?>/edit" class="btn btn-sm btn-info">Edit</a>
                                <form action="/bank-accounts/<?php echo $account['id']; ?>/delete" method="POST" style="display:inline;">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger delete-btn">Delete</button>
                                </form>
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

// Delete confirmation
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
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
                    form.submit();
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 