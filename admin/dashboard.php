<?php
/**
 * Workido - Admin Dashboard
 * Real-time skilled-worker analytics, summaries, and quick operations
 */

$activeMenu = 'dashboard';
$pageTitle = 'Executive Dashboard';
$pageSubtitle = 'Real-time skilled-worker operational metrics & live allocations';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error. Please run <a href="../install.php">install.php</a>.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

// 1. Dynamic Metric Queries
try {
    // Total Workers
    $totalWorkers = (int)$pdo->query("SELECT COUNT(*) FROM workers")->fetchColumn();
    // Total Jobs
    $totalJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
    // Assigned Jobs
    $assignedJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Assigned'")->fetchColumn();
    // In Progress Jobs
    $inProgressJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'In Progress'")->fetchColumn();
    // Completed Jobs
    $completedJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Completed'")->fetchColumn();
    // Pending Jobs
    $pendingJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Pending'")->fetchColumn();
    // Total Wage Records Count
    $totalWageRecords = (int)$pdo->query("SELECT COUNT(*) FROM wage_records")->fetchColumn();
    // Total Wages Sum
    $totalWagesPayable = (float)$pdo->query("SELECT COALESCE(SUM(total_payable), 0) FROM wage_records")->fetchColumn();
    $totalWagesPaid = (float)$pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM wage_records")->fetchColumn();
    $pendingWagesAmount = max(0, $totalWagesPayable - $totalWagesPaid);

    // 2. Category Distribution
    $categoryStats = ['Carpenter' => 0, 'Electrician' => 0, 'Plumber' => 0, 'Mason' => 0];
    $catStmt = $pdo->query("SELECT category, COUNT(*) as cnt FROM workers GROUP BY category");
    while ($r = $catStmt->fetch()) {
        if (isset($categoryStats[$r['category']])) {
            $categoryStats[$r['category']] = (int)$r['cnt'];
        }
    }

    // 3. Recent Worker Registrations (Limit 5)
    $recentWorkersStmt = $pdo->query("SELECT * FROM workers ORDER BY id DESC LIMIT 5");
    $recentWorkers = $recentWorkersStmt->fetchAll();

    // 4. Recent Job Assignments (Limit 5)
    $recentAssignStmt = $pdo->query("
        SELECT ja.id as assignment_id, ja.assignment_date, ja.assignment_status,
               j.id as job_id, j.job_code, j.title as job_title, j.category as job_category, j.status as job_status,
               w.id as worker_id, w.full_name as worker_name, w.worker_code
        FROM job_assignments ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN workers w ON ja.worker_id = w.id
        ORDER BY ja.id DESC LIMIT 5
    ");
    $recentAssignments = $recentAssignStmt->fetchAll();

    // 5. Recent Completed Jobs (Limit 5)
    $completedJobsStmt = $pdo->query("
        SELECT j.*, w.full_name as worker_name, w.worker_code
        FROM jobs j
        LEFT JOIN job_assignments ja ON j.id = ja.job_id
        LEFT JOIN workers w ON ja.worker_id = w.id
        WHERE j.status = 'Completed'
        ORDER BY j.actual_completion_date DESC, j.id DESC LIMIT 5
    ");
    $recentCompleted = $completedJobsStmt->fetchAll();

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error retrieving dashboard records: ' . e($e->getMessage()) . '</div>';
    $totalWorkers = $totalJobs = $assignedJobs = $completedJobs = $pendingJobs = $totalWageRecords = 0;
    $recentWorkers = $recentAssignments = $recentCompleted = [];
}
?>

<!-- Quick Action Command Bar -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 p-3 bg-white border rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary p-2 rounded-circle"><i class="bi bi-lightning-fill"></i></span>
        <div>
            <h6 class="mb-0 fw-bold">Quick Management Actions</h6>
            <small class="text-muted">Create records and dispatches instantly</small>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= getBaseUrl('admin/add_worker.php') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i>Add Worker
        </a>
        <a href="<?= getBaseUrl('admin/add_job.php') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-circle-fill me-1"></i>Post Job
        </a>
        <a href="<?= getBaseUrl('admin/assignments.php') ?>" class="btn btn-sm btn-outline-info text-dark">
            <i class="bi bi-diagram-3-fill me-1"></i>Allocate Work
        </a>
        <a href="<?= getBaseUrl('admin/calculate_wage.php') ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-calculator-fill me-1"></i>Calculate Wage
        </a>
        <a href="<?= getBaseUrl('admin/reports.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i>Export CSV
        </a>
    </div>
</div>

<!-- 6 Core Dynamic Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- 1. Total Workers -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card card-blue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Workers</span>
                    <div class="stat-value"><?= number_format($totalWorkers) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="mt-3 small">
                <a href="<?= getBaseUrl('admin/workers.php') ?>" class="text-decoration-none text-primary fw-semibold">
                    View Directory &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Total Jobs -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card card-purple">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Jobs</span>
                    <div class="stat-value"><?= number_format($totalJobs) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
            </div>
            <div class="mt-3 small">
                <a href="<?= getBaseUrl('admin/jobs.php') ?>" class="text-decoration-none text-purple fw-semibold" style="color: #8b5cf6;">
                    View All Orders &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- 3. Assigned Jobs -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card card-cyan">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Assigned Jobs</span>
                    <div class="stat-value"><?= number_format($assignedJobs) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
            <div class="mt-3 small">
                <a href="<?= getBaseUrl('admin/jobs.php?status=Assigned') ?>" class="text-decoration-none text-info fw-semibold">
                    View Assigned &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- 4. Completed Jobs -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card card-emerald">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Completed Jobs</span>
                    <div class="stat-value"><?= number_format($completedJobs) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
            <div class="mt-3 small">
                <a href="<?= getBaseUrl('admin/jobs.php?status=Completed') ?>" class="text-decoration-none text-success fw-semibold">
                    View Completed &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- 5. Pending Jobs -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card card-amber">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Pending Jobs</span>
                    <div class="stat-value"><?= number_format($pendingJobs) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <div class="mt-3 small">
                <a href="<?= getBaseUrl('admin/jobs.php?status=Pending') ?>" class="text-decoration-none text-warning fw-semibold">
                    Awaiting Assign &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- 6. Total Wage Records -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card card-rose">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Wage Records</span>
                    <div class="stat-value"><?= number_format($totalWageRecords) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="mt-3 small">
                <a href="<?= getBaseUrl('admin/wages.php') ?>" class="text-decoration-none text-danger fw-semibold">
                    View Ledger &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Financial Summary & Trade Distribution Row -->
<div class="row g-4 mb-4">
    <!-- Financial Overview Card -->
    <div class="col-lg-6">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-header">
                <h5 class="dashboard-card-title">
                    <i class="bi bi-wallet2 text-success"></i> Wage Payroll Overview
                </h5>
                <a href="<?= getBaseUrl('admin/wages.php') ?>" class="btn btn-sm btn-outline-secondary">Details</a>
            </div>
            <div class="dashboard-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Total Payable</small>
                            <h4 class="fw-bold text-dark mb-0 mt-1"><?= formatCurrency($totalWagesPayable) ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Disbursed / Paid</small>
                            <h4 class="fw-bold text-success mb-0 mt-1"><?= formatCurrency($totalWagesPaid) ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Pending Balance</small>
                            <h4 class="fw-bold text-danger mb-0 mt-1"><?= formatCurrency($pendingWagesAmount) ?></h4>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <?php 
                    $disbursedPercent = $totalWagesPayable > 0 ? round(($totalWagesPaid / $totalWagesPayable) * 100, 1) : 0;
                    ?>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Disbursement Ratio</span>
                        <span class="fw-bold text-dark"><?= $disbursedPercent ?>% Paid</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $disbursedPercent ?>%" aria-valuenow="<?= $disbursedPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trade Categories Breakdown -->
    <div class="col-lg-6">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-header">
                <h5 class="dashboard-card-title">
                    <i class="bi bi-pie-chart-fill text-primary"></i> Worker Category Distribution
                </h5>
                <a href="<?= getBaseUrl('admin/workers.php') ?>" class="btn btn-sm btn-outline-secondary">Manage</a>
            </div>
            <div class="dashboard-card-body">
                <div class="row g-3">
                    <?php foreach ($categoryStats as $cat => $cnt): 
                        $pct = $totalWorkers > 0 ? round(($cnt / $totalWorkers) * 100, 1) : 0;
                        $iconClass = match($cat) {
                            'Carpenter'   => 'bi-hammer text-amber',
                            'Electrician' => 'bi-lightning-charge-fill text-warning',
                            'Plumber'     => 'bi-droplet-fill text-primary',
                            'Mason'       => 'bi-bricks text-danger',
                            default       => 'bi-tools'
                        };
                    ?>
                        <div class="col-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold small"><i class="bi <?= $iconClass ?> me-1"></i><?= $cat ?></span>
                                    <span class="badge bg-secondary rounded-pill"><?= $cnt ?></span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="text-end text-muted mt-1" style="font-size: 0.75rem;"><?= $pct ?>% of workforce</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities Tables (Workers & Assignments) -->
<div class="row g-4 mb-4">
    <!-- Recent Worker Registrations -->
    <div class="col-lg-6">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-header">
                <h5 class="dashboard-card-title">
                    <i class="bi bi-person-plus text-primary"></i> Recent Worker Registrations
                </h5>
                <a href="<?= getBaseUrl('admin/workers.php') ?>" class="btn btn-sm btn-link text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-workido">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Worker Name</th>
                            <th>Category</th>
                            <th>Daily Wage</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentWorkers)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No workers registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentWorkers as $w): ?>
                                <tr>
                                    <td><span class="fw-bold font-monospace small"><?= e($w['worker_code']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($w['full_name']) ?></div>
                                        <small class="text-muted"><?= e($w['mobile_number']) ?></small>
                                    </td>
                                    <td><?= getCategoryBadge($w['category']) ?></td>
                                    <td><span class="fw-semibold"><?= formatCurrency($w['daily_wage']) ?></span></td>
                                    <td><?= getAvailabilityBadge($w['availability_status']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $w['id']) ?>" class="btn btn-sm btn-light border btn-action" title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Job Assignments -->
    <div class="col-lg-6">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-header">
                <h5 class="dashboard-card-title">
                    <i class="bi bi-diagram-3 text-info"></i> Recent Job Assignments
                </h5>
                <a href="<?= getBaseUrl('admin/assignments.php') ?>" class="btn btn-sm btn-link text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-workido">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Job Title</th>
                            <th>Assigned Worker</th>
                            <th>Assigned Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentAssignments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No active work assignments recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAssignments as $a): ?>
                                <tr>
                                    <td><span class="fw-bold font-monospace small"><?= e($a['job_code']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 140px;" title="<?= e($a['job_title']) ?>">
                                            <?= e($a['job_title']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold"><?= e($a['worker_name']) ?></span>
                                    </td>
                                    <td><small class="text-muted"><?= formatDate($a['assignment_date']) ?></small></td>
                                    <td><?= getJobStatusBadge($a['job_status']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= getBaseUrl('admin/view_job.php?id=' . $a['job_id']) ?>" class="btn btn-sm btn-light border btn-action" title="View Job Details">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recent Completed Jobs Table -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-header">
        <h5 class="dashboard-card-title">
            <i class="bi bi-check2-circle text-success"></i> Recent Completed Work Orders
        </h5>
        <a href="<?= getBaseUrl('admin/jobs.php?status=Completed') ?>" class="btn btn-sm btn-outline-secondary">View All Completed</a>
    </div>
    <div class="table-responsive">
        <table class="table table-workido">
            <thead>
                <tr>
                    <th>Job Code</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Assigned Craftsman</th>
                    <th>Location</th>
                    <th>Agreed Payment</th>
                    <th>Completion Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentCompleted)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No completed jobs recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentCompleted as $comp): ?>
                        <tr>
                            <td><span class="fw-bold font-monospace small"><?= e($comp['job_code']) ?></span></td>
                            <td><span class="fw-semibold"><?= e($comp['title']) ?></span></td>
                            <td><?= getCategoryBadge($comp['category']) ?></td>
                            <td><?= $comp['worker_name'] ? e($comp['worker_name']) : '<span class="text-muted">—</span>' ?></td>
                            <td><small class="text-muted"><?= e($comp['location']) ?></small></td>
                            <td><span class="fw-bold text-success"><?= formatCurrency($comp['agreed_wage']) ?></span> <small class="text-muted">(<?= e($comp['payment_type']) ?>)</small></td>
                            <td><span class="badge bg-success-subtle text-success"><i class="bi bi-calendar-check me-1"></i><?= formatDate($comp['actual_completion_date'] ?: $comp['expected_completion_date']) ?></span></td>
                            <td class="text-end">
                                <a href="<?= getBaseUrl('admin/view_job.php?id=' . $comp['id']) ?>" class="btn btn-sm btn-light border btn-action" title="View Job Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= getBaseUrl('admin/calculate_wage.php?job_id=' . $comp['id']) ?>" class="btn btn-sm btn-success btn-action text-white" title="Calculate Settlement Wage">
                                    <i class="bi bi-calculator"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
