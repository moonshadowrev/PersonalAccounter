<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Add Subscription</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/subscriptions">Subscriptions</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">New Subscription Details</h4>
                    <p class="card-title-desc">Fill out the form below to add a new subscription.</p>
                    <form action="/subscriptions" method="POST">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Subscription Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount</label>
                                    <input type="number" id="amount" name="amount" class="form-control" required step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <input type="text" id="currency" name="currency" class="form-control" required value="USD">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="credit_card_id" class="form-label">Credit Card</label>
                            <select id="credit_card_id" name="credit_card_id" class="form-select" required>
                                <option value="">Select a card</option>
                                <?php foreach ($credit_cards as $card): ?>
                                    <option value="<?php echo htmlspecialchars($card['id']); ?>"><?php echo htmlspecialchars($card['name']); ?> (**** <?php echo htmlspecialchars($card['card_number_last4']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="billing_cycle" class="form-label">Billing Cycle</label>
                                    <select id="billing_cycle" name="billing_cycle" class="form-select" required>
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                        <option value="one-time">One-Time</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="quarterly">Quarterly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="next_payment_date_group">
                                    <label for="next_payment_date" class="form-label">Next Payment Date</label>
                                    <input type="date" id="next_payment_date" name="next_payment_date" class="form-control" required>
                                    <small class="form-text text-muted">Not required for one-time payments</small>
                                </div>
                            </div>
                        </div>
                         <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Add Subscription</button>
                            <a href="/subscriptions" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const billingCycleSelect = document.getElementById('billing_cycle');
    const nextPaymentDateInput = document.getElementById('next_payment_date');
    const nextPaymentDateGroup = document.getElementById('next_payment_date_group');
    
    function toggleNextPaymentDate() {
        if (billingCycleSelect.value === 'one-time') {
            nextPaymentDateInput.removeAttribute('required');
            nextPaymentDateInput.value = '';
            nextPaymentDateGroup.style.opacity = '0.5';
            nextPaymentDateInput.disabled = true;
        } else {
            nextPaymentDateInput.setAttribute('required', 'required');
            nextPaymentDateGroup.style.opacity = '1';
            nextPaymentDateInput.disabled = false;
        }
    }
    
    // Initial check
    toggleNextPaymentDate();
    
    // Listen for changes
    billingCycleSelect.addEventListener('change', toggleNextPaymentDate);
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 