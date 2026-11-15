<?php
/**
 * Workido - Customer: Browse & Hire Skilled Workers
 */

$activeCustomerMenu = 'workers';
$customerPageTitle = 'Find & Hire Skilled Workers';

require_once __DIR__ . '/../includes/customer_header.php';

$pdo = getDBConnection();

$categoryFilter = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$maxWage = (float)($_GET['max_wage'] ?? 0);

// Build Worker Query
$sql = "
    SELECT w.*, 
           (SELECT AVG(rating) FROM performance_records pr WHERE pr.worker_id = w.id) as avg_rating,
           (SELECT COUNT(*) FROM performance_records pr WHERE pr.worker_id = w.id) as review_count,
           (SELECT COUNT(*) FROM job_assignments ja WHERE ja.worker_id = w.id AND ja.assignment_status = 'Completed') as completed_jobs_count
    FROM workers w
    WHERE w.availability_status != 'Inactive'
";
$params = [];

if ($categoryFilter !== '') {
    $sql .= " AND w.category = ?";
    $params[] = $categoryFilter;
}

if ($search !== '') {
    $sql .= " AND (w.full_name LIKE ? OR w.skills LIKE ? OR w.address LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($maxWage > 0) {
    $sql .= " AND w.daily_wage <= ?";
    $params[] = $maxWage;
}

$sql .= " ORDER BY w.availability_status = 'Available' DESC, avg_rating DESC, w.experience_years DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workers = $stmt->fetchAll();
?>

<!-- Header Banner -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Find & Hire Verified Artisans</h3>
        <p class="text-muted small mb-0">Browse specialized tradesmen, compare ratings & daily wages, and book directly</p>
    </div>
    <a href="<?= getBaseUrl('customer/post_job.php') ?>" class="btn btn-primary fw-semibold">
        <i class="bi bi-plus-circle me-1"></i>Post Open Work Requirement
    </a>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-3">
    <form method="GET" action="" class="row g-2 align-items-center">
        <!-- Search Keyword -->
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, skill (e.g. kitchen, rewiring, tiles)..." value="<?= e($search) ?>">
                <?php if ($search !== ''): ?>
                    <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Dropdown -->
        <div class="col-sm-6 col-md-3">
            <select name="category" class="form-select" onchange="this.form.submit()">
                <option value="">All Trade Specialities</option>
                <option value="Carpenter" <?= $categoryFilter === 'Carpenter' ? 'selected' : '' ?>>Carpenter</option>
                <option value="Electrician" <?= $categoryFilter === 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                <option value="Plumber" <?= $categoryFilter === 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                <option value="Mason" <?= $categoryFilter === 'Mason' ? 'selected' : '' ?>>Mason</option>
            </select>
        </div>

        <!-- Filter Action Buttons -->
        <div class="col-sm-6 col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-funnel-fill me-1"></i>Filter Workers
            </button>
            <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Category Filter Pills -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="?category=" class="btn btn-sm <?= empty($categoryFilter) ? 'btn-dark' : 'btn-outline-secondary bg-white' ?> rounded-pill px-3">
        All Trades (<?= count($workers) ?>)
    </a>
    <a href="?category=Carpenter" class="btn btn-sm <?= $categoryFilter === 'Carpenter' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary bg-white' ?> rounded-pill px-3">
        <i class="bi bi-hammer me-1 text-warning"></i>Carpenters
    </a>
    <a href="?category=Electrician" class="btn btn-sm <?= $categoryFilter === 'Electrician' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary bg-white' ?> rounded-pill px-3">
        <i class="bi bi-lightning-charge-fill me-1 text-warning"></i>Electricians
    </a>
    <a href="?category=Plumber" class="btn btn-sm <?= $categoryFilter === 'Plumber' ? 'btn-primary text-white fw-bold' : 'btn-outline-secondary bg-white' ?> rounded-pill px-3">
        <i class="bi bi-droplet-fill me-1 text-primary"></i>Plumbers
    </a>
    <a href="?category=Mason" class="btn btn-sm <?= $categoryFilter === 'Mason' ? 'btn-danger text-white fw-bold' : 'btn-outline-secondary bg-white' ?> rounded-pill px-3">
        <i class="bi bi-bricks me-1 text-danger"></i>Masons
    </a>
