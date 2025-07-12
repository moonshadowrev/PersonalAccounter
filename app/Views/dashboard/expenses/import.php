<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Import Expenses</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/expenses">Expenses</a></li>
                    <li class="breadcrumb-item active">Import</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Upload Excel File</h4>
                    <p class="card-title-desc">Import multiple expenses from an Excel spreadsheet.</p>
                    
                    <form action="/expenses/import" method="POST" enctype="multipart/form-data" id="import-form">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        
                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="excel_file" class="form-label">Excel/CSV File <span class="text-danger">*</span></label>
                            <input type="file" id="excel_file" name="excel_file" class="form-control" accept=".xls,.xlsx,.csv" required>
                            <div class="form-text">Supported formats: .xls, .xlsx, .csv (limited by PHP upload settings)</div>
                        </div>
                        
                        <!-- Field Mapping -->
                        <h6 class="mb-3">Field Mapping</h6>
                        <p class="text-muted mb-3">Map your Excel columns to expense fields. Required fields are marked with *</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_title" class="form-label">Title Column <span class="text-danger">*</span></label>
                                    <input type="text" id="map_title" name="map_title" class="form-control" placeholder="e.g., A or Title" value="A" required>
                                    <small class="form-text text-muted">Column containing expense titles</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_amount" class="form-label">Amount Column <span class="text-danger">*</span></label>
                                    <input type="text" id="map_amount" name="map_amount" class="form-control" placeholder="e.g., B or Amount" value="B" required>
                                    <small class="form-text text-muted">Column containing expense amounts</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_category" class="form-label">Category Column <span class="text-danger">*</span></label>
                                    <input type="text" id="map_category" name="map_category" class="form-control" placeholder="e.g., C or Category" value="C" required>
                                    <small class="form-text text-muted">Column containing category names</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_expense_date" class="form-label">Date Column</label>
                                    <input type="text" id="map_expense_date" name="map_expense_date" class="form-control" placeholder="e.g., D or Date" value="D">
                                    <small class="form-text text-muted">Column containing expense dates (optional)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_description" class="form-label">Description Column</label>
                                    <input type="text" id="map_description" name="map_description" class="form-control" placeholder="e.g., E or Description">
                                    <small class="form-text text-muted">Column containing descriptions (optional)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_currency" class="form-label">Currency Column</label>
                                    <input type="text" id="map_currency" name="map_currency" class="form-control" placeholder="e.g., F or Currency">
                                    <small class="form-text text-muted">Column containing currency codes (optional)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_tax_rate" class="form-label">Tax Rate Column</label>
                                    <input type="text" id="map_tax_rate" name="map_tax_rate" class="form-control" placeholder="e.g., G or Tax">
                                    <small class="form-text text-muted">Column containing tax rates (optional)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_notes" class="form-label">Notes Column</label>
                                    <input type="text" id="map_notes" name="map_notes" class="form-control" placeholder="e.g., H or Notes">
                                    <small class="form-text text-muted">Column containing notes (optional)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="map_tags" class="form-label">Tags Column</label>
                                    <input type="text" id="map_tags" name="map_tags" class="form-control" placeholder="e.g., I or Tags">
                                    <small class="form-text text-muted">Column containing comma-separated tags (optional)</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Default Values -->
                        <h6 class="mb-3 mt-4">Default Values</h6>
                        <p class="text-muted mb-3">These values will be used when not specified in the Excel file.</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="default_payment_method" class="form-label">Default Payment Method <span class="text-danger">*</span></label>
                                    <select id="default_payment_method" name="default_payment_method" class="form-control" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="bank_account">Bank Account</option>
                                        <option value="crypto_wallet">Crypto Wallet</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="default_payment_id" class="form-label">Default Payment Source <span class="text-danger">*</span></label>
                                    <select id="default_payment_id" name="default_payment_id" class="form-control" required>
                                        <option value="">Select payment method first</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="default_currency" class="form-label">Default Currency</label>
                                    <select id="default_currency" name="default_currency" class="form-control">
                                        <option value="USD" selected>USD - US Dollar</option>
                                        <option value="EUR">EUR - Euro</option>
                                        <option value="GBP">GBP - British Pound</option>
                                        <option value="CAD">CAD - Canadian Dollar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="default_status" class="form-label">Default Status</label>
                                    <select id="default_status" name="default_status" class="form-control">
                                        <option value="pending" selected>Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Import Options -->
                        <h6 class="mb-3 mt-4">Import Options</h6>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skip_header" name="skip_header" value="1" checked>
                                <label class="form-check-label" for="skip_header">
                                    Skip first row (header row)
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="create_categories" name="create_categories" value="1" checked>
                                <label class="form-check-label" for="create_categories">
                                    Automatically create missing categories
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skip_invalid" name="skip_invalid" value="1" checked>
                                <label class="form-check-label" for="skip_invalid">
                                    Skip invalid rows instead of stopping import
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates" value="1">
                                <label class="form-check-label" for="skip_duplicates">
                                    Skip duplicate expense titles (prevent importing expenses with same title)
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Import Expenses</button>
                            <a href="/expenses" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Example Template -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Excel Template Example</h6>
                    <p class="text-muted">Your Excel file should have columns like this:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr class="table-light">
                                    <th>A</th>
                                    <th>B</th>
                                    <th>C</th>
                                    <th>D</th>
                                    <th>I</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Title</td>
                                    <td>Amount</td>
                                    <td>Category</td>
                                    <td>Date</td>
                                    <td>Tags</td>
                                </tr>
                                <tr>
                                    <td>Office Supplies</td>
                                    <td>150.00</td>
                                    <td>Office</td>
                                    <td>2024-01-15</td>
                                    <td>business,office</td>
                                </tr>
                                <tr>
                                    <td>Business Lunch</td>
                                    <td>75.50</td>
                                    <td>Meals</td>
                                    <td>2024-01-16</td>
                                    <td>client,food</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <a href="/expenses/download-template" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i> Download Template
                    </a>
                    
                    <!-- Import Tips -->
                    <hr class="my-3">
                    <h6 class="card-title mb-2">Import Tips</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-1">
                            <i class="fas fa-check text-success me-1"></i>
                            Use column letters (A, B, C) or names
                        </li>
                        <li class="mb-1">
                            <i class="fas fa-check text-success me-1"></i>
                            Dates should be in YYYY-MM-DD format
                        </li>
                        <li class="mb-1">
                            <i class="fas fa-check text-success me-1"></i>
                            Amounts should be numeric values
                        </li>
                        <li class="mb-1">
                            <i class="fas fa-check text-success me-1"></i>
                            Categories and tags will be created if they don't exist
                        </li>
                        <li class="mb-1">
                            <i class="fas fa-check text-success me-1"></i>
                            Empty cells will use default values
                        </li>
                        <li class="mb-1">
                            <i class="fas fa-check text-success me-1"></i>
                            Enable duplicate prevention to skip duplicate titles
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-1"></i>
                            File size limited by PHP settings
                        </li>
                    </ul>
                </div>
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
    document.getElementById('default_payment_method').addEventListener('change', function() {
        const method = this.value;
        const paymentIdSelect = document.getElementById('default_payment_id');
        
        paymentIdSelect.innerHTML = '<option value="">Select ' + method.replace('_', ' ') + '</option>';
        
        if (method && paymentMethods[method]) {
            paymentMethods[method].forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                
                if (method === 'credit_card') {
                    option.textContent = `${item.name || 'Card'} (**** ${item.card_number_last4 || item.last4})`;
                } else if (method === 'bank_account') {
                    option.textContent = `${item.name} (**** ${item.account_number_last4})`;
                } else if (method === 'crypto_wallet') {
                    option.textContent = `${item.name} (${item.currency})`;
                }
                
                paymentIdSelect.appendChild(option);
            });
        }
    });
    
    // Form validation
    document.getElementById('import-form').addEventListener('submit', function(e) {
        const fileInput = document.getElementById('excel_file');
        const paymentMethod = document.getElementById('default_payment_method').value;
        const paymentId = document.getElementById('default_payment_id').value;
        
        if (!fileInput.files.length) {
            e.preventDefault();
            showDarkAlert({
                title: 'File Required',
                text: 'Please select an Excel file to import.',
                icon: 'warning'
            });
            return;
        }
        
        if (!paymentMethod || !paymentId) {
            e.preventDefault();
            showDarkAlert({
                title: 'Payment Method Required',
                text: 'Please select a default payment method and source.',
                icon: 'warning'
            });
            return;
        }
        
        if (fileInput.files[0].size > 10 * 1024 * 1024) {
            e.preventDefault();
            showDarkAlert({
                title: 'File Too Large',
                text: 'File size must be less than 10MB.',
                icon: 'warning'
            });
            return;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 