<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Add Credit Card</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/credit-cards">Credit Cards</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">New Card Details</h4>
                    <p class="card-title-desc">Fill out the form below to add a new credit card.</p>
                    <form action="/credit-cards" method="POST">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Card Name / Nickname</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" id="card_number" name="card_number" class="form-control" required pattern="\d{16}" title="16 digit card number">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expiry_month" class="form-label">Expiry Month</label>
                                    <input type="text" id="expiry_month" name="expiry_month" class="form-control" required pattern="\d{2}" title="2 digit month (e.g. 01)" placeholder="MM">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expiry_year" class="form-label">Expiry Year</label>
                                    <input type="text" id="expiry_year" name="expiry_year" class="form-control" required pattern="\d{4}" title="4 digit year (e.g. 2025)" placeholder="YYYY">
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Add Card</button>
                            <a href="/credit-cards" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 