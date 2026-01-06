<?php
require '../config/db.php';

// Only admin allowed
requireRole('admin');

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFFromPost()) {
    redirectWithError('admin_dashboard.php', 'Invalid security token');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $course_id = intval($_POST['course_id'] ?? 0);

    if (empty($student_id) || empty($course_id)) {
        redirectWithError('admin_dashboard.php', 'Student and course are required');
    }

    try {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT * FROM enrollment WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);

        if ($stmt->rowCount() > 0) {
            redirectWithError('admin_dashboard.php', 'Student is already enrolled in this course');
        }

        // Insert Enrollment
        $stmt = $pdo->prepare("INSERT INTO enrollment (student_id, course_id, enrollment_date) VALUES (?, ?, NOW())");
        $stmt->execute([$student_id, $course_id]);

        redirectWithSuccess('admin_dashboard.php', 'Student enrolled successfully');

    } catch (Exception $e) {
        redirectWithError('admin_dashboard.php', 'Error enrolling student: ' . $e->getMessage());
    }
} else {
    redirectWithError('admin_dashboard.php', 'Invalid request');
}
