<?php
/**
 * Workido - Public Site Footer Template
 */
?>
    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <span class="brand-badge bg-primary text-white p-2 rounded-3 me-2"><i class="bi bi-tools fs-5"></i></span>
                        <h4 class="text-white fw-bold mb-0">Workido</h4>
                    </div>
                    <p class="text-muted pe-lg-4">
                        Workido is the dedicated skilled-worker management platform engineered to streamline trade professional profiles, job assignments, wage calculations, and verified performance tracking.
                    </p>
                    <div class="d-flex gap-3 text-muted">
                        <span class="badge bg-secondary"><i class="bi bi-shield-check me-1"></i>Secure PHP/MySQL</span>
                        <span class="badge bg-secondary"><i class="bi bi-cpu me-1"></i>Automated Payroll</span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Trade Sectors</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 small">
                        <li><a href="<?= getBaseUrl('index.php#categories') ?>"><i class="bi bi-hammer me-1 text-warning"></i>Carpenters</a></li>
                        <li><a href="<?= getBaseUrl('index.php#categories') ?>"><i class="bi bi-lightning-charge-fill me-1 text-warning"></i>Electricians</a></li>
                        <li><a href="<?= getBaseUrl('index.php#categories') ?>"><i class="bi bi-droplet-fill me-1 text-primary"></i>Plumbers</a></li>
                        <li><a href="<?= getBaseUrl('index.php#categories') ?>"><i class="bi bi-bricks me-1 text-danger"></i>Masons</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Quick Navigation</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 small">
                        <li><a href="<?= getBaseUrl('index.php') ?>"><i class="bi bi-chevron-right me-1 text-primary"></i>Home</a></li>
                        <li><a href="<?= getBaseUrl('about.php') ?>"><i class="bi bi-chevron-right me-1 text-primary"></i>About Workido</a></li>
                        <li><a href="<?= getBaseUrl('contact.php') ?>"><i class="bi bi-chevron-right me-1 text-primary"></i>Contact Support</a></li>
                        <li><a href="<?= getBaseUrl('login.php') ?>"><i class="bi bi-chevron-right me-1 text-primary"></i>Admin Sign In</a></li>
                        <li><a href="<?= getBaseUrl('install.php') ?>"><i class="bi bi-chevron-right me-1 text-primary"></i>Database Setup</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Headquarters</h6>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-geo-alt-fill text-primary me-2"></i>Workido Tower, Sector 29, Gurgaon, NCR
                    </p>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-telephone-fill text-primary me-2"></i>+91 (0124) 4920-800
                    </p>
                    <p class="small text-muted">
                        <i class="bi bi-envelope-fill text-primary me-2"></i>support@workido.local
                    </p>
                </div>
            </div>

            <hr class="footer-hr">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
                <p class="mb-2 mb-md-0">&copy; <?= date('Y') ?> Workido Skilled-Worker Management Platform. All rights reserved.</p>
                <div>
                    <span>Built with Core PHP & MySQL. Local XAMPP Environment.</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Application JS -->
    <script src="<?= getBaseUrl('assets/js/script.js') ?>"></script>
    <script src="<?= getBaseUrl('assets/js/validation.js') ?>"></script>
</body>
</html>
