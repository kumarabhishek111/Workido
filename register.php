<?php
/**
 * Workido - User / Customer Registration Page
 */
$pageTitle = 'Create Account - Workido Platform';
$activePage = 'register';
require_once __DIR__ . '/config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isCustomer()) {
        header('Location: ' . getBaseUrl('customer/dashboard.php'));
    } else {
        header('Location: ' . getBaseUrl('admin/dashboard.php'));
    }
    exit;
}

$error = '';
$fullName = '';
$email = '';
$phone = '';
$address = '';
$role = 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired). Please try again.';
    } else {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['customer', 'admin', 'manager', 'staff']) ? $_POST['role'] : 'customer';

        if (!$fullName || !$email || !$password) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match. Please verify your password confirmation.';
        } else {
            $pdo = getDBConnection();
            if (!$pdo) {
                $error = 'Database connection error. Please ensure XAMPP MySQL is active or run install.php.';
            } else {
                // Check for duplicate email
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = 'An account with this email address already exists. Please sign in instead.';
                } else {
                    try {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, address, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                        $insertStmt->execute([$fullName, $email, $phone ?: null, $address ?: null, $hashedPassword, $role]);

                        $newUserId = $pdo->lastInsertId();

                        // Automatically log in the user upon registration
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['user_name'] = $fullName;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;

                        if ($role === 'customer') {
                            setFlash('success', "Welcome to Workido, {$fullName}! Your customer account is ready. You can now browse and hire skilled workers.");
                            header('Location: ' . getBaseUrl('customer/dashboard.php'));
                        } else {
                            setFlash('success', "Welcome, {$fullName}! Your administrative account has been created.");
                            header('Location: ' . getBaseUrl('admin/dashboard.php'));
                        }
                        exit;
                    } catch (Exception $e) {
                        $error = 'Registration failed: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="bg-primary text-white p-4 text-center">
                    <div class="d-inline-flex p-3 bg-white bg-opacity-10 rounded-circle mb-2">
                        <i class="bi bi-person-plus-fill fs-2 text-white"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Join Workido</h3>
                    <p class="text-white-50 small mb-0">Create an account to hire skilled trade workers or manage operations</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                            <div><?= e($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <?= csrfField() ?>

                        <!-- Account Type Selector -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label class="form-label fw-bold text-dark d-block mb-2">Select Your Account Type *</label>
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <div class="form-check p-3 border rounded-3 bg-white h-100">
                                        <input class="form-check-input ms-0 me-2" type="radio" name="role" id="roleCustomer" value="customer" <?= $role === 'customer' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold text-dark" for="roleCustomer">
                                            <i class="bi bi-person-badge text-primary me-1"></i>Customer / Client
                                            <small class="d-block text-muted fw-normal" style="font-size: 0.785rem;">Hire carpenters, electricians, plumbers & masons for your projects</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check p-3 border rounded-3 bg-white h-100">
                                        <input class="form-check-input ms-0 me-2" type="radio" name="role" id="roleAdmin" value="admin" <?= $role === 'admin' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold text-dark" for="roleAdmin">
                                            <i class="bi bi-shield-lock text-secondary me-1"></i>Administrator / Contractor
                                            <small class="d-block text-muted fw-normal" style="font-size: 0.785rem;">Manage worker registry, work allocation, payroll & reports</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="regFullName">Full Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" id="regFullName" name="full_name" class="form-control" value="<?= e($fullName) ?>" required placeholder="e.g. Vikram Singhania">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="regEmail">Email Address *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="regEmail" name="email" class="form-control" value="<?= e($email) ?>" required placeholder="name@domain.com">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="regPhone">Phone / Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" id="regPhone" name="phone" class="form-control" value="<?= e($phone) ?>" placeholder="10-digit mobile number">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="regAddress">City / Location</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" id="regAddress" name="address" class="form-control" value="<?= e($address) ?>" placeholder="e.g. Gurgaon, Sector 57">
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label" for="regPassword">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="regPassword" name="password" class="form-control" required minlength="6" placeholder="Min. 6 chars">
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label" for="regConfirmPassword">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" id="regConfirmPassword" name="confirm_password" class="form-control" required minlength="6" placeholder="Repeat password">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold shadow-sm">
                                <i class="bi bi-check-circle-fill me-2"></i>Create Account
                            </button>
                        </div>

                        <div class="text-center small text-muted">
                            Already registered? 
                            <a href="<?= getBaseUrl('login.php') ?>" class="text-primary fw-semibold text-decoration-none">Sign In Here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
