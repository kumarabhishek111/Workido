<?php
/**
 * Workido - Worker Directory & Management
 * View, Search, and Multi-Filter Workers
 */

$activeMenu = 'workers';
$pageTitle = 'Worker Directory';
$pageSubtitle = 'Manage skilled tradesmen, view availability, and filter profiles';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

// 1. Search and Filter Parameters
$search = sanitize($_GET['search'] ?? '');
$categoryFilter = sanitize($_GET['category'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$expFilter = sanitize($_GET['experience'] ?? '');

// 2. Build Dynamic Prepared SQL Query
$sql = "SELECT w.*, 
        (SELECT COUNT(*) FROM job_assignments ja WHERE ja.worker_id = w.id) as total_jobs_count,
        (SELECT AVG(rating) FROM performance_records pr WHERE pr.worker_id = w.id) as avg_rating
        FROM workers w 
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (w.full_name LIKE ? OR w.worker_code LIKE ? OR w.mobile_number LIKE ? OR w.skills LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoryFilter !== '') {
    $sql .= " AND w.category = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter !== '') {
    $sql .= " AND w.availability_status = ?";
    $params[] = $statusFilter;
}

if ($expFilter !== '') {
    if ($expFilter === '10+') {
        $sql .= " AND w.experience_years >= 10";
    } elseif ($expFilter === '5-10') {
        $sql .= " AND w.experience_years >= 5 AND w.experience_years < 10";
    } elseif ($expFilter === '0-5') {
        $sql .= " AND w.experience_years < 5";
    }
}

$sql .= " ORDER BY w.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workers = $stmt->fetchAll();

// Category counters for badges
$totalCount = count($workers);
?>

<!-- Filter & Search Toolbar -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <!-- Search Query -->
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, ID, mobile, skill..." value="<?= e($search) ?>" data-table-search="#workersTable">
                    <?php if ($search !== ''): ?>
                        <a href="<?= getBaseUrl('admin/workers.php') ?>" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="col-sm-6 col-md-2">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <option value="Carpenter" <?= $categoryFilter === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                    <option value="Electrician" <?= $categoryFilter === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                    <option value="Plumber" <?= $categoryFilter === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                    <option value="Mason" <?= $categoryFilter === 'Mason' ? 'selected' : '' ?>>Mason</option>
                </select>
            </div>

            <!-- Availability Status Filter -->
            <div class="col-sm-6 col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Available" <?= $statusFilter === 'Available' ? 'selected' : '' ?>>Available</option>
                    <option value="Assigned" <?= $statusFilter === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                    <option value="On Leave" <?= $statusFilter === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                    <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <!-- Experience Filter -->
            <div class="col-sm-6 col-md-2">
                <select name="experience" class="form-select" onchange="this.form.submit()">
                    <option value="">Any Experience</option>
                    <option value="0-5" <?= $expFilter === '0-5' ? 'selected' : '' ?>>Under 5 Years</option>
                    <option value="5-10" <?= $expFilter === '5-10' ? 'selected' : '' ?>>5 - 10 Years</option>
                    <option value="10+" <?= $expFilter === '10+' ? 'selected' : '' ?>>10+ Years (Senior)</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-sm-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel-fill me-1"></i>Filter
                </button>
                <a href="<?= getBaseUrl('admin/workers.php') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Workers Table Card -->
<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="d-flex align-items-center gap-2">
            <h5 class="dashboard-card-title mb-0">
                <i class="bi bi-people-fill text-primary"></i> Registered Skilled Workers
            </h5>
            <span class="badge bg-primary rounded-pill"><?= $totalCount ?> Listed</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= getBaseUrl('admin/add_worker.php') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus-fill me-1"></i>Add New Worker
            </a>
            <a href="<?= getBaseUrl('admin/reports.php?export=workers') ?>" class="btn btn-outline-secondary btn-sm" title="Export Workers CSV">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-workido" id="workersTable">
            <thead>
                <tr>
                    <th>Worker ID</th>
                    <th>Worker Profile</th>
                    <th>Category</th>
                    <th>Skills / Specialization</th>
                    <th>Experience</th>
                    <th>Daily Wage</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($workers)): ?>
                    <tr class="no-filter">
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-person-x fs-1"></i></div>
                            <h6 class="fw-bold text-dark">No workers found matching your criteria</h6>
                            <p class="text-muted small">Try adjusting your search query or reset the category/status filters.</p>
                            <a href="<?= getBaseUrl('admin/workers.php') ?>" class="btn btn-sm btn-outline-primary">Reset Filters</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($workers as $w): ?>
                        <tr>
                            <td>
                                <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $w['id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                    <?= e($w['worker_code']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.85rem; background-color: #2563eb;">
                                        <?= strtoupper(substr($w['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $w['id']) ?>" class="fw-bold text-dark text-decoration-none d-block">
                                            <?= e($w['full_name']) ?>
                                        </a>
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($w['mobile_number']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= getCategoryBadge($w['category']) ?></td>
                            <td>
                                <div class="text-truncate text-muted small" style="max-width: 220px;" title="<?= e($w['skills']) ?>">
                                    <?= e($w['skills']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($w['experience_years']) ?> yrs</span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= formatCurrency($w['daily_wage']) ?></span>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">per day</small>
                            </td>
                            <td><?= getAvailabilityBadge($w['availability_status']) ?></td>
                            <td>
                                <?php if ($w['avg_rating']): ?>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                        <i class="bi bi-star-fill text-warning me-1"></i><?= number_format((float)$w['avg_rating'], 1) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">New</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $w['id']) ?>" class="btn btn-light border btn-action" title="View Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= getBaseUrl('admin/edit_worker.php?id=' . $w['id']) ?>" class="btn btn-light border btn-action text-primary" title="Edit Worker">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= getBaseUrl('admin/calculate_wage.php?worker_id=' . $w['id']) ?>" class="btn btn-light border btn-action text-success" title="Calculate Wage">
                                        <i class="bi bi-calculator"></i>
                                    </a>
                                    <form method="POST" action="<?= getBaseUrl('admin/delete_worker.php') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete worker <?= e($w['full_name']) ?> (<?= e($w['worker_code']) ?>)?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="worker_id" value="<?= $w['id'] ?>">
                                        <button type="submit" class="btn btn-light border btn-action text-danger" title="Delete Worker">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
