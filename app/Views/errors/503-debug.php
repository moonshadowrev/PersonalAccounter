<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="alert alert-warning">
        <h1 class="h2 mb-2">⚠️ 503 - Service Unavailable</h1>
        <h4 class="h5 mb-0">Development Mode - Database/Service Error</h4>
    </div>

    <div class="card mb-3 bg-dark border-warning">
        <div class="card-header bg-warning text-dark py-2">
            <h5 class="mb-0"><i class="fas fa-database"></i> Service Error Details</h5>
        </div>
        <div class="card-body bg-dark text-light">
            <pre class="bg-dark text-light p-2 rounded small border border-secondary" style="max-height: 200px; overflow-y: auto; background-color: #495057 !important;"><?= htmlspecialchars($message ?? 'Service unavailable') ?></pre>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3 bg-dark border-info">
                <div class="card-header bg-info text-white py-2">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Troubleshooting Tips</h5>
                </div>
                <div class="card-body bg-dark text-light">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item py-2 bg-dark text-light border-secondary">
                            <strong class="text-info">Database Connection:</strong> Check if your database server is running
                        </li>
                        <li class="list-group-item py-2 bg-dark text-light border-secondary">
                            <strong class="text-info">Configuration:</strong> Verify database credentials in your .env file
                        </li>
                        <li class="list-group-item py-2 bg-dark text-light border-secondary">
                            <strong class="text-info">Migrations:</strong> Run <code class="bg-secondary text-light px-1 rounded">php control migrate run</code> to ensure tables exist
                        </li>
                        <li class="list-group-item py-2 bg-dark text-light border-secondary">
                            <strong class="text-info">Permissions:</strong> Check database user permissions
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3 bg-dark border-secondary">
                <div class="card-header bg-secondary text-white py-2">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Environment Information</h5>
                </div>
                <div class="card-body bg-dark text-light">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-dark">
                            <tr>
                                <th width="120" class="bg-secondary">Request URI:</th>
                                <td><code class="small text-info bg-transparent"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'unknown') ?></code></td>
                            </tr>
                            <tr>
                                <th class="bg-secondary">Timestamp:</th>
                                <td><code class="text-light bg-transparent"><?= date('Y-m-d H:i:s') ?></code></td>
                            </tr>
                            <tr>
                                <th class="bg-secondary">Database Host:</th>
                                <td><code class="text-warning bg-transparent"><?= htmlspecialchars($_ENV['DB_HOST'] ?? 'not configured') ?></code></td>
                            </tr>
                            <tr>
                                <th class="bg-secondary">Database Name:</th>
                                <td><code class="text-warning bg-transparent"><?= htmlspecialchars($_ENV['DB_NAME'] ?? 'not configured') ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-info alert-sm">
                <h6 class="mb-1"><i class="fas fa-lightbulb"></i> Quick Fix</h6>
                <p class="mb-0 small">Try running: <code class="bg-secondary text-light px-1 rounded">php control migrate run</code> to ensure your database tables are properly set up.</p>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-3 bg-dark border-primary">
                <div class="card-header bg-primary text-white py-2">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Quick Actions</h5>
                </div>
                <div class="card-body bg-dark text-light">
                    <div class="d-grid gap-2">
                        <a href="/" class="btn btn-primary btn-sm">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
                        <button onclick="location.reload()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-redo"></i> Try Again
                        </button>
                        <button onclick="history.back()" class="btn btn-info btn-sm">
                            <i class="fas fa-arrow-left"></i> Go Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for better readability in dark theme */
.alert-sm {
    padding: 0.5rem 0.75rem;
}

.card-header {
    padding: 0.5rem 0.75rem;
}

.table-sm th,
.table-sm td {
    padding: 0.3rem;
    font-size: 0.875rem;
}

.table-dark {
    background-color: #343a40;
}

.table-dark th {
    background-color: #495057;
    border-color: #6c757d;
}

.table-dark td {
    border-color: #6c757d;
}

.list-group-item {
    font-size: 0.9rem;
}

.list-group-item.bg-dark {
    background-color: #343a40 !important;
    border-color: #6c757d !important;
}

pre {
    font-size: 0.8rem;
    line-height: 1.4;
    background-color: #495057 !important;
    color: #f8f9fa !important;
    border: 1px solid #6c757d !important;
}

pre.bg-dark {
    background-color: #495057 !important;
}

code.bg-transparent {
    background-color: transparent !important;
}

code.bg-secondary {
    background-color: #6c757d !important;
}

.container-fluid {
    max-width: 1400px;
}

.card.bg-dark {
    background-color: #343a40 !important;
}

.card-body.bg-dark {
    background-color: #343a40 !important;
}

@media (max-width: 768px) {
    .h2 {
        font-size: 1.5rem;
    }
    
    .h5 {
        font-size: 1rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .list-group-item {
        font-size: 0.8rem;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 