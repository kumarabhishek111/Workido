<?php
/**
 * Workido - Dedicated Contact Page
 */
$activePage = 'contact';
$pageTitle = 'Contact Us - Workido Support';
require_once __DIR__ . '/config/functions.php';

$pdo = getDBConnection();
$contactSuccess = '';
$contactError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $contactError = 'Security validation failed (invalid CSRF token). Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = sanitize($_POST['phone'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (!$name || !$email || !$subject || !$message) {
            $contactError = 'Please complete all required fields with valid details.';
        } elseif (!$pdo) {
            $contactError = 'Database connection error. Please ensure XAMPP MySQL is active.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $subject, $message]);
                $contactSuccess = 'Thank you, ' . $name . '! Your message has been safely recorded. A Workido support representative will respond shortly.';
            } catch (Exception $e) {
                $contactError = 'Failed to submit inquiry: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-light border-bottom">
    <div class="container text-center">
        <span class="section-tag">Direct Communication</span>
        <h1 class="display-5 fw-bold text-dark mb-2">Contact Workido</h1>
        <p class="text-muted mx-auto" style="max-width: 600px;">
            Get in touch with our skilled worker coordinators or request assistance with your Workido enterprise account.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row gy-5">
        <div class="col-lg-5">
            <h3 class="fw-bold mb-4">Contact Information</h3>
            
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-geo-alt-fill fs-4"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Corporate Office</h6>
                        <p class="text-muted small mb-0">Workido Tower, Sector 29, Gurgaon, NCR 122002</p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-telephone-fill fs-4"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Direct Helpdesk</h6>
                        <p class="text-muted small mb-0">+91 (0124) 4920-800</p>
                        <p class="text-muted small mb-0">+91 98765 43210 (Toll Free)</p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-envelope-fill fs-4"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Electronic Mail</h6>
                        <p class="text-muted small mb-0">support@workido.local</p>
                        <p class="text-muted small mb-0">admin@workido.com</p>
                    </div>
                </div>
            </div>

            <div class="card bg-dark text-white p-4 rounded-4 border-0">
                <h6 class="fw-bold mb-2"><i class="bi bi-clock me-2 text-warning"></i>Operational Timings</h6>
                <p class="small text-white-50 mb-1">Monday – Saturday: 8:00 AM – 7:00 PM</p>
                <p class="small text-white-50 mb-0">Sunday: Closed for routine system backups</p>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4">
                <h4 class="fw-bold mb-3">Send Inquiry</h4>

                <?php if ($contactSuccess): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                        <div><?= e($contactSuccess) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($contactError): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <div><?= e($contactError) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <?= csrfField() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required placeholder="Your name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" required placeholder="name@domain.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Mobile number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subject *</label>
                            <input type="text" name="subject" class="form-control" required placeholder="Topic of discussion">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Detailed Message *</label>
                            <textarea name="message" rows="5" class="form-control" required placeholder="Type your message here..."></textarea>
                        </div>
                        <div class="col-12 pt-2">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-send-fill me-2"></i>Submit Inquiry
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
