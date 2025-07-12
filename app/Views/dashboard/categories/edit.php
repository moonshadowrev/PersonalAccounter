<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Category</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/categories">Categories</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Edit Category Details</h4>
                    <p class="card-title-desc">Update the category information below.</p>
                    
                    <form action="/categories/<?php echo $category['id']; ?>" method="POST">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        <input type="hidden" name="_method" value="PUT">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($category['name']); ?>" placeholder="Enter category name">
                            <div class="form-text">This will be the display name for the category.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" maxlength="500" placeholder="Optional description for this category"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                            <div class="form-text">Optional description to help identify this category.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="color" id="color" name="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($category['color']); ?>" title="Choose category color">
                                    <div class="form-text">Select a color to visually identify this category.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Icon</label>
                                    <input type="text" id="icon" name="icon" class="form-control" placeholder="fa fa-shopping-cart" maxlength="50" value="<?php echo htmlspecialchars($category['icon'] ?? ''); ?>">
                                    <div class="form-text">Optional Font Awesome icon class (e.g., "fa fa-shopping-cart").</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="category-preview">
                                <label class="form-label">Preview</label>
                                <div class="d-flex align-items-center">
                                    <span id="category-preview-badge" class="badge" style="background-color: <?php echo htmlspecialchars($category['color']); ?>; color: white;">
                                        <i id="category-preview-icon" class="<?php echo htmlspecialchars($category['icon'] ?? ''); ?>"></i>
                                        <span id="category-preview-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Category</button>
                            <a href="/categories" class="btn btn-secondary">Cancel</a>
                            <?php if (($category['expense_count'] ?? 0) == 0): ?>
                                <button type="button" class="btn btn-danger float-end" onclick="confirmDelete()">Delete Category</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-danger float-end" disabled title="Cannot delete category with expenses">Delete Category</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<!-- Delete Form (hidden) -->
<form id="delete-form" action="/categories/<?php echo $category['id']; ?>/delete" method="POST" style="display: none;">
    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const nameInput = document.getElementById('name');
    const colorInput = document.getElementById('color');
    const iconInput = document.getElementById('icon');
    const previewBadge = document.getElementById('category-preview-badge');
    const previewIcon = document.getElementById('category-preview-icon');
    const previewName = document.getElementById('category-preview-name');
    
    // Update preview
    function updatePreview() {
        const name = nameInput.value || 'Category Name';
        const color = colorInput.value || '#10B981';
        const icon = iconInput.value || '';
        
        previewBadge.style.backgroundColor = color;
        previewName.textContent = name;
        previewIcon.className = icon;
        
        if (icon) {
            previewIcon.style.display = 'inline';
        } else {
            previewIcon.style.display = 'none';
        }
    }
    
    // Live preview updates
    nameInput.addEventListener('input', updatePreview);
    colorInput.addEventListener('input', updatePreview);
    iconInput.addEventListener('input', updatePreview);
    
    // Initial preview update
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
        title: 'Delete Category',
        text: 'Are you sure you want to delete this category? This action cannot be undone and will remove the category from all associated expenses.',
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