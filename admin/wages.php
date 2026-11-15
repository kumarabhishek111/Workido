<?php
/**
 * Workido - Wage Records & Payroll Management
 */

$activeMenu = 'wages';
$pageTitle = 'Wage Records & Payroll';
$pageSubtitle = 'Monitor skilled-worker wage disbursements, pending settlements, and payroll ledgers';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

// 1. Filter Parameters
$statusFilter = sanitize($_GET['status'] ?? '');
$workerFilter = (int)($_GET['worker_id'] ?? 0);
$search = sanitize($_GET['search'] ?? '');

// 2. Build Query
$sql = "
    SELECT wr.*, 
           w.full_name as worker_name, w.worker_code, w.category as worker_category, w.mobile_number,
           j.job_code, j.title as job_title
    FROM wage_records wr
    JOIN workers w ON wr.worker_id = w.id
    LEFT JOIN jobs j ON wr.job_id = j.id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND wr.payment_status = ?";
    $params[] = $statusFilter;
}

if ($workerFilter > 0) {
    $sql .= " AND wr.worker_id = ?";
    $params[] = $workerFilter;
}

if ($search !== '') {
    $sql .= " AND (wr.voucher_no LIKE ? OR w.full_name LIKE ? OR j.title LIKE ? OR wr.reference_no LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY wr.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$wageRecords = $stmt->fetchAll();

// Dynamic Overall Financial Totals
$totalsStmt = $pdo->query("
    SELECT 
        COALESCE(SUM(total_payable), 0) as total_payable_sum,
        COALESCE(SUM(amount_paid), 0) as total_paid_sum
    FROM wage_records
");
$totals = $totalsStmt->fetch();
$totalPayable = (float)$totals['total_payable_sum'];
$totalPaid = (float)$totals['total_paid_sum'];
$totalPending = max(0, $totalPayable - $totalPaid);

// Fetch workers for dropdown
$workersList = $pdo->query("SELECT id, full_name, worker_code FROM workers ORDER BY full_name ASC")->fetchAll();
?>

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Total Wages Payable -->
    <div class="col-md-4">
        <div class="stat-card card-blue p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Wages Payable</span>
                    <div class="stat-value text-dark"><?= formatCurrency($totalPayable) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <small class="text-muted d-block mt-2">Cumulative payroll billing across all work orders</small>
        </div>
    </div>

    <!-- Total Wages Paid -->
    <div class="col-md-4">
        <div class="stat-card card-emerald p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Wages Disbursed / Paid</span>
                    <div class="stat-value text-success"><?= formatCurrency($totalPaid) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <small class="text-success d-block mt-2">
                <?= $totalPayable > 0 ? round(($totalPaid / $totalPayable) * 100, 1) : 0 ?>% of total wages settled
            </small>
        </div>
    </div>

    <!-- Total Wages Pending -->
    <div class="col-md-4">
        <div class="stat-card card-amber p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Pending / Outstanding Wages</span>
                    <div class="stat-value text-danger"><?= formatCurrency($totalPending) ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <small class="text-muted d-block mt-2">Awaiting final sign-off or cash clearance</small>
        </div>
    </div>
</div>

<!-- Filter & Search Toolbar -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search voucher #, worker, job, ref..." value="<?= e($search) ?>" data-table-search="#wagesTable">
                    <?php if ($search !== ''): ?>
                        <a href="<?= getBaseUrl('admin/wages.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Payment Statuses</option>
                    <option value="Paid" <?= $statusFilter === 'Paid' ? 'selected' : '' ?>>Paid (Settled)</option>
                    <option value="Partially Paid" <?= $statusFilter === 'Partially Paid' ? 'selected' : '' ?>>Partially Paid (Advance)</option>
                    <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending (Unpaid)</option>
                </select>
            </div>

            <div class="col-sm-6 col-md-3">
                <select name="worker_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Workers</option>
                    <?php foreach ($workersList as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $workerFilter == $w['id'] ? 'selected' : '' ?>>
                            <?= e($w['full_name']) ?> (<?= e($w['worker_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
                <a href="<?= getBaseUrl('admin/wages.php') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Wage Records Table Card -->
<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="d-flex align-items-center gap-2">
            <h5 class="dashboard-card-title mb-0">
                <i class="bi bi-cash-stack text-success"></i> Wage Vouchers & Payroll Ledger
            </h5>
            <span class="badge bg-secondary rounded-pill"><?= count($wageRecords) ?> Vouchers</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= getBaseUrl('admin/calculate_wage.php') ?>" class="btn btn-success btn-sm text-white">
                <i class="bi bi-calculator me-1"></i>Calculate New Wage
            </a>
            <a href="<?= getBaseUrl('admin/reports.php?export=wages') ?>" class="btn btn-outline-secondary btn-sm" title="Export Wages CSV">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-workido" id="wagesTable">
            <thead>
                <tr>
                    <th>Voucher No</th>
                    <th>Worker Name</th>
                    <th>Job Order</th>
                    <th>Structure & Days</th>
                    <th>Base Amount</th>
                    <th>Extra / Ded.</th>
                    <th>Total Payable</th>
                    <th>Amount Paid</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th class="text-end">Payslip</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($wageRecords)): ?>
                    <tr class="no-filter">
                        <td colspan="11" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-cash-stack fs-1"></i></div>
                            <h6 class="fw-bold text-dark">No wage records found</h6>
                            <p class="text-muted small">Calculate a new wage settlement for a completed or ongoing work order.</p>
                            <a href="<?= getBaseUrl('admin/calculate_wage.php') ?>" class="btn btn-sm btn-success text-white">Calculate Wage Now</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($wageRecords as $wr): ?>
                        <tr>
                            <td>
                                <a href="<?= getBaseUrl('admin/view_wage.php?id=' . $wr['id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                    <?= e($wr['voucher_no']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $wr['worker_id']) ?>" class="fw-semibold text-dark text-decoration-none d-block">
                                    <?= e($wr['worker_name']) ?>
                                </a>
                                <small class="text-muted"><?= getCategoryBadge($wr['worker_category']) ?></small>
                            </td>
                            <td>
                                <?php if ($wr['job_id']): ?>
                                    <a href="<?= getBaseUrl('admin/view_job.php?id=' . $wr['job_id']) ?>" class="fw-semibold text-dark text-decoration-none d-block small">
                                        <?= e($wr['job_code']) ?>
                                    </a>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 140px;" title="<?= e($wr['job_title']) ?>">
                                        <?= e($wr['job_title']) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted small">General Job</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="d-block fw-semibold"><?= e($wr['payment_type']) ?></small>
                                <?php if ($wr['payment_type'] === 'Daily Rate'): ?>
                                    <small class="text-muted"><?= e($wr['working_days']) ?> days @ <?= formatCurrency($wr['daily_wage']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="fw-semibold"><?= formatCurrency($wr['base_amount']) ?></span></td>
                            <td>
                                <small class="d-block text-success">+<?= formatCurrency($wr['additional_payment']) ?></small>
                                <small class="d-block text-danger">-<?= formatCurrency($wr['deductions']) ?></small>
                            </td>
                            <td>
                                <span class="fw-bold text-dark fs-6"><?= formatCurrency($wr['total_payable']) ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-success"><?= formatCurrency($wr['amount_paid']) ?></span>
                            </td>
                            <td><?= getPaymentStatusBadge($wr['payment_status']) ?></td>
                            <td>
                                <small class="text-muted"><?= formatDate($wr['payment_date']) ?></small>
                                <?php if ($wr['payment_method']): ?>
                                    <span class="badge bg-light text-secondary border d-block mt-1" style="font-size: 0.7rem;"><?= e($wr['payment_method']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= getBaseUrl('admin/view_wage.php?id=' . $wr['id']) ?>" class="btn btn-sm btn-light border btn-action" title="View & Print Slip">
                                    <i class="bi bi-printer"></i>
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
