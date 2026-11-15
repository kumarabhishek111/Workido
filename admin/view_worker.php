<?php
/**
 * Workido - Individual Worker Detail & History View
 */

$activeMenu = 'workers';
$pageTitle = 'Worker Profile & History';
$pageSubtitle = 'Detailed worker credentials, allocation history, wage ledger, and scorecards';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$workerId = (int)($_GET['id'] ?? 0);
if ($workerId <= 0) {
    setFlash('danger', 'Invalid worker identification.');
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}

// 1. Fetch Worker Record
$stmt = $pdo->prepare("SELECT * FROM workers WHERE id = ? LIMIT 1");
$stmt->execute([$workerId]);
$worker = $stmt->fetch();

if (!$worker) {
    setFlash('danger', 'Worker record not found.');
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}

// 2. Fetch Job Assignments & Associated Jobs
$assignStmt = $pdo->prepare("
    SELECT ja.*, j.job_code, j.title as job_title, j.category as job_category, 
           j.status as job_status, j.start_date, j.expected_completion_date, j.actual_completion_date,
           j.payment_type, j.agreed_wage, j.location
    FROM job_assignments ja
    JOIN jobs j ON ja.job_id = j.id
    WHERE ja.worker_id = ?
    ORDER BY ja.id DESC
");
$assignStmt->execute([$workerId]);
$assignments = $assignStmt->fetchAll();

// Calculate Job Metrics
$totalAssigned = count($assignments);
$completedJobs = 0;
$inProgressJobs = 0;
$cancelledJobs = 0;

foreach ($assignments as $a) {
    if ($a['job_status'] === 'Completed') $completedJobs++;
    elseif ($a['job_status'] === 'In Progress' || $a['job_status'] === 'Assigned') $inProgressJobs++;
    elseif ($a['job_status'] === 'Cancelled') $cancelledJobs++;
}

$completionPercentage = calculateCompletionRate($completedJobs, $totalAssigned);

// 3. Fetch Wage Records
$wageStmt = $pdo->prepare("
    SELECT wr.*, j.job_code, j.title as job_title 
    FROM wage_records wr
    LEFT JOIN jobs j ON wr.job_id = j.id
    WHERE wr.worker_id = ?
    ORDER BY wr.id DESC
");
$wageStmt->execute([$workerId]);
$wages = $wageStmt->fetchAll();

$totalEarned = 0;
$totalPaid = 0;
foreach ($wages as $w) {
    $totalEarned += (float)$w['total_payable'];
    $totalPaid += (float)$w['amount_paid'];
}
$pendingWages = max(0, $totalEarned - $totalPaid);

// 4. Fetch Performance Records
$perfStmt = $pdo->prepare("
    SELECT pr.*, j.job_code, j.title as job_title 
    FROM performance_records pr
    LEFT JOIN jobs j ON pr.job_id = j.id
    WHERE pr.worker_id = ?
    ORDER BY pr.id DESC
");
$perfStmt->execute([$workerId]);
$performances = $perfStmt->fetchAll();

$avgRating = 0;
if (count($performances) > 0) {
    $sumRating = array_sum(array_column($performances, 'rating'));
    $avgRating = round($sumRating / count($performances), 1);
}
?>

<!-- Worker Profile Top Card -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-body p-4">
        <div class="row align-items-center gy-4">
            <div class="col-md-auto text-center text-md-start">
                <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 2rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                    <?= strtoupper(substr($worker['full_name'], 0, 1)) ?>
                </div>
            </div>

            <div class="col-md">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h3 class="fw-bold mb-0 text-dark"><?= e($worker['full_name']) ?></h3>
                    <span class="badge bg-secondary font-monospace"><?= e($worker['worker_code']) ?></span>
                    <?= getCategoryBadge($worker['category']) ?>
                    <?= getAvailabilityBadge($worker['availability_status']) ?>
                </div>

                <p class="text-muted small mb-2">
                    <i class="bi bi-telephone-fill me-1 text-primary"></i><strong><?= e($worker['mobile_number']) ?></strong>
                    <?php if ($worker['email']): ?>
                        <span class="mx-2">&bull;</span>
                        <i class="bi bi-envelope-fill me-1 text-secondary"></i><?= e($worker['email']) ?>
                    <?php endif; ?>
                    <span class="mx-2">&bull;</span>
                    <i class="bi bi-geo-alt-fill me-1 text-danger"></i><?= e($worker['address']) ?>
                </p>

                <div class="d-flex flex-wrap gap-1">
                    <?php 
                    $skillList = explode(',', $worker['skills']);
                    foreach ($skillList as $sk): 
                        $skClean = trim($sk);
                        if ($skClean):
                    ?>
                        <span class="badge bg-light text-dark border me-1"><i class="bi bi-check-circle-fill text-success me-1"></i><?= e($skClean) ?></span>
                    <?php endif; endforeach; ?>
                </div>
            </div>

            <div class="col-md-auto d-flex flex-wrap flex-md-column gap-2 justify-content-end">
                <a href="<?= getBaseUrl('admin/edit_worker.php?id=' . $worker['id']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit Profile
                </a>
                <a href="<?= getBaseUrl('admin/calculate_wage.php?worker_id=' . $worker['id']) ?>" class="btn btn-success btn-sm text-white">
                    <i class="bi bi-calculator me-1"></i>Issue Wage
                </a>
                <a href="<?= getBaseUrl('admin/add_performance.php?worker_id=' . $worker['id']) ?>" class="btn btn-warning btn-sm text-dark">
                    <i class="bi bi-star me-1"></i>Add Review
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Performance Summary Mini Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card-blue p-3">
            <span class="stat-label">Total Assigned Jobs</span>
            <div class="stat-value"><?= $totalAssigned ?></div>
            <small class="text-muted"><?= $completedJobs ?> Completed, <?= $inProgressJobs ?> In Progress</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card-emerald p-3">
            <span class="stat-label">Job Completion Rate</span>
            <div class="stat-value"><?= $completionPercentage ?>%</div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: <?= $completionPercentage ?>%"></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card-amber p-3">
            <span class="stat-label">Avg Quality Score</span>
            <div class="stat-value">
                <?= $avgRating ? number_format($avgRating, 1) . ' / 5.0' : 'N/A' ?>
            </div>
            <small class="text-warning">
                <?php for($i=1; $i<=5; $i++): ?>
                    <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : '' ?>"></i>
                <?php endfor; ?>
                (<?= count($performances) ?> Reviews)
            </small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card-purple p-3">
            <span class="stat-label">Daily Wage Standard</span>
            <div class="stat-value"><?= formatCurrency($worker['daily_wage']) ?></div>
            <small class="text-muted">Joined <?= formatDate($worker['joining_date']) ?> (<?= e($worker['experience_years']) ?> yrs exp)</small>
        </div>
    </div>
</div>

<!-- Detailed Tabs for Assignments, Wages, and Performance -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-header p-0">
        <ul class="nav nav-tabs border-0 px-3 pt-2" id="workerTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#tab-assignments" type="button" role="tab">
                    <i class="bi bi-briefcase me-1"></i>Work Allocation History (<?= count($assignments) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="wages-tab" data-bs-toggle="tab" data-bs-target="#tab-wages" type="button" role="tab">
                    <i class="bi bi-cash me-1"></i>Wage Payments (<?= count($wages) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="performance-tab" data-bs-toggle="tab" data-bs-target="#tab-performance" type="button" role="tab">
                    <i class="bi bi-star me-1"></i>Performance Scorecards (<?= count($performances) ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="workerTabContent">
        <!-- 1. Assignments Tab -->
        <div class="tab-pane fade show active p-4" id="tab-assignments" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Assigned Jobs & Work Orders</h6>
                <a href="<?= getBaseUrl('admin/assignments.php?worker_id=' . $worker['id']) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i>Assign to New Job
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-workido">
                    <thead>
                        <tr>
                            <th>Job Code</th>
                            <th>Job Title</th>
                            <th>Location</th>
                            <th>Start Date</th>
                            <th>Expected Completion</th>
                            <th>Payment Terms</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No jobs have been assigned to this worker yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><span class="font-monospace fw-bold"><?= e($a['job_code']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($a['job_title']) ?></div>
                                        <small class="text-muted">Assigned on <?= formatDate($a['assignment_date']) ?></small>
                                    </td>
                                    <td><small><?= e($a['location']) ?></small></td>
                                    <td><?= formatDate($a['start_date']) ?></td>
                                    <td><?= formatDate($a['expected_completion_date']) ?></td>
                                    <td><span class="fw-bold"><?= formatCurrency($a['agreed_wage']) ?></span> <small class="text-muted">(<?= e($a['payment_type']) ?>)</small></td>
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

        <!-- 2. Wages Tab -->
        <div class="tab-pane fade p-4" id="tab-wages" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0">Wage Payment Ledger</h6>
                    <small class="text-muted">Total Billed: <?= formatCurrency($totalEarned) ?> | Paid: <?= formatCurrency($totalPaid) ?> | Pending: <?= formatCurrency($pendingWages) ?></small>
                </div>
                <a href="<?= getBaseUrl('admin/calculate_wage.php?worker_id=' . $worker['id']) ?>" class="btn btn-sm btn-success text-white">
                    <i class="bi bi-plus-circle me-1"></i>Calculate New Wage
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-workido">
                    <thead>
                        <tr>
                            <th>Voucher</th>
                            <th>Job Order</th>
                            <th>Days / Payment</th>
                            <th>Total Payable</th>
                            <th>Amount Paid</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($wages)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No wage vouchers recorded for this worker yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($wages as $w): ?>
                                <tr>
                                    <td><span class="font-monospace fw-bold"><?= e($w['voucher_no']) ?></span></td>
                                    <td><?= $w['job_code'] ? e($w['job_code']) . ' - ' . e($w['job_title']) : '<span class="text-muted">General Assignment</span>' ?></td>
                                    <td>
                                        <?php if ($w['payment_type'] === 'Daily Rate'): ?>
                                            <?= e($w['working_days']) ?> days @ <?= formatCurrency($w['daily_wage']) ?>
                                        <?php else: ?>
                                            Fixed Price
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="fw-bold"><?= formatCurrency($w['total_payable']) ?></span></td>
                                    <td><span class="text-success fw-bold"><?= formatCurrency($w['amount_paid']) ?></span></td>
                                    <td><?= getPaymentStatusBadge($w['payment_status']) ?></td>
                                    <td><?= formatDate($w['payment_date']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= getBaseUrl('admin/view_wage.php?id=' . $w['id']) ?>" class="btn btn-sm btn-light border btn-action" title="View Payslip">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Performance Tab -->
        <div class="tab-pane fade p-4" id="tab-performance" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Supervisor Reviews & Quality Ratings</h6>
                <a href="<?= getBaseUrl('admin/add_performance.php?worker_id=' . $worker['id']) ?>" class="btn btn-sm btn-warning text-dark">
                    <i class="bi bi-plus-circle me-1"></i>Record Review
                </a>
            </div>

            <?php if (empty($performances)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-star fs-1 mb-2"></i>
                    <p>No supervisor performance records logged yet for <?= e($worker['full_name']) ?>.</p>
                    <a href="<?= getBaseUrl('admin/add_performance.php?worker_id=' . $worker['id']) ?>" class="btn btn-sm btn-primary">Add First Review</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($performances as $p): ?>
                        <div class="col-md-6">
                            <div class="card border rounded-3 p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-warning text-dark fw-bold me-1">
                                            <i class="bi bi-star-fill me-1"></i><?= number_format((float)$p['rating'], 1) ?> / 5.0
                                        </span>
                                        <span class="text-muted small">Reviewed on <?= formatDate($p['review_date']) ?></span>
                                    </div>
                                    <?php if ($p['job_code']): ?>
                                        <span class="badge bg-secondary font-monospace"><?= e($p['job_code']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-3 small text-muted mb-2">
                                    <span>Punctuality: <strong><?= e($p['punctuality_rating']) ?>/5</strong></span>
                                    <span>Quality: <strong><?= e($p['quality_rating']) ?>/5</strong></span>
                                    <span>Safety: <strong><?= e($p['safety_rating']) ?>/5</strong></span>
                                </div>

                                <p class="card-text mb-2 text-dark">"<?= e($p['remarks']) ?>"</p>

                                <?php if ($p['admin_feedback']): ?>
                                    <div class="bg-white p-2 rounded border small text-muted">
                                        <strong class="text-primary">Admin Feedback:</strong> <?= e($p['admin_feedback']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
