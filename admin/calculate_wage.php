<?php
/**
 * Workido - Wage Calculation & Settlement Generator
 * Formula:
 *   Daily: Total = Daily Wage × Days + Additional Payment − Deductions
 *   Fixed: Total = Agreed Amount + Additional Payment − Deductions
 */

$activeMenu = 'calculate_wage';
$pageTitle = 'Calculate & Disburse Wages';
$pageSubtitle = 'Compute labor earnings, overtime bonuses, advances, and issue formal payment vouchers';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$voucherNo = generateVoucherNo($pdo);
$error = '';

$preselectedWorkerId = (int)($_GET['worker_id'] ?? 0);
$preselectedJobId = (int)($_GET['job_id'] ?? 0);

// Fetch all workers
$workersStmt = $pdo->query("SELECT id, full_name, worker_code, category, daily_wage FROM workers ORDER BY full_name ASC");
$allWorkers = $workersStmt->fetchAll();

// Fetch jobs
$jobsStmt = $pdo->query("SELECT id, job_code, title, category, payment_type, agreed_wage, status FROM jobs ORDER BY id DESC");
$allJobs = $jobsStmt->fetchAll();

// If a job was preselected, check its details
$initialPaymentType = 'Daily Rate';
$initialDailyWage = '800.00';
if ($preselectedJobId > 0) {
    foreach ($allJobs as $jb) {
        if ($jb['id'] == $preselectedJobId) {
            $initialPaymentType = $jb['payment_type'];
            $initialDailyWage = $jb['agreed_wage'];
            break;
        }
    }
} elseif ($preselectedWorkerId > 0) {
    foreach ($allWorkers as $wk) {
        if ($wk['id'] == $preselectedWorkerId) {
            $initialDailyWage = $wk['daily_wage'];
            break;
        }
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $workerId = (int)($_POST['worker_id'] ?? 0);
        $jobId = (int)($_POST['job_id'] ?? 0);
        $paymentType = sanitize($_POST['payment_type'] ?? 'Daily Rate');
        $dailyWage = (float)($_POST['daily_wage'] ?? 0);
        $workingDays = (float)($_POST['working_days'] ?? 0);
        $additionalPayment = (float)($_POST['additional_payment'] ?? 0);
        $deductions = (float)($_POST['deductions'] ?? 0);
        $amountPaid = (float)($_POST['amount_paid'] ?? 0);
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'Cash');
        $paymentStatus = sanitize($_POST['payment_status'] ?? 'Pending');
        $paymentDate = sanitize($_POST['payment_date'] ?? '');
        $referenceNo = sanitize($_POST['reference_no'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        // Validation
        if ($workerId <= 0) {
            $error = 'Please select a valid skilled worker.';
        } elseif ($dailyWage <= 0) {
            $error = 'Please enter a valid wage amount greater than 0.';
        } elseif ($paymentType === 'Daily Rate' && $workingDays <= 0) {
            $error = 'Please specify the number of working days for a Daily Rate calculation.';
        } else {
            // STRICT SERVER-SIDE CALCULATION
            if ($paymentType === 'Daily Rate') {
                $baseAmount = $dailyWage * $workingDays;
            } else {
                $baseAmount = $dailyWage; // Fixed contract agreed amount
                $workingDays = 0.0;
            }

            $totalPayable = max(0, ($baseAmount + $additionalPayment) - $deductions);

            // Auto determine payment status if amount paid is provided
            if ($amountPaid >= $totalPayable && $totalPayable > 0) {
                $paymentStatus = 'Paid';
                if (!$paymentDate) $paymentDate = date('Y-m-d');
            } elseif ($amountPaid > 0 && $amountPaid < $totalPayable) {
                $paymentStatus = 'Partially Paid';
                if (!$paymentDate) $paymentDate = date('Y-m-d');
            } elseif ($amountPaid == 0) {
                $paymentStatus = 'Pending';
                $paymentDate = null;
            }

            try {
                $currentUser = getCurrentUser();
                $createdBy = $currentUser['id'] ?? null;

                // Re-verify unique voucher no
                $chk = $pdo->prepare("SELECT id FROM wage_records WHERE voucher_no = ?");
                $chk->execute([$voucherNo]);
                if ($chk->fetch()) {
                    $voucherNo = generateVoucherNo($pdo);
                }

                $stmt = $pdo->prepare("
                    INSERT INTO wage_records 
                    (voucher_no, worker_id, job_id, payment_type, daily_wage, working_days, base_amount, additional_payment, deductions, total_payable, payment_status, amount_paid, payment_method, payment_date, reference_no, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $voucherNo,
                    $workerId,
                    $jobId > 0 ? $jobId : null,
                    $paymentType,
                    $dailyWage,
                    $workingDays,
                    $baseAmount,
                    $additionalPayment,
                    $deductions,
                    $totalPayable,
                    $paymentStatus,
                    $amountPaid,
                    $paymentMethod,
                    $paymentDate ?: null,
                    $referenceNo ?: null,
                    $notes ?: null,
                    $createdBy
                ]);

                $newWageId = $pdo->lastInsertId();
                setFlash('success', "Wage Voucher {$voucherNo} for " . formatCurrency($totalPayable) . " was successfully generated!");
                header('Location: ' . getBaseUrl('admin/view_wage.php?id=' . $newWageId));
                exit;

            } catch (Exception $e) {
                $error = 'Failed to record wage voucher: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row g-4">
    <!-- Calculation Form -->
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h5 class="dashboard-card-title">
                    <i class="bi bi-calculator-fill text-success"></i> Wage Calculation Form
                </h5>
                <span class="badge bg-secondary font-monospace"><?= e($voucherNo) ?></span>
            </div>

            <div class="dashboard-card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate id="wageForm">
                    <?= csrfField() ?>

                    <div class="row g-3 mb-4">
                        <!-- Worker Selection -->
                        <div class="col-md-6">
                            <label class="form-label" for="worker_id">Select Skilled Worker *</label>
                            <select id="worker_id" name="worker_id" class="form-select" required>
                                <option value="">-- Choose Worker --</option>
                                <?php foreach ($allWorkers as $w): ?>
                                    <option value="<?= $w['id'] ?>" 
                                            data-daily-wage="<?= e($w['daily_wage']) ?>" 
                                            data-category="<?= e($w['category']) ?>"
                                            <?= $preselectedWorkerId == $w['id'] ? 'selected' : '' ?>>
                                        <?= e($w['full_name']) ?> (<?= e($w['worker_code']) ?> - <?= e($w['category']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Job Selection -->
                        <div class="col-md-6">
                            <label class="form-label" for="job_id">Associated Work Order (Optional)</label>
                            <select id="job_id" name="job_id" class="form-select">
                                <option value="">-- General Maintenance / Direct Settlement --</option>
                                <?php foreach ($allJobs as $j): ?>
                                    <option value="<?= $j['id'] ?>" 
                                            data-payment-type="<?= e($j['payment_type']) ?>" 
                                            data-agreed-wage="<?= e($j['agreed_wage']) ?>"
                                            <?= $preselectedJobId == $j['id'] ? 'selected' : '' ?>>
                                        [<?= e($j['job_code']) ?>] <?= e($j['title']) ?> (<?= e($j['status']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Payment Type -->
                        <div class="col-md-4">
                            <label class="form-label" for="payment_type">Payment Structure *</label>
                            <select id="payment_type" name="payment_type" class="form-select" required>
                                <option value="Daily Rate" <?= $initialPaymentType === 'Daily Rate' ? 'selected' : '' ?>>Daily Rate (Wage × Days)</option>
                                <option value="Fixed Price" <?= $initialPaymentType === 'Fixed Price' ? 'selected' : '' ?>>Fixed Price / Lump Sum</option>
                            </select>
                        </div>

                        <!-- Daily Wage Rate -->
                        <div class="col-md-4" id="daily_wage_group">
                            <label class="form-label" for="daily_wage">Daily Wage Rate (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="10" min="100" id="daily_wage" name="daily_wage" class="form-control fw-bold" value="<?= e($initialDailyWage) ?>" required>
                            </div>
                        </div>

                        <!-- Working Days -->
                        <div class="col-md-4" id="working_days_group">
                            <label class="form-label" for="working_days">Working Days *</label>
                            <input type="number" step="0.5" min="0.5" max="365" id="working_days" name="working_days" class="form-control fw-bold" value="5.0" required>
                        </div>

                        <!-- Additional Payment -->
                        <div class="col-md-6">
                            <label class="form-label" for="additional_payment">Additional Payment / Bonus / Overtime (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text text-success">+ ₹</span>
                                <input type="number" step="10" min="0" id="additional_payment" name="additional_payment" class="form-control" value="0.00">
                            </div>
                            <div class="form-text">Overtime allowances, early completion incentive, travel.</div>
                        </div>

                        <!-- Deductions -->
                        <div class="col-md-6">
                            <label class="form-label" for="deductions">Deductions / Advances / Penalties (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text text-danger">- ₹</span>
                                <input type="number" step="10" min="0" id="deductions" name="deductions" class="form-control" value="0.00">
                            </div>
                            <div class="form-text">Salary advance recovery, safety gear, damage adjustments.</div>
                        </div>
                    </div>

                    <!-- Payment Disbursement Details -->
                    <h6 class="fw-bold text-dark border-top pt-3 mb-3">Disbursement & Settlement Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" for="amount_paid">Amount Paid Now (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="10" min="0" id="amount_paid" name="amount_paid" class="form-control fw-bold text-success" value="0.00">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="payment_status">Payment Status *</label>
                            <select id="payment_status" name="payment_status" class="form-select fw-bold" required>
                                <option value="Pending">Pending (Unpaid)</option>
                                <option value="Partially Paid">Partially Paid (Advance)</option>
                                <option value="Paid">Paid (Full Settlement)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method" class="form-select">
                                <option value="Cash">Cash in Hand</option>
                                <option value="Bank Transfer">Bank Transfer (NEFT/IMPS)</option>
                                <option value="UPI">UPI / Digital Payment</option>
                                <option value="Cheque">Bank Cheque</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="payment_date">Payment Date</label>
                            <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="reference_no">Transaction Ref / Cheque No / Receipt ID</label>
                            <input type="text" id="reference_no" name="reference_no" class="form-control" placeholder="e.g. UPI-TXN-98212 or CASH-REC-01">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="notes">Voucher Remarks</label>
                            <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Supervisor notes on work verification and settlement approval..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= getBaseUrl('admin/wages.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success px-4 text-white">
                            <i class="bi bi-check2-circle me-1"></i>Generate & Record Wage Voucher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Live Calculation Preview Sidebar -->
    <div class="col-lg-4">
        <div class="dashboard-card sticky-top" style="top: 80px;">
            <div class="dashboard-card-header bg-dark text-white">
                <h5 class="dashboard-card-title text-white">
                    <i class="bi bi-receipt"></i> Real-time Wage Breakdown
                </h5>
            </div>
            <div class="dashboard-card-body p-4">
                <div class="mb-3 text-center">
                    <small class="text-muted text-uppercase fw-bold">Calculated Net Payable</small>
                    <div class="display-6 fw-bold text-primary mt-1" id="preview_total_payable">₹ 0.00</div>
                    <span class="badge bg-light text-muted border mt-1">Verified Server Formula</span>
                </div>

                <ul class="list-group list-group-flush mb-4 small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Base Wages:</span>
                        <strong class="text-dark" id="preview_base_amount">₹ 0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Additional Payments:</span>
                        <strong class="text-success" id="preview_additional">+ ₹ 0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Deductions:</span>
                        <strong class="text-danger" id="preview_deductions">- ₹ 0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 fw-bold border-top">
                        <span class="text-dark">Net Payable:</span>
                        <span class="text-primary fs-6" id="preview_total_payable">₹ 0.00</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Pending Balance:</span>
                        <strong class="text-danger" id="preview_pending_balance">₹ 0.00</strong>
                    </li>
                </ul>

                <div class="p-3 bg-light rounded-3 border small">
                    <div class="fw-bold mb-1"><i class="bi bi-shield-check text-success me-1"></i>Server-side Enforcement</div>
                    <p class="text-muted mb-0">Even if frontend values are modified in developer tools, the PHP backend recalculates all math with exact floating-point precision before writing to MySQL.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load interactive calculation script -->
<script src="<?= getBaseUrl('assets/js/wage_calc.js') ?>"></script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
