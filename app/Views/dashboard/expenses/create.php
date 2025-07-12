<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Add New Expense</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/expenses">Expenses</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">New Expense Details</h4>
                    <p class="card-title-desc">Fill out the form below to add a new expense.</p>
                    
                    <form action="/expenses" method="POST" enctype="multipart/form-data" id="expense-form" class="expense-form">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-lg-8 col-12">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control" required maxlength="255" placeholder="Expense title">
                                </div>
                            </div>
                            <div class="col-lg-4 col-12">
                                <div class="mb-3">
                                    <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                                    <input type="date" id="expense_date" name="expense_date" class="form-control" required>
                                    <small class="form-text text-muted">When did this expense occur?</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" maxlength="1000" placeholder="Optional expense description"></textarea>
                        </div>
                        
                        <!-- Amount and Tax -->
                        <div class="row">
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" id="amount" name="amount" class="form-control" required min="0" step="0.01" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <select id="currency" name="currency" class="form-control" readonly>
                                        <option value="">Select payment method first</option>
                                    </select>
                                    <small class="form-text text-muted">Currency is set by payment source</small>
                                </div>
                            </div>
                            <div class="col-lg-4 col-12">
                                <div class="mb-3">
                                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                    <input type="number" id="tax_rate" name="tax_rate" class="form-control" min="0" max="100" step="0.01" placeholder="0.00">
                                    <small class="form-text text-muted">Optional tax percentage</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tax and Total Display -->
                        <div class="row" id="tax-display" style="display: none;">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tax Amount</label>
                                    <div class="form-control-plaintext" id="tax-amount">$0.00</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Total Amount</label>
                                    <div class="form-control-plaintext font-weight-bold" id="total-amount">$0.00</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category and Tags -->
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <select id="category_id" name="category_id" class="form-control flex-grow-1">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" data-color="<?php echo htmlspecialchars($category['color']); ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-primary" onclick="openQuickCreate('category')">
                                            <i class="fas fa-plus"></i><span class="d-none d-sm-inline ms-1">New</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <label for="tag_ids" class="form-label">Tags</label>
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <select id="tag_ids" name="tag_ids[]" class="form-control flex-grow-1" multiple>
                                            <?php foreach ($tags as $tag): ?>
                                            <option value="<?php echo $tag['id']; ?>" data-color="<?php echo htmlspecialchars($tag['color']); ?>">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-primary" onclick="openQuickCreate('tag')">
                                            <i class="fas fa-plus"></i><span class="d-none d-sm-inline ms-1">New</span>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple tags</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select id="payment_method" name="payment_method" class="form-control" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="bank_account">Bank Account</option>
                                        <option value="crypto_wallet">Crypto Wallet</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <label for="payment_id" class="form-label">Payment Source <span class="text-danger">*</span></label>
                                    <select id="payment_id" name="payment_id" class="form-control" required>
                                        <option value="">Select payment method first</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Due Date and Status -->
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control">
                                    <small class="form-text text-muted">Optional due date for payment</small>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="pending" selected>Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- File Attachment -->
                        <div class="mb-3">
                            <label for="attachment" class="form-label">Attachment</label>
                            <input type="file" id="attachment" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx">
                            <small class="form-text text-muted">Upload receipt or supporting document (max 10MB)</small>
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000" placeholder="Additional notes or comments"></textarea>
                        </div>
                        
                        <div class="mt-4">
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="submit" class="btn btn-primary flex-sm-fill">
                                    <i class="fas fa-save me-2"></i>Create Expense
                                </button>
                                <a href="/expenses" class="btn btn-secondary flex-sm-fill">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Create Modals -->
<div class="modal fade" id="quickCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickCreateModalLabel">Quick Create</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="quickCreateForm">
                    <input type="hidden" id="quickCreateType" name="type">
                    <div class="mb-3">
                        <label for="quickCreateName" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="quickCreateName" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="quickCreateDescription" class="form-label">Description</label>
                        <textarea id="quickCreateDescription" name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="quickCreateColor" class="form-label">Color</label>
                        <input type="color" id="quickCreateColor" name="color" class="form-control" value="#007bff">
                    </div>
                    <div class="mb-3" id="quickCreateIconGroup" style="display: none;">
                        <label for="quickCreateIcon" class="form-label">Icon</label>
                        <select id="quickCreateIcon" name="icon" class="form-control">
                            <option value="fas fa-tag">Tag</option>
                            <option value="fas fa-folder">Folder</option>
                            <option value="fas fa-briefcase">Briefcase</option>
                            <option value="fas fa-star">Star</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveQuickCreate()">Create</button>
            </div>
        </div>
    </div>
