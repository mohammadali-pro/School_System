<?php
session_start();
require '../config/db.php';

// Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';

    if (empty($student_id) || empty($course_id)) {
        header("Location: admin_dashboard.php?error=missing_fields");
        exit;
    }

    try {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT * FROM enrollment WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);

        if ($stmt->rowCount() > 0) {
            header("Location: admin_dashboard.php?error=already_enrolled");
            exit;
        }

        // Insert Enrollment
        $stmt = $pdo->prepare("INSERT INTO enrollment (student_id, course_id, enrollment_date) VALUES (?, ?, NOW())");
        $stmt->execute([$student_id, $course_id]);

        header("Location: admin_dashboard.php?success=enrollment_created");
        exit;

    } catch (Exception $e) {
         // In a real app, log error. For now, show generic error or debug.
        header("Location: admin_dashboard.php?error=db_error");
        exit;
    }
} else {
    header("Location: admin_dashboard.php");
    exit;
}
