<?php
session_start();
require '../config/db.php';

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
$id = $_GET['id'] ?? 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
        $stmt->execute([$id]);
        
        header("Location: admin_dashboard.php?success=course_deleted"); 
        exit;
    } catch (Exception $e) {
        die("Error deleting course: " . $e->getMessage());
    }
} else {
    header("Location: admin_dashboard.php");
    exit;
}
?>
