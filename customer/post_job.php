<?php
/**
 * Workido - Customer: Post Work Requirement (Open Request)
 */

$activeCustomerMenu = 'post_job';
$customerPageTitle = 'Post Work Requirement';

require_once __DIR__ . '/../includes/customer_header.php';

$pdo = getDBConnection();
$customerId = $user['id'];

// Fetch Customer profile defaults
$cStmt = $pdo->prepare("SELECT phone, address FROM users WHERE id = ?");
$cStmt->execute([$customerId]);
$cProfile = $cStmt->fetch();

$error = '';
$title = '';
$category = 'Carpenter';
$description = '';
$location = $cProfile['address'] ?? '';
$startDate = date('Y-m-d', strtotime('+1 day'));
$expectedCompletionDate = date('Y-m-d', strtotime('+5 days'));
$paymentType = 'Daily Rate';
$agreedWage = '850.00';
$clientContact = $cProfile['phone'] ?? '';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $category = sanitize($_POST['category'] ?? 'Carpenter');
        $description = sanitize($_POST['description'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $startDate = sanitize($_POST['start_date'] ?? '');
        $expectedCompletionDate = sanitize($_POST['expected_completion_date'] ?? '');
        $paymentType = sanitize($_POST['payment_type'] ?? 'Daily Rate');
        $agreedWage = (float)($_POST['agreed_wage'] ?? 0);
        $clientContact = sanitize($_POST['client_contact'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$title) {
            $error = 'Please enter a descriptive project title.';
        } elseif (!$description) {
            $error = 'Please provide details about what needs to be done.';
        } elseif (!$location) {
            $error = 'Please provide your address/location for the work.';
        } elseif ($agreedWage <= 0) {
            $error = 'Please propose an agreed wage / budget.';
        } else {
            try {
                $jobCode = generateJobCode($pdo);

                $stmt = $pdo->prepare("
                    INSERT INTO jobs 
                    (job_code, title, category, description, location, start_date, expected_completion_date, payment_type, agreed_wage, status, client_name, client_contact, customer_id, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?)
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
                    $user['full_name'],
                    $clientContact ?: null,
                    $customerId,
                    $notes ?: null,
                    $customerId
                ]);

                $newJobId = $pdo->lastInsertId();

                setFlash('success', "Your work request '{$title}' ({$jobCode}) was posted! Workido will match and dispatch an available {$category} for you.");
                header('Location: ' . getBaseUrl('customer/view_job.php?id=' . $newJobId));
                exit;

            } catch (Exception $e) {
                $error = 'Failed to submit work request: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3">
                <div class="bg-primary text-white p-3 rounded-circle">
                    <i class="bi bi-briefcase fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-dark">Post Your Work Requirement</h3>
                    <p class="text-muted small mb-0">Describe your project and we will match you with a verified skilled artisan</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <?= csrfField() ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label" for="title">Project / Task Title *</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?= e($title) ?>" required placeholder="e.g. Bathroom Leakage Repair & CPVC Pipe Replacement">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="category">Required Trade Specialization *</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="Carpenter" <?= $category === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                            <option value="Electrician" <?= $category === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                            <option value="Plumber" <?= $category === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                            <option value="Mason" <?= $category === 'Mason' ? 'selected' : '' ?>>Mason</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Detailed Description & Specifications *</label>
                        <textarea id="description" name="description" rows="4" class="form-control" required placeholder="Specify scope of work, problems faced, dimensions, special materials, or tools required..."><?= e($description) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="location">Service Address / Work Location *</label>
                        <input type="text" id="location" name="location" class="form-control" value="<?= e($location) ?>" required placeholder="House/Flat No, Apartment, Landmark, City">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="start_date">Preferred Start Date *</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= e($startDate) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="expected_completion_date">Expected Completion Date *</label>
                        <input type="date" id="expected_completion_date" name="expected_completion_date" class="form-control" value="<?= e($expectedCompletionDate) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="payment_type">Proposed Payment Type *</label>
                        <select id="payment_type" name="payment_type" class="form-select" required>
                            <option value="Daily Rate" <?= $paymentType === 'Daily Rate' ? 'selected' : '' ?>>Daily Rate Contract</option>
                            <option value="Fixed Price" <?= $paymentType === 'Fixed Price' ? 'selected' : '' ?>>Fixed Price / Total Budget</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="agreed_wage">Proposed Budget / Wage (₹) *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="10" min="100" id="agreed_wage" name="agreed_wage" class="form-control fw-bold" value="<?= e($agreedWage) ?>" required>
                        </div>
                        <div class="form-text">Per day rate or total contract budget.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="client_contact">Your Contact Phone *</label>
                        <input type="tel" id="client_contact" name="client_contact" class="form-control" value="<?= e($clientContact) ?>" required placeholder="10-digit mobile">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="notes">Additional Access or Timing Remarks</label>
                        <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Convenient visiting hours, parking instructions, supervisor contact..."><?= e($notes) ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="<?= getBaseUrl('customer/dashboard.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        <i class="bi bi-send-fill me-2"></i>Post Work Requirement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/customer_footer.php'; ?>
