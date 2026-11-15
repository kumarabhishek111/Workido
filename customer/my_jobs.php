<?php
/**
 * Workido - Customer: My Bookings & Work Requests
 */

$activeCustomerMenu = 'my_jobs';
$customerPageTitle = 'My Bookings & Work Requests';

require_once __DIR__ . '/../includes/customer_header.php';

$pdo = getDBConnection();
$customerId = $user['id'];

$statusFilter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$sql = "
    SELECT j.*, 
           w.id as worker_id, w.full_name as worker_name, w.mobile_number as worker_phone, 
           w.worker_code, w.category as worker_category,
           (SELECT COUNT(*) FROM performance_records pr WHERE pr.job_id = j.id AND pr.worker_id = w.id) as is_reviewed
    FROM jobs j
    LEFT JOIN job_assignments ja ON j.id = ja.job_id
    LEFT JOIN workers w ON ja.worker_id = w.id
    WHERE j.customer_id = ?
";
$params = [$customerId];

if ($statusFilter !== '') {
    $sql .= " AND j.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $sql .= " AND (j.title LIKE ? OR j.job_code LIKE ? OR j.location LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY j.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$myJobs = $stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">My Bookings & Work Requests</h3>
        <p class="text-muted small mb-0">Track live progress, communicate with assigned tradesmen, and review completed work</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search me-1"></i>Hire Worker
        </a>
        <a href="<?= getBaseUrl('customer/post_job.php') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Post New Task
        </a>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-3">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search order title, code, location..." value="<?= e($search) ?>">
                <?php if ($search !== ''): ?>
                    <a href="<?= getBaseUrl('customer/my_jobs.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses (<?= count($myJobs) ?>)</option>
                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending (Matching Worker)</option>
                <option value="Assigned" <?= $statusFilter === 'Assigned' ? 'selected' : '' ?>>Assigned (Craftsman Allocated)</option>
                <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress (Work Underway)</option>
                <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <div class="col-sm-6 col-md-2">
            <a href="<?= getBaseUrl('customer/my_jobs.php') ?>" class="btn btn-outline-secondary w-100" title="Reset Filters">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Orders Table Card -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th class="ps-4">Job Code</th>
                    <th>Project Title</th>
                    <th>Trade Category</th>
                    <th>Assigned Craftsman</th>
                    <th>Schedule</th>
                    <th>Agreed Budget</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($myJobs)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-1 text-muted mb-2 d-block"></i>
                            <h6 class="fw-bold text-dark">No work orders match this filter</h6>
                            <p class="small text-muted mb-3">You can browse skilled trade professionals or post your task specifications.</p>
                            <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-sm btn-primary">Find a Worker</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($myJobs as $job): ?>
                        <tr>
                            <td class="ps-4">
                                <a href="<?= getBaseUrl('customer/view_job.php?id=' . $job['id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                    <?= e($job['job_code']) ?>
                                </a>
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
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.85rem; background-color: #2563eb;">
                                            <?= strtoupper(substr($job['worker_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark d-block"><?= e($job['worker_name']) ?></span>
                                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($job['worker_phone']) ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="bi bi-clock me-1"></i>Coordinators Matching
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="d-block text-dark fw-semibold"><?= formatDate($job['start_date']) ?></small>
                                <small class="text-muted">Target: <?= formatDate($job['expected_completion_date']) ?></small>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= formatCurrency($job['agreed_wage']) ?></span>
                                <small class="d-block text-muted" style="font-size: 0.72rem;"><?= e($job['payment_type']) ?></small>
                            </td>
                            <td><?= getJobStatusBadge($job['status']) ?></td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= getBaseUrl('customer/view_job.php?id=' . $job['id']) ?>" class="btn btn-light border" title="View Progress">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                    <?php if ($job['status'] === 'Completed' && $job['worker_id'] && !(int)$job['is_reviewed']): ?>
                                        <a href="<?= getBaseUrl('customer/review_worker.php?job_id=' . $job['id'] . '&worker_id=' . $job['worker_id']) ?>" class="btn btn-warning text-dark fw-semibold" title="Review Craftsman">
                                            <i class="bi bi-star-fill me-1"></i>Rate
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/customer_footer.php'; ?>
