<?php
/**
 * Workido - Post New Work Order / Job
 */

$activeMenu = 'add_job';
$pageTitle = 'Post New Job Order';
$pageSubtitle = 'Create work order specifications, budget, timeline, and assign personnel';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$jobCode = generateJobCode($pdo);
$error = '';
$title = '';
$category = 'Carpenter';
$description = '';
$location = '';
$startDate = date('Y-m-d');
$expectedCompletionDate = date('Y-m-d', strtotime('+7 days'));
$paymentType = 'Daily Rate';
$agreedWage = '850.00';
$clientName = '';
$clientContact = '';
$selectedWorkerId = (int)($_GET['worker_id'] ?? 0);
$notes = '';

// Fetch workers grouped by category for assignment dropdown
$workersStmt = $pdo->query("SELECT id, full_name, worker_code, category, daily_wage, availability_status FROM workers ORDER BY category ASC, full_name ASC");
$allWorkers = $workersStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $jobCode = sanitize($_POST['job_code'] ?? $jobCode);
        $title = sanitize($_POST['title'] ?? '');
        $category = sanitize($_POST['category'] ?? 'Carpenter');
        $description = sanitize($_POST['description'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $startDate = sanitize($_POST['start_date'] ?? date('Y-m-d'));
        $expectedCompletionDate = sanitize($_POST['expected_completion_date'] ?? date('Y-m-d'));
        $paymentType = sanitize($_POST['payment_type'] ?? 'Daily Rate');
        $agreedWage = (float)($_POST['agreed_wage'] ?? 0);
        $clientName = sanitize($_POST['client_name'] ?? '');
        $clientContact = sanitize($_POST['client_contact'] ?? '');
        $selectedWorkerId = (int)($_POST['worker_id'] ?? 0);
        $notes = sanitize($_POST['notes'] ?? '');

        // Validation
        if (!$title) {
            $error = 'Please enter a descriptive job title.';
        } elseif (!$description) {
            $error = 'Please enter the work scope description.';
        } elseif (!$location) {
            $error = 'Please provide the physical work location/address.';
        } elseif ($agreedWage <= 0) {
            $error = 'Please enter a valid agreed payment amount greater than 0.';
        } elseif (strtotime($expectedCompletionDate) < strtotime($startDate)) {
            $error = 'Expected completion date cannot be earlier than the starting date.';
        } else {
            try {
                $pdo->beginTransaction();

                // Determine initial status based on whether worker is selected
                $status = ($selectedWorkerId > 0) ? 'Assigned' : 'Pending';

                $currentUser = getCurrentUser();
                $createdBy = $currentUser['id'] ?? null;

                $stmt = $pdo->prepare("
                    INSERT INTO jobs 
                    (job_code, title, category, description, location, start_date, expected_completion_date, payment_type, agreed_wage, status, client_name, client_contact, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $jobCode,
                    $title,
                    $category,
                    $description,
                    $location,
                    $startDate,
                    $expectedCompletionDate,
                    $paymentType,
                    $agreedWage,
                    $status,
                    $clientName ?: null,
                    $clientContact ?: null,
                    $notes ?: null,
                    $createdBy
                ]);

                $newJobId = $pdo->lastInsertId();

                // If a worker was selected, create the job_assignment and update worker status
                if ($selectedWorkerId > 0) {
                    $assignStmt = $pdo->prepare("
                        INSERT INTO job_assignments (job_id, worker_id, assignment_date, assigned_by, assignment_notes, assignment_status)
                        VALUES (?, ?, ?, ?, ?, 'Assigned')
                    ");
                    $assignStmt->execute([
                        $newJobId,
                        $selectedWorkerId,
                        $startDate,
                        $createdBy,
                        "Assigned during job order creation."
                    ]);

                    // Update worker availability to Assigned
                    $updWorker = $pdo->prepare("UPDATE workers SET availability_status = 'Assigned' WHERE id = ?");
                    $updWorker->execute([$selectedWorkerId]);
                }

                $pdo->commit();

                setFlash('success', "Job Order '{$title}' ({$jobCode}) was created successfully!");
                header('Location: ' . getBaseUrl('admin/view_job.php?id=' . $newJobId));
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to create job order: ' . $e->getMessage();
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
                    <i class="bi bi-plus-circle-fill text-primary"></i> Create Work Order
                </h5>
                <a href="<?= getBaseUrl('admin/jobs.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Orders
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
                        <div class="col-md-3">
                            <label class="form-label" for="job_code">Job Code / ID *</label>
                            <input type="text" id="job_code" name="job_code" class="form-control font-monospace fw-bold" value="<?= e($jobCode) ?>" required readonly>
                            <div class="form-text">System-generated unique identifier.</div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label" for="title">Work Order Title *</label>
                            <input type="text" id="title" name="title" class="form-control" value="<?= e($title) ?>" required placeholder="e.g. Master Bedroom Wardrobe & Paneling">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="category">Trade Category *</label>
                            <select id="category" name="category" class="form-select" required onchange="filterWorkerOptions(this.value)">
                                <option value="Carpenter" <?= $category === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                                <option value="Electrician" <?= $category === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                                <option value="Plumber" <?= $category === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                                <option value="Mason" <?= $category === 'Mason' ? 'selected' : '' ?>>Mason</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Scope of Work & Specifications *</label>
                            <textarea id="description" name="description" rows="3" class="form-control" required placeholder="Detailed job tasks, materials needed, dimensions, safety requirements..."><?= e($description) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="location">Job Site Location *</label>
                            <input type="text" id="location" name="location" class="form-control" value="<?= e($location) ?>" required placeholder="Address, Plot / Flat number, Landmark, City">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="start_date">Start Date *</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?= e($startDate) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="expected_completion_date">Expected Completion *</label>
                            <input type="date" id="expected_completion_date" name="expected_completion_date" class="form-control" value="<?= e($expectedCompletionDate) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="payment_type">Payment Structure *</label>
                            <select id="payment_type" name="payment_type" class="form-select" required>
                                <option value="Daily Rate" <?= $paymentType === 'Daily Rate' ? 'selected' : '' ?>>Daily Rate Contract</option>
                                <option value="Fixed Price" <?= $paymentType === 'Fixed Price' ? 'selected' : '' ?>>Fixed Price / Lump Sum</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="agreed_wage">Agreed Wage / Budget (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="10" min="50" id="agreed_wage" name="agreed_wage" class="form-control fw-bold" value="<?= e($agreedWage) ?>" required placeholder="850.00">
                            </div>
                            <div class="form-text">Daily rate per worker OR total fixed price.</div>
                        </div>

                        <!-- Worker Allocation Selector -->
                        <div class="col-md-4">
                            <label class="form-label" for="worker_id">Assign Skilled Worker (Optional)</label>
                            <select id="worker_id" name="worker_id" class="form-select">
                                <option value="">-- Leave Unassigned (Pending) --</option>
                                <?php foreach ($allWorkers as $w): ?>
                                    <option value="<?= $w['id'] ?>" 
                                            data-category="<?= e($w['category']) ?>" 
                                            data-daily-wage="<?= e($w['daily_wage']) ?>"
                                            <?= $selectedWorkerId == $w['id'] ? 'selected' : '' ?>>
                                        <?= e($w['full_name']) ?> (<?= e($w['category']) ?> - <?= e($w['availability_status']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecting a worker assigns them immediately.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="client_name">Client / Owner Name</label>
                            <input type="text" id="client_name" name="client_name" class="form-control" value="<?= e($clientName) ?>" placeholder="e.g. Skyline Residency RWA">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="client_contact">Client Phone / Contact</label>
                            <input type="tel" id="client_contact" name="client_contact" class="form-control" value="<?= e($clientContact) ?>" placeholder="Client phone number">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="notes">Internal Supervisor Notes</label>
                            <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Special access instructions, parking rules, client preferences..."><?= e($notes) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= getBaseUrl('admin/jobs.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i>Save & Post Job Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Filter workers dropdown based on category if desired
function filterWorkerOptions(cat) {
    const workerSelect = document.getElementById('worker_id');
    const options = workerSelect.querySelectorAll('option');
    options.forEach(opt => {
        if (!opt.value) return; // Keep placeholder
        if (opt.dataset.category === cat) {
            opt.style.fontWeight = 'bold';
        } else {
            opt.style.fontWeight = 'normal';
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
