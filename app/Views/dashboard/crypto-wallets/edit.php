<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Crypto Wallet</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/crypto-wallets">Crypto Wallets</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Edit Crypto Wallet Details</h4>
                    <form action="/crypto-wallets/<?php echo $wallet['id']; ?>" method="POST" class="crypto-wallet-form">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                        <input type="hidden" name="_method" value="PUT">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Wallet Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($wallet['name']); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Network</label>
                                    <input type="text" class="form-control" disabled value="<?php echo htmlspecialchars($wallet['network']); ?>">
                                    <div class="form-text">Network cannot be changed for security reasons.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Currency</label>
                                    <input type="text" class="form-control" disabled value="<?php echo htmlspecialchars($wallet['currency']); ?>">
                                    <div class="form-text">Currency cannot be changed for security reasons.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Wallet Address</label>
                            <input type="text" class="form-control" disabled value="<?php echo htmlspecialchars($wallet['address']); ?>">
                            <div class="form-text">Address cannot be changed for security reasons.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($wallet['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mt-4 crypto-wallet-actions">
                            <button type="submit" class="btn btn-primary">Update Wallet</button>
                            <a href="/crypto-wallets" class="btn btn-secondary">Cancel</a>
                            <button type="button" class="btn btn-danger float-end" onclick="confirmDelete()">Delete Wallet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="delete-form" action="/crypto-wallets/<?php echo $wallet['id']; ?>/delete" method="POST" style="display: none;">
    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
</form>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this crypto wallet? This action cannot be undone.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 