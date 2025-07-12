<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- Enhanced Stat Cards -->
<div class="row mb-4" id="stats-cards">
    <!-- Subscription Stats -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-primary bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Subscriptions</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="total-subscriptions"><?php echo $stats['total_subscriptions']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-list fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-success bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Subscriptions</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="active-subscriptions"><?php echo $stats['active_subscriptions']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-info bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monthly Recurring</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="monthly-cost">$<?php echo number_format($stats['total_monthly_cost'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-warning bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Annual Recurring</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="annual-cost">$<?php echo number_format($stats['total_annual_cost'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-alt fa-2x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expense Stats Row -->
<div class="row mb-4" id="expense-stats-cards">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-expense-primary bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-expense-primary text-uppercase mb-1">Total Expenses</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="total-expenses"><?php echo $expense_stats['total_expenses']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-receipt fa-2x text-expense-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-expense-success bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-expense-success text-uppercase mb-1">Paid Expenses</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="paid-expenses"><?php echo $expense_stats['paid_expenses']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-double fa-2x text-expense-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-expense-warning bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-expense-warning text-uppercase mb-1">Total Amount</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="total-expense-amount">$<?php echo number_format($expense_stats['total_amount'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-expense-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-expense-info bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-expense-info text-uppercase mb-1">Avg Per Expense</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="avg-expense-amount">$<?php echo number_format($expense_stats['avg_expense_amount'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-bar fa-2x text-expense-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats Row -->
<div class="row mb-5" id="additional-stats">
    <!-- Subscription Additional Stats -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-secondary bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Expired</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="expired-subscriptions"><?php echo $stats['expired_subscriptions']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-secondary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-danger bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Cancelled</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="cancelled-subscriptions"><?php echo $stats['cancelled_subscriptions']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-light bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-light text-uppercase mb-1">Avg Per Service</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="avg-spend">$<?php echo number_format($stats['avg_monthly_spend'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-light opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-purple bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-purple text-uppercase mb-1">One-time Costs</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="onetime-cost">$<?php echo number_format($stats['total_onetime_cost'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hand-holding-usd fa-2x text-purple opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expense Additional Stats Row -->
<div class="row mb-5" id="expense-additional-stats">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-expense-pending bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-expense-pending text-uppercase mb-1">Pending Approval</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="pending-expenses"><?php echo $expense_stats['pending_expenses']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hourglass-half fa-2x text-expense-pending opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-expense-approved bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-expense-approved text-uppercase mb-1">Approved</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="approved-expenses"><?php echo $expense_stats['approved_expenses']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check fa-2x text-expense-approved opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-expense-danger bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-expense-danger text-uppercase mb-1">Rejected</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="rejected-expenses"><?php echo $expense_stats['rejected_expenses']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times fa-2x text-expense-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
        <div class="card h-100 border-left-expense-tax bg-dark">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-expense-tax text-uppercase mb-1">Total Tax</div>
                        <div class="h5 mb-0 font-weight-bold text-light" id="total-tax">$<?php echo number_format($expense_stats['total_tax'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percentage fa-2x text-expense-tax opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Date Filter with Better Layout -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-dark border-secondary">
            <div class="card-header bg-dark border-secondary">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">
                        <i class="fas fa-filter mr-2"></i>Analytics Filter
                    </h6>
                    <div class="d-flex align-items-center">
                        <span class="text-light small mr-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span id="date-range-info">
                                <?php 
                                if (!empty($filter_dates['from']) && !empty($filter_dates['to'])) {
                                    $days = (strtotime($filter_dates['to']) - strtotime($filter_dates['from'])) / (60*60*24);
                                    echo ceil($days) . ' days selected';
                                } else {
                                    echo 'Showing all data';
                                }
                                ?>
                            </span>
                        </span>
                        <span class="text-light mr-2" id="loading-indicator" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="/" class="row align-items-end g-3">
                    <!-- Date Inputs -->
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                        <label for="from_date" class="form-label text-light small mb-2">
                            <i class="fas fa-calendar-alt mr-2"></i>From Date
                        </label>
                        <input type="date" 
                               id="from_date" 
                               name="from" 
                               value="<?php echo htmlspecialchars($filter_dates['from'] ?? ''); ?>" 
                               class="form-control bg-dark text-light border-secondary"
                               placeholder="Select start date">
                    </div>
                    
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                        <label for="to_date" class="form-label text-light small mb-2">
                            <i class="fas fa-calendar-alt mr-2"></i>To Date
                        </label>
                        <input type="date" 
                               id="to_date" 
                               name="to" 
                               value="<?php echo htmlspecialchars($filter_dates['to'] ?? ''); ?>" 
                               class="form-control bg-dark text-light border-secondary"
                               placeholder="Select end date">
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="col-xl-6 col-lg-4 col-md-12 col-12">
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search mr-2"></i>Apply Filter
                            </button>
                            <a href="/" class="btn btn-secondary flex-fill">
                                <i class="fas fa-undo mr-2"></i>Show All Data
                            </a>
                            <button type="button" class="btn btn-info flex-fill" onclick="setQuickFilter('30')">
                                <i class="fas fa-clock mr-2"></i>Last 30 Days
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Quick Filter Buttons Row -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <button type="button" class="btn btn-outline-primary quick-filter-btn" onclick="setQuickFilter('7')">
                                <i class="fas fa-calendar-week mr-2"></i>Last 7 Days
                            </button>
                            <button type="button" class="btn btn-outline-primary quick-filter-btn" onclick="setQuickFilter('90')">
                                <i class="fas fa-calendar-day mr-2"></i>Last 3 Months
                            </button>
                            <button type="button" class="btn btn-outline-primary quick-filter-btn" onclick="setQuickFilter('365')">
                                <i class="fas fa-calendar mr-2"></i>Last Year
                            </button>
                            <button type="button" class="btn btn-outline-warning quick-filter-btn" onclick="setCurrentMonth()">
                                <i class="fas fa-calendar-alt mr-2"></i>This Month
                            </button>
                            <button type="button" class="btn btn-outline-success quick-filter-btn" onclick="setCurrentYear()">
                                <i class="fas fa-calendar-check mr-2"></i>This Year
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Charts -->
<div class="row mb-4" id="charts-container">
    <!-- Monthly Spending Trend -->
    <div class="col-xl-8 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line mr-2"></i>Monthly Recurring Spending Trend
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Status Distribution -->
    <div class="col-xl-4 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-pie mr-2"></i>Status Distribution
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Top Spending Services -->
    <div class="col-xl-8 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-bar mr-2"></i>Top Recurring Services (Monthly Equivalent)
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="topServicesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing Cycle Distribution -->
    <div class="col-xl-4 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-doughnut mr-2"></i>Billing Cycles
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="billingCycleChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Subscription Growth -->
    <div class="col-xl-8 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-area mr-2"></i>Subscription Growth
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Currency Breakdown -->
    <div class="col-xl-4 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-coins mr-2"></i>Currency Breakdown (Recurring)
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="currencyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expense Analytics Section -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="text-light mb-3">
            <i class="fas fa-receipt mr-2 text-expense-primary"></i>Expense Analytics
        </h4>
    </div>
</div>

<!-- Expense Charts -->
<div class="row mb-4" id="expense-charts-container">
    <!-- Monthly Expense Trend -->
    <div class="col-xl-8 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-expense-primary">
                    <i class="fas fa-chart-line mr-2"></i>Monthly Expense Trend
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="monthlyExpenseTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Status Distribution -->
    <div class="col-xl-4 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-expense-primary">
                    <i class="fas fa-chart-pie mr-2"></i>Expense Status Distribution
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="expenseStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Top Expense Categories -->
    <div class="col-xl-8 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-expense-primary">
                    <i class="fas fa-chart-bar mr-2"></i>Top Expense Categories
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="topExpenseCategoriesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Distribution -->
    <div class="col-xl-4 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-expense-primary">
                    <i class="fas fa-credit-card mr-2"></i>Payment Methods
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="expensePaymentMethodChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Expense Growth -->
    <div class="col-xl-8 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-expense-primary">
                    <i class="fas fa-chart-area mr-2"></i>Expense Growth
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="expenseGrowthChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Currency Breakdown -->
    <div class="col-xl-4 col-lg-12 col-12 mb-4">
        <div class="card shadow bg-dark border-secondary h-100">
            <div class="card-header py-3 bg-dark border-secondary">
                <h6 class="m-0 font-weight-bold text-expense-primary">
                    <i class="fas fa-coins mr-2"></i>Expense Currency Breakdown
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="expenseCurrencyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chart-container {
    position: relative;
    height: 350px;
    width: 100%;
}

/* Dark theme card styling */
.card.bg-dark {
    background-color: #2d3748 !important;
    border: 1px solid #4a5568 !important;
}

.card-header.bg-dark {
    background-color: #1a202c !important;
    border-bottom: 1px solid #4a5568 !important;
}

/* Border colors for stat cards */
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.border-left-secondary {
    border-left: 0.25rem solid #858796 !important;
}

.border-left-light {
    border-left: 0.25rem solid #f8f9fa !important;
}

.border-left-purple {
    border-left: 0.25rem solid #6f42c1 !important;
}

/* Purple text color for onetime costs */
.text-purple {
    color: #6f42c1 !important;
}

/* Expense-specific colors */
.text-expense-primary {
    color: #17a2b8 !important;
}

.text-expense-success {
    color: #28a745 !important;
}

.text-expense-warning {
    color: #ffc107 !important;
}

.text-expense-info {
    color: #007bff !important;
}

.text-expense-pending {
    color: #fd7e14 !important;
}

.text-expense-approved {
    color: #20c997 !important;
}

.text-expense-danger {
    color: #dc3545 !important;
}

.text-expense-tax {
    color: #6610f2 !important;
}

/* Border colors for expense stat cards */
.border-left-expense-primary {
    border-left: 0.25rem solid #17a2b8 !important;
}

.border-left-expense-success {
    border-left: 0.25rem solid #28a745 !important;
}

.border-left-expense-warning {
    border-left: 0.25rem solid #ffc107 !important;
}

.border-left-expense-info {
    border-left: 0.25rem solid #007bff !important;
}

.border-left-expense-pending {
    border-left: 0.25rem solid #fd7e14 !important;
}

.border-left-expense-approved {
    border-left: 0.25rem solid #20c997 !important;
}

.border-left-expense-danger {
    border-left: 0.25rem solid #dc3545 !important;
}

.border-left-expense-tax {
    border-left: 0.25rem solid #6610f2 !important;
}

/* Dark theme form controls */
.form-control.bg-dark {
    background-color: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

.form-control.bg-dark:focus {
    background-color: #2d3748 !important;
    border-color: #4e73df !important;
    color: #e2e8f0 !important;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25) !important;
}

/* Text colors for dark theme */
.text-light {
    color: #e2e8f0 !important;
}

/* Icon opacity for subtle effect */
.opacity-50 {
    opacity: 0.5 !important;
}

/* Enhanced shadows for dark theme */
.card.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.3) !important;
}

/* Loading animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.loading {
    animation: pulse 1.5s infinite;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .chart-container {
        height: 300px;
    }
}

@media (max-width: 768px) {
    .chart-container {
        height: 250px;
    }
    
    .row.mb-4, .row.mb-5 {
        margin-bottom: 2rem !important;
    }
    
    .col-xl-8, .col-xl-4 {
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 576px) {
    .chart-container {
        height: 200px;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .h5 {
        font-size: 1.1rem;
    }
    
    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Analytics data from PHP
let analyticsData = <?php echo json_encode($analytics); ?>;
let expenseAnalyticsData = <?php echo json_encode($expense_analytics); ?>;
let charts = {};

// Chart.js default configuration for dark theme
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#a0aec0';
Chart.defaults.backgroundColor = 'rgba(255, 255, 255, 0.1)';
Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';

// Initialize all charts
function initializeCharts() {
    // Destroy existing charts if they exist
    Object.values(charts).forEach(chart => {
        if (chart) chart.destroy();
    });
    charts = {};
    
    // Initialize subscription charts
    initializeSubscriptionCharts();
    
    // Initialize expense charts
    initializeExpenseCharts();
}

// Initialize subscription charts
function initializeSubscriptionCharts() {
    // Monthly Spending Trend Chart
    const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    charts.monthlyTrend = new Chart(monthlyTrendCtx, {
        type: 'line',
        data: {
            labels: analyticsData.monthly_spending_trend.labels,
            datasets: [{
                label: 'Monthly Recurring ($)',
                data: analyticsData.monthly_spending_trend.data,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4e73df',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Monthly Recurring: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0',
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    charts.status = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Expired', 'Cancelled'],
            datasets: [{
                data: [
                    analyticsData.status_distribution.active || 0,
                    analyticsData.status_distribution.expired || 0,
                    analyticsData.status_distribution.cancelled || 0
                ],
                backgroundColor: ['#1cc88a', '#858796', '#e74a3b'],
                borderWidth: 3,
                borderColor: '#2d3748'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#a0aec0',
                        padding: 20
                    }
                }
            }
        }
    });

    // Top Services Chart
    const topServicesCtx = document.getElementById('topServicesChart').getContext('2d');
    charts.topServices = new Chart(topServicesCtx, {
        type: 'bar',
        data: {
            labels: analyticsData.top_services.labels,
            datasets: [{
                label: 'Monthly Equivalent ($)',
                data: analyticsData.top_services.data,
                backgroundColor: '#36b9cc',
                borderColor: '#36b9cc',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Monthly Equivalent: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0',
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // Billing Cycle Chart
    const billingCycleCtx = document.getElementById('billingCycleChart').getContext('2d');
    charts.billingCycle = new Chart(billingCycleCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(analyticsData.billing_cycle_distribution).map(key => 
                key.charAt(0).toUpperCase() + key.slice(1)
            ),
            datasets: [{
                data: Object.values(analyticsData.billing_cycle_distribution),
                backgroundColor: ['#4e73df', '#1cc88a', '#6f42c1', '#f6c23e', '#e74a3b'],
                borderWidth: 3,
                borderColor: '#2d3748'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#a0aec0',
                        padding: 20
                    }
                }
            }
        }
    });

    // Subscription Growth Chart
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    charts.growth = new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: analyticsData.subscription_growth.labels,
            datasets: [{
                label: 'Total Subscriptions',
                data: analyticsData.subscription_growth.data,
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#1cc88a',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0',
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Currency Breakdown Chart
    const currencyCtx = document.getElementById('currencyChart').getContext('2d');
    charts.currency = new Chart(currencyCtx, {
        type: 'doughnut',
        data: {
            labels: analyticsData.currency_breakdown.labels,
            datasets: [{
                data: analyticsData.currency_breakdown.data,
                backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#858796'],
                borderWidth: 3,
                borderColor: '#2d3748'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#a0aec0',
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': $' + context.parsed.toFixed(2) + ' (monthly equiv.)';
                        }
                    }
                }
            }
        }
    });
}

// Initialize expense charts
function initializeExpenseCharts() {
    // Monthly Expense Trend Chart
    const monthlyExpenseTrendCtx = document.getElementById('monthlyExpenseTrendChart').getContext('2d');
    charts.monthlyExpenseTrend = new Chart(monthlyExpenseTrendCtx, {
        type: 'line',
        data: {
            labels: expenseAnalyticsData.monthly_expense_trend.labels,
            datasets: [{
                label: 'Monthly Expenses ($)',
                data: expenseAnalyticsData.monthly_expense_trend.data,
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#17a2b8',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Monthly Expenses: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0',
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // Expense Status Distribution Chart
    const expenseStatusCtx = document.getElementById('expenseStatusChart').getContext('2d');
    charts.expenseStatus = new Chart(expenseStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Rejected', 'Paid'],
            datasets: [{
                data: [
                    expenseAnalyticsData.status_distribution.pending || 0,
                    expenseAnalyticsData.status_distribution.approved || 0,
                    expenseAnalyticsData.status_distribution.rejected || 0,
                    expenseAnalyticsData.status_distribution.paid || 0
                ],
                backgroundColor: ['#fd7e14', '#20c997', '#dc3545', '#28a745'],
                borderWidth: 3,
                borderColor: '#2d3748'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#a0aec0',
                        padding: 20
                    }
                }
            }
        }
    });

    // Top Expense Categories Chart
    const topExpenseCategoriesCtx = document.getElementById('topExpenseCategoriesChart').getContext('2d');
    charts.topExpenseCategories = new Chart(topExpenseCategoriesCtx, {
        type: 'bar',
        data: {
            labels: expenseAnalyticsData.top_categories.labels,
            datasets: [{
                label: 'Total Amount ($)',
                data: expenseAnalyticsData.top_categories.data,
                backgroundColor: '#17a2b8',
                borderColor: '#17a2b8',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Total Amount: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0',
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // Expense Payment Method Chart
    const expensePaymentMethodCtx = document.getElementById('expensePaymentMethodChart').getContext('2d');
    charts.expensePaymentMethod = new Chart(expensePaymentMethodCtx, {
        type: 'pie',
        data: {
            labels: expenseAnalyticsData.payment_method_distribution.labels,
            datasets: [{
                data: expenseAnalyticsData.payment_method_distribution.data,
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6610f2'],
                borderWidth: 3,
                borderColor: '#2d3748'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#a0aec0',
                        padding: 20
                    }
                }
            }
        }
    });

    // Expense Growth Chart
    const expenseGrowthCtx = document.getElementById('expenseGrowthChart').getContext('2d');
    charts.expenseGrowth = new Chart(expenseGrowthCtx, {
        type: 'line',
        data: {
            labels: expenseAnalyticsData.expense_growth.labels,
            datasets: [{
                label: 'Total Expenses',
                data: expenseAnalyticsData.expense_growth.data,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#28a745',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#a0aec0',
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Expense Currency Breakdown Chart
    const expenseCurrencyCtx = document.getElementById('expenseCurrencyChart').getContext('2d');
    charts.expenseCurrency = new Chart(expenseCurrencyCtx, {
        type: 'doughnut',
        data: {
            labels: expenseAnalyticsData.currency_breakdown.labels,
            datasets: [{
                data: expenseAnalyticsData.currency_breakdown.data,
                backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#dc3545', '#6610f2'],
                borderWidth: 3,
                borderColor: '#2d3748'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#a0aec0',
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': $' + context.parsed.toFixed(2);
                        }
                    }
                }
            }
        }
    });
}

// Update charts with new data
function updateCharts(newAnalytics, newExpenseAnalytics) {
    analyticsData = newAnalytics;
    expenseAnalyticsData = newExpenseAnalytics;
    
    // Update subscription charts
    updateSubscriptionCharts();
    
    // Update expense charts
    updateExpenseCharts();
}

// Update subscription charts
function updateSubscriptionCharts() {
    // Update each subscription chart
    charts.monthlyTrend.data.labels = analyticsData.monthly_spending_trend.labels;
    charts.monthlyTrend.data.datasets[0].data = analyticsData.monthly_spending_trend.data;
    charts.monthlyTrend.update();
    
    charts.status.data.datasets[0].data = [
        analyticsData.status_distribution.active || 0,
        analyticsData.status_distribution.expired || 0,
        analyticsData.status_distribution.cancelled || 0
    ];
    charts.status.update();
    
    charts.topServices.data.labels = analyticsData.top_services.labels;
    charts.topServices.data.datasets[0].data = analyticsData.top_services.data;
    charts.topServices.update();
    
    charts.billingCycle.data.labels = Object.keys(analyticsData.billing_cycle_distribution).map(key => 
        key.charAt(0).toUpperCase() + key.slice(1)
    );
    charts.billingCycle.data.datasets[0].data = Object.values(analyticsData.billing_cycle_distribution);
    charts.billingCycle.update();
    
    charts.growth.data.labels = analyticsData.subscription_growth.labels;
    charts.growth.data.datasets[0].data = analyticsData.subscription_growth.data;
    charts.growth.update();
    
    charts.currency.data.labels = analyticsData.currency_breakdown.labels;
    charts.currency.data.datasets[0].data = analyticsData.currency_breakdown.data;
    charts.currency.update();
}

// Update expense charts
function updateExpenseCharts() {
    // Update each expense chart
    charts.monthlyExpenseTrend.data.labels = expenseAnalyticsData.monthly_expense_trend.labels;
    charts.monthlyExpenseTrend.data.datasets[0].data = expenseAnalyticsData.monthly_expense_trend.data;
    charts.monthlyExpenseTrend.update();
    
    charts.expenseStatus.data.datasets[0].data = [
        expenseAnalyticsData.status_distribution.pending || 0,
        expenseAnalyticsData.status_distribution.approved || 0,
        expenseAnalyticsData.status_distribution.rejected || 0,
        expenseAnalyticsData.status_distribution.paid || 0
    ];
    charts.expenseStatus.update();
    
    charts.topExpenseCategories.data.labels = expenseAnalyticsData.top_categories.labels;
    charts.topExpenseCategories.data.datasets[0].data = expenseAnalyticsData.top_categories.data;
    charts.topExpenseCategories.update();
    
    charts.expensePaymentMethod.data.labels = expenseAnalyticsData.payment_method_distribution.labels;
    charts.expensePaymentMethod.data.datasets[0].data = expenseAnalyticsData.payment_method_distribution.data;
    charts.expensePaymentMethod.update();
    
    charts.expenseGrowth.data.labels = expenseAnalyticsData.expense_growth.labels;
    charts.expenseGrowth.data.datasets[0].data = expenseAnalyticsData.expense_growth.data;
    charts.expenseGrowth.update();
    
    charts.expenseCurrency.data.labels = expenseAnalyticsData.currency_breakdown.labels;
    charts.expenseCurrency.data.datasets[0].data = expenseAnalyticsData.currency_breakdown.data;
    charts.expenseCurrency.update();
}

// Update stats cards
function updateStatsCards(stats, expenseStats) {
    // Update subscription stats
    document.getElementById('total-subscriptions').textContent = stats.total_subscriptions;
    document.getElementById('active-subscriptions').textContent = stats.active_subscriptions;
    document.getElementById('expired-subscriptions').textContent = stats.expired_subscriptions;
    document.getElementById('cancelled-subscriptions').textContent = stats.cancelled_subscriptions;
    document.getElementById('monthly-cost').textContent = '$' + Number(stats.total_monthly_cost).toFixed(2);
    document.getElementById('annual-cost').textContent = '$' + Number(stats.total_annual_cost).toFixed(2);
    document.getElementById('avg-spend').textContent = '$' + Number(stats.avg_monthly_spend).toFixed(2);
    document.getElementById('onetime-cost').textContent = '$' + Number(stats.total_onetime_cost).toFixed(2);
    
    // Update expense stats
    document.getElementById('total-expenses').textContent = expenseStats.total_expenses;
    document.getElementById('paid-expenses').textContent = expenseStats.paid_expenses;
    document.getElementById('total-expense-amount').textContent = '$' + Number(expenseStats.total_amount).toFixed(2);
    document.getElementById('avg-expense-amount').textContent = '$' + Number(expenseStats.avg_expense_amount).toFixed(2);
    document.getElementById('pending-expenses').textContent = expenseStats.pending_expenses;
    document.getElementById('approved-expenses').textContent = expenseStats.approved_expenses;
    document.getElementById('rejected-expenses').textContent = expenseStats.rejected_expenses;
    document.getElementById('total-tax').textContent = '$' + Number(expenseStats.total_tax).toFixed(2);
}

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

// Quick filter functions for common date ranges
function setQuickFilter(days) {
    // Show loading state
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Applying Filter...';
    button.disabled = true;
    
    const toDate = new Date();
    const fromDate = new Date();
    fromDate.setDate(toDate.getDate() - parseInt(days));
    
    document.getElementById('from_date').value = fromDate.toISOString().split('T')[0];
    document.getElementById('to_date').value = toDate.toISOString().split('T')[0];
    
    // Show success message
    showDarkAlert({
        title: 'Filter Applied!',
        text: `Showing data for the last ${days} days`,
        icon: 'success',
        timer: 2000,
        timerProgressBar: true
    });
    
    // Auto-submit the form after a short delay
    setTimeout(() => {
        document.querySelector('form[method="GET"]').submit();
    }, 1500);
}

// Set current month filter
function setCurrentMonth() {
    // Show loading state
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Applying Filter...';
    button.disabled = true;
    
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    document.getElementById('from_date').value = firstDay.toISOString().split('T')[0];
    document.getElementById('to_date').value = lastDay.toISOString().split('T')[0];
    
    // Show success message
    const monthName = now.toLocaleString('default', { month: 'long' });
    showDarkAlert({
        title: 'Filter Applied!',
        text: `Showing data for ${monthName} ${now.getFullYear()}`,
        icon: 'success',
        timer: 2000,
        timerProgressBar: true
    });
    
    // Auto-submit the form after a short delay
    setTimeout(() => {
        document.querySelector('form[method="GET"]').submit();
    }, 1500);
}

// Set current year filter
function setCurrentYear() {
    // Show loading state
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Applying Filter...';
    button.disabled = true;
    
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), 0, 1);
    const lastDay = new Date(now.getFullYear(), 11, 31);
    
    document.getElementById('from_date').value = firstDay.toISOString().split('T')[0];
    document.getElementById('to_date').value = lastDay.toISOString().split('T')[0];
    
    // Show success message
    showDarkAlert({
        title: 'Filter Applied!',
        text: `Showing data for ${now.getFullYear()}`,
        icon: 'success',
        timer: 2000,
        timerProgressBar: true
    });
    
    // Auto-submit the form after a short delay
    setTimeout(() => {
        document.querySelector('form[method="GET"]').submit();
    }, 1500);
}

// Form validation before submission
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts when page loads
    initializeCharts();
    
    // Add form validation
    const form = document.querySelector('form[method="GET"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;
            
            // Allow submission if both dates are empty (show all data)
            if (!fromDate && !toDate) {
                return true;
            }
            
            // If one date is filled, both must be filled
            if (!fromDate || !toDate) {
                e.preventDefault();
                showDarkAlert({
                    title: 'Invalid Date Range',
                    text: 'Please select both from and to dates, or leave both empty to show all data.',
                    icon: 'warning'
                });
                return false;
            }
            
            // Validate date range
            if (new Date(fromDate) > new Date(toDate)) {
                e.preventDefault();
                showDarkAlert({
                    title: 'Invalid Date Range',
                    text: 'From date cannot be later than to date.',
                    icon: 'error'
                });
                return false;
            }
            
            // Show loading indicator
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'inline-block';
            }
            
            return true;
        });
    }
    
    // Add date input change handlers for better UX
    const fromDateInput = document.getElementById('from_date');
    const toDateInput = document.getElementById('to_date');
    
    if (fromDateInput && toDateInput) {
        fromDateInput.addEventListener('change', function() {
            if (this.value && !toDateInput.value) {
                // Auto-set to date to today if from date is selected
                toDateInput.value = new Date().toISOString().split('T')[0];
            }
        });
        
        toDateInput.addEventListener('change', function() {
            if (this.value && !fromDateInput.value) {
                // Auto-set from date to 30 days ago if to date is selected
                const fromDate = new Date(this.value);
                fromDate.setDate(fromDate.getDate() - 30);
                fromDateInput.value = fromDate.toISOString().split('T')[0];
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 