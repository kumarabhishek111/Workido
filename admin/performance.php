<?php
/**
 * Workido - Worker Performance Analytics & Tracking
 * Tracks Total Assigned, Completed, Pending, Cancelled, and Completion Rates
 */

$activeMenu = 'performance';
$pageTitle = 'Worker Performance Tracking';
$pageSubtitle = 'Monitor job completion percentages, supervisor ratings, and workmanship scorecards';

require_once __DIR__ . '/../includes/admin_header.php';

$pdo = getDBConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error.</div>';
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}

$categoryFilter = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['search'] ?? '');

// 1. Fetch Workers with dynamic completion counts and ratings
$sql = "
    SELECT w.*,
        COUNT(ja.id) as total_assigned,
        SUM(CASE WHEN j.status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN j.status IN ('Assigned', 'In Progress', 'Pending') THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN j.status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count,
        (SELECT AVG(rating) FROM performance_records pr WHERE pr.worker_id = w.id) as avg_rating,
        (SELECT COUNT(*) FROM performance_records pr WHERE pr.worker_id = w.id) as total_reviews
    FROM workers w
    LEFT JOIN job_assignments ja ON w.id = ja.worker_id
    LEFT JOIN jobs j ON ja.job_id = j.id
    WHERE 1=1
";
$params = [];

if ($categoryFilter !== '') {
    $sql .= " AND w.category = ?";
    $params[] = $categoryFilter;
}

if ($search !== '') {
    $sql .= " AND (w.full_name LIKE ? OR w.worker_code LIKE ? OR w.skills LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " GROUP BY w.id ORDER BY avg_rating DESC, completed_count DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workersPerformance = $stmt->fetchAll();

// Top Level Summary Stats
$overallAssigned = 0;
$overallCompleted = 0;
foreach ($workersPerformance as $wp) {
    $overallAssigned += (int)$wp['total_assigned'];
    $overallCompleted += (int)$wp['completed_count'];
}
$overallCompletionRate = calculateCompletionRate($overallCompleted, $overallAssigned);
?>

<!-- Performance Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card card-blue p-3">
            <span class="stat-label">Total Assigned Jobs</span>
            <div class="stat-value"><?= number_format($overallAssigned) ?></div>
            <small class="text-muted">Total tasks dispatched to personnel</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card card-emerald p-3">
            <span class="stat-label">Completed Work Orders</span>
            <div class="stat-value text-success"><?= number_format($overallCompleted) ?></div>
            <small class="text-muted">Fully verified & signed off</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card card-cyan p-3">
            <span class="stat-label">Platform Completion Rate</span>
            <div class="stat-value text-primary"><?= $overallCompletionRate ?>%</div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: <?= $overallCompletionRate ?>%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card card-amber p-3">
            <span class="stat-label">Zero Division-by-Zero</span>
            <div class="stat-value text-warning"><i class="bi bi-shield-check"></i> Safe</div>
            <small class="text-muted">Protected algorithmic metric</small>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="dashboard-card mb-4">
    <div class="dashboard-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search worker name, code, trade..." value="<?= e($search) ?>" data-table-search="#perfTable">
                    <?php if ($search !== ''): ?>
                        <a href="<?= getBaseUrl('admin/performance.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">All Trade Categories</option>
                    <option value="Carpenter" <?= $categoryFilter === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                    <option value="Electrician" <?= $categoryFilter === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                    <option value="Plumber" <?= $categoryFilter === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                    <option value="Mason" <?= $categoryFilter === 'Mason' ? 'selected' : '' ?>>Mason</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
                <a href="<?= getBaseUrl('admin/performance.php') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Performance Scorecards Table -->
<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="d-flex align-items-center gap-2">
            <h5 class="dashboard-card-title mb-0">
                <i class="bi bi-star-fill text-warning"></i> Worker Performance & Completion Scorecards
            </h5>
            <span class="badge bg-secondary rounded-pill"><?= count($workersPerformance) ?> Workers</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= getBaseUrl('admin/add_performance.php') ?>" class="btn btn-warning btn-sm text-dark">
                <i class="bi bi-plus-circle me-1"></i>Record Review
            </a>
            <a href="<?= getBaseUrl('admin/reports.php?export=performance') ?>" class="btn btn-outline-secondary btn-sm" title="Export Performance CSV">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-workido" id="perfTable">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Category</th>
                    <th class="text-center">Total Assigned</th>
                    <th class="text-center">Completed</th>
                    <th class="text-center">In Progress</th>
                    <th class="text-center">Cancelled</th>
                    <th style="min-width: 170px;">Completion Rate</th>
                    <th>Average Rating</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($workersPerformance)): ?>
                    <tr class="no-filter">
                        <td colspan="9" class="text-center py-5 text-muted">No worker records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($workersPerformance as $wp): 
                        $totalAssigned = (int)$wp['total_assigned'];
                        $compCount = (int)$wp['completed_count'];
                        $pendCount = (int)$wp['pending_count'];
                        $cancCount = (int)$wp['cancelled_count'];

                        // DIVISION-BY-ZERO PROTECTED CALCULATION:
                        $completionRate = calculateCompletionRate($compCount, $totalAssigned);
                        $rating = $wp['avg_rating'] ? (float)$wp['avg_rating'] : null;
                    ?>
                        <tr>
                            <td>
                                <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $wp['id']) ?>" class="fw-bold text-dark text-decoration-none d-block">
                                    <?= e($wp['full_name']) ?>
                                </a>
                                <small class="text-muted font-monospace"><?= e($wp['worker_code']) ?></small>
                            </td>
                            <td><?= getCategoryBadge($wp['category']) ?></td>
                            <td class="text-center"><span class="badge bg-light text-dark border px-2 py-1"><?= $totalAssigned ?></span></td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><?= $compCount ?></span></td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><?= $pendCount ?></span></td>
                            <td class="text-center"><span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><?= $cancCount ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold small" style="width: 45px;"><?= $completionRate ?>%</span>
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $completionRate >= 80 ? 'bg-success' : ($completionRate >= 50 ? 'bg-primary' : 'bg-warning') ?>" 
                                             role="progressbar" 
                                             style="width: <?= $completionRate ?>%" 
                                             aria-valuenow="<?= $completionRate ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($rating !== null): ?>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                        <i class="bi bi-star-fill text-warning me-1"></i><?= number_format($rating, 1) ?> / 5.0
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;"><?= (int)$wp['total_reviews'] ?> reviews</small>
                                <?php else: ?>
                                    <span class="text-muted small">No reviews yet</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= getBaseUrl('admin/view_worker.php?id=' . $wp['id']) ?>#tab-performance" class="btn btn-light border btn-action" title="View Full Scorecard">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= getBaseUrl('admin/add_performance.php?worker_id=' . $wp['id']) ?>" class="btn btn-light border btn-action text-warning" title="Add Performance Review">
                                        <i class="bi bi-star"></i>
                                    </a>
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
