<?php
/**
 * Workido - Customer: Rate & Review Completed Work
 */

$activeCustomerMenu = 'my_jobs';
$customerPageTitle = 'Rate & Review Craftsman';

require_once __DIR__ . '/../includes/customer_header.php';

$pdo = getDBConnection();
$customerId = $user['id'];

$jobId = (int)($_GET['job_id'] ?? $_POST['job_id'] ?? 0);
$workerId = (int)($_GET['worker_id'] ?? $_POST['worker_id'] ?? 0);

if ($jobId <= 0 || $workerId <= 0) {
    setFlash('warning', 'Invalid review request.');
    header('Location: ' . getBaseUrl('customer/my_jobs.php'));
    exit;
}

// Verify Job belongs to customer
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND customer_id = ? LIMIT 1");
$stmt->execute([$jobId, $customerId]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('danger', 'Job order not found.');
    header('Location: ' . getBaseUrl('customer/my_jobs.php'));
    exit;
}

// Fetch Worker
$wStmt = $pdo->prepare("SELECT * FROM workers WHERE id = ? LIMIT 1");
$wStmt->execute([$workerId]);
$worker = $wStmt->fetch();

if (!$worker) {
    setFlash('danger', 'Worker record not found.');
    header('Location: ' . getBaseUrl('customer/my_jobs.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $rating = (float)($_POST['rating'] ?? 5.0);
        $punctuality = (int)($_POST['punctuality_rating'] ?? 5);
        $quality = (int)($_POST['quality_rating'] ?? 5);
        $safety = (int)($_POST['safety_rating'] ?? 5);
        $remarks = sanitize($_POST['remarks'] ?? '');

        if (!$remarks) {
            $error = 'Please write a brief feedback comment about the craftsmanship.';
        } elseif ($rating < 1 || $rating > 5) {
            $error = 'Rating must be between 1.0 and 5.0 stars.';
        } else {
            try {
                // Check if already reviewed
                $check = $pdo->prepare("SELECT id FROM performance_records WHERE job_id = ? AND worker_id = ?");
                $check->execute([$jobId, $workerId]);
                if ($check->fetch()) {
                    setFlash('info', 'You have already submitted a review for this job order.');
                    header('Location: ' . getBaseUrl('customer/view_job.php?id=' . $jobId));
                    exit;
                }

                $insStmt = $pdo->prepare("
                    INSERT INTO performance_records 
                    (worker_id, job_id, rating, punctuality_rating, quality_rating, safety_rating, remarks, recorded_by, review_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                $insStmt->execute([
                    $workerId,
                    $jobId,
                    $rating,
                    $punctuality,
                    $quality,
                    $safety,
                    $remarks,
                    $customerId
                ]);

                setFlash('success', "Thank you! Your verified review for {$worker['full_name']} has been published.");
                header('Location: ' . getBaseUrl('customer/view_job.php?id=' . $jobId));
                exit;

            } catch (Exception $e) {
                $error = 'Failed to submit review: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <div class="text-center mb-4">
                <div class="user-avatar mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.5rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                    <?= strtoupper(substr($worker['full_name'], 0, 1)) ?>
                </div>
                <h4 class="fw-bold mb-1 text-dark">Rate <?= e($worker['full_name']) ?></h4>
                <p class="text-muted small mb-0">For completed work on: <strong><?= e($job['title']) ?></strong> (<?= e($job['job_code']) ?>)</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="job_id" value="<?= $jobId ?>">
                <input type="hidden" name="worker_id" value="<?= $workerId ?>">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="rating">Overall Star Rating (1.0 - 5.0) *</label>
                        <select id="rating" name="rating" class="form-select fw-bold text-dark">
                            <option value="5.0">⭐⭐⭐⭐⭐ 5.0 - Exceptional Work</option>
                            <option value="4.5">⭐⭐⭐⭐ 4.5 - Very Good</option>
                            <option value="4.0">⭐⭐⭐⭐ 4.0 - Good Standard</option>
                            <option value="3.5">⭐⭐⭐ 3.5 - Satisfactory</option>
                            <option value="3.0">⭐⭐⭐ 3.0 - Average</option>
                            <option value="2.0">⭐⭐ 2.0 - Below Expectations</option>
                            <option value="1.0">⭐ 1.0 - Unsatisfactory</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="punctuality_rating">Punctuality (1-5)</label>
                        <select id="punctuality_rating" name="punctuality_rating" class="form-select">
                            <option value="5">5 - Arrived exactly on time</option>
                            <option value="4">4 - On time</option>
                            <option value="3">3 - Slight delay</option>
                            <option value="2">2 - Late</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="quality_rating">Workmanship Quality (1-5)</label>
                        <select id="quality_rating" name="quality_rating" class="form-select">
                            <option value="5">5 - Superior craft & finish</option>
                            <option value="4">4 - High quality</option>
                            <option value="3">3 - Acceptable</option>
                            <option value="2">2 - Required adjustments</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="safety_rating">Cleanliness & Behavior (1-5)</label>
                        <select id="safety_rating" name="safety_rating" class="form-select">
                            <option value="5">5 - Very polite & tidied up after work</option>
                            <option value="4">4 - Professional behavior</option>
                            <option value="3">3 - Standard</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="remarks">Your Review & Comments *</label>
                        <textarea id="remarks" name="remarks" rows="4" class="form-control" required placeholder="Share your experience: How was the finish quality, communication, speed, and accuracy of the artisan?"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="<?= getBaseUrl('customer/view_job.php?id=' . $jobId) ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-warning px-4 py-2 text-dark fw-bold">
                        <i class="bi bi-star-fill me-1"></i>Publish Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/customer_footer.php'; ?>
