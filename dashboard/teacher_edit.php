<?php
require '../config/db.php';

// Only admin
requireRole('admin');

$teacher_id = intval($_GET['id'] ?? 0);

// Fetch teacher info from Users
$stmt = $pdo->prepare("
SELECT u.user_id, u.full_name, u.email, u.phone, 
       t.department, t.specialization
FROM Users u
LEFT JOIN teachers t ON u.user_id = t.user_id
WHERE u.user_id = ? AND u.role = 'teacher'
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if teacher not found
if (!$teacher) {
    redirectWithError('admin_dashboard.php', 'Teacher not found');
}

// Fetch list of courses
$courses = $pdo->query("SELECT course_id, course_name, teacher_id FROM courses ORDER BY course_name ASC")
               ->fetchAll(PDO::FETCH_ASSOC);

// Fetch course currently assigned to this teacher
$currentCourse = null;
$stmt2 = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ?");
$stmt2->execute([$teacher_id]);
$currentCourse = $stmt2->fetch(PDO::FETCH_ASSOC);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!validateCSRFFromPost()) {
        redirectWithError('admin_dashboard.php', 'Invalid security token');
    }
    
    $department = trim($_POST['department'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $assignedCourse = intval($_POST['assigned_course'] ?? 0);
    
    if (empty($department) || empty($specialization)) {
        redirectWithError('admin_dashboard.php', 'All fields are required');
    }

    // Update teacher department + specialization
    $stmt = $pdo->prepare("
        UPDATE teachers SET department = ?, specialization = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$department, $specialization, $teacher_id]);

    // FIRST: Reset any course that this teacher previously taught
    $pdo->prepare("UPDATE courses SET teacher_id = NULL WHERE teacher_id = ?")
        ->execute([$teacher_id]);

    // Assign teacher to new course (if not empty)
    if (!empty($assignedCourse)) {
        $stmt = $pdo->prepare("UPDATE courses SET teacher_id = ? WHERE course_id = ?");
        $stmt->execute([$teacher_id, $assignedCourse]);
    }

    redirectWithSuccess('admin_dashboard.php', 'Teacher updated successfully');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher - School Management System</title>
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
        <h2 class="welcome1">Edit Teacher</h2>

        <div class="section" style="display:block;">
            <div class="update-container">
                <h2>Teacher Information</h2>
                
                <div class="info-box">
                    <p><strong>Name:</strong> <?= htmlspecialchars($teacher['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($teacher['email']) ?></p>
                </div>

                <form method="POST">
                    <?php echo csrfTokenField(); ?>
                    <label>Department:</label>
                    <div class="select-wrapper">
                        <select name="department" required>
                            <option value="">-- Select Department --</option>
                            <option value="Math">Math</option>
                            <option value="Science">Science</option>
                            <option value="English">English</option>
                            <option value="IT">IT</option>
                        </select>
                    </div>

                    <label>Specialization:</label>
                    <input type="text" name="specialization" required placeholder="Enter specialization">

                    <label>Assigned Course:</label>
                    <div class="select-wrapper">
                        <select name="assigned_course">
                            <option value="">-- No Course Assigned --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['course_id']; ?>"
                                    <?php if ($c['teacher_id'] != null && $c['teacher_id'] != $teacher_id) echo "disabled"; ?>>
                                    <?= htmlspecialchars($c['course_name']); ?>
                                    <?php if ($c['teacher_id'] != null && $c['teacher_id'] != $teacher_id): ?>
                                        (Taken)
                                    <?php endif; ?>
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