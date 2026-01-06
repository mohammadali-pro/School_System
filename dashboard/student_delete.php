<?php
require '../config/db.php';

// Only admin
requireRole('admin');

// Validate request method and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('admin_dashboard.php', 'Invalid request method');
}

if (!validateCSRFFromPost()) {
    redirectWithError('admin_dashboard.php', 'Invalid security token');
}

$id = intval($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $pdo->prepare("DELETE FROM students WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM Users WHERE user_id = ? AND role = 'student'")->execute([$id]);
        redirectWithSuccess('admin_dashboard.php', 'Student deleted successfully');
    } catch (Exception $e) {
        redirectWithError('admin_dashboard.php', 'Error deleting student: ' . $e->getMessage());
    }
} else {
    redirectWithError('admin_dashboard.php', 'Invalid student ID');
}
?>
