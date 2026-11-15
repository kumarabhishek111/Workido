<?php
/**
 * Workido - Reports & Data Export Center
 * Generates interactive reports & downloadable CSV files
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection error. Please run install.php");
}

// -------------------------------------------------------------
// CSV Export Handler
// -------------------------------------------------------------
if (isset($_GET['export'])) {
    $exportType = sanitize($_GET['export']);
    $filename = "workido_" . $exportType . "_report_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($exportType === 'workers') {
        fputcsv($output, ['Worker ID', 'Full Name', 'Category', 'Mobile', 'Email', 'Address', 'Skills', 'Experience (Yrs)', 'Daily Wage (INR)', 'Status', 'Joining Date']);
        $rows = $pdo->query("SELECT worker_code, full_name, category, mobile_number, email, address, skills, experience_years, daily_wage, availability_status, joining_date FROM workers ORDER BY id ASC")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [
                $r['worker_code'], $r['full_name'], $r['category'], $r['mobile_number'],
                $r['email'], $r['address'], $r['skills'], $r['experience_years'],
                $r['daily_wage'], $r['availability_status'], $r['joining_date']
            ]);
        }
    } elseif ($exportType === 'jobs') {
        fputcsv($output, ['Job Code', 'Title', 'Category', 'Location', 'Start Date', 'Expected Completion', 'Actual Completion', 'Payment Type', 'Agreed Wage (INR)', 'Status', 'Client Name', 'Client Contact']);
        $rows = $pdo->query("SELECT job_code, title, category, location, start_date, expected_completion_date, actual_completion_date, payment_type, agreed_wage, status, client_name, client_contact FROM jobs ORDER BY id ASC")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [
                $r['job_code'], $r['title'], $r['category'], $r['location'],
                $r['start_date'], $r['expected_completion_date'], $r['actual_completion_date'],
                $r['payment_type'], $r['agreed_wage'], $r['status'],
                $r['client_name'], $r['client_contact']
            ]);
        }
    } elseif ($exportType === 'wages') {
        fputcsv($output, ['Voucher No', 'Worker Code', 'Worker Name', 'Job Code', 'Payment Type', 'Daily Wage', 'Days', 'Base Amount', 'Extra Bonus', 'Deductions', 'Total Payable', 'Amount Paid', 'Status', 'Payment Method', 'Payment Date', 'Reference']);
        $rows = $pdo->query("
            SELECT wr.*, w.worker_code, w.full_name, j.job_code 
            FROM wage_records wr
            JOIN workers w ON wr.worker_id = w.id
            LEFT JOIN jobs j ON wr.job_id = j.id
            ORDER BY wr.id ASC
        ")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [
                $r['voucher_no'], $r['worker_code'], $r['full_name'], $r['job_code'] ?: 'General',
                $r['payment_type'], $r['daily_wage'], $r['working_days'], $r['base_amount'],
                $r['additional_payment'], $r['deductions'], $r['total_payable'], $r['amount_paid'],
                $r['payment_status'], $r['payment_method'], $r['payment_date'], $r['reference_no']
            ]);
        }
    } elseif ($exportType === 'performance') {
        fputcsv($output, ['Worker Code', 'Worker Name', 'Category', 'Total Assigned Jobs', 'Completed Jobs', 'Pending Jobs', 'Cancelled Jobs', 'Completion Rate (%)', 'Average Rating']);
        $rows = $pdo->query("
            SELECT w.worker_code, w.full_name, w.category,
                COUNT(ja.id) as total_assigned,
                SUM(CASE WHEN j.status = 'Completed' THEN 1 ELSE 0 END) as comp_cnt,
                SUM(CASE WHEN j.status IN ('Assigned', 'In Progress', 'Pending') THEN 1 ELSE 0 END) as pend_cnt,
                SUM(CASE WHEN j.status = 'Cancelled' THEN 1 ELSE 0 END) as canc_cnt,
                (SELECT AVG(rating) FROM performance_records pr WHERE pr.worker_id = w.id) as avg_rating
            FROM workers w
            LEFT JOIN job_assignments ja ON w.id = ja.worker_id
            LEFT JOIN jobs j ON ja.job_id = j.id
            GROUP BY w.id ORDER BY w.id ASC
        ")->fetchAll();
        foreach ($rows as $r) {
            $tot = (int)$r['total_assigned'];
            $comp = (int)$r['comp_cnt'];
            $rate = calculateCompletionRate($comp, $tot);
            fputcsv($output, [
                $r['worker_code'], $r['full_name'], $r['category'],
                $tot, $comp, (int)$r['pend_cnt'], (int)$r['canc_cnt'],
                $rate . '%', $r['avg_rating'] ? number_format((float)$r['avg_rating'], 1) : 'N/A'
            ]);
        }
    }

    fclose($output);
    exit;
}

// -------------------------------------------------------------
// Browser View Report
// -------------------------------------------------------------
$activeMenu = 'reports';
$pageTitle = 'Audited Reports & Data Export';
$pageSubtitle = 'Comprehensive reports on workers, job orders, wage disbursements, and verified trade performance';

$reportTab = sanitize($_GET['tab'] ?? 'workers');
$categoryFilter = sanitize($_GET['category'] ?? '');
$fromDate = sanitize($_GET['from_date'] ?? '');
$toDate = sanitize($_GET['to_date'] ?? '');

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Report Selector Tabs & Filter Header -->
<div class="dashboard-card mb-4 no-print">
    <div class="dashboard-card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?= $reportTab === 'workers' ? 'active' : '' ?>" href="?tab=workers">
                        <i class="bi bi-people-fill me-1"></i>Workers Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $reportTab === 'jobs' ? 'active' : '' ?>" href="?tab=jobs">
                        <i class="bi bi-briefcase-fill me-1"></i>Work Orders Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $reportTab === 'wages' ? 'active' : '' ?>" href="?tab=wages">
                        <i class="bi bi-cash-stack me-1"></i>Wages & Payroll
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $reportTab === 'performance' ? 'active' : '' ?>" href="?tab=performance">
                        <i class="bi bi-star-fill me-1"></i>Performance Scorecards
                    </a>
                </li>
            </ul>

            <div class="d-flex gap-2">
                <a href="?export=<?= e($reportTab) ?>" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i>Download CSV
                </a>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Report Content -->
<?php if ($reportTab === 'workers'): ?>
    <?php
    $wRows = $pdo->query("SELECT * FROM workers ORDER BY category ASC, id ASC")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h5 class="dashboard-card-title">
                <i class="bi bi-people-fill text-primary"></i> Master Skilled Worker Directory Report
            </h5>
            <span class="badge bg-secondary font-monospace"><?= count($wRows) ?> Registered</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Phone</th>
                        <th>Skills</th>
                        <th>Experience</th>
                        <th>Daily Wage</th>
                        <th>Status</th>
                        <th>Joining Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wRows as $row): ?>
                        <tr>
                            <td class="font-monospace fw-bold"><?= e($row['worker_code']) ?></td>
                            <td class="fw-semibold text-dark"><?= e($row['full_name']) ?></td>
                            <td><?= getCategoryBadge($row['category']) ?></td>
                            <td><?= e($row['mobile_number']) ?></td>
                            <td><?= e($row['skills']) ?></td>
                            <td><?= e($row['experience_years']) ?> Yrs</td>
                            <td class="fw-bold"><?= formatCurrency($row['daily_wage']) ?></td>
                            <td><?= getAvailabilityBadge($row['availability_status']) ?></td>
                            <td><?= formatDate($row['joining_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportTab === 'jobs'): ?>
    <?php
    $jRows = $pdo->query("
        SELECT j.*, w.full_name as worker_name, w.worker_code 
        FROM jobs j
        LEFT JOIN job_assignments ja ON j.id = ja.job_id
        LEFT JOIN workers w ON ja.worker_id = w.id
        ORDER BY j.id DESC
    ")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h5 class="dashboard-card-title">
                <i class="bi bi-briefcase-fill text-primary"></i> Work Orders & Assignment Status Report
            </h5>
            <span class="badge bg-secondary font-monospace"><?= count($jRows) ?> Total Orders</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Job Title</th>
                        <th>Trade</th>
                        <th>Assigned Worker</th>
                        <th>Location</th>
                        <th>Start Date</th>
                        <th>Completion</th>
                        <th>Agreed Wage</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jRows as $row): ?>
                        <tr>
                            <td class="font-monospace fw-bold"><?= e($row['job_code']) ?></td>
                            <td class="fw-semibold text-dark"><?= e($row['title']) ?></td>
                            <td><?= getCategoryBadge($row['category']) ?></td>
                            <td><?= $row['worker_name'] ? e($row['worker_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                            <td><?= e($row['location']) ?></td>
                            <td><?= formatDate($row['start_date']) ?></td>
                            <td><?= formatDate($row['actual_completion_date'] ?: $row['expected_completion_date']) ?></td>
                            <td class="fw-bold"><?= formatCurrency($row['agreed_wage']) ?> (<?= e($row['payment_type']) ?>)</td>
                            <td><?= getJobStatusBadge($row['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportTab === 'wages'): ?>
    <?php
    $wgRows = $pdo->query("
        SELECT wr.*, w.full_name as worker_name, w.worker_code, j.job_code, j.title as job_title
        FROM wage_records wr
        JOIN workers w ON wr.worker_id = w.id
        LEFT JOIN jobs j ON wr.job_id = j.id
        ORDER BY wr.id DESC
    ")->fetchAll();

    $sumPayable = array_sum(array_column($wgRows, 'total_payable'));
    $sumPaid = array_sum(array_column($wgRows, 'amount_paid'));
    ?>
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <div>
                <h5 class="dashboard-card-title mb-0">
                    <i class="bi bi-cash-stack text-success"></i> Comprehensive Payroll & Settlement Ledger
                </h5>
                <small class="text-muted">Billed: <?= formatCurrency($sumPayable) ?> | Settled: <?= formatCurrency($sumPaid) ?> | Pending: <?= formatCurrency($sumPayable - $sumPaid) ?></small>
            </div>
            <span class="badge bg-secondary font-monospace"><?= count($wgRows) ?> Vouchers</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Voucher #</th>
                        <th>Worker</th>
                        <th>Job Order</th>
                        <th>Type & Days</th>
                        <th>Base Amount</th>
                        <th>Extra / Ded.</th>
                        <th>Net Payable</th>
                        <th>Amount Paid</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Ref No</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wgRows as $row): ?>
                        <tr>
                            <td class="font-monospace fw-bold"><?= e($row['voucher_no']) ?></td>
                            <td class="fw-semibold text-dark"><?= e($row['worker_name']) ?></td>
                            <td><?= $row['job_code'] ? e($row['job_code']) : 'General' ?></td>
                            <td><?= e($row['payment_type']) ?> (<?= e($row['working_days']) ?> d)</td>
                            <td><?= formatCurrency($row['base_amount']) ?></td>
                            <td>+<?= formatCurrency($row['additional_payment']) ?> / -<?= formatCurrency($row['deductions']) ?></td>
                            <td class="fw-bold"><?= formatCurrency($row['total_payable']) ?></td>
                            <td class="fw-bold text-success"><?= formatCurrency($row['amount_paid']) ?></td>
                            <td><?= getPaymentStatusBadge($row['payment_status']) ?></td>
                            <td><?= formatDate($row['payment_date']) ?></td>
                            <td><?= $row['reference_no'] ? e($row['reference_no']) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportTab === 'performance'): ?>
    <?php
    $pfRows = $pdo->query("
        SELECT w.*,
            COUNT(ja.id) as total_assigned,
            SUM(CASE WHEN j.status = 'Completed' THEN 1 ELSE 0 END) as comp_cnt,
            SUM(CASE WHEN j.status IN ('Assigned', 'In Progress', 'Pending') THEN 1 ELSE 0 END) as pend_cnt,
            SUM(CASE WHEN j.status = 'Cancelled' THEN 1 ELSE 0 END) as canc_cnt,
            (SELECT AVG(rating) FROM performance_records pr WHERE pr.worker_id = w.id) as avg_rating
        FROM workers w
        LEFT JOIN job_assignments ja ON w.id = ja.worker_id
        LEFT JOIN jobs j ON ja.job_id = j.id
        GROUP BY w.id ORDER BY avg_rating DESC
    ")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h5 class="dashboard-card-title">
                <i class="bi bi-star-fill text-warning"></i> Worker Performance & Completion Rate Scorecards
            </h5>
            <span class="badge bg-secondary font-monospace"><?= count($pfRows) ?> Workers Evaluated</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Worker Name</th>
                        <th>Category</th>
                        <th class="text-center">Assigned Jobs</th>
                        <th class="text-center">Completed</th>
                        <th class="text-center">Pending / In Progress</th>
                        <th class="text-center">Cancelled</th>
                        <th>Completion %</th>
                        <th>Quality Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pfRows as $row): 
                        $assigned = (int)$row['total_assigned'];
                        $comp = (int)$row['comp_cnt'];
                        $compRate = calculateCompletionRate($comp, $assigned);
                        $rating = $row['avg_rating'] ? (float)$row['avg_rating'] : null;
                    ?>
                        <tr>
                            <td class="font-monospace fw-bold"><?= e($row['worker_code']) ?></td>
                            <td class="fw-semibold text-dark"><?= e($row['full_name']) ?></td>
                            <td><?= getCategoryBadge($row['category']) ?></td>
                            <td class="text-center"><?= $assigned ?></td>
                            <td class="text-center text-success fw-bold"><?= $comp ?></td>
                            <td class="text-center"><?= (int)$row['pend_cnt'] ?></td>
                            <td class="text-center text-danger"><?= (int)$row['canc_cnt'] ?></td>
                            <td>
                                <strong><?= $compRate ?>%</strong>
                            </td>
                            <td>
                                <?= $rating !== null ? number_format($rating, 1) . ' / 5.0' : 'N/A' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
