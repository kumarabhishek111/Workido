<?php
/**
 * Workido - Customer Dashboard
 * Overview of customer's service requests, hired workers, and project status
 */

$activeCustomerMenu = 'dashboard';
$customerPageTitle = 'My Client Dashboard';

require_once __DIR__ . '/../includes/customer_header.php';

$pdo = getDBConnection();
$customerId = $user['id'];

// Fetch Customer Stats
$totalRequests = 0;
$inProgressCount = 0;
$completedCount = 0;
$recentJobs = [];

if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $totalRequests = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE customer_id = ? AND status IN ('Assigned', 'In Progress', 'Pending')");
        $stmt->execute([$customerId]);
        $inProgressCount = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE customer_id = ? AND status = 'Completed'");
        $stmt->execute([$customerId]);
        $completedCount = (int)$stmt->fetchColumn();

        // Recent customer jobs
        $jStmt = $pdo->prepare("
            SELECT j.*, w.id as worker_id, w.full_name as worker_name, w.mobile_number as worker_phone, w.worker_code, w.category as worker_category
            FROM jobs j
            LEFT JOIN job_assignments ja ON j.id = ja.job_id
            LEFT JOIN workers w ON ja.worker_id = w.id
            WHERE j.customer_id = ?
            ORDER BY j.id DESC LIMIT 5
        ");
        $jStmt->execute([$customerId]);
        $recentJobs = $jStmt->fetchAll();

        // Top Available Skilled Workers for Recommendations
        $topWorkersStmt = $pdo->query("
            SELECT w.*, 
                   (SELECT AVG(rating) FROM performance_records pr WHERE pr.worker_id = w.id) as avg_rating
            FROM workers w 
            WHERE w.availability_status = 'Available'
            ORDER BY avg_rating DESC, experience_years DESC LIMIT 4
        ");
        $recommendedWorkers = $topWorkersStmt->fetchAll();

    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error retrieving dashboard: ' . e($e->getMessage()) . '</div>';
    }
}
?>

<!-- Welcome Banner -->
<div class="card border-0 rounded-4 shadow-sm mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff;">
    <div class="card-body p-4 p-md-5">
        <div class="row align-items-center gy-4">
            <div class="col-lg-8">
                <span class="badge bg-primary px-3 py-2 rounded-pill mb-2"><i class="bi bi-star-fill me-1 text-warning"></i>Verified Trade Marketplace</span>
                <h2 class="fw-bold mb-2">Welcome, <?= e($user['full_name']) ?>!</h2>
                <p class="text-white-50 mb-4" style="max-width: 600px;">
                    Hire background-checked master <strong>Carpenters</strong>, licensed <strong>Electricians</strong>, certified <strong>Plumbers</strong>, and structural <strong>Masons</strong> for your maintenance and construction projects.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-primary fw-semibold px-4 py-2">
                        <i class="bi bi-search me-2"></i>Find & Hire Workers
                    </a>
                    <a href="<?= getBaseUrl('customer/post_job.php') ?>" class="btn btn-outline-light fw-semibold px-4 py-2">
                        <i class="bi bi-plus-circle me-2"></i>Post Work Requirement
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <i class="bi bi-tools display-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Customer Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Active Bookings</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1"><?= $inProgressCount ?></h3>
                </div>
                <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-4">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Work orders currently in progress</small>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Completed Work</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= $completedCount ?></h3>
                </div>
                <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Finished and verified projects</small>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Total Requests</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= $totalRequests ?></h3>
                </div>
                <div class="bg-secondary-subtle text-secondary p-3 rounded-circle fs-4">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">All historical orders created by you</small>
        </div>
    </div>
</div>

<!-- Recent Service Requests Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-clock-history text-primary me-2"></i>My Recent Work Orders & Hires
        </h5>
        <a href="<?= getBaseUrl('customer/my_jobs.php') ?>" class="btn btn-sm btn-outline-primary">View All Bookings</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th class="ps-4">Order Code</th>
                    <th>Job Title</th>
                    <th>Trade Category</th>
                    <th>Assigned Craftsman</th>
                    <th>Agreed Budget</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentJobs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-briefcase fs-1 text-muted mb-2 d-block"></i>
                            <h6 class="fw-bold text-dark">You have not posted any work orders yet</h6>
                            <p class="small text-muted mb-3">Browse our top rated trade professionals or post your task specifications.</p>
                            <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-people-fill me-1"></i>Hire a Skilled Worker Now
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentJobs as $job): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="font-monospace fw-bold text-primary"><?= e($job['job_code']) ?></span>
                            </td>
                            <td>
                                <a href="<?= getBaseUrl('customer/view_job.php?id=' . $job['id']) ?>" class="fw-bold text-dark text-decoration-none d-block">
                                    <?= e($job['title']) ?>
                                </a>
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($job['location']) ?></small>
                            </td>
                            <td><?= getCategoryBadge($job['category']) ?></td>
                            <td>
                                <?php if ($job['worker_name']): ?>
                                    <div class="fw-semibold text-dark"><?= e($job['worker_name']) ?></div>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($job['worker_phone']) ?></small>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="bi bi-clock me-1"></i>Awaiting Dispatch
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= formatCurrency($job['agreed_wage']) ?></span>
                                <small class="text-muted d-block" style="font-size: 0.72rem;"><?= e($job['payment_type']) ?></small>
                            </td>
                            <td><?= getJobStatusBadge($job['status']) ?></td>
                            <td class="text-end pe-4">
                                <a href="<?= getBaseUrl('customer/view_job.php?id=' . $job['id']) ?>" class="btn btn-sm btn-light border" title="View Full Details">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recommended Available Workers -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark">Available Top-Rated Artisans</h5>
            <small class="text-muted">Directly book verified craftsmen currently open for assignments</small>
        </div>
        <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-sm btn-link text-decoration-none">
            Browse All Workers &rarr;
        </a>
    </div>

    <div class="row g-3">
        <?php foreach ($recommendedWorkers as $wk): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card border rounded-4 p-3 bg-white h-100 shadow-sm d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <?= getCategoryBadge($wk['category']) ?>
                        <?php if ($wk['avg_rating']): ?>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                <i class="bi bi-star-fill text-warning me-1"></i><?= number_format((float)$wk['avg_rating'], 1) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="text-center my-2">
                        <div class="user-avatar mx-auto mb-2" style="width: 50px; height: 50px; font-size: 1.25rem; background-color: #2563eb;">
                            <?= strtoupper(substr($wk['full_name'], 0, 1)) ?>
                        </div>
                        <h6 class="fw-bold mb-0 text-dark"><?= e($wk['full_name']) ?></h6>
                        <small class="text-muted font-monospace"><?= e($wk['worker_code']) ?></small>
                    </div>

                    <div class="text-muted small text-center mb-3 text-truncate" title="<?= e($wk['skills']) ?>">
                        <?= e($wk['skills']) ?>
                    </div>

                    <div class="mt-auto border-top pt-2 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.72rem;">Daily Wage</small>
                            <span class="fw-bold text-dark"><?= formatCurrency($wk['daily_wage']) ?></span>
                        </div>
                        <a href="<?= getBaseUrl('customer/hire_worker.php?worker_id=' . $wk['id']) ?>" class="btn btn-sm btn-primary px-3">
                            <i class="bi bi-calendar-plus me-1"></i>Hire
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/customer_footer.php'; ?>
