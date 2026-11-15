<?php
/**
 * Workido - Work Allocation & Assignment Center
 */

$activeMenu = 'assignments';
$pageTitle = 'Work Allocation Center';
$pageSubtitle = 'Assign qualified trade workers to pending work orders & oversee field dispatches';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$error = '';
$preselectedJobId = (int)($_GET['job_id'] ?? 0);
$preselectedWorkerId = (int)($_GET['worker_id'] ?? 0);

// Handle New Assignment Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_assignment') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $jobId = (int)($_POST['job_id'] ?? 0);
        $workerId = (int)($_POST['worker_id'] ?? 0);
        $assignmentDate = sanitize($_POST['assignment_date'] ?? date('Y-m-d'));
        $notes = sanitize($_POST['assignment_notes'] ?? '');

        if ($jobId <= 0 || $workerId <= 0) {
            $error = 'Please select both a valid work order and a skilled worker.';
        } else {
            try {
                $pdo->beginTransaction();

                // Check if already assigned
                $checkExisting = $pdo->prepare("SELECT id FROM job_assignments WHERE job_id = ? AND worker_id = ?");
                $checkExisting->execute([$jobId, $workerId]);
                if ($checkExisting->fetch()) {
                    throw new Exception("This worker is already assigned to this work order.");
                }

                $currentUser = getCurrentUser();
                $assignedBy = $currentUser['id'] ?? null;

                // Create assignment
                $insAssign = $pdo->prepare("
                    INSERT INTO job_assignments (job_id, worker_id, assignment_date, assigned_by, assignment_notes, assignment_status)
                    VALUES (?, ?, ?, ?, ?, 'Assigned')
                ");
                $insAssign->execute([$jobId, $workerId, $assignmentDate, $assignedBy, $notes ?: null]);

                // Update job status to Assigned
                $updJob = $pdo->prepare("UPDATE jobs SET status = 'Assigned' WHERE id = ? AND status = 'Pending'");
                $updJob->execute([$jobId]);

                // Update worker availability to Assigned
                $updWorker = $pdo->prepare("UPDATE workers SET availability_status = 'Assigned' WHERE id = ?");
                $updWorker->execute([$workerId]);

                $pdo->commit();

                setFlash('success', 'Skilled worker was successfully allocated to the work order!');
                header('Location: ' . getBaseUrl('admin/assignments.php'));
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to allocate worker: ' . $e->getMessage();
            }
        }
    }
}

// Handle Status Change of Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $assignId = (int)($_POST['assignment_id'] ?? 0);
        $newStatus = sanitize($_POST['new_status'] ?? '');

        if ($assignId > 0 && in_array($newStatus, ['Assigned', 'In Progress', 'Completed', 'Cancelled'])) {
            try {
                $pdo->beginTransaction();

                // Get job and worker id
                $getAssign = $pdo->prepare("SELECT job_id, worker_id FROM job_assignments WHERE id = ?");
                $getAssign->execute([$assignId]);
                $asRow = $getAssign->fetch();

                if ($asRow) {
                    $updAssign = $pdo->prepare("UPDATE job_assignments SET assignment_status = ? WHERE id = ?");
                    $updAssign->execute([$newStatus, $assignId]);

                    // Sync job status
                    $updJob = $pdo->prepare("UPDATE jobs SET status = ?, actual_completion_date = ? WHERE id = ?");
                    $compDate = ($newStatus === 'Completed') ? date('Y-m-d') : null;
                    $updJob->execute([$newStatus, $compDate, $asRow['job_id']]);

                    // If completed or cancelled, check if worker can be marked Available
                    if (in_array($newStatus, ['Completed', 'Cancelled'])) {
                        $checkActive = $pdo->prepare("
                            SELECT COUNT(*) FROM jobs j
                            JOIN job_assignments ja ON j.id = ja.job_id
                            WHERE ja.worker_id = ? AND j.status IN ('Assigned', 'In Progress') AND j.id != ?
                        ");
                        $checkActive->execute([$asRow['worker_id'], $asRow['job_id']]);
                        if ((int)$checkActive->fetchColumn() === 0) {
                            $freeWorker = $pdo->prepare("UPDATE workers SET availability_status = 'Available' WHERE id = ?");
                            $freeWorker->execute([$asRow['worker_id']]);
                        }
                    } elseif ($newStatus === 'In Progress') {
                        $markBusy = $pdo->prepare("UPDATE workers SET availability_status = 'Assigned' WHERE id = ?");
                        $markBusy->execute([$asRow['worker_id']]);
                    }

                    $pdo->commit();
                    setFlash('success', 'Assignment and work order status successfully updated to ' . $newStatus);
                    header('Location: ' . getBaseUrl('admin/assignments.php'));
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Failed to update assignment status: ' . $e->getMessage();
            }
        }
    }
}

