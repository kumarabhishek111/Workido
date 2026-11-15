<?php
/**
 * Workido - About Us Page
 */
$activePage = 'about';
$pageTitle = 'About Workido - Skilled-Worker Management Platform';
require_once __DIR__ . '/config/functions.php';

include __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-light border-bottom">
    <div class="container text-center">
        <span class="section-tag">Empowering Skilled Artisans & Builders</span>
        <h1 class="display-5 fw-bold text-dark mb-3">About Workido</h1>
        <p class="lead text-muted mx-auto" style="max-width: 700px;">
            Workido is the dedicated skilled-worker management software engineered for construction supervisors, interior fit-out contractors, and maintenance companies to manage trade labor transparently.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row align-items-center gy-5 mb-5">
        <div class="col-lg-6">
            <h2 class="fw-bold mb-3">Our Mission</h2>
            <p class="text-muted">
                In many construction and home-service sectors, skilled workers face erratic wages, delayed allocations, and a lack of recognized trade performance credentials. Meanwhile, contractors struggle with absenteeism, miscommunication on job requirements, and time-consuming manual payroll calculations.
            </p>
            <p class="text-muted">
                <strong>Workido bridges this gap</strong> through an integrated platform where worker master data, scheduled work allocations, daily wages, and verifiable job performance ratings reside in a secure, unified MySQL database.
            </p>
            <div class="row g-3 pt-2">
                <div class="col-sm-6">
                    <div class="p-3 border rounded-3 bg-white shadow-sm">
                        <i class="bi bi-shield-check text-primary fs-3"></i>
                        <h6 class="fw-bold mt-2 mb-1">Reliable Records</h6>
                        <small class="text-muted">Permanent historical records for assignments, wage slips, and reviews.</small>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 border rounded-3 bg-white shadow-sm">
                        <i class="bi bi-wallet2 text-success fs-3"></i>
                        <h6 class="fw-bold mt-2 mb-1">Accurate Payroll</h6>
                        <small class="text-muted">Dynamic calculations factoring days worked, bonuses, and advances.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4 border-0 shadow-sm rounded-4 bg-primary text-white">
                <h4 class="fw-bold mb-4"><i class="bi bi-gear-fill me-2"></i>Core Pillars of Workido</h4>
                
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="bg-white text-primary p-2 rounded-3 fw-bold">01</div>
                    <div>
                        <h5 class="fw-bold mb-1">Standardized Trade Categorization</h5>
                        <p class="text-white-50 small mb-0">Strict categorization for Carpenters, Electricians, Plumbers, and Masons with trade-specific skill tagging.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="bg-white text-primary p-2 rounded-3 fw-bold">02</div>
                    <div>
                        <h5 class="fw-bold mb-1">Zero-Division Safe Metrics</h5>
                        <p class="text-white-50 small mb-0">Robust completion rate formulas designed to eliminate mathematical errors and provide fair worker scorecards.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <div class="bg-white text-primary p-2 rounded-3 fw-bold">03</div>
                    <div>
                        <h5 class="fw-bold mb-1">Exportable Ledger Audits</h5>
                        <p class="text-white-50 small mb-0">Quick CSV downloads allowing seamless integration into accounting books and field supervisor spreadsheets.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Supported Trade Specializations -->
    <div class="my-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Trade Coverage</h3>
            <p class="text-muted">We specialize in the four foundational crafts of modern infrastructure:</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 p-4 border rounded-3 text-center">
                    <div class="display-5 text-warning mb-2"><i class="bi bi-hammer"></i></div>
                    <h5 class="fw-bold">Carpentry & Joinery</h5>
                    <p class="text-muted small mb-0">Cabinetry, interior panelling, structural roofs, doors, and antique restoration.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 p-4 border rounded-3 text-center">
                    <div class="display-5 text-warning mb-2"><i class="bi bi-lightning-charge"></i></div>
                    <h5 class="fw-bold">Electrical Systems</h5>
                    <p class="text-muted small mb-0">Power distribution, 3-phase wiring, smart automation, safety breakers, and lighting.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 p-4 border rounded-3 text-center">
                    <div class="display-5 text-primary mb-2"><i class="bi bi-droplet"></i></div>
                    <h5 class="fw-bold">Plumbing & Sanitary</h5>
                    <p class="text-muted small mb-0">Concealed waterline installations, drainage, pumps, pressure tests, and fittings.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 p-4 border rounded-3 text-center">
                    <div class="display-5 text-danger mb-2"><i class="bi bi-bricks"></i></div>
                    <h5 class="fw-bold">Masonry & Concrete</h5>
                    <p class="text-muted small mb-0">Bricklaying, structural plastering, RCC casting, tile setting, and stone masonry.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
