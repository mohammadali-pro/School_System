<?php
session_start();
require '../config/db.php';

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['course_name']);
    $code = trim($_POST['course_code']);
    $credits = intval($_POST['credits']);
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;

    if (empty($name) || empty($code)) {
        die("Course Name and Code are required.");
    }

    try {
        // Check if course code already exists
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_code = ?");
        $stmtCheck->execute([$code]);
        if ($stmtCheck->fetchColumn() > 0) {
            header("Location: admin_dashboard.php?error=course_exists");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_code, credits, teacher_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $code, $credits, $teacher_id]);

        header("Location: admin_dashboard.php?success=course_added");
        exit;
    } catch (Exception $e) {
        die("Error adding course: " . $e->getMessage());
    }
}
?>