</div>

<!-- Worker Cards Grid -->
<div class="row g-4 mb-4">
    <?php if (empty($workers)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-people fs-1 text-muted mb-3"></i>
                <h5 class="fw-bold text-dark">No skilled workers match your search criteria</h5>
                <p class="text-muted small mb-4">Try clearing filters or post an open job request so our coordinators can dispatch an artisan for you.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-outline-primary">Reset Filters</a>
                    <a href="<?= getBaseUrl('customer/post_job.php') ?>" class="btn btn-primary">Post Custom Requirement</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($workers as $w): 
            $rating = $w['avg_rating'] ? (float)$w['avg_rating'] : null;
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column hover-lift">
                    <!-- Top Badge Row -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <?= getCategoryBadge($w['category']) ?>
                        <?= getAvailabilityBadge($w['availability_status']) ?>
                    </div>

                    <!-- Profile Header -->
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="user-avatar" style="width: 54px; height: 54px; font-size: 1.4rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                            <?= strtoupper(substr($w['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><?= e($w['full_name']) ?></h5>
                            <span class="badge bg-secondary font-monospace small"><?= e($w['worker_code']) ?></span>
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-clock-history me-1"></i><?= e($w['experience_years']) ?> yrs experience
                            </small>
                        </div>
                    </div>

                    <!-- Ratings & Stats -->
                    <div class="d-flex align-items-center gap-3 p-2 bg-light rounded-3 mb-3 small">
                        <div>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <strong><?= $rating !== null ? number_format($rating, 1) : 'New' ?></strong>
                            <span class="text-muted">(<?= (int)$w['review_count'] ?>)</span>
                        </div>
                        <span class="text-muted">&bull;</span>
                        <div>
                            <i class="bi bi-check2-circle text-success me-1"></i>
                            <strong><?= (int)$w['completed_jobs_count'] ?></strong>
                            <span class="text-muted">Jobs done</span>
                        </div>
                    </div>

                    <!-- Skills Tags -->
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.72rem;">Specializations:</small>
                        <div class="d-flex flex-wrap gap-1">
                            <?php 
                            $skills = array_slice(explode(',', $w['skills']), 0, 3);
                            foreach ($skills as $sk): 
                                $sk = trim($sk);
                                if ($sk):
                            ?>
                                <span class="badge bg-light text-dark border small" style="font-size: 0.75rem;"><?= e($sk) ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>

                    <!-- Address & Tools -->
                    <?php if ($w['address']): ?>
                        <div class="text-muted small mb-3">
                            <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($w['address']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bottom Pricing & Hire Button -->
                    <div class="mt-auto border-top pt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.72rem;">Standard Rate</small>
                            <span class="fw-bold fs-5 text-dark"><?= formatCurrency($w['daily_wage']) ?></span>
                            <small class="text-muted">/day</small>
                        </div>

                        <?php if ($w['availability_status'] === 'Available'): ?>
                            <a href="<?= getBaseUrl('customer/hire_worker.php?worker_id=' . $w['id']) ?>" class="btn btn-primary fw-semibold px-3 py-2">
                                <i class="bi bi-calendar-check me-1"></i>Hire Worker
                            </a>
                        <?php else: ?>
                            <a href="<?= getBaseUrl('customer/hire_worker.php?worker_id=' . $w['id']) ?>" class="btn btn-outline-primary btn-sm px-3 py-2">
                                <i class="bi bi-calendar-plus me-1"></i>Reserve
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/customer_footer.php'; ?>
