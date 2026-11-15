<?php
/**
 * Workido - Edit Work Order & Status
 */

$activeMenu = 'jobs';
$pageTitle = 'Edit Work Order';
$pageSubtitle = 'Update job specifications, dispatch timeline, and progress milestones';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$jobId = (int)($_GET['id'] ?? $_POST['job_id'] ?? 0);
if ($jobId <= 0) {
    setFlash('danger', 'Invalid work order identification.');
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}

// Fetch Job
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? LIMIT 1");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('danger', 'Work order record not found.');
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}

// Fetch current assigned worker if any
$assignStmt = $pdo->prepare("
    SELECT ja.*, w.id as worker_id, w.full_name as worker_name, w.worker_code 
    FROM job_assignments ja
    JOIN workers w ON ja.worker_id = w.id
    WHERE ja.job_id = ?
    LIMIT 1
");
$assignStmt->execute([$jobId]);
$currentAssignment = $assignStmt->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $category = sanitize($_POST['category'] ?? 'Carpenter');
        $description = sanitize($_POST['description'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $startDate = sanitize($_POST['start_date'] ?? date('Y-m-d'));
        $expectedCompletionDate = sanitize($_POST['expected_completion_date'] ?? date('Y-m-d'));
        $paymentType = sanitize($_POST['payment_type'] ?? 'Daily Rate');
        $agreedWage = (float)($_POST['agreed_wage'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'Pending');
        $actualCompletionDate = sanitize($_POST['actual_completion_date'] ?? '');
        $clientName = sanitize($_POST['client_name'] ?? '');
        $clientContact = sanitize($_POST['client_contact'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$title) {
            $error = 'Please enter a descriptive job title.';
        } elseif (!$description) {
            $error = 'Please enter the work scope description.';
        } elseif (!$location) {
            $error = 'Please provide the physical work location.';
        } elseif ($agreedWage <= 0) {
            $error = 'Please enter a valid agreed payment amount.';
        } else {
            // Auto handle actual completion date if marked Completed
            if ($status === 'Completed' && empty($actualCompletionDate)) {
                $actualCompletionDate = date('Y-m-d');
            } elseif ($status !== 'Completed') {
                $actualCompletionDate = null;
            }

            try {
                $pdo->beginTransaction();

                $updStmt = $pdo->prepare("
                    UPDATE jobs SET 
                        title = ?, category = ?, description = ?, location = ?,
                        start_date = ?, expected_completion_date = ?, actual_completion_date = ?,
                        payment_type = ?, agreed_wage = ?, status = ?,
                        client_name = ?, client_contact = ?, notes = ?
                    WHERE id = ?
                ");
                $updStmt->execute([
                    $title,
                    $category,
                    $description,
                    $location,
                    $startDate,
                    $expectedCompletionDate,
                    $actualCompletionDate ?: null,
                    $paymentType,
                    $agreedWage,
                    $status,
                    $clientName ?: null,
                    $clientContact ?: null,
                    $notes ?: null,
                    $jobId
                ]);

                // Update assignment status if exists
                if ($currentAssignment) {
                    $updAssign = $pdo->prepare("
                        UPDATE job_assignments SET assignment_status = ? WHERE id = ?
                    ");
                    $assignStatus = match($status) {
                        'Completed'   => 'Completed',
                        'In Progress' => 'In Progress',
                        'Cancelled'   => 'Cancelled',
                        default       => 'Assigned'
                    };
                    $updAssign->execute([$assignStatus, $currentAssignment['id']]);

                    // If completed, set worker availability back to Available if no other active jobs
                    if ($status === 'Completed') {
                        $otherJobsCheck = $pdo->prepare("
                            SELECT COUNT(*) FROM jobs j 
                            JOIN job_assignments ja ON j.id = ja.job_id
                            WHERE ja.worker_id = ? AND j.status = 'In Progress' AND j.id != ?
                        ");
                        $otherJobsCheck->execute([$currentAssignment['worker_id'], $jobId]);
                        if ((int)$otherJobsCheck->fetchColumn() === 0) {
                            $freeWorker = $pdo->prepare("UPDATE workers SET availability_status = 'Available' WHERE id = ?");
                            $freeWorker->execute([$currentAssignment['worker_id']]);
                        }
                    }
                }

                $pdo->commit();

                setFlash('success', "Work Order '{$title}' ({$job['job_code']}) updated successfully.");
                header('Location: ' . getBaseUrl('admin/view_job.php?id=' . $jobId));
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to update job order: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h5 class="dashboard-card-title">
                    <i class="bi bi-pencil-square text-primary"></i> Edit Work Order: <?= e($job['title']) ?> (<?= e($job['job_code']) ?>)
                </h5>
                <a href="<?= getBaseUrl('admin/view_job.php?id=' . $jobId) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Order Details
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
                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Job Code</label>
                            <input type="text" class="form-control font-monospace fw-bold" value="<?= e($job['job_code']) ?>" readonly disabled>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label" for="title">Work Order Title *</label>
                            <input type="text" id="title" name="title" class="form-control" value="<?= e($job['title']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="status">Job Status *</label>
                            <select id="status" name="status" class="form-select fw-bold" required>
                                <option value="Pending" <?= $job['status'] === 'Pending' ? 'selected' : '' ?>>Pending (Unassigned)</option>
                                <option value="Assigned" <?= $job['status'] === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                                <option value="In Progress" <?= $job['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress (Active Work)</option>
                                <option value="Completed" <?= $job['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= $job['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="category">Trade Category *</label>
                            <select id="category" name="category" class="form-select" required>
                                <option value="Carpenter" <?= $job['category'] === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                                <option value="Electrician" <?= $job['category'] === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                                <option value="Plumber" <?= $job['category'] === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                                <option value="Mason" <?= $job['category'] === 'Mason' ? 'selected' : '' ?>>Mason</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="payment_type">Payment Structure *</label>
                            <select id="payment_type" name="payment_type" class="form-select" required>
                                <option value="Daily Rate" <?= $job['payment_type'] === 'Daily Rate' ? 'selected' : '' ?>>Daily Rate</option>
                                <option value="Fixed Price" <?= $job['payment_type'] === 'Fixed Price' ? 'selected' : '' ?>>Fixed Price / Lump Sum</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="agreed_wage">Agreed Wage / Budget (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="10" min="50" id="agreed_wage" name="agreed_wage" class="form-control fw-bold" value="<?= e($job['agreed_wage']) ?>" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Scope of Work & Specifications *</label>
                            <textarea id="description" name="description" rows="3" class="form-control" required><?= e($job['description']) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="location">Job Site Location *</label>
                            <input type="text" id="location" name="location" class="form-control" value="<?= e($job['location']) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="start_date">Start Date *</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?= e($job['start_date']) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="expected_completion_date">Expected Completion *</label>
                            <input type="date" id="expected_completion_date" name="expected_completion_date" class="form-control" value="<?= e($job['expected_completion_date']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="actual_completion_date">Actual Completion Date</label>
                            <input type="date" id="actual_completion_date" name="actual_completion_date" class="form-control" value="<?= e($job['actual_completion_date']) ?>">
                            <div class="form-text">Filled automatically when marked Completed.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="client_name">Client / Owner Name</label>
                            <input type="text" id="client_name" name="client_name" class="form-control" value="<?= e($job['client_name']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="client_contact">Client Phone / Contact</label>
                            <input type="tel" id="client_contact" name="client_contact" class="form-control" value="<?= e($job['client_contact']) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="notes">Supervisor Notes</label>
                            <textarea id="notes" name="notes" rows="2" class="form-control"><?= e($job['notes']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= getBaseUrl('admin/view_job.php?id=' . $jobId) ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
