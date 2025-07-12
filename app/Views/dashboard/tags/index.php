<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Tags</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item active">Tags</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="card-title">All Tags</h4>
                        <a href="/tags/create" class="btn btn-primary">Add New Tag</a>
                    </div>

                    <table id="tags-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Color</th>
                                <th>Expenses</th>
                                <th>Owner</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><?php echo $tag['id']; ?></td>
                            <td>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: white;">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($tag['description'] ?? ''); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="color-preview me-2" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; width: 20px; height: 20px; display: inline-block; border-radius: 50%; border: 1px solid #ccc;"></span>
                                    <span><?php echo htmlspecialchars($tag['color']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo number_format($tag['expense_count'] ?? 0); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($tag['creator_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo date('M j, Y', strtotime($tag['created_at'])); ?></td>
                            <td>
                                <a href="/tags/<?php echo $tag['id']; ?>/edit" class="btn btn-sm btn-info">Edit</a>
                                <form action="/tags/<?php echo $tag['id']; ?>/delete" method="POST" style="display:inline;">
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
                title: 'Delete Tag',
                text: 'Are you sure you want to delete this tag? This action cannot be undone and will remove the tag from all associated expenses.',
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