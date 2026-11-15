<?php
/**
 * Workido - Admin Area Header Template
 */
require_once __DIR__ . '/auth_check.php';

$activeMenu = $activeMenu ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Admin Dashboard - Workido';
$pageSubtitle = $pageSubtitle ?? 'Skilled-Worker Operations & Monitoring';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Workido</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Public & Dashboard CSS -->
    <link rel="stylesheet" href="<?= getBaseUrl('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= getBaseUrl('assets/css/dashboard.css') ?>">
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <!-- Main Content Container -->
    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="topbar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div class="page-title-box">
                    <h1><?= e($pageTitle) ?></h1>
                    <p><?= e($pageSubtitle) ?></p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="<?= getBaseUrl('index.php') ?>" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-items-center" target="_blank" title="View Public Landing Page">
                    <i class="bi bi-box-arrow-up-right me-1"></i>View Site
                </a>

                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2" type="button" id="adminUserDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.85rem;">
                            <?= strtoupper(substr($user['full_name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <span class="d-none d-sm-inline fw-semibold small"><?= e($user['full_name']) ?></span>
                        <span class="badge bg-primary text-uppercase" style="font-size: 0.65rem;"><?= e($user['role']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="adminUserDropdown">
                        <li class="dropdown-header">
                            <h6 class="mb-0 text-dark"><?= e($user['full_name']) ?></h6>
                            <span class="text-muted small"><?= e($user['email']) ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= getBaseUrl('admin/dashboard.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?= getBaseUrl('admin/reports.php') ?>"><i class="bi bi-file-earmark-bar-graph me-2"></i>Reports</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= getBaseUrl('logout.php') ?>">
                                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash messages -->
        <div class="px-4 pt-3">
            <?= renderFlash() ?>
        </div>

        <!-- Page Main View Body -->
        <main class="app-content">
