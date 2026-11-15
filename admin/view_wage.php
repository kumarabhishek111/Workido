<?php
/**
 * Workido - View & Print Wage Slip Voucher
 */

$activeMenu = 'wages';
$pageTitle = 'Wage Slip Voucher';
$pageSubtitle = 'Official payment receipt and audited labor compensation record';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$wageId = (int)($_GET['id'] ?? 0);
if ($wageId <= 0) {
    setFlash('danger', 'Invalid wage voucher identification.');
    header('Location: ' . getBaseUrl('admin/wages.php'));
    exit;
}

// Fetch Wage Record with Worker and Job details
$stmt = $pdo->prepare("
    SELECT wr.*, 
           w.full_name as worker_name, w.worker_code, w.category as worker_category, 
           w.mobile_number, w.address as worker_address,
           j.job_code, j.title as job_title, j.location as job_location
    FROM wage_records wr
    JOIN workers w ON wr.worker_id = w.id
    LEFT JOIN jobs j ON wr.job_id = j.id
    WHERE wr.id = ?
    LIMIT 1
");
$stmt->execute([$wageId]);
$wage = $stmt->fetch();

if (!$wage) {
    setFlash('danger', 'Wage record not found.');
    header('Location: ' . getBaseUrl('admin/wages.php'));
    exit;
}

$balanceDue = max(0, (float)$wage['total_payable'] - (float)$wage['amount_paid']);
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <!-- Print / Action Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="<?= getBaseUrl('admin/wages.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Wage Ledger
            </a>
            <div class="d-flex gap-2">
                <a href="<?= getBaseUrl('admin/calculate_wage.php?worker_id=' . $wage['worker_id']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>New Calculation
                </a>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i>Print Payslip / Voucher
                </button>
            </div>
        </div>

        <!-- Official Payslip Card -->
        <div class="card border shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <!-- Voucher Header -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start border-bottom pb-4 mb-4">
                <div class="d-flex align-items-center gap-3 mb-3 mb-sm-0">
                    <div class="bg-primary text-white p-3 rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-tools fs-3"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-dark">Workido</h3>
                        <p class="text-muted small mb-0">Skilled-Worker Platform &bull; Official Payment Voucher</p>
                    </div>
                </div>

                <div class="text-sm-end">
                    <span class="badge bg-secondary font-monospace fs-6 px-3 py-2 mb-1 d-inline-block"><?= e($wage['voucher_no']) ?></span>
                    <div class="small text-muted">Date: <strong><?= formatDate($wage['payment_date'] ?: $wage['created_at']) ?></strong></div>
                    <div class="mt-1"><?= getPaymentStatusBadge($wage['payment_status']) ?></div>
                </div>
            </div>

            <!-- Worker and Job Information Grid -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <h6 class="text-uppercase text-muted fw-bold small mb-2">Worker Details</h6>
                    <div class="p-3 bg-light rounded-3">
                        <h5 class="fw-bold text-dark mb-1"><?= e($wage['worker_name']) ?></h5>
                        <div class="small text-muted mb-1">
                            <span class="badge bg-secondary font-monospace me-1"><?= e($wage['worker_code']) ?></span>
                            <?= getCategoryBadge($wage['worker_category']) ?>
                        </div>
                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= e($wage['mobile_number']) ?></div>
                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($wage['worker_address']) ?></div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <h6 class="text-uppercase text-muted fw-bold small mb-2">Job Order Assignment</h6>
                    <div class="p-3 bg-light rounded-3">
                        <?php if ($wage['job_id']): ?>
                            <h5 class="fw-bold text-dark mb-1"><?= e($wage['job_title']) ?></h5>
                            <div class="small text-muted mb-1">
                                <span class="badge bg-primary font-monospace"><?= e($wage['job_code']) ?></span>
                            </div>
                            <div class="small text-muted"><i class="bi bi-geo me-1"></i><?= e($wage['job_location']) ?></div>
                        <?php else: ?>
                            <h5 class="fw-bold text-dark mb-1">Direct Labor Settlement</h5>
                            <span class="text-muted small">Ad-hoc workshop maintenance or direct task</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Calculation Breakdown Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Description / Line Item</th>
                            <th class="text-center">Rate / Days</th>
                            <th class="text-end">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Base Earnings</strong>
                                <small class="text-muted d-block"><?= e($wage['payment_type']) ?> payment calculation</small>
                            </td>
                            <td class="text-center">
                                <?php if ($wage['payment_type'] === 'Daily Rate'): ?>
                                    <?= e($wage['working_days']) ?> days @ <?= formatCurrency($wage['daily_wage']) ?>
                                <?php else: ?>
                                    Agreed Fixed Contract
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold"><?= formatCurrency($wage['base_amount']) ?></td>
                        </tr>

                        <?php if ((float)$wage['additional_payment'] > 0): ?>
                            <tr>
                                <td>
                                    <strong class="text-success">Additional Payments / Bonus / Overtime</strong>
                                    <small class="text-muted d-block">Approved supervisor incentive</small>
                                </td>
                                <td class="text-center">—</td>
                                <td class="text-end text-success fw-semibold">+ <?= formatCurrency($wage['additional_payment']) ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if ((float)$wage['deductions'] > 0): ?>
                            <tr>
                                <td>
                                    <strong class="text-danger">Statutory Deductions / Advance Recovery</strong>
                                    <small class="text-muted d-block">Tooling or wage advance adjustment</small>
                                </td>
                                <td class="text-center">—</td>
                                <td class="text-end text-danger fw-semibold">- <?= formatCurrency($wage['deductions']) ?></td>
                            </tr>
                        <?php endif; ?>

                        <tr class="table-light fw-bold fs-6">
                            <td colspan="2" class="text-end">Total Payable Wage:</td>
                            <td class="text-end text-primary"><?= formatCurrency($wage['total_payable']) ?></td>
                        </tr>

                        <tr>
                            <td colspan="2" class="text-end text-success fw-bold">Disbursed / Paid Amount:</td>
                            <td class="text-end text-success fw-bold"><?= formatCurrency($wage['amount_paid']) ?></td>
                        </tr>

                        <tr class="table-light">
                            <td colspan="2" class="text-end fw-bold text-danger">Pending Balance Due:</td>
                            <td class="text-end fw-bold text-danger"><?= formatCurrency($balanceDue) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Payment Metadata Box -->
            <div class="p-3 bg-light rounded-3 mb-4 small">
                <div class="row g-2">
                    <div class="col-sm-4">
                        <span class="text-muted">Payment Mode:</span>
                        <strong class="d-block text-dark"><?= e($wage['payment_method']) ?></strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Payment Date:</span>
                        <strong class="d-block text-dark"><?= formatDate($wage['payment_date']) ?></strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Reference / Transaction ID:</span>
                        <strong class="d-block text-dark"><?= $wage['reference_no'] ? e($wage['reference_no']) : 'N/A' ?></strong>
                    </div>
                    <?php if ($wage['notes']): ?>
                        <div class="col-12 mt-2 pt-2 border-top">
                            <span class="text-muted">Supervisor Remarks:</span>
                            <span class="text-dark d-block"><?= e($wage['notes']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="row pt-5 mt-4 text-center">
                <div class="col-6">
                    <div class="border-top pt-2 mx-auto" style="max-width: 200px;">
                        <small class="text-muted fw-semibold">Worker Signature / Thumb Impression</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border-top pt-2 mx-auto" style="max-width: 200px;">
                        <small class="text-muted fw-semibold">Authorized Supervisor / Workido</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
