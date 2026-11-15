<?php
/**
 * Workido - Global Helper Functions & Security Utilities
 */

require_once __DIR__ . '/database.php';

// Safe session start
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }
}
initSession();

/**
 * Escapes HTML output against XSS attacks.
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizes input string (trims, strips null bytes).
 */
function sanitize(?string $string): string {
    return trim((string)($string ?? ''));
}

/**
 * Generates and returns a CSRF token stored in session.
 */
function getCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Outputs a hidden input with the CSRF token.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(getCSRFToken()) . '">';
}

/**
 * Verifies a submitted CSRF token.
 */
function verifyCSRFToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sets a flash message to be displayed on next request.
 * Types: success, danger, warning, info
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieves and clears the flash message.
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Renders HTML alert for the flash message if present.
 */
function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) {
        return '';
    }
    $type = e($flash['type']);
    $message = e($flash['message']);
    $icon = match($flash['type']) {
        'success' => '<i class="bi bi-check-circle-fill me-2"></i>',
        'danger'  => '<i class="bi bi-exclamation-triangle-fill me-2"></i>',
        'warning' => '<i class="bi bi-exclamation-circle-fill me-2"></i>',
        default   => '<i class="bi bi-info-circle-fill me-2"></i>',
    };

    return '<div class="alert alert-' . $type . ' alert-dismissible fade show shadow-sm" role="alert">
                ' . $icon . '<span>' . $message . '</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Checks if user is currently authenticated.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_email']);
}

/**
 * Returns authenticated user info array from session.
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email'     => $_SESSION['user_email'] ?? '',
        'role'      => $_SESSION['user_role'] ?? 'staff',
    ];
}

function isCustomer(): bool {
    $user = getCurrentUser();
    return $user && ($user['role'] === 'customer');
}

function isAdmin(): bool {
    $user = getCurrentUser();
    return $user && in_array($user['role'], ['admin', 'manager', 'staff']);
}

function requireCustomer(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to access your customer dashboard and hire workers.');
        $loginUrl = getBaseUrl('login.php');
        header("Location: $loginUrl");
        exit;
    }
    if (!isCustomer()) {
        setFlash('info', 'Redirected to your administration workspace.');
        $dashUrl = getBaseUrl('admin/dashboard.php');
        header("Location: $dashUrl");
        exit;
    }
}

/**
 * Determines project base URL dynamically based on script name.
 */
function getBaseUrl(string $path = ''): string {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Normalize slashes
    $scriptDir = str_replace('\\', '/', $scriptDir);
    // If inside /admin, /customer, or /includes, get root folder
    if (str_ends_with($scriptDir, '/admin') || str_ends_with($scriptDir, '/customer') || str_ends_with($scriptDir, '/includes') || str_ends_with($scriptDir, '/config')) {
        $base = dirname($scriptDir);
    } else {
        $base = $scriptDir;
    }
    $base = rtrim($base, '/');
    $cleanPath = ltrim($path, '/');
    return ($base ? $base : '') . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * Formats a monetary amount into a clean currency format.
 */
function formatCurrency($amount): string {
    $num = is_numeric($amount) ? (float)$amount : 0.00;
    return '₹ ' . number_format($num, 2);
}

/**
 * Formats a date string safely.
 */
function formatDate(?string $dateStr, string $format = 'd M Y'): string {
    if (empty($dateStr) || $dateStr === '0000-00-00' || $dateStr === '0000-00-00 00:00:00') {
        return '—';
    }
    try {
        $dt = new DateTime($dateStr);
        return $dt->format($format);
    } catch (Exception $e) {
        return '—';
    }
}

/**
 * Job Status Badge HTML
 */
function getJobStatusBadge(string $status): string {
    $badgeClass = match ($status) {
        'Pending'     => 'bg-warning text-dark',
        'Assigned'    => 'bg-info text-dark',
        'In Progress' => 'bg-primary text-white',
        'Completed'   => 'bg-success text-white',
        'Cancelled'   => 'bg-danger text-white',
        default       => 'bg-secondary text-white'
    };
    return '<span class="badge ' . $badgeClass . ' px-2 py-1 rounded-pill">' . e($status) . '</span>';
}

/**
 * Worker Availability Badge HTML
 */
function getAvailabilityBadge(string $status): string {
    $badgeClass = match ($status) {
        'Available' => 'bg-success text-white',
        'Assigned'  => 'bg-info text-dark',
        'On Leave'  => 'bg-warning text-dark',
        'Inactive'  => 'bg-secondary text-white',
        default     => 'bg-light text-dark'
    };
    return '<span class="badge ' . $badgeClass . ' px-2 py-1 rounded-pill">' . e($status) . '</span>';
}

/**
 * Wage Payment Status Badge HTML
 */
function getPaymentStatusBadge(string $status): string {
    $badgeClass = match ($status) {
        'Paid'           => 'bg-success text-white',
        'Partially Paid' => 'bg-warning text-dark',
        'Pending'        => 'bg-danger text-white',
        default          => 'bg-secondary text-white'
    };
    return '<span class="badge ' . $badgeClass . ' px-2 py-1 rounded-pill">' . e($status) . '</span>';
}

/**
 * Worker Category Badge HTML with Icon
 */
function getCategoryBadge(string $category): string {
    $icon = match ($category) {
        'Carpenter'   => '<i class="bi bi-hammer me-1 text-amber"></i>',
        'Electrician' => '<i class="bi bi-lightning-charge-fill me-1 text-warning"></i>',
        'Plumber'     => '<i class="bi bi-droplet-fill me-1 text-primary"></i>',
        'Mason'       => '<i class="bi bi-bricks me-1 text-danger"></i>',
        default       => '<i class="bi bi-tools me-1"></i>'
    };
    return '<span class="badge bg-light text-dark border px-2 py-1">' . $icon . e($category) . '</span>';
}

/**
 * Calculates completion percentage safely without division by zero.
 */
function calculateCompletionRate(int|float $completed, int|float $total): float {
    if ($total <= 0) {
        return 0.0;
    }
    return round(($completed / $total) * 100, 1);
}

/**
 * Generates next unique Worker Code (e.g. WRK-1009)
 */
function generateWorkerCode(PDO $pdo): string {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM workers");
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'WRK-' . str_pad($nextId + 1000, 4, '0', STR_PAD_LEFT);
}

/**
 * Generates next unique Job Code (e.g. JOB-2008)
 */
function generateJobCode(PDO $pdo): string {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM jobs");
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'JOB-' . str_pad($nextId + 2000, 4, '0', STR_PAD_LEFT);
}

/**
 * Generates next unique Voucher Number for Wages (e.g. WAG-3006)
 */
function generateVoucherNo(PDO $pdo): string {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM wage_records");
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'WAG-' . str_pad($nextId + 3000, 4, '0', STR_PAD_LEFT);
}
