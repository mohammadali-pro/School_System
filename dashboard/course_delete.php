<?php
session_start();
require '../config/db.php';

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Method not allowed");
}

// Validate CSRF token
if (!validateCSRFFromPost()) {
    redirectWithError('admin_dashboard.php', 'Invalid security token');
}

$id = $_POST['id'] ?? 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
        $stmt->execute([$id]);
        redirectWithSuccess('admin_dashboard.php', 'Course deleted successfully');
    } catch (Exception $e) {
        redirectWithError('admin_dashboard.php', 'Error deleting course: ' . $e->getMessage());
    }
} else {
    redirectWithError('admin_dashboard.php', 'Invalid course ID');
}
?>
