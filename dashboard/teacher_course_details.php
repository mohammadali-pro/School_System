<?php
session_start();
require '../config/db.php';

// Ensure user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    header('Location: teacher_dashboard.php');
    exit;
}

// Check if this course belongs to the teacher
$stmtC = $pdo->prepare("SELECT course_name, course_code FROM courses WHERE course_id = ? AND teacher_id = ?");
$stmtC->execute([$course_id, $user_id]);
$course = $stmtC->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Access Denied: You are not assigned to this course.");
}

// HANDLE GRADE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grade'])) {
    // Validate CSRF token
    if (!validateCSRFFromPost()) {
        redirectWithError("teacher_course_details.php?course_id=$course_id", 'Invalid security token');
    }

    $enrollment_id = $_POST['enrollment_id'];
    $grade_value = $_POST['grade'];

    // Check if grade already exists
    $stmtG = $pdo->prepare("SELECT grade_id FROM grades WHERE enrollment_id = ?");
    $stmtG->execute([$enrollment_id]);
    $existingGrade = $stmtG->fetch(PDO::FETCH_ASSOC);

    if ($existingGrade) {
        $stmtUpdate = $pdo->prepare("UPDATE grades SET grade = ? WHERE enrollment_id = ?");
        $stmtUpdate->execute([$grade_value, $enrollment_id]);
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO grades (enrollment_id, grade) VALUES (?, ?)");
        $stmtInsert->execute([$enrollment_id, $grade_value]);
    }

    redirectWithSuccess("teacher_course_details.php?course_id=$course_id", "Grade updated successfully!");
}

// Fetch Enrolled Students
$stmtStudents = $pdo->prepare("
    SELECT e.enrollment_id, u.full_name, u.email, u.photo_path, g.grade
    FROM enrollment e
    JOIN students s ON e.student_id = s.user_id
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
    WHERE e.course_id = ?
    ORDER BY u.full_name ASC
");
$stmtStudents->execute([$course_id]);
$enrolledStudents = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

// Fetch Profile Photo for Navbar
$stmtUser = $pdo->prepare("SELECT full_name, photo_path FROM Users WHERE user_id = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
$profilePhoto = !empty($currentUser['photo_path']) ? '../uploads/' . $currentUser['photo_path'] : 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['full_name']) . '&background=random&color=fff';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - <?= htmlspecialchars($course['course_name']); ?></title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">

</head>
<body class="dashboard-body">

    <nav class="navbar">
        <div class="logo">Teacher Panel</div>
        <div class="nav-buttons">
            <button onclick="window.location.href='teacher_dashboard.php'">Dashboard</button>
            <button onclick="window.location.href='../logout.php'">Logout</button>
        </div>
        <div class="navbar-profile">
            <img src="<?= htmlspecialchars($profilePhoto); ?>" alt="Profile" class="navbar-profile-img" onclick="openPhotoModal()" style="cursor: pointer;">
        </div>
    </nav>

    <div class="dashboard-container">
        
        <a href="teacher_dashboard.php" class="back-btn">&larr; Back to Dashboard</a>

        <!-- Premium Header -->
        <div class="course-header-card">
            <h2><?= htmlspecialchars($course['course_name']); ?></h2>
            <span class="course-badge"><?= htmlspecialchars($course['course_code']); ?></span>
        </div>

        <?php 
        $success = getSuccessMessage();
        $error = getErrorMessage();
        if ($success): 
        ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="section" style="display: block;">
            <h3 style="margin-bottom: 20px; color: #444;">Enrolled Students</h3>
            
            <div class="students-container">
                <?php if (count($enrolledStudents) > 0): ?>
                    <?php foreach ($enrolledStudents as $student): ?>
                        <div class="student-item">
                            <div class="student-info">
                                <?php if ($student['photo_path']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($student['photo_path']); ?>" class="student-img" alt="Student">
                                <?php else: ?>
                                    <div class="student-default-img">
                                        <?= strtoupper(substr($student['full_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="student-details">
                                    <h4><?= htmlspecialchars($student['full_name']); ?></h4>
                                    <p><?= htmlspecialchars($student['email']); ?></p>
                                </div>
                            </div>

                            <form method="POST" class="grade-form">
                                <?php echo csrfTokenField(); ?>
                                <input type="hidden" name="enrollment_id" value="<?= $student['enrollment_id']; ?>">
                                <input type="number" name="grade" step="0.01" min="0" max="100" class="grade-input" placeholder="--" required>
                                <button type="submit" name="update_grade" class="btn-update">Save</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#777; padding:40px;">No students enrolled in this course yet.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
    
    <!-- PHOTO MODAL -->
    <div id="photoModal" class="photo-modal">
        <span class="photo-modal-close" onclick="closePhotoModal()">&times;</span>
        <img class="photo-modal-content" id="modalImage" src="<?= htmlspecialchars($profilePhoto); ?>">
    </div>

    <script>
        // Auto-dismiss alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            });
        }, 3000);

        // Modal Logic
        function openPhotoModal() {
            document.getElementById("photoModal").style.display = "block";
        }

        function closePhotoModal() {
            document.getElementById("photoModal").style.display = "none";
        }

        window.onclick = function(event) {
            const modal = document.getElementById("photoModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
