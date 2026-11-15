<?php
/**
 * Workido - Add New Skilled Worker
 */

$activeMenu = 'add_worker';
$pageTitle = 'Add New Skilled Worker';
$pageSubtitle = 'Create worker profile, trade category, and wage standards';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$workerCode = generateWorkerCode($pdo);
$error = '';
$fullName = '';
$mobileNumber = '';
$email = '';
$address = '';
$category = 'Carpenter';
$skills = '';
$experienceYears = '3.0';
$dailyWage = '800.00';
$availabilityStatus = 'Available';
$joiningDate = date('Y-m-d');
$additionalDetails = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired).';
    } else {
        $workerCode = sanitize($_POST['worker_code'] ?? $workerCode);
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
            // Check for duplicate worker code
            $chkStmt = $pdo->prepare("SELECT id FROM workers WHERE worker_code = ? LIMIT 1");
            $chkStmt->execute([$workerCode]);
            if ($chkStmt->fetch()) {
                $workerCode = generateWorkerCode($pdo); // regenerate if taken
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO workers 
                    (worker_code, full_name, mobile_number, email, address, category, skills, experience_years, daily_wage, availability_status, joining_date, additional_details)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $workerCode,
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
                    $additionalDetails ?: null
                ]);

                $newId = $pdo->lastInsertId();
                setFlash('success', "Worker '{$fullName}' ({$workerCode}) was registered successfully!");
                header('Location: ' . getBaseUrl('admin/view_worker.php?id=' . $newId));
                exit;
            } catch (Exception $e) {
                $error = 'Failed to register worker: ' . $e->getMessage();
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
                    <i class="bi bi-person-plus-fill text-primary"></i> Skilled Worker Registration Form
                </h5>
                <a href="<?= getBaseUrl('admin/workers.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Directory
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
                        <div class="col-md-4">
                            <label class="form-label" for="worker_code">Worker Code / ID *</label>
                            <input type="text" id="worker_code" name="worker_code" class="form-control font-monospace fw-bold" value="<?= e($workerCode) ?>" required readonly>
                            <div class="form-text">System-generated unique identifier.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($fullName) ?>" required placeholder="e.g. Anand Kumar">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="mobile_number">Mobile Phone *</label>
                            <input type="tel" id="mobile_number" name="mobile_number" class="form-control" value="<?= e($mobileNumber) ?>" required placeholder="10-digit mobile number">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="category">Worker Category *</label>
                            <select id="category" name="category" class="form-select" required>
                                <option value="Carpenter" <?= $category === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                                <option value="Electrician" <?= $category === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                                <option value="Plumber" <?= $category === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                                <option value="Mason" <?= $category === 'Mason' ? 'selected' : '' ?>>Mason</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="email">Email Address (Optional)</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" placeholder="worker@example.com">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="daily_wage">Baseline Daily Wage (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="10" min="100" id="daily_wage" name="daily_wage" class="form-control fw-bold" value="<?= e($dailyWage) ?>" required placeholder="800.00">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="experience_years">Experience (Years) *</label>
                            <input type="number" step="0.5" min="0" max="60" id="experience_years" name="experience_years" class="form-control" value="<?= e($experienceYears) ?>" required placeholder="e.g. 5.0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="availability_status">Availability Status *</label>
                            <select id="availability_status" name="availability_status" class="form-select" required>
                                <option value="Available" <?= $availabilityStatus === 'Available' ? 'selected' : '' ?>>Available for Assignment</option>
                                <option value="Assigned" <?= $availabilityStatus === 'Assigned' ? 'selected' : '' ?>>Assigned (On Duty)</option>
                                <option value="On Leave" <?= $availabilityStatus === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                                <option value="Inactive" <?= $availabilityStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="joining_date">Joining Date *</label>
                            <input type="date" id="joining_date" name="joining_date" class="form-control" value="<?= e($joiningDate) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="skills">Skills & Technical Specializations *</label>
                            <input type="text" id="skills" name="skills" class="form-control" value="<?= e($skills) ?>" required placeholder="e.g. Modular Kitchens, Concealed Wiring, Tile Leveling, High-pressure Plumbing">
                            <div class="form-text">Comma-separated list of practical skills, certifications, and equipment expertise.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="address">Residential Address *</label>
                            <textarea id="address" name="address" rows="2" class="form-control" required placeholder="House number, Street, City, State, PIN"><?= e($address) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="additional_details">Additional Worker Notes / Tool Inventory</label>
                            <textarea id="additional_details" name="additional_details" rows="3" class="form-control" placeholder="Owns power tools, safety kit, ID verification remarks, supervisor recommendations..."><?= e($additionalDetails) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= getBaseUrl('admin/workers.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i>Save & Register Worker
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
