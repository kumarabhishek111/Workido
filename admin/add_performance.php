<?php
/**
 * Workido - Record Worker Performance & Supervisor Review
 */

$activeMenu = 'performance';
$pageTitle = 'Record Performance Review';
$pageSubtitle = 'Add workmanship evaluation, ratings, safety compliance, and supervisor feedback';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$error = '';
$preselectedWorkerId = (int)($_GET['worker_id'] ?? 0);
$preselectedJobId = (int)($_GET['job_id'] ?? 0);

// Fetch workers
$workersStmt = $pdo->query("SELECT id, full_name, worker_code, category FROM workers ORDER BY full_name ASC");
$allWorkers = $workersStmt->fetchAll();

// Fetch jobs
$jobsStmt = $pdo->query("SELECT id, job_code, title, category FROM jobs ORDER BY id DESC");
$allJobs = $jobsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $workerId = (int)($_POST['worker_id'] ?? 0);
        $jobId = (int)($_POST['job_id'] ?? 0);
        $rating = (float)($_POST['rating'] ?? 5.0);
        $punctuality = (int)($_POST['punctuality_rating'] ?? 5);
        $quality = (int)($_POST['quality_rating'] ?? 5);
        $safety = (int)($_POST['safety_rating'] ?? 5);
        $remarks = sanitize($_POST['remarks'] ?? '');
        $adminFeedback = sanitize($_POST['admin_feedback'] ?? '');
        $reviewDate = sanitize($_POST['review_date'] ?? date('Y-m-d'));

        if ($workerId <= 0) {
            $error = 'Please select a valid skilled worker.';
        } elseif (!$remarks) {
            $error = 'Please provide detailed performance remarks regarding workmanship.';
        } elseif ($rating < 1 || $rating > 5) {
            $error = 'Overall rating must be between 1.0 and 5.0.';
        } else {
            try {
                $currentUser = getCurrentUser();
                $recordedBy = $currentUser['id'] ?? null;

                $stmt = $pdo->prepare("
                    INSERT INTO performance_records 
                    (worker_id, job_id, rating, punctuality_rating, quality_rating, safety_rating, remarks, admin_feedback, recorded_by, review_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $workerId,
                    $jobId > 0 ? $jobId : null,
                    $rating,
                    $punctuality,
                    $quality,
                    $safety,
                    $remarks,
                    $adminFeedback ?: null,
                    $recordedBy,
                    $reviewDate
                ]);

                setFlash('success', 'Worker performance evaluation was recorded successfully!');
                header('Location: ' . getBaseUrl('admin/view_worker.php?id=' . $workerId));
                exit;

            } catch (Exception $e) {
                $error = 'Failed to record performance review: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h5 class="dashboard-card-title">
                    <i class="bi bi-star-fill text-warning"></i> Supervisor Performance Evaluation
                </h5>
                <a href="<?= getBaseUrl('admin/performance.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <div class="dashboard-card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <?= csrfField() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="worker_id">Select Skilled Worker *</label>
                            <select id="worker_id" name="worker_id" class="form-select" required>
                                <option value="">-- Choose Worker --</option>
                                <?php foreach ($allWorkers as $w): ?>
                                    <option value="<?= $w['id'] ?>" <?= $preselectedWorkerId == $w['id'] ? 'selected' : '' ?>>
                                        <?= e($w['full_name']) ?> (<?= e($w['worker_code']) ?> - <?= e($w['category']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="job_id">Linked Work Order (Optional)</label>
                            <select id="job_id" name="job_id" class="form-select">
                                <option value="">-- General / Workshop Review --</option>
                                <?php foreach ($allJobs as $j): ?>
                                    <option value="<?= $j['id'] ?>" <?= $preselectedJobId == $j['id'] ? 'selected' : '' ?>>
                                        [<?= e($j['job_code']) ?>] <?= e($j['title']) ?> (<?= e($j['category']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="rating">Overall Score (1 - 5) *</label>
                            <input type="number" step="0.1" min="1.0" max="5.0" id="rating" name="rating" class="form-control fw-bold" value="5.0" required>
                            <div class="form-text">e.g. 4.8 / 5.0</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="punctuality_rating">Punctuality (1-5)</label>
                            <select id="punctuality_rating" name="punctuality_rating" class="form-select">
                                <option value="5">5 - Excellent (Always on time)</option>
                                <option value="4">4 - Good</option>
                                <option value="3">3 - Average</option>
                                <option value="2">2 - Needs Improvement</option>
                                <option value="1">1 - Unsatisfactory</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="quality_rating">Work Quality (1-5)</label>
                            <select id="quality_rating" name="quality_rating" class="form-select">
                                <option value="5">5 - Superior Craftsmanship</option>
                                <option value="4">4 - High Quality</option>
                                <option value="3">3 - Acceptable</option>
                                <option value="2">2 - Inconsistent</option>
                                <option value="1">1 - Defective / Subpar</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="safety_rating">Safety & Gear (1-5)</label>
                            <select id="safety_rating" name="safety_rating" class="form-select">
                                <option value="5">5 - Strict Safety Compliance</option>
                                <option value="4">4 - Follows Protocols</option>
                                <option value="3">3 - Standard</option>
                                <option value="2">2 - Minor Violations</option>
                                <option value="1">1 - Safety Hazard</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="review_date">Review Date *</label>
                            <input type="date" id="review_date" name="review_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="remarks">Performance Remarks & Quality Observations *</label>
                            <textarea id="remarks" name="remarks" rows="3" class="form-control" required placeholder="Describe precision of joints, speed, neatness, alignment, problem-solving skills..."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="admin_feedback">Administrator / Contractor Feedback</label>
                            <textarea id="admin_feedback" name="admin_feedback" rows="2" class="form-control" placeholder="Recommendations for bonus, promotion to master artisan, or training areas..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= getBaseUrl('admin/performance.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning px-4 text-dark fw-semibold">
                            <i class="bi bi-star-fill me-1"></i>Save Performance Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
