<?php
/**
 * Workido - Authentication & Authorization Guard
 * Protects admin routes from unauthorized public access.
 */

require_once __DIR__ . '/../config/functions.php';

if (!isLoggedIn()) {
    setFlash('warning', 'Please sign in to access the Workido administration dashboard.');
    $loginUrl = getBaseUrl('login.php');
    header("Location: $loginUrl");
    exit;
}

$currentUser = getCurrentUser();
// Admin role verification
if (!$currentUser || !in_array($currentUser['role'], ['admin', 'manager'])) {
    setFlash('danger', 'Access denied. You do not have administrative privileges.');
    $homeUrl = getBaseUrl('index.php');
    header("Location: $homeUrl");
    exit;
}
