<?php
/**
 * Workido - Admin Sidebar Navigation Template
 */
$activeMenu = $activeMenu ?? 'dashboard';
?>
<aside class="app-sidebar" id="appSidebar">
    <!-- Sidebar Brand -->
    <div class="sidebar-header">
        <a href="<?= getBaseUrl('admin/dashboard.php') ?>" class="sidebar-brand">
            <span class="sidebar-brand-icon"><i class="bi bi-tools"></i></span>
            <span>Workido</span>
        </a>
    </div>

    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <li class="menu-heading">Main Overview</li>
        <li class="menu-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/dashboard.php') ?>" class="menu-link">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-heading">Worker Operations</li>
        <li class="menu-item <?= $activeMenu === 'workers' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/workers.php') ?>" class="menu-link">
                <i class="bi bi-people-fill"></i>
                <span>Worker Directory</span>
            </a>
        </li>
        <li class="menu-item <?= $activeMenu === 'add_worker' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/add_worker.php') ?>" class="menu-link">
                <i class="bi bi-person-plus-fill"></i>
                <span>Add New Worker</span>
            </a>
        </li>

        <li class="menu-heading">Jobs & Allocation</li>
        <li class="menu-item <?= $activeMenu === 'jobs' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/jobs.php') ?>" class="menu-link">
                <i class="bi bi-briefcase-fill"></i>
                <span>All Work Orders</span>
            </a>
        </li>
        <li class="menu-item <?= $activeMenu === 'add_job' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/add_job.php') ?>" class="menu-link">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Post New Job</span>
            </a>
        </li>
        <li class="menu-item <?= $activeMenu === 'assignments' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/assignments.php') ?>" class="menu-link">
                <i class="bi bi-diagram-3-fill"></i>
                <span>Work Allocations</span>
            </a>
        </li>

        <li class="menu-heading">Finance & Payroll</li>
        <li class="menu-item <?= $activeMenu === 'wages' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/wages.php') ?>" class="menu-link">
                <i class="bi bi-cash-stack"></i>
                <span>Wage Records</span>
            </a>
        </li>
        <li class="menu-item <?= $activeMenu === 'calculate_wage' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/calculate_wage.php') ?>" class="menu-link">
                <i class="bi bi-calculator-fill"></i>
                <span>Calculate Wages</span>
            </a>
        </li>

        <li class="menu-heading">Analytics & Tracking</li>
        <li class="menu-item <?= $activeMenu === 'performance' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/performance.php') ?>" class="menu-link">
                <i class="bi bi-star-fill text-warning"></i>
                <span>Worker Performance</span>
            </a>
        </li>
        <li class="menu-item <?= $activeMenu === 'reports' ? 'active' : '' ?>">
            <a href="<?= getBaseUrl('admin/reports.php') ?>" class="menu-link">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                <span>Reports & Export</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar User / Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user mb-2">
            <div class="user-avatar">
                <?= strtoupper(substr($user['full_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= e($user['full_name'] ?? 'Administrator') ?></div>
                <div class="user-role"><?= e($user['role'] ?? 'Admin') ?></div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= getBaseUrl('logout.php') ?>" class="btn btn-outline-light btn-sm w-100 py-1" style="font-size: 0.8rem;">
                <i class="bi bi-box-arrow-right me-1"></i>Sign Out
            </a>
        </div>
    </div>
</aside>
