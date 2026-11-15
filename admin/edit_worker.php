<?php
/**
 * Workido - Edit Skilled Worker Details
 */

$activeMenu = 'workers';
$pageTitle = 'Edit Worker Profile';
$pageSubtitle = 'Update worker information, category, daily wage, and availability';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$workerId = (int)($_GET['id'] ?? $_POST['worker_id'] ?? 0);
if ($workerId <= 0) {
    setFlash('danger', 'Invalid worker identification.');
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}

// Fetch Worker Record
$stmt = $pdo->prepare("SELECT * FROM workers WHERE id = ? LIMIT 1");
$stmt->execute([$workerId]);
$worker = $stmt->fetch();

if (!$worker) {
    setFlash('danger', 'Worker record not found.');
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $mobileNumber = sanitize($_POST['mobile_number'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $category = sanitize($_POST['category'] ?? 'Carpenter');
        $skills = sanitize($_POST['skills'] ?? '');
        $experienceYears = (float)($_POST['experience_years'] ?? 0);
        $dailyWage = (float)($_POST['daily_wage'] ?? 0);
        $availabilityStatus = sanitize($_POST['availability_status'] ?? 'Available');
        $joiningDate = sanitize($_POST['joining_date'] ?? date('Y-m-d'));
        $additionalDetails = sanitize($_POST['additional_details'] ?? '');

        // Validation
        if (!$fullName) {
            $error = 'Please provide the worker\'s full name.';
        } elseif (!$mobileNumber) {
            $error = 'Please provide a valid mobile number.';
        } elseif (!in_array($category, ['Carpenter', 'Electrician', 'Plumber', 'Mason'])) {
            $error = 'Please select a valid worker category.';
        } elseif ($dailyWage <= 0) {
            $error = 'Please enter a valid daily wage rate greater than 0.';
        } else {
            try {
                $updStmt = $pdo->prepare("
                    UPDATE workers SET 
                        full_name = ?, mobile_number = ?, email = ?, address = ?, category = ?,
                        skills = ?, experience_years = ?, daily_wage = ?, availability_status = ?,
                        joining_date = ?, additional_details = ?
                    WHERE id = ?
                ");
                $updStmt->execute([
                    $fullName,
                    $mobileNumber,
                    $email ?: null,
                    $address,
                    $category,
                    $skills,
                    $experienceYears,
                    $dailyWage,
                    $availabilityStatus,
                    $joiningDate,
                    $additionalDetails ?: null,
                    $workerId
                ]);

                setFlash('success', "Worker '{$fullName}' details have been successfully updated.");
                header('Location: ' . getBaseUrl('admin/view_worker.php?id=' . $workerId));
                exit;
            } catch (Exception $e) {
                $error = 'Failed to update worker: ' . $e->getMessage();
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
                    <i class="bi bi-pencil-square text-primary"></i> Edit Profile: <?= e($worker['full_name']) ?> (<?= e($worker['worker_code']) ?>)
                </h5>
                <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $workerId) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Profile
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
                    <input type="hidden" name="worker_id" value="<?= $worker['id'] ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Worker Code</label>
                            <input type="text" class="form-control font-monospace fw-bold" value="<?= e($worker['worker_code']) ?>" readonly disabled>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($worker['full_name']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="mobile_number">Mobile Phone *</label>
                            <input type="tel" id="mobile_number" name="mobile_number" class="form-control" value="<?= e($worker['mobile_number']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="category">Worker Category *</label>
                            <select id="category" name="category" class="form-select" required>
                                <option value="Carpenter" <?= $worker['category'] === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                                <option value="Electrician" <?= $worker['category'] === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                                <option value="Plumber" <?= $worker['category'] === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                                <option value="Mason" <?= $worker['category'] === 'Mason' ? 'selected' : '' ?>>Mason</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= e($worker['email']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="daily_wage">Daily Wage Rate (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="10" min="100" id="daily_wage" name="daily_wage" class="form-control fw-bold" value="<?= e($worker['daily_wage']) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="experience_years">Experience (Years) *</label>
                            <input type="number" step="0.5" min="0" max="60" id="experience_years" name="experience_years" class="form-control" value="<?= e($worker['experience_years']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="availability_status">Availability Status *</label>
                            <select id="availability_status" name="availability_status" class="form-select" required>
                                <option value="Available" <?= $worker['availability_status'] === 'Available' ? 'selected' : '' ?>>Available for Assignment</option>
                                <option value="Assigned" <?= $worker['availability_status'] === 'Assigned' ? 'selected' : '' ?>>Assigned (On Duty)</option>
                                <option value="On Leave" <?= $worker['availability_status'] === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                                <option value="Inactive" <?= $worker['availability_status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="joining_date">Joining Date *</label>
                            <input type="date" id="joining_date" name="joining_date" class="form-control" value="<?= e($worker['joining_date']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="skills">Skills & Technical Specializations *</label>
                            <input type="text" id="skills" name="skills" class="form-control" value="<?= e($worker['skills']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="address">Residential Address *</label>
                            <textarea id="address" name="address" rows="2" class="form-control" required><?= e($worker['address']) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="additional_details">Additional Worker Notes / Inventory</label>
                            <textarea id="additional_details" name="additional_details" rows="3" class="form-control"><?= e($worker['additional_details']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $workerId) ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i>Update Worker
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
