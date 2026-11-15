<?php
/**
 * Workido - View Work Order Details & Assigned Craftsman
 */

$activeMenu = 'jobs';
$pageTitle = 'Work Order Details';
$pageSubtitle = 'Full job specifications, assigned worker, milestones, and settlement wages';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$jobId = (int)($_GET['id'] ?? 0);
if ($jobId <= 0) {
    setFlash('danger', 'Invalid work order identification.');
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}

// 1. Fetch Job Record
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? LIMIT 1");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('danger', 'Work order record not found.');
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}

// 2. Fetch Assigned Worker
$assignStmt = $pdo->prepare("
    SELECT ja.*, w.id as worker_id, w.full_name, w.worker_code, w.mobile_number, 
           w.category as worker_category, w.daily_wage as worker_daily_wage, 
           w.experience_years, w.availability_status
    FROM job_assignments ja
    JOIN workers w ON ja.worker_id = w.id
    WHERE ja.job_id = ?
    ORDER BY ja.id DESC LIMIT 1
");
$assignStmt->execute([$jobId]);
$assignedWorker = $assignStmt->fetch();

// 3. Fetch Wages related to this Job
$wageStmt = $pdo->prepare("
    SELECT wr.*, w.full_name as worker_name 
    FROM wage_records wr
    JOIN workers w ON wr.worker_id = w.id
    WHERE wr.job_id = ?
    ORDER BY wr.id DESC
");
$wageStmt->execute([$jobId]);
$wages = $wageStmt->fetchAll();

$totalWagesForJob = array_sum(array_column($wages, 'total_payable'));
$totalPaidForJob = array_sum(array_column($wages, 'amount_paid'));
?>

<!-- Job Header Card -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 border-bottom pb-3 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-secondary font-monospace"><?= e($job['job_code']) ?></span>
                    <?= getCategoryBadge($job['category']) ?>
                    <?= getJobStatusBadge($job['status']) ?>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= e($job['title']) ?></h3>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= getBaseUrl('admin/edit_job.php?id=' . $job['id']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit Order
                </a>
                <?php if ($assignedWorker): ?>
                    <a href="<?= getBaseUrl('admin/calculate_wage.php?job_id=' . $job['id'] . '&worker_id=' . $assignedWorker['worker_id']) ?>" class="btn btn-success btn-sm text-white">
                        <i class="bi bi-calculator me-1"></i>Issue Wage
                    </a>
                <?php else: ?>
                    <a href="<?= getBaseUrl('admin/assignments.php?job_id=' . $job['id']) ?>" class="btn btn-info btn-sm text-dark">
                        <i class="bi bi-person-plus me-1"></i>Assign Worker
                    </a>
                <?php endif; ?>
                <a href="<?= getBaseUrl('admin/jobs.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Job Specs Column -->
            <div class="col-lg-8">
                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase text-muted small mb-2">Scope of Work</h6>
                    <div class="p-3 bg-light rounded-3 text-dark" style="white-space: pre-line;">
                        <?= e($job['description']) ?>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-white">
                            <span class="text-muted small d-block mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Work Location</span>
                            <span class="fw-semibold text-dark"><?= e($job['location']) ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-white">
                            <span class="text-muted small d-block mb-1"><i class="bi bi-cash-stack text-success me-1"></i>Agreed Payment Terms</span>
                            <span class="fw-bold text-success fs-5"><?= formatCurrency($job['agreed_wage']) ?></span>
                            <small class="text-muted ms-1">(<?= e($job['payment_type']) ?>)</small>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase text-muted small mb-2">Schedule Timeline</h6>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 border rounded-3 bg-light">
                                <small class="text-muted d-block">Start Date</small>
                                <strong class="text-dark"><?= formatDate($job['start_date']) ?></strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded-3 bg-light">
                                <small class="text-muted d-block">Target Completion</small>
                                <strong class="text-dark"><?= formatDate($job['expected_completion_date']) ?></strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded-3 bg-light">
                                <small class="text-muted d-block">Actual Sign-off</small>
                                <strong class="<?= $job['actual_completion_date'] ? 'text-success' : 'text-muted' ?>">
                                    <?= formatDate($job['actual_completion_date']) ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($job['notes']): ?>
                    <div>
                        <h6 class="fw-bold text-uppercase text-muted small mb-1">Supervisor Notes</h6>
                        <p class="text-muted small bg-light p-3 rounded-3"><?= e($job['notes']) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Side Cards Column: Assigned Worker & Client -->
            <div class="col-lg-4">
                <!-- Assigned Worker Card -->
                <div class="card border rounded-3 p-3 mb-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-person-badge text-primary me-1"></i>Assigned Craftsman
                        </h6>
                        <?php if ($assignedWorker): ?>
                            <?= getAvailabilityBadge($assignedWorker['availability_status']) ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($assignedWorker): ?>
                        <div class="d-flex align-items-center gap-3 my-2">
                            <div class="user-avatar" style="width: 44px; height: 44px; font-size: 1.1rem; background-color: #2563eb;">
                                <?= strtoupper(substr($assignedWorker['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?= e($assignedWorker['full_name']) ?></h6>
                                <span class="badge bg-secondary font-monospace"><?= e($assignedWorker['worker_code']) ?></span>
                                <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i><?= e($assignedWorker['mobile_number']) ?></small>
                            </div>
                        </div>

                        <ul class="list-group list-group-flush small bg-transparent mt-2">
                            <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                <span class="text-muted">Trade Speciality:</span>
                                <strong><?= e($assignedWorker['worker_category']) ?></strong>
                            </li>
                            <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                <span class="text-muted">Trade Experience:</span>
                                <strong><?= e($assignedWorker['experience_years']) ?> Years</strong>
                            </li>
                            <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                <span class="text-muted">Base Daily Wage:</span>
                                <strong><?= formatCurrency($assignedWorker['worker_daily_wage']) ?></strong>
                            </li>
                            <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                <span class="text-muted">Assigned On:</span>
                                <strong><?= formatDate($assignedWorker['assignment_date']) ?></strong>
                            </li>
                        </ul>

                        <div class="mt-3">
                            <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $assignedWorker['worker_id']) ?>" class="btn btn-sm btn-outline-primary w-100">
                                View Worker Profile & History
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-person-x fs-2 text-warning mb-2"></i>
                            <p class="text-muted small mb-2">No skilled worker is currently allocated to this job order.</p>
                            <a href="<?= getBaseUrl('admin/assignments.php?job_id=' . $job['id']) ?>" class="btn btn-sm btn-primary">
                                Allocate Worker Now
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Client Card -->
                <?php if ($job['client_name'] || $job['client_contact']): ?>
                    <div class="card border rounded-3 p-3 bg-white">
                        <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-building text-secondary me-1"></i>Client Information</h6>
                        <?php if ($job['client_name']): ?>
                            <div class="small fw-semibold text-dark"><?= e($job['client_name']) ?></div>
                        <?php endif; ?>
                        <?php if ($job['client_contact']): ?>
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= e($job['client_contact']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Wage Records for this Job -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-header">
        <h5 class="dashboard-card-title">
            <i class="bi bi-cash-stack text-success"></i> Settlement & Wage Vouchers for this Order
        </h5>
        <?php if ($assignedWorker): ?>
            <a href="<?= getBaseUrl('admin/calculate_wage.php?job_id=' . $job['id'] . '&worker_id=' . $assignedWorker['worker_id']) ?>" class="btn btn-sm btn-success text-white">
                <i class="bi bi-plus-circle me-1"></i>Calculate / Settle Wage
            </a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-workido">
            <thead>
                <tr>
                    <th>Voucher #</th>
                    <th>Worker</th>
                    <th>Structure</th>
                    <th>Working Days</th>
                    <th>Total Payable</th>
                    <th>Amount Paid</th>
                    <th>Payment Status</th>
                    <th>Date Paid</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($wages)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No wage settlements have been created for this work order yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($wages as $w): ?>
                        <tr>
                            <td><span class="font-monospace fw-bold"><?= e($w['voucher_no']) ?></span></td>
                            <td><span class="fw-semibold"><?= e($w['worker_name']) ?></span></td>
                            <td><?= e($w['payment_type']) ?></td>
                            <td><?= $w['working_days'] > 0 ? e($w['working_days']) . ' days' : 'Fixed Lump Sum' ?></td>
                            <td><span class="fw-bold"><?= formatCurrency($w['total_payable']) ?></span></td>
                            <td><span class="fw-bold text-success"><?= formatCurrency($w['amount_paid']) ?></span></td>
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

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
