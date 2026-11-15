<?php
/**
 * Workido - One-Click Database Setup & Installer
 * Creates workido_db and imports schema/seed data.
 */

require_once __DIR__ . '/config/functions.php';

$message = '';
$error = '';
$alreadyInstalled = false;

// Check if database already exists with tables
try {
    $dsnTest = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdoTest = new PDO($dsnTest, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdoTest->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $alreadyInstalled = true;
    }
} catch (Exception $e) {
    // Database or tables do not exist yet
    $alreadyInstalled = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        // Connect to MySQL server without database specified
        $serverDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $pdoServer = new PDO($serverDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $sqlFile = __DIR__ . '/database/workido_db.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found at " . $sqlFile);
        }

        $sqlContent = file_get_contents($sqlFile);
        
        // Split queries by semicolon (basic parser for multi-statement execution)
        $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdoServer->exec("USE `" . DB_NAME . "`;");

        // Execute SQL queries
        // PDO allows multiple statements in one exec if MySQL supports it
        $pdoServer->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
        $pdoServer->exec($sqlContent);

        $message = "Database 'workido_db' and all required tables have been successfully installed and seeded with initial demo data!";
        $alreadyInstalled = true;
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workido - Database Setup & Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                    <div class="bg-primary text-white p-4 text-center">
                        <div class="d-inline-flex p-3 bg-white bg-opacity-10 rounded-circle mb-3">
                            <i class="bi bi-tools fs-1 text-white"></i>
                        </div>
                        <h2 class="fw-bold mb-1">Workido Setup</h2>
                        <p class="mb-0 text-white-50">Skilled-Worker Management Database Installer</p>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <?php if ($message): ?>
                            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                                <div><?= e($message) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                                <div><?= e($error) ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h5 class="fw-bold text-dark mb-2">Environment Configuration</h5>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">MySQL Host:</span>
                                    <span class="fw-semibold"><?= e(DB_HOST) ?>:<?= e(DB_PORT) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">MySQL User:</span>
                                    <span class="fw-semibold"><?= e(DB_USER) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Target Database:</span>
                                    <span class="badge bg-primary px-2 py-1"><?= e(DB_NAME) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Database Status:</span>
                                    <?php if ($alreadyInstalled): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Installed & Ready</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Not Installed</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>

                        <div class="card bg-light border-0 p-3 mb-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-lock me-2 text-primary"></i>Initial Test Accounts</h6>
                            <p class="small text-muted mb-2">Once installed, you can immediately sign in with:</p>
                            <div class="row g-2 small">
                                <div class="col-sm-6 border-end">
                                    <strong class="text-primary d-block">Admin Account:</strong>
                                    <div>Email: <code>admin@workido.com</code></div>
                                    <div>Pass: <code>Admin@123</code></div>
                                </div>
                                <div class="col-sm-6 ps-sm-3">
                                    <strong class="text-success d-block">Customer / Client Account:</strong>
                                    <div>Email: <code>customer@example.com</code></div>
                                    <div>Pass: <code>Customer@123</code></div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="install">
                            
                            <?php if ($alreadyInstalled): ?>
                                <div class="d-grid gap-2">
                                    <a href="login.php" class="btn btn-primary btn-lg fw-semibold shadow-sm">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Go to Admin Login
                                    </a>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-house me-2"></i>Visit Home Page
                                    </a>
                                    <button type="submit" class="btn btn-outline-danger btn-sm mt-3" onclick="return confirm('Re-installing will reset and re-seed all tables. Are you sure you wish to proceed?');">
                                        <i class="bi bi-arrow-repeat me-1"></i>Re-Install / Reset Database
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg fw-semibold shadow">
                                        <i class="bi bi-lightning-charge-fill me-2"></i>Install Workido Database Now
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