</div>

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

document.addEventListener('DOMContentLoaded', function() {
    // Payment method data
    const paymentMethods = {
        credit_card: <?php echo json_encode($creditCards); ?>,
        bank_account: <?php echo json_encode($bankAccounts); ?>,
        crypto_wallet: <?php echo json_encode($cryptoWallets); ?>
    };
    
    // Update payment sources when payment method changes
    document.getElementById('payment_method').addEventListener('change', function() {
        const method = this.value;
        const paymentIdSelect = document.getElementById('payment_id');
        const currencySelect = document.getElementById('currency');
        
        paymentIdSelect.innerHTML = '<option value="">Select ' + method.replace('_', ' ') + '</option>';
        currencySelect.innerHTML = '<option value="">Select payment source first</option>';
        
        if (method && paymentMethods[method]) {
            paymentMethods[method].forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.setAttribute('data-currency', item.currency || 'USD');
                
                if (method === 'credit_card') {
                    option.textContent = `${item.name || 'Card'} (**** ${item.card_number_last4 || item.last4})`;
                } else if (method === 'bank_account') {
                    option.textContent = `${item.name} (**** ${item.account_number_last4}) - ${item.currency}`;
                } else if (method === 'crypto_wallet') {
                    option.textContent = `${item.name} (${item.currency})`;
                }
                
                paymentIdSelect.appendChild(option);
            });
        }
    });
    
    // Update currency when payment source changes
    document.getElementById('payment_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const currencySelect = document.getElementById('currency');
        
        if (selectedOption && selectedOption.getAttribute('data-currency')) {
            const currency = selectedOption.getAttribute('data-currency');
            currencySelect.innerHTML = `<option value="${currency}" selected>${currency}</option>`;
        } else {
            currencySelect.innerHTML = '<option value="">Select payment source first</option>';
        }
    });
    
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
});

// Quick create functions
function openQuickCreate(type) {
    document.getElementById('quickCreateType').value = type;
    document.getElementById('quickCreateModalLabel').textContent = 'Quick Create ' + type.charAt(0).toUpperCase() + type.slice(1);
    
    if (type === 'category') {
        document.getElementById('quickCreateIconGroup').style.display = 'block';
    } else {
        document.getElementById('quickCreateIconGroup').style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('quickCreateModal')).show();
}

function saveQuickCreate() {
    const form = document.getElementById('quickCreateForm');
    const formData = new FormData(form);
    formData.append('_token', '<?php echo htmlspecialchars($csrf_token ?? ''); ?>');
    
    const type = formData.get('type');
    const url = type === 'category' ? '/categories/quick-create' : '/tags/quick-create';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
            .then(data => {
            if (data.success) {
                // Add new option to select
                const select = document.getElementById(type + '_id' + (type === 'tag' ? 's' : ''));
                const option = document.createElement('option');
                
                // The response contains either 'category' or 'tag' object
                const item = data.category || data.tag;
                option.value = item.id;
                option.textContent = item.name;
                option.selected = true;
                
                // Add color data attribute for styling
                if (item.color) {
                    option.setAttribute('data-color', item.color);
                }
                
                select.appendChild(option);
            
            // Close modal and reset form
            bootstrap.Modal.getInstance(document.getElementById('quickCreateModal')).hide();
            form.reset();
            
            // Show success message
                            showDarkAlert({
                    title: 'Success!',
                    text: type.charAt(0).toUpperCase() + type.slice(1) + ' created successfully!',
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                showDarkAlert({
                    title: 'Error!',
                    text: 'Error creating ' + type + ': ' + (data.message || 'Unknown error'),
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showDarkAlert({
                title: 'Error!',
                text: 'Error creating ' + type,
                icon: 'error'
            });
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 