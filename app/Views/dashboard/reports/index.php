<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Unified Transaction Reports</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </div>
        </div>
    </div>
    <br>
    <!-- Date Filter -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Filter Transactions by Date Range</h5>
                    <?php if (!empty($filter_dates['from']) && !empty($filter_dates['to'])): ?>
                        <div class="alert alert-info">
                            <i class="mdi mdi-information"></i> 
                            Showing transactions from <strong><?php echo htmlspecialchars($filter_dates['from']); ?></strong> to <strong><?php echo htmlspecialchars($filter_dates['to']); ?></strong>
                            (<?php echo count($transactions); ?> transactions found)
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary">
                            <i class="mdi mdi-information-outline"></i> 
                            Showing all transactions (<?php echo count($transactions); ?> total)
                        </div>
                    <?php endif; ?>
                    <form method="GET" action="/reports" class="row align-items-end g-3 reports-filter-form" id="dateFilterForm">
                        <!-- Date Inputs -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                            <label for="from_date" class="form-label">
                                <i class="mdi mdi-calendar mr-2"></i>From Date
                            </label>
                            <input type="date" 
                                   id="from_date" 
                                   name="from" 
                                   value="<?php echo htmlspecialchars($filter_dates['from'] ?? ''); ?>" 
                                   class="form-control"
                                   placeholder="Select start date">
                        </div>
                        
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                            <label for="to_date" class="form-label">
                                <i class="mdi mdi-calendar mr-2"></i>To Date
                            </label>
                            <input type="date" 
                                   id="to_date" 
                                   name="to" 
                                   value="<?php echo htmlspecialchars($filter_dates['to'] ?? ''); ?>" 
                                   class="form-control"
                                   placeholder="Select end date">
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="col-xl-6 col-lg-4 col-md-12 col-12">
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="mdi mdi-filter mr-2"></i>Apply Filter
                                </button>
                                <a href="/reports" class="btn btn-secondary flex-fill">
                                    <i class="mdi mdi-refresh mr-2"></i>Show All Data
                                </a>
                                <button type="button" class="btn btn-info flex-fill" onclick="setQuickFilter('30')">
                                    <i class="mdi mdi-clock mr-2"></i>Last 30 Days
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Quick Filter Buttons Row -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex flex-wrap justify-content-between gap-2 quick-filter-buttons">
                                <button type="button" class="btn btn-outline-primary quick-filter-btn" onclick="setQuickFilter('7')">
                                    <i class="mdi mdi-calendar-week mr-2"></i>Last 7 Days
                                </button>
                                <button type="button" class="btn btn-outline-primary quick-filter-btn" onclick="setQuickFilter('90')">
                                    <i class="mdi mdi-calendar-range mr-2"></i>Last 3 Months
                                </button>
                                <button type="button" class="btn btn-outline-primary quick-filter-btn" onclick="setQuickFilter('365')">
                                    <i class="mdi mdi-calendar mr-2"></i>Last Year
                                </button>
                                <button type="button" class="btn btn-outline-warning quick-filter-btn" onclick="setCurrentMonth()">
                                    <i class="mdi mdi-calendar-today mr-2"></i>This Month
                                </button>
                                <button type="button" class="btn btn-outline-success quick-filter-btn" onclick="setCurrentYear()">
                                    <i class="mdi mdi-calendar-check mr-2"></i>This Year
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <!-- Transaction Statistics -->
    <div class="row reports-stats">
        <div class="col-md-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title">
                                    <i class="mdi mdi-cash-multiple font-size-16"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Total Revenue</h6>
                            <b>$<?php echo number_format($transaction_stats['total_revenue'], 2); ?></b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success">
                                <span class="avatar-title">
                                    <i class="mdi mdi-check-circle font-size-16"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Successful</h6>
                            <b><?php echo number_format($transaction_stats['successful_transactions']); ?></b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-danger">
                                <span class="avatar-title">
                                    <i class="mdi mdi-close-circle font-size-16"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Failed</h6>
                            <b><?php echo number_format($transaction_stats['failed_transactions']); ?></b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-warning">
                                <span class="avatar-title">
                                    <i class="mdi mdi-clock font-size-16"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Pending</h6>
                            <b><?php echo number_format($transaction_stats['pending_transactions']); ?></b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-info">
                                <span class="avatar-title">
                                    <i class="mdi mdi-repeat font-size-16"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Subscriptions</h6>
                            <b><?php echo number_format($transaction_stats['subscription_transactions']); ?></b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-purple">
                                <span class="avatar-title">
                                    <i class="mdi mdi-receipt font-size-16"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Expenses</h6>
                            <b><?php echo number_format($transaction_stats['expense_transactions']); ?></b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <!-- Transactions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="card-title">Unified Transaction History</h4>
                        <a href="/reports/export<?php echo (!empty($filter_dates['from']) && !empty($filter_dates['to'])) ? '?from=' . urlencode($filter_dates['from']) . '&to=' . urlencode($filter_dates['to']) : ''; ?>" class="btn btn-success">Export to Excel</a>
                    </div>

                    <table id="transactions-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>User</th>
                                <th>Item/Service</th>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Billing Cycle</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['id']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['transaction_type'] === 'subscription' ? 'info' : 'purple'; ?>">
                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['user_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['item_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['vendor'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['category_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['amount'] . ' ' . $transaction['currency']); ?></td>
                                <td>
                                    <span class="text-muted small"><?php echo ucfirst($transaction['payment_method_type'] ?? 'Unknown'); ?></span><br>
                                    <strong><?php echo htmlspecialchars($transaction['payment_method_name'] ?? 'Unknown'); ?></strong>
                                </td>
                                <td><span class="badge bg-<?php echo $transaction['status'] === 'successful' ? 'success' : ($transaction['status'] === 'failed' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars(ucfirst($transaction['status'])); ?></span></td>
                                <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($transaction['billing_cycle'] ?? '-')); ?></td>
                                <td>
                                    <span class="text-muted small"><?php echo htmlspecialchars($transaction['reference_number'] ?? '-'); ?></span>
                                    <?php if (!empty($transaction['description'])): ?>
                                    <br><small class="text-info"><?php echo htmlspecialchars(substr($transaction['description'], 0, 50)); ?><?php echo strlen($transaction['description']) > 50 ? '...' : ''; ?></small>
                                    <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('dateFilterForm');
    const fromDate = document.getElementById('from_date');
    const toDate = document.getElementById('to_date');
    
    // Form validation with SweetAlert2 (same as dashboard)
    form.addEventListener('submit', function(e) {
        const fromValue = fromDate.value;
        const toValue = toDate.value;
        
        // Allow submission if both dates are empty (show all data)
        if (!fromValue && !toValue) {
            return true;
        }
        
        // If one date is filled, both must be filled (same as dashboard)
        if (!fromValue || !toValue) {
            e.preventDefault();
            showDarkAlert({
                icon: 'warning',
                title: 'Date Selection Required',
                text: 'Please select both from and to dates, or leave both empty to show all data.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Validate date range
        if (new Date(fromValue) > new Date(toValue)) {
            e.preventDefault();
            showDarkAlert({
                icon: 'error',
                title: 'Invalid Date Range',
                text: 'From date cannot be later than to date.',
                confirmButtonText: 'OK'
            });
            return false;
        }
    });
    
    // Set max date for from_date when to_date changes
    toDate.addEventListener('change', function() {
        fromDate.max = this.value;
    });
    
    // Set min date for to_date when from_date changes
    fromDate.addEventListener('change', function() {
        toDate.min = this.value;
    });
});

// Quick filter functions for common date ranges
function setQuickFilter(days) {
    const toDate = new Date();
    const fromDate = new Date();
    fromDate.setDate(toDate.getDate() - parseInt(days));
    
    document.getElementById('from_date').value = fromDate.toISOString().split('T')[0];
    document.getElementById('to_date').value = toDate.toISOString().split('T')[0];
    
    // Add loading state to all quick filter buttons
    const quickFilterButtons = document.querySelectorAll('.quick-filter-btn');
    quickFilterButtons.forEach(btn => btn.classList.add('loading'));
    
    // Show loading message and auto-submit the form
    showDarkAlert({
        title: 'Applying Filter...',
        text: `Loading transactions for the last ${days} days`,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Auto-submit the form
    setTimeout(() => {
        document.getElementById('dateFilterForm').submit();
    }, 500);
}

// Set current month filter
function setCurrentMonth() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    document.getElementById('from_date').value = firstDay.toISOString().split('T')[0];
    document.getElementById('to_date').value = lastDay.toISOString().split('T')[0];
    
    // Add loading state to all quick filter buttons
    const quickFilterButtons = document.querySelectorAll('.quick-filter-btn');
    quickFilterButtons.forEach(btn => btn.classList.add('loading'));
    
    // Show loading message and auto-submit the form
    showDarkAlert({
        title: 'Applying Filter...',
        text: 'Loading transactions for this month',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Auto-submit the form
    setTimeout(() => {
        document.getElementById('dateFilterForm').submit();
    }, 500);
}

// Set current year filter
function setCurrentYear() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), 0, 1);
    const lastDay = new Date(now.getFullYear(), 11, 31);
    
    document.getElementById('from_date').value = firstDay.toISOString().split('T')[0];
    document.getElementById('to_date').value = lastDay.toISOString().split('T')[0];
    
    // Add loading state to all quick filter buttons
    const quickFilterButtons = document.querySelectorAll('.quick-filter-btn');
    quickFilterButtons.forEach(btn => btn.classList.add('loading'));
    
    // Show loading message and auto-submit the form
    showDarkAlert({
        title: 'Applying Filter...',
        text: 'Loading transactions for this year',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Auto-submit the form
    setTimeout(() => {
        document.getElementById('dateFilterForm').submit();
    }, 500);
}

// Add visual feedback for quick filter buttons
document.addEventListener('DOMContentLoaded', function() {
    const quickFilterButtons = document.querySelectorAll('.quick-filter-btn');
    quickFilterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Add active state to clicked button
            quickFilterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>

<style>
.quick-filter-btn {
    transition: all 0.3s ease;
    border-radius: 20px;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.quick-filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.quick-filter-btn.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

.gap-2 {
    gap: 0.5rem !important;
}

.bg-purple {
    background-color: #6f42c1 !important;
}

@media (max-width: 768px) {
    .quick-filter-btn {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
        margin-bottom: 0.5rem;
    }
    
    .d-flex.flex-wrap {
        justify-content: center !important;
    }
}

/* Loading animation for filter buttons */
.quick-filter-btn.loading {
    pointer-events: none;
    opacity: 0.6;
}

.quick-filter-btn.loading::after {
    content: "";
    display: inline-block;
    width: 12px;
    height: 12px;
    margin-left: 8px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 