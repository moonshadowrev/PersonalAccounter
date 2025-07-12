<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Tag</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/tags">Tags</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Edit Tag Details</h4>
                    <p class="card-title-desc">Update the tag information below.</p>
                    
                    <form action="/tags/<?php echo $tag['id']; ?>" method="POST">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        <input type="hidden" name="_method" value="PUT">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Tag Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($tag['name']); ?>" placeholder="Enter tag name">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" maxlength="500" placeholder="Optional description for this tag"><?php echo htmlspecialchars($tag['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="color" id="color" name="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($tag['color']); ?>" title="Choose tag color">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Preview</label>
                            <div class="d-flex align-items-center">
                                <span id="tag-preview-badge" class="badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: white;">
                                    <span id="tag-preview-name"><?php echo htmlspecialchars($tag['name']); ?></span>
                                </span>
                            </div>
                        </div>
                        
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Tag</button>
                            <a href="/tags" class="btn btn-secondary">Cancel</a>
                            <?php if (($tag['expense_count'] ?? 0) == 0): ?>
                                <button type="button" class="btn btn-danger float-end" onclick="confirmDelete()">Delete Tag</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-danger float-end" disabled title="Cannot delete tag with expenses">Delete Tag</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<!-- Delete Form (hidden) -->
<form id="delete-form" action="/tags/<?php echo $tag['id']; ?>/delete" method="POST" style="display: none;">
    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const colorInput = document.getElementById('color');
    const previewBadge = document.getElementById('tag-preview-badge');
    const previewName = document.getElementById('tag-preview-name');
    
    function updatePreview() {
        const name = nameInput.value || 'Tag Name';
        const color = colorInput.value || '#10B981';
        
        previewBadge.style.backgroundColor = color;
        previewName.textContent = name;
    }
    
    nameInput.addEventListener('input', updatePreview);
    colorInput.addEventListener('input', updatePreview);
    updatePreview();
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
        title: 'Delete Tag',
        text: 'Are you sure you want to delete this tag? This action cannot be undone and will remove the tag from all associated expenses.',
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