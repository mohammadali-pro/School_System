<?php
require '../config/db.php';

// Only admin
requireRole('admin');

$student_id = intval($_GET['id'] ?? 0);

// Fetch student info from Users
$stmt = $pdo->prepare("
SELECT u.user_id, u.full_name, u.email, u.phone, 
       s.major, s.year_of_study
FROM Users u
LEFT JOIN students s ON u.user_id = s.user_id
WHERE u.user_id = ? AND u.role = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if student not found
if (!$student) {
    redirectWithError('admin_dashboard.php', 'Student not found');
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!validateCSRFFromPost()) {
        redirectWithError('admin_dashboard.php', 'Invalid security token');
    }
    
    $major = trim($_POST['major'] ?? '');
    $yearOfStudy = trim($_POST['year_of_study'] ?? '');
    
    if (empty($major) || empty($yearOfStudy)) {
        redirectWithError('admin_dashboard.php', 'All fields are required');
    }

    // Update student major + year
    $stmt = $pdo->prepare("
        UPDATE students SET major = ?, year_of_study = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$major, $yearOfStudy, $student_id]);

    redirectWithSuccess('admin_dashboard.php', 'Student updated successfully');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - School Management System</title>
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
        <h2 class="welcome1">Edit Student</h2>

        <div class="section" style="display:block;">
            <div class="update-container">
                <h2>Student Information</h2>
                
                <div class="info-box">
                    <p><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                </div>

                <form method="POST">
                    <?php echo csrfTokenField(); ?>
                    <label>Major:</label>
                    <input type="text" name="major" required 
                           value="<?= htmlspecialchars($student['major']) ?>">

                    <label>Year of Study:</label>
                    <div class="select-wrapper">
                        <select name="year_of_study" required>
                            <option value="2025/2026" <?= $student['year_of_study']=="2025/2026"?"selected":"" ?>>2025/2026</option>
                            <option value="2026/2027" <?= $student['year_of_study']=="2026/2027"?"selected":"" ?>>2026/2027</option>
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
