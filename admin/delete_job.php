<?php
/**
 * Workido - Delete Job Order Handler
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Security validation failed (CSRF token expired).');
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}

$jobId = (int)($_POST['job_id'] ?? 0);
if ($jobId <= 0) {
    setFlash('danger', 'Invalid job order identification.');
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    setFlash('danger', 'Database connection error.');
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}

try {
    // Check if job exists
    $stmt = $pdo->prepare("SELECT title, job_code, status FROM jobs WHERE id = ? LIMIT 1");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if (!$job) {
        setFlash('danger', 'Job order not found.');
        header('Location: ' . getBaseUrl('admin/jobs.php'));
        exit;
    }

    // Delete job record
    $delStmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
    $delStmt->execute([$jobId]);

    setFlash('success', "Work Order '{$job['title']}' ({$job['job_code']}) was deleted successfully.");
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;

} catch (Exception $e) {
    setFlash('danger', 'Failed to delete job order: ' . $e->getMessage());
    header('Location: ' . getBaseUrl('admin/jobs.php'));
    exit;
}