// Fetch Pending / Active Jobs for Allocation
$jobsForAssignStmt = $pdo->query("
    SELECT id, job_code, title, category, location, status, agreed_wage, payment_type 
    FROM jobs 
    WHERE status IN ('Pending', 'Assigned', 'In Progress')
    ORDER BY status = 'Pending' DESC, id DESC
");
$jobsForAssign = $jobsForAssignStmt->fetchAll();

// Fetch Workers
$workersStmt = $pdo->query("SELECT id, full_name, worker_code, category, availability_status, daily_wage FROM workers ORDER BY availability_status = 'Available' DESC, category ASC");
$allWorkers = $workersStmt->fetchAll();

// Fetch All Existing Assignments
$filterStatus = sanitize($_GET['filter_status'] ?? '');
$assignSql = "
    SELECT ja.*, 
           j.job_code, j.title as job_title, j.category as job_category, j.location, j.agreed_wage, j.payment_type, j.status as job_status,
           w.full_name as worker_name, w.worker_code, w.category as worker_category, w.mobile_number
    FROM job_assignments ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN workers w ON ja.worker_id = w.id
    WHERE 1=1
";
$assignParams = [];
if ($filterStatus !== '') {
    $assignSql .= " AND ja.assignment_status = ?";
    $assignParams[] = $filterStatus;
}
$assignSql .= " ORDER BY ja.id DESC";

$assignQuery = $pdo->prepare($assignSql);
$assignQuery->execute($assignParams);
$assignmentsList = $assignQuery->fetchAll();
?>

<!-- Allocation Dispatch Row -->
<div class="row g-4 mb-4">
    <!-- Allocation Form -->
    <div class="col-lg-5">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-header bg-primary text-white">
                <h5 class="dashboard-card-title text-white">
                    <i class="bi bi-diagram-3-fill"></i> New Work Allocation
                </h5>
            </div>
            <div class="dashboard-card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create_assignment">

                    <!-- Select Job -->
                    <div class="mb-3">
                        <label class="form-label" for="alloc_job_id">Select Work Order *</label>
                        <select id="alloc_job_id" name="job_id" class="form-select" required onchange="matchJobWorkerCategory()">
                            <option value="">-- Choose Work Order --</option>
                            <?php foreach ($jobsForAssign as $job): ?>
                                <option value="<?= $job['id'] ?>" 
                                        data-category="<?= e($job['category']) ?>" 
                                        <?= $preselectedJobId == $job['id'] ? 'selected' : '' ?>>
                                    [<?= e($job['job_code']) ?>] <?= e($job['title']) ?> (<?= e($job['category']) ?> - <?= e($job['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Choose a pending or active work order.</div>
                    </div>

                    <!-- Select Worker -->
                    <div class="mb-3">
                        <label class="form-label" for="alloc_worker_id">Select Skilled Worker *</label>
                        <select id="alloc_worker_id" name="worker_id" class="form-select" required>
                            <option value="">-- Choose Skilled Worker --</option>
                            <?php foreach ($allWorkers as $w): ?>
                                <option value="<?= $w['id'] ?>" 
                                        data-category="<?= e($w['category']) ?>" 
                                        <?= $preselectedWorkerId == $w['id'] ? 'selected' : '' ?>>
                                    <?= e($w['full_name']) ?> (<?= e($w['category']) ?> | <?= e($w['availability_status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label" for="assignment_date">Assignment Date *</label>
                        <input type="date" id="assignment_date" name="assignment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label class="form-label" for="assignment_notes">Assignment Instructions / Scope Notes</label>
                        <textarea id="assignment_notes" name="assignment_notes" rows="2" class="form-control" placeholder="Reporting time, site supervisor contact, key deliverable..."></textarea>
                    </div>

                    <div class="d-grid pt-2">
                        <button type="submit" class="btn btn-primary fw-semibold">
                            <i class="bi bi-person-check-fill me-1"></i>Dispatch / Allocate Worker
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Active Allocations Statistics -->
    <div class="col-lg-7">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-header">
                <h5 class="dashboard-card-title">
                    <i class="bi bi-info-circle-fill text-info"></i> Allocation Quick Summary
                </h5>
                <span class="badge bg-light text-dark border"><?= count($assignmentsList) ?> Total Dispatches</span>
            </div>
            <div class="dashboard-card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="p-3 bg-light border rounded-3 text-center">
                            <i class="bi bi-hourglass-split text-warning fs-3"></i>
                            <h4 class="fw-bold mb-0 mt-1">
                                <?= count(array_filter($assignmentsList, fn($x) => $x['assignment_status'] === 'Assigned')) ?>
                            </h4>
                            <small class="text-muted fw-semibold">Assigned (Queued)</small>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 bg-light border rounded-3 text-center">
                            <i class="bi bi-play-circle-fill text-primary fs-3"></i>
                            <h4 class="fw-bold mb-0 mt-1">
                                <?= count(array_filter($assignmentsList, fn($x) => $x['assignment_status'] === 'In Progress')) ?>
                            </h4>
                            <small class="text-muted fw-semibold">In Progress (Active)</small>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 bg-light border rounded-3 text-center">
                            <i class="bi bi-check-circle-fill text-success fs-3"></i>
                            <h4 class="fw-bold mb-0 mt-1">
                                <?= count(array_filter($assignmentsList, fn($x) => $x['assignment_status'] === 'Completed')) ?>
                            </h4>
                            <small class="text-muted fw-semibold">Completed</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border small text-muted mb-0">
                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-lightbulb-fill text-warning me-1"></i>Allocation Workflow</h6>
                    Assigning a skilled worker links them permanently to the work order. When work commences, advance the status to <code>In Progress</code>. Once finished, mark it <code>Completed</code>, which enables final wage settlement and frees worker availability for future assignments.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- All Allocations Table -->
<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="d-flex align-items-center gap-2">
            <h5 class="dashboard-card-title mb-0">
                <i class="bi bi-list-task text-primary"></i> Work Allocation Dispatches
            </h5>
        </div>

        <!-- Filter Status Dropdown -->
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" onchange="location.href='?filter_status=' + this.value;">
                <option value="">All Allocation Statuses</option>
                <option value="Assigned" <?= $filterStatus === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                <option value="In Progress" <?= $filterStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Completed" <?= $filterStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Cancelled" <?= $filterStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-workido">
            <thead>
                <tr>
                    <th>Job Order</th>
                    <th>Worker Name</th>
                    <th>Trade Category</th>
                    <th>Location</th>
                    <th>Assignment Date</th>
                    <th>Agreed Payment</th>
                    <th>Status</th>
                    <th class="text-end">Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignmentsList)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No work allocations found matching your filter.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignmentsList as $as): ?>
                        <tr>
                            <td>
                                <a href="<?= getBaseUrl('admin/view_job.php?id=' . $as['job_id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none d-block">
                                    <?= e($as['job_code']) ?>
                                </a>
                                <small class="text-dark fw-semibold"><?= e($as['job_title']) ?></small>
                            </td>
                            <td>
                                <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $as['worker_id']) ?>" class="fw-semibold text-dark text-decoration-none d-block">
                                    <?= e($as['worker_name']) ?>
                                </a>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($as['mobile_number']) ?></small>
                            </td>
                            <td><?= getCategoryBadge($as['worker_category']) ?></td>
                            <td><small class="text-muted"><?= e($as['location']) ?></small></td>
                            <td><?= formatDate($as['assignment_date']) ?></td>
                            <td><span class="fw-bold text-dark"><?= formatCurrency($as['agreed_wage']) ?></span></td>
                            <td><?= getJobStatusBadge($as['assignment_status']) ?></td>
                            <td class="text-end">
                                <form method="POST" action="" class="d-inline-flex gap-1">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="assignment_id" value="<?= $as['id'] ?>">

                                    <select name="new_status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                        <option value="Assigned" <?= $as['assignment_status'] === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                                        <option value="In Progress" <?= $as['assignment_status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="Completed" <?= $as['assignment_status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $as['assignment_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function matchJobWorkerCategory() {
    const jobSelect = document.getElementById('alloc_job_id');
    const workerSelect = document.getElementById('alloc_worker_id');
    const selectedJobOpt = jobSelect.options[jobSelect.selectedIndex];
    if (!selectedJobOpt || !selectedJobOpt.dataset.category) return;

    const cat = selectedJobOpt.dataset.category;
    Array.from(workerSelect.options).forEach(opt => {
        if (!opt.value) return;
        if (opt.dataset.category === cat) {
            opt.style.fontWeight = 'bold';
            opt.style.color = '#1e293b';
        } else {
            opt.style.fontWeight = 'normal';
            opt.style.color = '#94a3b8';
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
