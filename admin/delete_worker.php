<?php
/**
 * Workido - Delete Worker Handler
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Security validation failed (CSRF token expired).');
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}

$workerId = (int)($_POST['worker_id'] ?? 0);
if ($workerId <= 0) {
    setFlash('danger', 'Invalid worker identification.');
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    setFlash('danger', 'Database connection error.');
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}

try {
    // Check if worker exists
    $stmt = $pdo->prepare("SELECT full_name, worker_code FROM workers WHERE id = ? LIMIT 1");
    $stmt->execute([$workerId]);
    $worker = $stmt->fetch();

    if (!$worker) {
        setFlash('danger', 'Worker record not found.');
        header('Location: ' . getBaseUrl('admin/workers.php'));
        exit;
    }

    // Check if worker is assigned to any active in-progress jobs
    $jobCheck = $pdo->prepare("
        SELECT j.id FROM jobs j 
        JOIN job_assignments ja ON j.id = ja.job_id 
        WHERE ja.worker_id = ? AND j.status = 'In Progress'
    ");
    $jobCheck->execute([$workerId]);
    if ($jobCheck->rowCount() > 0) {
        setFlash('danger', "Cannot delete worker '{$worker['full_name']}': Worker is currently actively assigned to In-Progress jobs. Please reassign or complete those jobs first.");
        header('Location: ' . getBaseUrl('admin/view_worker.php?id=' . $workerId));
        exit;
    }

    // Delete worker record (cascades to assignments, wages, performance if foreign keys allow)
    $delStmt = $pdo->prepare("DELETE FROM workers WHERE id = ?");
    $delStmt->execute([$workerId]);

    setFlash('success', "Worker '{$worker['full_name']}' ({$worker['worker_code']}) was deleted successfully.");
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;

} catch (Exception $e) {
    setFlash('danger', 'Failed to delete worker: ' . $e->getMessage());
    header('Location: ' . getBaseUrl('admin/workers.php'));
    exit;
}
