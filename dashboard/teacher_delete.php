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
        $pdo->prepare("DELETE FROM teachers WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM Users WHERE user_id = ? AND role = 'teacher'")->execute([$id]);
        $message = "Teacher deleted successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Error deleting teacher: " . $e->getMessage();
        $messageType = "error";
    }
} else {
    $message = "Invalid teacher ID!";
    $messageType = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Teacher - School Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">

    <nav class="navbar">
        <div class="logo">MyPortal Admin</div>
        <div class="nav-buttons">
            <button onclick="window.location.href='admin_dashboard.php'">Dashboard</button>
            <button onclick="window.location.href='../logout.php'">Logout</button>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="section" style="display:block;">
            <div class="update-container">
                <h2>Delete Teacher</h2>
                
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>

                <button class="btn-primary" onclick="window.location.href='admin_dashboard.php'">Back to Dashboard</button>
            </div>
        </div>
    </div>

    <script>
        // Auto-redirect after 2 seconds if successful
        <?php if ($messageType === 'success'): ?>
        setTimeout(function() {
            window.location.href = 'admin_dashboard.php';
        }, 2000);
        <?php endif; ?>
    </script>

</body>
</html>

