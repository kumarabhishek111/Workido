<?php
/**
 * Workido - Public Site Header Template
 */
require_once __DIR__ . '/../config/functions.php';
$activePage = $activePage ?? 'home';
$pageTitle = $pageTitle ?? 'Workido - Skilled-Worker Management Platform';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= getBaseUrl('assets/css/style.css') ?>">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-workido sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= getBaseUrl('index.php') ?>">
                <span class="brand-badge"><i class="bi bi-tools"></i></span>
                <span>Workido</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#workidoNavbar" aria-controls="workidoNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="workidoNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'home' ? 'active text-primary fw-bold' : '' ?>" href="<?= getBaseUrl('index.php') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'about' ? 'active text-primary fw-bold' : '' ?>" href="<?= getBaseUrl('about.php') ?>">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= getBaseUrl('index.php#categories') ?>">Worker Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= getBaseUrl('index.php#features') ?>">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'contact' ? 'active text-primary fw-bold' : '' ?>" href="<?= getBaseUrl('contact.php') ?>">Contact</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isCustomer()): ?>
                            <a href="<?= getBaseUrl('customer/workers.php') ?>" class="btn btn-outline-primary d-none d-md-inline-flex align-items-center">
                                <i class="bi bi-people-fill me-1"></i>Hire Workers
                            </a>
                            <a href="<?= getBaseUrl('customer/dashboard.php') ?>" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="bi bi-person-workspace me-2"></i>My Portal
                            </a>
                        <?php else: ?>
                            <a href="<?= getBaseUrl('admin/dashboard.php') ?>" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="<?= getBaseUrl('logout.php') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
                            <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                        </a>
                    <?php else: ?>
                        <a href="<?= getBaseUrl('login.php') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                        </a>
                        <a href="<?= getBaseUrl('register.php') ?>" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i>Hire Workers / Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Flash Notice -->
    <div class="container pt-3">
        <?= renderFlash() ?>
    </div>
