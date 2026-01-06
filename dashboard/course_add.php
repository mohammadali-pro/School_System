<?php
require '../config/db.php';

// Only admin
requireRole('admin');

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFFromPost()) {
    redirectWithError('admin_dashboard.php', 'Invalid security token');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['course_name'] ?? '');
    $code = trim($_POST['course_code'] ?? '');
    $credits = intval($_POST['credits'] ?? 0);
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    if (empty($name) || empty($code)) {
        redirectWithError('admin_dashboard.php', 'Course Name and Code are required');
    }

    try {
        // Check if course code already exists
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_code = ?");
        $stmtCheck->execute([$code]);
        if ($stmtCheck->fetchColumn() > 0) {
            redirectWithError('admin_dashboard.php', 'Course code already exists');
        }

        $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_code, credits, teacher_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $code, $credits, $teacher_id]);

        redirectWithSuccess('admin_dashboard.php', 'Course added successfully');
    } catch (Exception $e) {
        redirectWithError('admin_dashboard.php', 'Error adding course: ' . $e->getMessage());
    }
}
?>
