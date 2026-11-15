<?php
/**
 * Workido - Jobs & Work Orders Directory
 * View, Search, and Multi-Filter Job Assignments
 */

$activeMenu = 'jobs';
$pageTitle = 'Work Orders & Jobs';
$pageSubtitle = 'Monitor job dispatches, progress, trades, and completion milestones';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

// 1. Search and Filter Inputs
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$categoryFilter = sanitize($_GET['category'] ?? '');
$workerFilter = (int)($_GET['worker_id'] ?? 0);
$dateFilter = sanitize($_GET['date'] ?? '');

// 2. Build Query
$sql = "
    SELECT j.*, 
           w.id as worker_id, w.full_name as worker_name, w.worker_code,
           ja.id as assignment_id, ja.assignment_date, ja.assignment_status
    FROM jobs j
    LEFT JOIN job_assignments ja ON j.id = ja.job_id
    LEFT JOIN workers w ON ja.worker_id = w.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (j.title LIKE ? OR j.job_code LIKE ? OR j.location LIKE ? OR j.client_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter !== '') {
    $sql .= " AND j.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter !== '') {
    $sql .= " AND j.category = ?";
    $params[] = $categoryFilter;
}

if ($workerFilter > 0) {
    $sql .= " AND w.id = ?";
    $params[] = $workerFilter;
}

if ($dateFilter !== '') {
    $sql .= " AND (j.start_date <= ? AND j.expected_completion_date >= ?)";
    $params[] = $dateFilter;
    $params[] = $dateFilter;
}

$sql .= " ORDER BY j.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Fetch workers for filter dropdown
$allWorkers = $pdo->query("SELECT id, full_name, worker_code, category FROM workers ORDER BY full_name ASC")->fetchAll();
?>

<!-- Filter & Search Toolbar -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <!-- Search Query -->
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search order, title, client..." value="<?= e($search) ?>" data-table-search="#jobsTable">
                    <?php if ($search !== ''): ?>
                        <a href="<?= getBaseUrl('admin/jobs.php') ?>" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-sm-6 col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Assigned" <?= $statusFilter === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                    <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="col-sm-6 col-md-2">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <option value="Carpenter" <?= $categoryFilter === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                    <option value="Electrician" <?= $categoryFilter === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                    <option value="Plumber" <?= $categoryFilter === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                    <option value="Mason" <?= $categoryFilter === 'Mason' ? 'selected' : '' ?>>Mason</option>
                </select>
            </div>

            <!-- Worker Filter -->
            <div class="col-sm-6 col-md-3">
                <select name="worker_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Assigned Workers</option>
                    <?php foreach ($allWorkers as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $workerFilter == $w['id'] ? 'selected' : '' ?>>
                            <?= e($w['full_name']) ?> (<?= e($w['category']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-sm-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel-fill me-1"></i>Filter
                </button>
                <a href="<?= getBaseUrl('admin/jobs.php') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Jobs Table Card -->
<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="d-flex align-items-center gap-2">
            <h5 class="dashboard-card-title mb-0">
                <i class="bi bi-briefcase-fill text-primary"></i> Work Orders & Job Allocation
            </h5>
            <span class="badge bg-primary rounded-pill"><?= count($jobs) ?> Orders</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= getBaseUrl('admin/add_job.php') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Post New Job
            </a>
            <a href="<?= getBaseUrl('admin/assignments.php') ?>" class="btn btn-outline-info text-dark btn-sm">
                <i class="bi bi-diagram-3-fill me-1"></i>Allocate Work
            </a>
            <a href="<?= getBaseUrl('admin/reports.php?export=jobs') ?>" class="btn btn-outline-secondary btn-sm" title="Export CSV">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-workido" id="jobsTable">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Title & Client</th>
                    <th>Category</th>
                    <th>Assigned Craftsman</th>
                    <th>Location</th>
                    <th>Schedule</th>
                    <th>Agreed Payment</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jobs)): ?>
                    <tr class="no-filter">
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-briefcase fs-1"></i></div>
                            <h6 class="fw-bold text-dark">No work orders match the filter criteria</h6>
                            <p class="text-muted small">Try modifying search parameters or create a new job order.</p>
                            <a href="<?= getBaseUrl('admin/add_job.php') ?>" class="btn btn-sm btn-primary">Create New Job</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($jobs as $j): ?>
                        <tr>
                            <td>
                                <a href="<?= getBaseUrl('admin/view_job.php?id=' . $j['id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                    <?= e($j['job_code']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= getBaseUrl('admin/view_job.php?id=' . $j['id']) ?>" class="fw-bold text-dark text-decoration-none d-block">
                                    <?= e($j['title']) ?>
                                </a>
                                <?php if ($j['client_name']): ?>
                                    <small class="text-muted"><i class="bi bi-person me-1"></i><?= e($j['client_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= getCategoryBadge($j['category']) ?></td>
                            <td>
                                <?php if ($j['worker_id']): ?>
                                    <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $j['worker_id']) ?>" class="fw-semibold text-dark text-decoration-none d-flex align-items-center gap-1">
                                        <i class="bi bi-person-check text-success"></i><?= e($j['worker_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="bi bi-exclamation-circle me-1"></i>Unassigned
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-truncate text-muted small" style="max-width: 140px;" title="<?= e($j['location']) ?>">
                                    <?= e($j['location']) ?>
                                </div>
                            </td>
                            <td>
                                <small class="d-block text-dark fw-semibold"><?= formatDate($j['start_date']) ?></small>
                                <small class="text-muted">to <?= formatDate($j['expected_completion_date']) ?></small>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= formatCurrency($j['agreed_wage']) ?></span>
                                <small class="d-block text-muted" style="font-size: 0.72rem;"><?= e($j['payment_type']) ?></small>
                            </td>
                            <td><?= getJobStatusBadge($j['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= getBaseUrl('admin/view_job.php?id=' . $j['id']) ?>" class="btn btn-light border btn-action" title="View Job Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= getBaseUrl('admin/edit_job.php?id=' . $j['id']) ?>" class="btn btn-light border btn-action text-primary" title="Edit Job">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($j['status'] === 'Completed' || $j['status'] === 'In Progress'): ?>
                                        <a href="<?= getBaseUrl('admin/calculate_wage.php?job_id=' . $j['id'] . ($j['worker_id'] ? '&worker_id=' . $j['worker_id'] : '')) ?>" class="btn btn-light border btn-action text-success" title="Calculate Wage">
                                            <i class="bi bi-calculator"></i>
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= getBaseUrl('admin/delete_job.php') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete job order <?= e($j['job_code']) ?>?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                                        <button type="submit" class="btn btn-light border btn-action text-danger" title="Delete Job">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
