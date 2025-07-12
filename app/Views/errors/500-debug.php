<?php defined('APP_RAN') or die('Direct access not allowed'); ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="alert alert-danger">
        <h1 class="h2 mb-2">🚨 500 - Internal Server Error</h1>
        <h4 class="h5 mb-0">Development Mode - Detailed Error Information</h4>
    </div>

    <div class="card mb-3 bg-dark border-danger">
        <div class="card-header bg-danger text-white py-2">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Error Message</h5>
        </div>
        <div class="card-body bg-dark text-light">
            <pre class="bg-secondary text-light p-2 rounded small border" style="max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($message ?? 'Unknown error') ?></pre>
        </div>
    </div>

    <?php if (isset($exception) && $exception instanceof Throwable): ?>
    <div class="card mb-3 bg-dark border-warning">
        <div class="card-header bg-warning text-dark py-2">
            <h5 class="mb-0"><i class="fas fa-bug"></i> Exception Details</h5>
        </div>
        <div class="card-body bg-dark text-light">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-dark">
                    <tr>
                        <th width="150" class="bg-secondary">Exception Type:</th>
                        <td><code class="text-danger bg-transparent"><?= htmlspecialchars(get_class($exception)) ?></code></td>
                    </tr>
                    <tr>
                        <th class="bg-secondary">File:</th>
                        <td><code class="text-info bg-transparent"><?= htmlspecialchars($exception->getFile()) ?></code></td>
                    </tr>
                    <tr>
                        <th class="bg-secondary">Line:</th>
                        <td><code class="text-primary bg-transparent"><?= htmlspecialchars($exception->getLine()) ?></code></td>
                    </tr>
                    <tr>
                        <th class="bg-secondary">Message:</th>
                        <td><code class="text-warning bg-transparent"><?= htmlspecialchars($exception->getMessage()) ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3 bg-dark border-info">
        <div class="card-header bg-info text-white py-2">
            <h5 class="mb-0"><i class="fas fa-list"></i> Stack Trace</h5>
        </div>
        <div class="card-body bg-dark text-light">
            <pre class="bg-secondary text-light p-2 rounded small border" style="max-height: 300px; overflow-y: auto; font-size: 0.8rem; line-height: 1.3;"><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3 bg-dark border-secondary">
                <div class="card-header bg-secondary text-white py-2">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Environment Information</h5>
                </div>
                <div class="card-body bg-dark text-light">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-dark">
                            <tr>
                                <th width="120" class="bg-secondary">Environment:</th>
                                <td><span class="badge badge-warning"><?= htmlspecialchars($env ?? 'unknown') ?></span></td>
                            </tr>
                            <tr>
                                <th class="bg-secondary">Request URI:</th>
                                <td><code class="small text-info bg-transparent"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'unknown') ?></code></td>
                            </tr>
                            <tr>
                                <th class="bg-secondary">Method:</th>
                                <td><code class="text-success bg-transparent"><?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'unknown') ?></code></td>
                            </tr>
                            <tr>
                                <th class="bg-secondary">Timestamp:</th>
                                <td><code class="text-light bg-transparent"><?= date('Y-m-d H:i:s') ?></code></td>
                            </tr>
                            <tr>
                                <th class="bg-secondary">PHP Version:</th>
                                <td><code class="text-light bg-transparent"><?= PHP_VERSION ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
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
                        <button onclick="history.back()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Go Back
                        </button>
                        <button onclick="location.reload()" class="btn btn-info btn-sm">
                            <i class="fas fa-redo"></i> Reload Page
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-warning alert-sm">
                <h6 class="mb-1"><i class="fas fa-shield-alt"></i> Security Notice</h6>
                <p class="mb-0 small">This detailed error information is only shown in development mode. In production, users will see a generic error message.</p>
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

pre {
    font-size: 0.8rem;
    line-height: 1.4;
    background-color: #495057 !important;
    color: #f8f9fa !important;
    border: 1px solid #6c757d;
}

code.bg-transparent {
    background-color: transparent !important;
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
    
    pre {
        font-size: 0.7rem;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 