<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">API Keys</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/profile/edit">Profile</a></li>
                    <li class="breadcrumb-item active">API Keys</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Your API Keys</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                            <i class="fas fa-plus"></i> Create API Key
                        </button>
                    </div>
                            <?php if (empty($apiKeys)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-key fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No API Keys</h5>
                                    <p class="text-muted">You haven't created any API keys yet. Create one to start using the API.</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                                        Create Your First API Key
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover api-keys-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Key Prefix</th>
                                                <th>Permissions</th>
                                                <th>Rate Limit</th>
                                                <th>Last Used</th>
                                                <th>Expires</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($apiKeys as $key): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($key['name']) ?></strong>
                                                        <br>
                                                        <small class="text-muted">Created <?= date('M j, Y', strtotime($key['created_at'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <code><?= htmlspecialchars($key['api_key_prefix']) ?>...</code>
                                                    </td>
                                                    <td>
                                                        <?php if (empty($key['permissions'])): ?>
                                                            <span class="badge bg-success">Full Access</span>
                                                        <?php else: ?>
                                                            <?php $permissions = json_decode($key['permissions'], true); ?>
                                                            <?php if (in_array('*', $permissions)): ?>
                                                                <span class="badge bg-success">Full Access</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-info"><?= count($permissions) ?> permissions</span>
                                                                <div class="small text-muted mt-1">
                                                                    <?= htmlspecialchars(implode(', ', array_slice($permissions, 0, 3))) ?>
                                                                    <?php if (count($permissions) > 3): ?>
                                                                        <br>and <?= count($permissions) - 3 ?> more...
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= $key['rate_limit_per_minute'] ?>/min
                                                    </td>
                                                    <td>
                                                        <?php if ($key['last_used_at']): ?>
                                                            <?= date('M j, Y g:i A', strtotime($key['last_used_at'])) ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Never</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($key['expires_at']): ?>
                                                            <?php $isExpired = strtotime($key['expires_at']) < time(); ?>
                                                            <span class="<?= $isExpired ? 'text-danger' : 'text-warning' ?>">
                                                                <?= date('M j, Y', strtotime($key['expires_at'])) ?>
                                                                <?php if ($isExpired): ?>
                                                                    <br><small>Expired</small>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Never</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $isBlocked = $key['blocked_until'] && strtotime($key['blocked_until']) > time();
                                                        $isExpired = $key['expires_at'] && strtotime($key['expires_at']) < time();
                                                        ?>
                                                        <?php if (!$key['is_active']): ?>
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        <?php elseif ($isBlocked): ?>
                                                            <span class="badge bg-danger">Blocked</span>
                                                            <div class="small text-muted">
                                                                Until <?= date('M j, g:i A', strtotime($key['blocked_until'])) ?>
                                                            </div>
                                                        <?php elseif ($isExpired): ?>
                                                            <span class="badge bg-warning">Expired</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($key['failed_attempts'] > 0): ?>
                                                            <div class="small text-warning mt-1">
                                                                <?= $key['failed_attempts'] ?> failed attempts
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                                                                                                                <form method="POST" action="/profile/api-keys/<?php echo htmlspecialchars($key['id']); ?>/delete" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to permanently delete this API key? This action cannot be undone and will immediately invalidate all requests using this key.')">
                                            <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Usage Information -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">API Usage Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Authentication</h6>
                                    <p>Include your API key in requests using one of these methods:</p>
                                    <ul>
                                        <li><strong>Authorization header:</strong> <code>Authorization: Bearer YOUR_API_KEY</code></li>
                                        <li><strong>X-API-Key header:</strong> <code>X-API-Key: YOUR_API_KEY</code></li>
                                        <li><strong>Query parameter:</strong> <code>?api_key=YOUR_API_KEY</code> (less secure)</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>API Endpoints</h6>
                                    <p>Base URL: <code><?= $_ENV['APP_URL'] ?? 'http://localhost' ?>/api/v1</code></p>
                                    <div class="small">
                                        <strong>Available Endpoints:</strong>
                                        <ul class="mb-2">
                                            <li><code>GET /users</code> - List users</li>
                                            <li><code>GET /categories</code> - List categories</li>
                                            <li><code>GET /tags</code> - List tags</li>
                                            <li><code>GET /bank-accounts</code> - List bank accounts</li>
                                            <li><code>GET /credit-cards</code> - List credit cards</li>
                                            <li><code>GET /crypto-wallets</code> - List crypto wallets</li>
                                            <li><code>GET /expenses</code> - List expenses</li>
                                            <li><code>GET /subscriptions</code> - List subscriptions</li>
                                            <li><code>GET /transactions</code> - List transactions</li>
                                            <li><code>GET /reports/dashboard</code> - Dashboard analytics</li>
                                            <li><code>GET /reports/expenses</code> - Expense analytics</li>
                                            <li><code>GET /reports/export</code> - Export data</li>
                                            <li><code>GET /api-keys</code> - List your API keys</li>
                                        </ul>
                                        <p class="text-muted mb-2">Each endpoint supports GET, POST, PUT, DELETE operations based on permissions.</p>
                                        <p class="text-muted mb-2"><strong>Special endpoints:</strong></p>
                                        <ul class="mb-2">
                                            <li><code>POST /expenses/{id}/approve</code> - Approve expense</li>
                                            <li><code>GET /expenses/analytics</code> - Expense analytics</li>
                                            <li><code>GET /categories/popular</code> - Popular categories</li>
                                            <li><code>GET /tags/popular</code> - Popular tags</li>
                                            <li><code>GET /bank-accounts/by-currency/{currency}</code> - Filter by currency</li>
                                            <li><code>GET /crypto-wallets/by-currency/{currency}</code> - Filter by currency</li>
                                            <li><code>GET /crypto-wallets/by-network/{network}</code> - Filter by network</li>
                                        </ul>
                                    </div>
                                    <?php if (Config::get('debug', false)): ?>
                                        <a href="/api/docs/ui" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-book"></i> View Full API Documentation
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div> <!-- container-fluid -->

<!-- Create API Key Modal -->
<div class="modal fade" id="createApiKeyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <form method="POST" action="/profile/api-keys/create">
                <div class="modal-header">
                    <h5 class="modal-title">Create New API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                                                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">API Key Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               placeholder="e.g., Mobile App, Integration Server">
                        <div class="form-text">Choose a descriptive name to identify this API key.</div>
                    </div>

                    <div class="mb-3">
                        <label for="rate_limit_per_minute" class="form-label">Rate Limit (requests per minute)</label>
                        <input type="number" class="form-control" id="rate_limit_per_minute" name="rate_limit_per_minute" 
                               value="60" min="1" max="1000">
                        <div class="form-text">Maximum number of API requests per minute (1-1000).</div>
                    </div>

                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Expiration Date (Optional)</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                        <div class="form-text">Leave empty for no expiration.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Permissions </label>
                        <div class="form-text mb-2">Select specific permissions:</div>   
                        <!-- All Permissions Option --> 
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="card-body py-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="perm_all" onchange="toggleAllPermissions(this)">
                                            <label class="form-check-label fw-bold text-primary" for="perm_all">
                                                <i class="fas fa-globe"></i> All Permissions (Full Access)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <h6 class="text-primary">Users</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="users.read" id="perm_users_read">
                                    <label class="form-check-label" for="perm_users_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="users.create" id="perm_users_create">
                                    <label class="form-check-label" for="perm_users_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="users.update" id="perm_users_update">
                                    <label class="form-check-label" for="perm_users_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="users.delete" id="perm_users_delete">
                                    <label class="form-check-label" for="perm_users_delete">Delete</label>
                                </div>
                                
                                <h6 class="text-primary mt-3">Categories</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="categories.read" id="perm_categories_read">
                                    <label class="form-check-label" for="perm_categories_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="categories.create" id="perm_categories_create">
                                    <label class="form-check-label" for="perm_categories_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="categories.update" id="perm_categories_update">
                                    <label class="form-check-label" for="perm_categories_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="categories.delete" id="perm_categories_delete">
                                    <label class="form-check-label" for="perm_categories_delete">Delete</label>
                                </div>
                                
                                <h6 class="text-primary mt-3">Tags</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="tags.read" id="perm_tags_read">
                                    <label class="form-check-label" for="perm_tags_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="tags.create" id="perm_tags_create">
                                    <label class="form-check-label" for="perm_tags_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="tags.update" id="perm_tags_update">
                                    <label class="form-check-label" for="perm_tags_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="tags.delete" id="perm_tags_delete">
                                    <label class="form-check-label" for="perm_tags_delete">Delete</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-primary">Bank Accounts</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="bank_accounts.read" id="perm_bank_accounts_read">
                                    <label class="form-check-label" for="perm_bank_accounts_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="bank_accounts.create" id="perm_bank_accounts_create">
                                    <label class="form-check-label" for="perm_bank_accounts_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="bank_accounts.update" id="perm_bank_accounts_update">
                                    <label class="form-check-label" for="perm_bank_accounts_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="bank_accounts.delete" id="perm_bank_accounts_delete">
                                    <label class="form-check-label" for="perm_bank_accounts_delete">Delete</label>
                                </div>
                                
                                <h6 class="text-primary mt-3">Credit Cards</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="credit_cards.read" id="perm_credit_cards_read">
                                    <label class="form-check-label" for="perm_credit_cards_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="credit_cards.create" id="perm_credit_cards_create">
                                    <label class="form-check-label" for="perm_credit_cards_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="credit_cards.update" id="perm_credit_cards_update">
                                    <label class="form-check-label" for="perm_credit_cards_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="credit_cards.delete" id="perm_credit_cards_delete">
                                    <label class="form-check-label" for="perm_credit_cards_delete">Delete</label>
                                </div>
                                
                                <h6 class="text-primary mt-3">Crypto Wallets</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="crypto_wallets.read" id="perm_crypto_wallets_read">
                                    <label class="form-check-label" for="perm_crypto_wallets_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="crypto_wallets.create" id="perm_crypto_wallets_create">
                                    <label class="form-check-label" for="perm_crypto_wallets_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="crypto_wallets.update" id="perm_crypto_wallets_update">
                                    <label class="form-check-label" for="perm_crypto_wallets_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="crypto_wallets.delete" id="perm_crypto_wallets_delete">
                                    <label class="form-check-label" for="perm_crypto_wallets_delete">Delete</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-primary">Expenses</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="expenses.read" id="perm_expenses_read">
                                    <label class="form-check-label" for="perm_expenses_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="expenses.create" id="perm_expenses_create">
                                    <label class="form-check-label" for="perm_expenses_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="expenses.update" id="perm_expenses_update">
                                    <label class="form-check-label" for="perm_expenses_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="expenses.delete" id="perm_expenses_delete">
                                    <label class="form-check-label" for="perm_expenses_delete">Delete</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="expenses.approve" id="perm_expenses_approve">
                                    <label class="form-check-label" for="perm_expenses_approve">Approve</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="expenses.analytics" id="perm_expenses_analytics">
                                    <label class="form-check-label" for="perm_expenses_analytics">Analytics</label>
                                </div>
                                
                                <h6 class="text-primary mt-3">Subscriptions</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="subscriptions.read" id="perm_subscriptions_read">
                                    <label class="form-check-label" for="perm_subscriptions_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="subscriptions.create" id="perm_subscriptions_create">
                                    <label class="form-check-label" for="perm_subscriptions_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="subscriptions.update" id="perm_subscriptions_update">
                                    <label class="form-check-label" for="perm_subscriptions_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="subscriptions.delete" id="perm_subscriptions_delete">
                                    <label class="form-check-label" for="perm_subscriptions_delete">Delete</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-primary">Transactions</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="transactions.read" id="perm_transactions_read">
                                    <label class="form-check-label" for="perm_transactions_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="transactions.create" id="perm_transactions_create">
                                    <label class="form-check-label" for="perm_transactions_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="transactions.update" id="perm_transactions_update">
                                    <label class="form-check-label" for="perm_transactions_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="transactions.delete" id="perm_transactions_delete">
                                    <label class="form-check-label" for="perm_transactions_delete">Delete</label>
                                </div>
                                
                                <h6 class="text-primary mt-3">Reports</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="reports.read" id="perm_reports_read">
                                    <label class="form-check-label" for="perm_reports_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="reports.export" id="perm_reports_export">
                                    <label class="form-check-label" for="perm_reports_export">Export</label>
                                </div>
                                
                                <h6 class="text-primary mt-3">API Keys</h6>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="api_keys.read" id="perm_api_keys_read">
                                    <label class="form-check-label" for="perm_api_keys_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="api_keys.create" id="perm_api_keys_create">
                                    <label class="form-check-label" for="perm_api_keys_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="api_keys.update" id="perm_api_keys_update">
                                    <label class="form-check-label" for="perm_api_keys_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="api_keys.delete" id="perm_api_keys_delete">
                                    <label class="form-check-label" for="perm_api_keys_delete">Delete</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create API Key</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAllPermissions(allCheckbox) {
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = allCheckbox.checked;
    });
}

// Listen for individual permission changes to update the "All" checkbox state
document.addEventListener('DOMContentLoaded', function() {
    const allCheckbox = document.getElementById('perm_all');
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Check if all individual checkboxes are checked
            const allChecked = Array.from(permissionCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(permissionCheckboxes).some(cb => cb.checked);
            
            if (allChecked) {
                allCheckbox.checked = true;
                allCheckbox.indeterminate = false;
            } else if (someChecked) {
                allCheckbox.checked = false;
                allCheckbox.indeterminate = true;
            } else {
                allCheckbox.checked = false;
                allCheckbox.indeterminate = false;
            }
        });
    });
    
    // Add category group selection functionality
    const categoryHeaders = document.querySelectorAll('h6.text-primary');
    categoryHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.title = 'Click to toggle all permissions in this category';
        
        header.addEventListener('click', function() {
            const category = this.textContent.trim().toLowerCase().replace(/\s+/g, '_');
            const categoryCheckboxes = document.querySelectorAll(`input[name="permissions[]"][value^="${category}."]`);
            
            // Check if all category checkboxes are checked
            const allCategoryChecked = Array.from(categoryCheckboxes).every(cb => cb.checked);
            
            // Toggle all checkboxes in this category
            categoryCheckboxes.forEach(checkbox => {
                checkbox.checked = !allCategoryChecked;
                checkbox.dispatchEvent(new Event('change')); // Trigger change event
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?> 