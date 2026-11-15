<?php
/**
 * Workido - Authentication Login Page (Admin & Customer Portal)
 */
$pageTitle = 'Sign In - Workido Platform';
$activePage = 'login';
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
$emailInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (CSRF token expired). Please try again.';
    } else {
        $emailInput = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($emailInput) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } else {
            $pdo = getDBConnection();
            if (!$pdo) {
                $error = 'Unable to connect to the database. Please ensure XAMPP MySQL is active or run install.php.';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$emailInput]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Your account has been deactivated. Please contact support.';
                    } else {
                        // Prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];

                        // Update last login
                        $updStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $updStmt->execute([$user['id']]);

                        setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');

                        // Role-aware redirection
                        if ($user['role'] === 'customer') {
                            header('Location: ' . getBaseUrl('customer/dashboard.php'));
                        } else {
                            header('Location: ' . getBaseUrl('admin/dashboard.php'));
                        }
                        exit;
                    }
                } else {
                    $error = 'Invalid email address or password. Please verify your credentials.';
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="bg-primary text-white p-4 text-center">
                    <div class="d-inline-flex p-3 bg-white bg-opacity-10 rounded-circle mb-2">
                        <i class="bi bi-person-lock fs-2 text-white"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Sign In to Workido</h3>
                    <p class="text-white-50 small mb-0">Customer Portal & Skilled-Worker Management</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                            <div><?= e($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Role Select Pill / Demo Credentials Switcher -->
                    <div class="card bg-light border p-3 mb-4 rounded-3 small">
                        <span class="fw-bold text-dark d-block mb-2">
                            <i class="bi bi-key-fill text-primary me-1"></i>Quick Demo Logins:
                        </span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm flex-fill text-start" onclick="fillCustomerCreds()">
                                <strong class="d-block text-dark"><i class="bi bi-person-check me-1"></i>Customer Login</strong>
                                <small class="text-muted">customer@example.com</small>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill text-start" onclick="fillAdminCreds()">
                                <strong class="d-block text-dark"><i class="bi bi-shield-lock me-1"></i>Admin Login</strong>
                                <small class="text-muted">admin@workido.com</small>
                            </button>
                        </div>
                    </div>

                    <form method="POST" action="" class="needs-validation" novalidate id="loginForm">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label class="form-label" for="loginEmail">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="loginEmail" name="email" class="form-control" value="<?= e($emailInput) ?>" required placeholder="name@domain.com" autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="loginPassword">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" id="loginPassword" name="password" class="form-control" required placeholder="Enter password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" onclick="togglePassVisibility()">
                                    <i class="bi bi-eye" id="togglePassIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                            </button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
                            <span>New customer?</span>
                            <a href="<?= getBaseUrl('register.php') ?>" class="text-primary fw-semibold text-decoration-none">
                                <i class="bi bi-person-plus me-1"></i>Create Customer Account
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillCustomerCreds() {
    document.getElementById('loginEmail').value = 'customer@example.com';
    document.getElementById('loginPassword').value = 'Customer@123';
}

function fillAdminCreds() {
    document.getElementById('loginEmail').value = 'admin@workido.com';
    document.getElementById('loginPassword').value = 'Admin@123';
}

function togglePassVisibility() {
    const passInput = document.getElementById('loginPassword');
    const icon = document.getElementById('togglePassIcon');
    if (passInput.type === 'password') {
        passInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
