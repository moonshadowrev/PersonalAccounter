<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Credit Cards</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item active">Credit Cards</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="card-title">All Credit Cards</h4>
                        <a href="/credit-cards/create" class="btn btn-primary">Add New Card</a>
                    </div>

                    <table id="credit-cards-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>User</th>
                                <th>Last 4 Digits</th>
                                <th>Expiry</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($credit_cards as $card): ?>
                        <tr>
                            <td><?php echo $card['id']; ?></td>
                            <td><?php echo htmlspecialchars($card['name']); ?></td>
                            <td><?php echo htmlspecialchars($card['user_name'] ?? 'Unknown'); ?></td>
                            <td>**** **** **** <?php echo $card['card_number_last4']; ?></td>
                            <td><?php echo htmlspecialchars($card['expiry_month'] . '/' . $card['expiry_year']); ?></td>
                            <td>
                                <a href="/credit-cards/<?php echo $card['id']; ?>/edit" class="btn btn-sm btn-info">Edit</a>
                                <form action="/credit-cards/<?php echo $card['id']; ?>/delete" method="POST" style="display:inline;">
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

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 