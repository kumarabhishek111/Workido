<?php
/**
 * Workido - Secure Logout Script
 */

require_once __DIR__ . '/config/functions.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session for the flash message
initSession();
setFlash('info', 'You have been successfully signed out of Workido.');

$loginUrl = getBaseUrl('login.php');
header("Location: $loginUrl");
exit;
