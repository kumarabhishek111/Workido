<?php
/**
 * Workido - Customer Portal Header
 */
require_once __DIR__ . '/../config/functions.php';

// Guard: Customer authentication required
requireCustomer();

$activeCustomerMenu = $activeCustomerMenu ?? 'dashboard';
$customerPageTitle = $customerPageTitle ?? 'Customer Portal - Workido';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($customerPageTitle) ?> | Workido</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= getBaseUrl('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= getBaseUrl('assets/css/dashboard.css') ?>">
</head>
<body class="bg-light">

<!-- Customer Top Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= getBaseUrl('customer/dashboard.php') ?>">
            <span class="brand-badge bg-primary text-white p-2 rounded-3 me-2"><i class="bi bi-tools"></i></span>
            <span class="text-white fw-bold">Workido <span class="badge bg-primary fs-6 fw-normal ms-1">Client Portal</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#customerNavbar" aria-controls="customerNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="customerNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $activeCustomerMenu === 'dashboard' ? 'active text-primary fw-bold' : 'text-white-50' ?>" href="<?= getBaseUrl('customer/dashboard.php') ?>">
                        <i class="bi bi-grid-fill me-1"></i>My Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeCustomerMenu === 'workers' ? 'active text-primary fw-bold' : 'text-white-50' ?>" href="<?= getBaseUrl('customer/workers.php') ?>">
                        <i class="bi bi-search me-1"></i>Find & Hire Workers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeCustomerMenu === 'post_job' ? 'active text-primary fw-bold' : 'text-white-50' ?>" href="<?= getBaseUrl('customer/post_job.php') ?>">
                        <i class="bi bi-plus-circle-fill me-1 text-warning"></i>Post Requirement
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeCustomerMenu === 'my_jobs' ? 'active text-primary fw-bold' : 'text-white-50' ?>" href="<?= getBaseUrl('customer/my_jobs.php') ?>">
                        <i class="bi bi-briefcase-fill me-1"></i>My Bookings
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2 py-1 px-3 rounded-pill" type="button" id="customerUserMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" style="width: 26px; height: 26px; font-size: 0.75rem;">
                            <?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <span class="fw-semibold small"><?= e($user['full_name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="customerUserMenu">
                        <li class="dropdown-header">
                            <h6 class="mb-0 text-dark"><?= e($user['full_name']) ?></h6>
                            <span class="text-muted small"><?= e($user['email']) ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= getBaseUrl('customer/dashboard.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?= getBaseUrl('customer/my_jobs.php') ?>"><i class="bi bi-briefcase me-2"></i>My Bookings</a></li>
                        <li><a class="dropdown-item" href="<?= getBaseUrl('customer/workers.php') ?>"><i class="bi bi-people me-2"></i>Hire Workers</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= getBaseUrl('index.php') ?>"><i class="bi bi-house me-2"></i>Public Home Page</a></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= getBaseUrl('logout.php') ?>">
                                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Global Flash Alert -->
<div class="container pt-3">
    <?= renderFlash() ?>
</div>

<main class="py-4">
    <div class="container">
