<?php
/**
 * Workido - Modern Landing Page
 * Skilled-Worker Management Platform
 */

$activePage = 'home';
$pageTitle = 'Workido - Skilled-Worker Management Platform (Carpenters, Electricians, Plumbers, Masons)';
require_once __DIR__ . '/config/functions.php';

$pdo = getDBConnection();

// Fetch live database statistics for the hero counters
$totalWorkers = 0;
$totalJobs = 0;
$completedJobs = 0;
$categoryCounts = [
    'Carpenter'   => 0,
    'Electrician' => 0,
    'Plumber'     => 0,
    'Mason'       => 0,
];

if ($pdo) {
    try {
        $totalWorkers = (int)$pdo->query("SELECT COUNT(*) FROM workers")->fetchColumn();
        $totalJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
        $completedJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Completed'")->fetchColumn();

        $catStmt = $pdo->query("SELECT category, COUNT(*) as cnt FROM workers GROUP BY category");
        while ($row = $catStmt->fetch()) {
            if (isset($categoryCounts[$row['category']])) {
                $categoryCounts[$row['category']] = (int)$row['cnt'];
            }
        }
    } catch (Exception $e) {
        // Table may not be ready yet
    }
}

// Handle Contact Form Submission
$contactSuccess = '';
$contactError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $contactError = 'Security validation failed (invalid CSRF token). Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = sanitize($_POST['phone'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (!$name || !$email || !$subject || !$message) {
            $contactError = 'Please fill in all required fields with valid details.';
        } elseif (!$pdo) {
            $contactError = 'Database connection is not available. Please verify XAMPP MySQL.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $subject, $message]);
                $contactSuccess = 'Thank you, ' . $name . '! Your inquiry has been received. Our team will contact you shortly.';
            } catch (Exception $e) {
                $contactError = 'Failed to submit message: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center gy-5">
            <div class="col-lg-7">
                <span class="hero-badge">
                    <i class="bi bi-patch-check-fill"></i> Enterprise Skilled-Worker Management
                </span>
                <h1 class="hero-title mb-4">
                    Manage Skilled Trades With <span class="text-warning">Precision & Ease</span>
                </h1>
                <p class="hero-subtitle mb-4">
                    The all-in-one platform to organize Carpenters, Electricians, Plumbers, and Masons. Assign jobs, calculate dynamic payroll wages, and track real verified performance.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= getBaseUrl('admin/dashboard.php') ?>" class="btn btn-primary btn-lg shadow">
                            <i class="bi bi-speedometer2 me-2"></i>Access Admin Dashboard
                        </a>
                        <a href="<?= getBaseUrl('admin/add_job.php') ?>" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-plus-circle me-2"></i>Post Work Order
                        </a>
                    <?php else: ?>
                        <a href="<?= getBaseUrl('login.php') ?>" class="btn btn-primary btn-lg shadow">
                            <i class="bi bi-shield-lock me-2"></i>Admin Sign In
                        </a>
                        <a href="<?= getBaseUrl('register.php') ?>" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Register New Account
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card bg-white text-dark shadow-lg border-0 rounded-4 p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary text-white p-2 rounded-3"><i class="bi bi-lightning-charge"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Live System Metrics</h6>
                                <small class="text-muted">Real-time database records</small>
                            </div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Active
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <i class="bi bi-people fs-3 text-primary"></i>
                                <h3 class="fw-bold mb-0 mt-1"><?= $totalWorkers ?></h3>
                                <small class="text-muted fw-semibold">Skilled Workers</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <i class="bi bi-briefcase fs-3 text-info"></i>
                                <h3 class="fw-bold mb-0 mt-1"><?= $totalJobs ?></h3>
                                <small class="text-muted fw-semibold">Work Orders</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <i class="bi bi-check-circle fs-3 text-success"></i>
                                <h3 class="fw-bold mb-0 mt-1"><?= $completedJobs ?></h3>
                                <small class="text-muted fw-semibold">Completed Jobs</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <i class="bi bi-currency-rupee fs-3 text-warning"></i>
                                <h3 class="fw-bold mb-0 mt-1">100%</h3>
                                <small class="text-muted fw-semibold">Payroll Accuracy</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-2">
                        <a href="<?= getBaseUrl('admin/dashboard.php') ?>" class="btn btn-outline-primary w-100 fw-semibold">
                            Open Control Center <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Worker Categories Section -->
<section id="categories" class="section-padding bg-light">
    <div class="container">
        <div class="text-center">
            <span class="section-tag">Core Trade Specializations</span>
            <h2 class="section-title">Manage 4 Primary Skilled Worker Sectors</h2>
            <p class="section-subtitle">
                Workido provides specialized profiles, daily wage tracking, and job allocations tailored to the specific needs of trade professionals.
            </p>
        </div>

        <div class="row g-4 mt-2">
            <!-- 1. Carpenters -->
            <div class="col-md-6 col-lg-3">
                <div class="category-card category-carpenter">
                    <div class="category-icon">
                        <i class="bi bi-hammer"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Carpenters</h4>
                    <p class="text-muted small mb-3">
                        Specialists in architectural woodwork, modular cabinetry, framing, structural joinery, and furniture fabrication.
                    </p>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top small">
                        <span class="text-muted">Registered Workers:</span>
                        <span class="badge bg-warning text-dark fw-bold"><?= $categoryCounts['Carpenter'] ?> Active</span>
                    </div>
                </div>
            </div>

            <!-- 2. Electricians -->
            <div class="col-md-6 col-lg-3">
                <div class="category-card category-electrician">
                    <div class="category-icon">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Electricians</h4>
                    <p class="text-muted small mb-3">
                        Licensed contractors for 3-phase wiring, circuit breaker boards, smart installations, backup inverters, and fault diagnostics.
                    </p>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top small">
                        <span class="text-muted">Registered Workers:</span>
                        <span class="badge bg-warning text-dark fw-bold"><?= $categoryCounts['Electrician'] ?> Active</span>
                    </div>
                </div>
            </div>

            <!-- 3. Plumbers -->
            <div class="col-md-6 col-lg-3">
                <div class="category-card category-plumber">
                    <div class="category-icon">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Plumbers</h4>
                    <p class="text-muted small mb-3">
                        Experts in high-pressure CPVC piping, concealed sanitary fixtures, leak detection, drainage systems, and water pumps.
                    </p>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top small">
                        <span class="text-muted">Registered Workers:</span>
                        <span class="badge bg-primary text-white fw-bold"><?= $categoryCounts['Plumber'] ?> Active</span>
                    </div>
                </div>
            </div>

            <!-- 4. Masons -->
            <div class="col-md-6 col-lg-3">
                <div class="category-card category-mason">
                    <div class="category-icon">
                        <i class="bi bi-bricks"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Masons</h4>
                    <p class="text-muted small mb-3">
                        Master craftsmen in brickwork, reinforced concrete foundations, structural plastering, stone cladding, and tile laying.
                    </p>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top small">
                        <span class="text-muted">Registered Workers:</span>
                        <span class="badge bg-danger text-white fw-bold"><?= $categoryCounts['Mason'] ?> Active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Workido Section -->
<section id="about" class="section-padding bg-white">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <span class="section-tag">About The Platform</span>
                <h2 class="section-title">Built Specifically for Contractors, Site Engineers & Business Managers</h2>
                <p class="text-muted mb-4">
                    Managing skilled trade labor has traditionally relied on scattered paper registers, confusing verbal agreements, and disputed wage records. Workido transforms this workflow into an orderly, transparent digital management suite.
                </p>
                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary text-white p-2 rounded-circle flex-shrink-0"><i class="bi bi-check-lg"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Centralized Worker Repository</h6>
                            <p class="text-muted small mb-0">Record contact details, verified skills, years of trade experience, and baseline daily wage rates in one place.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary text-white p-2 rounded-circle flex-shrink-0"><i class="bi bi-check-lg"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Precise Job Allocations</h6>
                            <p class="text-muted small mb-0">Dispatch work orders with location, expected timeline, and agreed payment terms directly to qualified workers.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary text-white p-2 rounded-circle flex-shrink-0"><i class="bi bi-check-lg"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Transparent Payroll & Wages</h6>
                            <p class="text-muted small mb-0">Automate daily wage calculations with working days, additional bonuses, and statutory deductions.</p>
                        </div>
                    </div>
                </div>
                <a href="<?= getBaseUrl('about.php') ?>" class="btn btn-secondary">Learn More About Workido</a>
            </div>

            <div class="col-lg-6">
                <div class="p-4 p-md-5 bg-light rounded-4 border">
                    <h4 class="fw-bold mb-3"><i class="bi bi-award-fill text-warning me-2"></i>Why Contractors Choose Workido</h4>
                    <ul class="list-group list-group-flush bg-transparent">
                        <li class="list-group-item bg-transparent px-0 py-3 d-flex align-items-center gap-3">
                            <i class="bi bi-graph-up-arrow fs-4 text-primary"></i>
                            <div>
                                <h6 class="mb-0 fw-semibold">Zero Division-by-Zero Performance Metric</h6>
                                <small class="text-muted">Safe completion formulas ensure dependable metrics at any scale.</small>
                            </div>
                        </li>
                        <li class="list-group-item bg-transparent px-0 py-3 d-flex align-items-center gap-3">
                            <i class="bi bi-shield-check fs-4 text-success"></i>
                            <div>
                                <h6 class="mb-0 fw-semibold">Prepared SQL Statements & CSRF Tokens</h6>
                                <small class="text-muted">Enterprise-grade security preventing SQL injection, XSS, and unauthorized requests.</small>
                            </div>
                        </li>
                        <li class="list-group-item bg-transparent px-0 py-3 d-flex align-items-center gap-3">
                            <i class="bi bi-file-earmark-spreadsheet fs-4 text-info"></i>
                            <div>
                                <h6 class="mb-0 fw-semibold">One-Click CSV Export</h6>
                                <small class="text-muted">Download payroll ledgers, worker directories, and job logs directly into Excel.</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="section-padding bg-light">
    <div class="container">
        <div class="text-center">
            <span class="section-tag">Platform Capabilities</span>
            <h2 class="section-title">Everything Needed to Oversee Field Personnel</h2>
            <p class="section-subtitle">
                Designed for speed, clarity, and reliability without unnecessary bloat or complex configuration.
            </p>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Worker Management</h5>
                    <p class="text-muted small">
                        Store worker profiles, phone contacts, trade category, skills breakdown, daily wage, and current availability status.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-calendar2-check"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Work Allocation</h5>
                    <p class="text-muted small">
                        Create jobs with scheduled start and completion dates, assign available workers, and monitor progress until sign-off.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Dynamic Wage Engine</h5>
                    <p class="text-muted small">
                        Instant calculation for daily and fixed contracts: <code>Daily Wage × Days + Extra − Deductions</code> with server-side validation.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-star-half"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Performance Tracking</h5>
                    <p class="text-muted small">
                        Automated job completion percentage tracking, punctuality ratings, and administrator feedback history.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-search"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Search & Multi-Filter</h5>
                    <p class="text-muted small">
                        Fast database search by name, code, phone, or skill with instant category, experience, and availability filtering.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-printer"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Reports & Printable Slips</h5>
                    <p class="text-muted small">
                        Comprehensive ledger reporting with printable payment vouchers and clean CSV data export capabilities.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="section-padding bg-white">
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-5">
                <span class="section-tag">Get In Touch</span>
                <h2 class="section-title">Have Questions or Need Support?</h2>
                <p class="text-muted mb-4">
                    Whether you are an individual trade worker seeking registration, a contractor managing projects, or need assistance running Workido on XAMPP, our team is ready to help.
                </p>

                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light p-3 rounded-3 text-primary"><i class="bi bi-geo-alt fs-5"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold">Operations Hub</h6>
                            <span class="text-muted small">Workido Tower, Sector 29, Gurgaon, Haryana, India</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light p-3 rounded-3 text-primary"><i class="bi bi-telephone fs-5"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold">Helpline Phone</h6>
                            <span class="text-muted small">+91 (0124) 4920-800 / +91 98765 43210</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light p-3 rounded-3 text-primary"><i class="bi bi-envelope fs-5"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold">Email Inquiries</h6>
                            <span class="text-muted small">support@workido.local / info@workido.com</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card p-4 p-md-5 border rounded-4 shadow-sm">
                    <h4 class="fw-bold mb-3">Send Us a Direct Message</h4>

                    <?php if ($contactSuccess): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                            <div><?= e($contactSuccess) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($contactError): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div><?= e($contactError) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="#contact" class="needs-validation" novalidate>
                        <?= csrfField() ?>
                        <input type="hidden" name="send_contact" value="1">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Ramesh Chandra">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="subject" class="form-control" required placeholder="Reason for contacting">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Your Message *</label>
                                <textarea name="message" rows="4" class="form-control" required placeholder="Write your requirements or inquiry here..."></textarea>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-send-fill me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
