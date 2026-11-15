<?php
/**
 * Workido - Customer: View Work Order Details & Assigned Craftsman
 */

$activeCustomerMenu = 'my_jobs';
$customerPageTitle = 'Booking Details';

require_once __DIR__ . '/../includes/customer_header.php';

$pdo = getDBConnection();
$customerId = $user['id'];

$jobId = (int)($_GET['id'] ?? 0);
if ($jobId <= 0) {
    setFlash('danger', 'Invalid booking identification.');
    header('Location: ' . getBaseUrl('customer/my_jobs.php'));
    exit;
}

// Fetch Job ensuring it belongs to this customer
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND customer_id = ? LIMIT 1");
$stmt->execute([$jobId, $customerId]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('danger', 'Work order record not found or does not belong to your account.');
    header('Location: ' . getBaseUrl('customer/my_jobs.php'));
    exit;
}

// Fetch Assigned Worker
$assignStmt = $pdo->prepare("
    SELECT ja.*, w.id as worker_id, w.full_name as worker_name, w.worker_code, w.mobile_number as worker_phone,
           w.category as worker_category, w.daily_wage as worker_daily_wage, w.skills as worker_skills,
           w.experience_years as worker_exp, w.availability_status
    FROM job_assignments ja
    JOIN workers w ON ja.worker_id = w.id
    WHERE ja.job_id = ?
    ORDER BY ja.id DESC LIMIT 1
");
$assignStmt->execute([$jobId]);
$assignedWorker = $assignStmt->fetch();

// Check if review exists for this job
$revStmt = $pdo->prepare("SELECT * FROM performance_records WHERE job_id = ? LIMIT 1");
$revStmt->execute([$jobId]);
$existingReview = $revStmt->fetch();
?>

<!-- Header Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?= getBaseUrl('customer/my_jobs.php') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to My Bookings
    </a>
    <?php if ($job['status'] === 'Completed' && $assignedWorker && !$existingReview): ?>
        <a href="<?= getBaseUrl('customer/review_worker.php?job_id=' . $job['id'] . '&worker_id=' . $assignedWorker['worker_id']) ?>" class="btn btn-warning btn-sm text-dark fw-bold">
            <i class="bi bi-star-fill me-1"></i>Rate & Review Craftsman
        </a>
    <?php endif; ?>
</div>

<!-- Main Details Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 border-bottom pb-4 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-secondary font-monospace"><?= e($job['job_code']) ?></span>
                <?= getCategoryBadge($job['category']) ?>
                <?= getJobStatusBadge($job['status']) ?>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= e($job['title']) ?></h3>
        </div>

        <div>
            <span class="text-muted small d-block">Agreed Compensation</span>
            <span class="fw-bold fs-4 text-primary"><?= formatCurrency($job['agreed_wage']) ?></span>
            <small class="text-muted">(<?= e($job['payment_type']) ?>)</small>
        </div>
    </div>

    <div class="row g-4">
        <!-- Scope and Timeline -->
        <div class="col-lg-7">
            <div class="mb-4">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Scope of Work</h6>
                <div class="p-3 bg-light rounded-3 text-dark" style="white-space: pre-line;">
                    <?= e($job['description']) ?>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold text-uppercase text-muted small mb-2"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Work Location</h6>
                <div class="p-3 border rounded-3 bg-white">
                    <span class="fw-semibold text-dark"><?= e($job['location']) ?></span>
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
                            <small class="text-muted d-block">Actual Completion</small>
                            <strong class="<?= $job['actual_completion_date'] ? 'text-success' : 'text-muted' ?>">
                                <?= formatDate($job['actual_completion_date']) ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($job['notes']): ?>
                <div>
                    <h6 class="fw-bold text-uppercase text-muted small mb-1">Your Special Instructions</h6>
                    <p class="text-muted small bg-light p-3 rounded-3"><?= e($job['notes']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Assigned Craftsman Card -->
        <div class="col-lg-5">
            <div class="card border rounded-4 p-4 bg-light h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-3">
                    <i class="bi bi-person-badge-fill text-primary me-1"></i>Assigned Tradesman
                </h6>

                <?php if ($assignedWorker): ?>
                    <div class="text-center mb-3">
                        <div class="user-avatar mx-auto mb-2" style="width: 64px; height: 64px; font-size: 1.6rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                            <?= strtoupper(substr($assignedWorker['worker_name'], 0, 1)) ?>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark"><?= e($assignedWorker['worker_name']) ?></h5>
                        <span class="badge bg-secondary font-monospace small"><?= e($assignedWorker['worker_code']) ?></span>
                        <div class="mt-2">
                            <?= getCategoryBadge($assignedWorker['worker_category']) ?>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush small bg-transparent mb-3">
                        <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                            <span class="text-muted">Direct Phone:</span>
                            <strong class="text-primary"><i class="bi bi-telephone-fill me-1"></i><?= e($assignedWorker['worker_phone']) ?></strong>
                        </li>
                        <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                            <span class="text-muted">Experience:</span>
                            <strong><?= e($assignedWorker['worker_exp']) ?> Years</strong>
                        </li>
                        <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                            <span class="text-muted">Assigned Date:</span>
                            <strong><?= formatDate($assignedWorker['assignment_date']) ?></strong>
                        </li>
                    </ul>

                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Specialized Skills:</small>
                        <p class="small text-dark mb-0"><?= e($assignedWorker['worker_skills']) ?></p>
                    </div>

                    <div class="mt-4 pt-3 border-top text-center">
                        <a href="tel:<?= e($assignedWorker['worker_phone']) ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-telephone-outbound me-1"></i>Call Craftsman Directly
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history display-4 text-warning mb-2 d-block"></i>
                        <h6 class="fw-bold text-dark">Matching in Progress</h6>
                        <p class="text-muted small mb-0">Our operations supervisor is assigning the best qualified and available <?= e($job['category']) ?> for your project.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Review & Feedback Section -->
<?php if ($existingReview): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-star-fill text-warning me-2"></i>Your Quality Rating & Review</h5>
        <div class="p-3 bg-light rounded-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-warning text-dark fw-bold">
                    <i class="bi bi-star-fill me-1"></i><?= number_format((float)$existingReview['rating'], 1) ?> / 5.0
                </span>
                <small class="text-muted">Reviewed on <?= formatDate($existingReview['review_date']) ?></small>
            </div>
            <p class="mb-0 text-dark">"<?= e($existingReview['remarks']) ?>"</p>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/customer_footer.php'; ?>
