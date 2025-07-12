<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Subscriptions</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item active">Subscriptions</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                     <div class="d-flex justify-content-between mb-3">
                        <h4 class="card-title">All Subscriptions</h4>
                        <a href="/subscriptions/create" class="btn btn-primary">Add New Subscription</a>
                    </div>

                    <table id="subscriptions-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                       <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Billing Cycle</th>
                                <th>Next Payment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                        <tr>
                            <td><?php echo $subscription['id']; ?></td>
                            <td><?php echo htmlspecialchars($subscription['name']); ?></td>
                            <td><?php echo htmlspecialchars($subscription['user_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($subscription['amount'] . ' ' . $subscription['currency']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($subscription['billing_cycle'])); ?></td>
                            <td><?php echo htmlspecialchars($subscription['next_payment_date']); ?></td>
                            <td><span class="badge bg-<?php echo $subscription['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars(ucfirst($subscription['status'])); ?></span></td>
                            <td>
                                <a href="/subscriptions/<?php echo $subscription['id']; ?>/edit" class="btn btn-sm btn-info">Edit</a>
                                <form action="/subscriptions/<?php echo $subscription['id']; ?>/delete" method="POST" style="display:inline;">
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