<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Add Category</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/categories">Categories</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">New Category Details</h4>
                    <p class="card-title-desc">Fill out the form below to add a new expense category.</p>
                    
                    <form action="/categories" method="POST" class="category-form">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required maxlength="100" placeholder="Enter category name">
                            <div class="form-text">This will be the display name for the category.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" maxlength="500" placeholder="Optional description for this category"></textarea>
                            <div class="form-text">Optional description to help identify this category.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6 col-12 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="color" id="color" name="color" class="form-control form-control-color" value="#10B981" title="Choose category color">
                                <div class="form-text">Select a color to visually identify this category.</div>
                            </div>
                            <div class="col-lg-6 col-12 mb-3">
                                <label for="icon" class="form-label">Icon</label>
                                <input type="text" id="icon" name="icon" class="form-control" placeholder="fa fa-shopping-cart" maxlength="50">
                                <div class="form-text">Optional Font Awesome icon class (e.g., "fa fa-shopping-cart").</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Default Categories</label>
                            <div class="row">
                                <?php foreach ($defaultCategories as $default): ?>
                                <div class="col-lg-4 col-md-6 col-12 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input default-category" type="radio" name="default_category" value="<?php echo htmlspecialchars($default['name']); ?>" data-color="<?php echo htmlspecialchars($default['color']); ?>" data-icon="<?php echo htmlspecialchars($default['icon']); ?>" data-description="<?php echo htmlspecialchars($default['description']); ?>">
                                        <label class="form-check-label">
                                            <span class="badge" style="background-color: <?php echo htmlspecialchars($default['color']); ?>; color: white;">
                                                <?php if (!empty($default['icon'])): ?>
                                                    <i class="<?php echo htmlspecialchars($default['icon']); ?>"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($default['name']); ?>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Select a default category to auto-fill the form, or create a custom category.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="category-preview">
                                <label class="form-label">Preview</label>
                                <div class="d-flex align-items-center">
                                    <span id="category-preview-badge" class="badge" style="background-color: #10B981; color: white;">
                                        <i id="category-preview-icon" class=""></i>
                                        <span id="category-preview-name">Category Name</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="submit" class="btn btn-primary flex-sm-fill">
                                    <i class="fas fa-save me-2"></i>Add Category
                                </button>
                                <a href="/categories" class="btn btn-secondary flex-sm-fill">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const nameInput = document.getElementById('name');
    const colorInput = document.getElementById('color');
    const iconInput = document.getElementById('icon');
    const previewBadge = document.getElementById('category-preview-badge');
    const previewIcon = document.getElementById('category-preview-icon');
    const previewName = document.getElementById('category-preview-name');
    const defaultCategoryRadios = document.querySelectorAll('.default-category');
    
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
    
    // Default category selection
    defaultCategoryRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                nameInput.value = this.value;
                colorInput.value = this.dataset.color;
                iconInput.value = this.dataset.icon;
                document.getElementById('description').value = this.dataset.description;
                updatePreview();
            }
        });
    });
    
    // Live preview updates
    nameInput.addEventListener('input', updatePreview);
    colorInput.addEventListener('input', updatePreview);
    iconInput.addEventListener('input', updatePreview);
    
    // Initial preview
    updatePreview();
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 