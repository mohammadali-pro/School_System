<?php
require '../config/db.php';

// Only admin
requireRole('admin');

$course_id = intval($_GET['id'] ?? 0);
// Fetch course info
$stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    redirectWithError('admin_dashboard.php', 'Course not found');
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!validateCSRFFromPost()) {
        redirectWithError('admin_dashboard.php', 'Invalid security token');
    }
    
    $name = trim($_POST['course_name'] ?? '');
    $code = trim($_POST['course_code'] ?? '');
    $credits = intval($_POST['credits'] ?? 0);
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    if (empty($name) || empty($code)) {
        redirectWithError('admin_dashboard.php', 'Course Name and Code are required');
    }
    
    // Check if course code exists for another course
    $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = ? AND course_id != ?");
    $stmt->execute([$code, $course_id]);
    if ($stmt->rowCount() > 0) {
        redirectWithError('admin_dashboard.php', 'Course code already exists for another course');
    }

    // Update
    $stmt = $pdo->prepare("
        UPDATE courses 
        SET course_name = ?, course_code = ?, credits = ?, teacher_id = ?
        WHERE course_id = ?
    ");
    $stmt->execute([$name, $code, $credits, $teacher_id, $course_id]);

    redirectWithSuccess('admin_dashboard.php', 'Course updated successfully');
}

// Fetch all teachers for dropdown
$stmtT = $pdo->prepare("SELECT user_id, full_name FROM Users WHERE role='teacher' ORDER BY full_name ASC");
$stmtT->execute();
$teachersList = $stmtT->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - School Management System</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
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
        <h2 class="welcome1">Edit Course</h2>

        <div class="section" style="display:block;">
            <div class="update-container">
                <h2>Course Information</h2>
                
                <form method="POST">
                    <?php echo csrfTokenField(); ?>
                    <label>Course Name:</label>
                    <input type="text" name="course_name" required value="<?= htmlspecialchars($course['course_name']); ?>">

                    <label>Course Code:</label>
                    <input type="text" name="course_code" required value="<?= htmlspecialchars($course['course_code']); ?>">

                    <label>Credits:</label>
                    <input type="number" name="credits" required value="<?= htmlspecialchars($course['credits']); ?>">

                    <label>Assign Teacher:</label>
                    <div class="select-wrapper">
                        <select name="teacher_id">
                            <option value="">-- No Teacher --</option>
                            <?php foreach ($teachersList as $tl): ?>
                                <option value="<?= $tl['user_id']; ?>" 
                                    <?= ($course['teacher_id'] == $tl['user_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($tl['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit">Save Changes</button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='admin_dashboard.php'">Back to Dashboard</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
