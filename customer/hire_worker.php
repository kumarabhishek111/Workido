<?php
/**
 * Workido - Customer: Hire a Specific Skilled Worker
 */

$activeCustomerMenu = 'workers';
$customerPageTitle = 'Hire Skilled Worker';

require_once __DIR__ . '/../includes/customer_header.php';

$pdo = getDBConnection();
$customerId = $user['id'];

$workerId = (int)($_GET['worker_id'] ?? $_POST['worker_id'] ?? 0);
if ($workerId <= 0) {
    setFlash('warning', 'Please select a skilled worker to hire.');
    header('Location: ' . getBaseUrl('customer/workers.php'));
    exit;
}

// Fetch Worker Record
$stmt = $pdo->prepare("SELECT * FROM workers WHERE id = ? LIMIT 1");
$stmt->execute([$workerId]);
$worker = $stmt->fetch();

if (!$worker) {
    setFlash('danger', 'Selected worker was not found.');
    header('Location: ' . getBaseUrl('customer/workers.php'));
    exit;
}

// Fetch Customer profile defaults
$cStmt = $pdo->prepare("SELECT phone, address FROM users WHERE id = ?");
$cStmt->execute([$customerId]);
$cProfile = $cStmt->fetch();

$error = '';
$title = '';
$description = '';
$location = $cProfile['address'] ?? '';
$startDate = date('Y-m-d', strtotime('+1 day'));
$expectedCompletionDate = date('Y-m-d', strtotime('+5 days'));
$paymentType = 'Daily Rate';
$agreedWage = $worker['daily_wage'];
$clientContact = $cProfile['phone'] ?? '';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $startDate = sanitize($_POST['start_date'] ?? '');
        $expectedCompletionDate = sanitize($_POST['expected_completion_date'] ?? '');
        $paymentType = sanitize($_POST['payment_type'] ?? 'Daily Rate');
        $agreedWage = (float)($_POST['agreed_wage'] ?? $worker['daily_wage']);
        $clientContact = sanitize($_POST['client_contact'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$title) {
            $error = 'Please enter a clear project / task title.';
        } elseif (!$description) {
            $error = 'Please describe the scope of work and requirements.';
        } elseif (!$location) {
            $error = 'Please specify the exact site address / location.';
        } elseif ($agreedWage <= 0) {
            $error = 'Please enter a valid wage / budget amount.';
        } elseif (strtotime($expectedCompletionDate) < strtotime($startDate)) {
            $error = 'Completion date cannot be earlier than start date.';
        } else {
            try {
                $pdo->beginTransaction();

                $jobCode = generateJobCode($pdo);

                // 1. Insert into jobs table
                $insJob = $pdo->prepare("
                    INSERT INTO jobs 
                    (job_code, title, category, description, location, start_date, expected_completion_date, payment_type, agreed_wage, status, client_name, client_contact, customer_id, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Assigned', ?, ?, ?, ?, ?)
                ");
                $insJob->execute([
                    $jobCode,
                    $title,
                    $worker['category'],
                    $description,
                    $location,
                    $startDate,
                    $expectedCompletionDate,
                    $paymentType,
                    $agreedWage,
                    $user['full_name'],
                    $clientContact ?: null,
                    $customerId,
                    $notes ?: null,
                    $customerId
                ]);

                $newJobId = $pdo->lastInsertId();

                // 2. Insert into job_assignments
                $insAssign = $pdo->prepare("
                    INSERT INTO job_assignments (job_id, worker_id, assignment_date, assigned_by, assignment_notes, assignment_status)
                    VALUES (?, ?, ?, ?, ?, 'Assigned')
                ");
                $insAssign->execute([
                    $newJobId,
                    $workerId,
                    $startDate,
                    $customerId,
                    "Direct client booking by " . $user['full_name']
                ]);

                // 3. Update worker status to Assigned
                $updWorker = $pdo->prepare("UPDATE workers SET availability_status = 'Assigned' WHERE id = ?");
                $updWorker->execute([$workerId]);

                $pdo->commit();

                setFlash('success', "Worker {$worker['full_name']} was successfully hired for '{$title}' ({$jobCode})!");
                header('Location: ' . getBaseUrl('customer/view_job.php?id=' . $newJobId));
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Failed to book worker: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row g-4">
    <!-- Worker Summary Sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
            <div class="text-center mb-3">
                <div class="user-avatar mx-auto mb-2" style="width: 70px; height: 70px; font-size: 1.8rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                    <?= strtoupper(substr($worker['full_name'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold text-dark mb-0"><?= e($worker['full_name']) ?></h5>
                <span class="badge bg-secondary font-monospace small"><?= e($worker['worker_code']) ?></span>
                <div class="mt-2">
                    <?= getCategoryBadge($worker['category']) ?>
                    <?= getAvailabilityBadge($worker['availability_status']) ?>
                </div>
            </div>

            <ul class="list-group list-group-flush small bg-transparent mb-3">
                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                    <span class="text-muted">Standard Daily Wage:</span>
                    <strong class="text-dark"><?= formatCurrency($worker['daily_wage']) ?></strong>
                </li>
                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                    <span class="text-muted">Trade Experience:</span>
                    <strong><?= e($worker['experience_years']) ?> Years</strong>
                </li>
                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                    <span class="text-muted">Direct Mobile:</span>
                    <strong><i class="bi bi-telephone me-1 text-primary"></i><?= e($worker['mobile_number']) ?></strong>
                </li>
            </ul>

            <div class="mb-2">
                <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Craft Skills:</small>
                <p class="small text-dark mb-0"><?= e($worker['skills']) ?></p>
            </div>

            <div class="mt-3 pt-3 border-top text-center">
                <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i>Select Different Worker
                </a>
            </div>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-calendar-check text-primary me-2"></i>Hire <?= e($worker['full_name']) ?>
            </h4>
            <p class="text-muted small mb-4">Provide your project specifications, work address, and schedule requirements</p>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="worker_id" value="<?= $worker['id'] ?>">

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label" for="title">Project / Task Title *</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?= e($title) ?>" required placeholder="e.g. Modular Kitchen Cabinet Installation or Living Room Rewiring">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Detailed Scope of Work & Requirements *</label>
                        <textarea id="description" name="description" rows="4" class="form-control" required placeholder="Describe what tasks need to be completed, materials available on site, special tools needed, and specific finish requirements..."><?= e($description) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="location">Site Location / Work Address *</label>
                        <input type="text" id="location" name="location" class="form-control" value="<?= e($location) ?>" required placeholder="House/Flat No, Apartment, Street, Landmark, City">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="start_date">Expected Start Date *</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= e($startDate) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="expected_completion_date">Target Completion Date *</label>
                        <input type="date" id="expected_completion_date" name="expected_completion_date" class="form-control" value="<?= e($expectedCompletionDate) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="payment_type">Payment Structure *</label>
                        <select id="payment_type" name="payment_type" class="form-select" required>
                            <option value="Daily Rate" <?= $paymentType === 'Daily Rate' ? 'selected' : '' ?>>Daily Rate (Standard)</option>
                            <option value="Fixed Price" <?= $paymentType === 'Fixed Price' ? 'selected' : '' ?>>Fixed Price / Lump Sum</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="agreed_wage">Agreed Wage / Budget (₹) *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="10" min="100" id="agreed_wage" name="agreed_wage" class="form-control fw-bold" value="<?= e($agreedWage) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="client_contact">Your Phone Number for Site Coordination</label>
                        <input type="tel" id="client_contact" name="client_contact" class="form-control" value="<?= e($clientContact) ?>" placeholder="Your mobile number">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="notes">Special Access Instructions</label>
                        <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Security gate entry, parking instructions, preferred work hours..."><?= e($notes) ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        <i class="bi bi-check-circle-fill me-2"></i>Confirm & Hire Worker
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/customer_footer.php'; ?>
