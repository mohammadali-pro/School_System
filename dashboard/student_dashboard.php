<?php
session_start();
require '../config/db.php';

// Ensure user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// HANDLE DROP COURSE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drop_course_id'])) {
    $drop_id = $_POST['drop_course_id'];
    
    // Security check: Ensure this enrollment belongs to the student
    $stmtCheck = $pdo->prepare("SELECT enrollment_id FROM enrollment WHERE enrollment_id = ? AND student_id = ?");
    $stmtCheck->execute([$drop_id, $user_id]);
    
    if ($stmtCheck->fetch()) {
        // Check if grade exists
        $stmtGradeCheck = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE enrollment_id = ?");
        $stmtGradeCheck->execute([$drop_id]);
        $hasGrade = $stmtGradeCheck->fetchColumn();

        if ($hasGrade > 0) {
            $error_msg = "Cannot drop course: You have already been graded.";
        } else {
            $stmtDrop = $pdo->prepare("DELETE FROM enrollment WHERE enrollment_id = ?");
            $stmtDrop->execute([$drop_id]);
            $success_msg = "Course dropped successfully.";
        }
    } else {
        $error_msg = "Error: Invalid request.";
    }
}

// Fetch current user details
$stmtUser = $pdo->prepare("SELECT full_name, email, phone, photo_path FROM Users WHERE user_id = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

$profilePhoto = !empty($currentUser['photo_path']) ? '../uploads/' . $currentUser['photo_path'] : 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['full_name']) . '&background=random&color=fff';

// FETCH ENROLLED COURSES
$stmtCourses = $pdo->prepare("
    SELECT c.course_id, c.course_name, c.course_code, c.credits, 
           u.full_name as teacher_name, 
           g.grade,
           e.enrollment_id
    FROM enrollment e
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN users u ON c.teacher_id = u.user_id
    LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
    WHERE e.student_id = ?
    ORDER BY c.course_name ASC
");
$stmtCourses->execute([$user_id]);
$myCourses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - School Management System</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">

</head>

<body class="dashboard-body">

    <nav class="navbar">
        <div class="logo">Student Panel</div>
        <div class="nav-buttons">
            <button onclick="showSection('myCourses')">My Courses</button>
            <button onclick="showSection('updateProfile')">Update Profile</button>
            <button onclick="window.location.href='../logout.php'">Logout</button>
        </div>
        <div class="navbar-profile">
            <img src="<?= htmlspecialchars($profilePhoto); ?>" alt="Profile" class="navbar-profile-img" onclick="openPhotoModal()" style="cursor: pointer;">
        </div>
    </nav>

    <div class="dashboard-container">

        <h2 class="welcome1">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>

        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] === 'profile_updated'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Profile updated successfully!
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;"><?= $success_msg; ?></div>
        <?php endif; ?>
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-error" style="max-width: 600px; margin: 20px auto;"><?= $error_msg; ?></div>
        <?php endif; ?>

        <!-- ================= MY COURSES ================= -->
        <div id="myCourses" class="section">
            <h2>My Courses</h2>
            
            <?php if (count($myCourses) > 0): ?>
                <div class="courses-grid">
                    <?php foreach ($myCourses as $course): ?>
                    <div class="course-card student-theme">
                        <div class="course-header">
                            <h3><?= htmlspecialchars($course['course_name']); ?></h3>
                            <span class="code"><?= htmlspecialchars($course['course_code']); ?></span>
                        </div>
                        <div class="course-body">
                            <div class="course-info-row">
                                <span class="label">Teacher</span>
                                <span class="value"><?= htmlspecialchars($course['teacher_name'] ?? 'TBA'); ?></span>
                            </div>
                            <div class="course-info-row">
                                <span class="label">Credits</span>
                                <span class="value"><?= htmlspecialchars($course['credits']); ?></span>
                            </div>
                            <div class="course-info-row">
                                <span class="label">Grade</span>
                                <span class="value">
                                    <?php if ($course['grade'] !== null): ?>
                                        <span class="grade-badge"><?= htmlspecialchars($course['grade']); ?></span>
                                    <?php else: ?>
                                        <span style="color:#999;">Not Graded</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="course-footer">
                            <?php if ($course['grade'] !== null): ?>
                                <button class="btn-drop" disabled title="Cannot drop graded course">Dropped Disabled</button>
                            <?php else: ?>
                                <form method="POST" class="drop-course-form" data-enrollment-id="<?= $course['enrollment_id']; ?>">
                                    <input type="hidden" name="drop_course_id" value="<?= $course['enrollment_id']; ?>">
                                    <button type="button" class="btn-drop" onclick="confirmDrop(this)">Drop Course</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="background:#eef2f7; color:#333; padding:20px; border-radius:8px; display:inline-block;">
                    You are not currently enrolled in any courses.
                </div>
            <?php endif; ?>
        </div>

        <!-- ================= UPDATE PROFILE ================= -->
        <div id="updateProfile" class="section" style="display:none;">
            <div class="update-container">
                <h2>Update Your Profile</h2>

                <form method="POST" action="update_profile.php" enctype="multipart/form-data">
                    <label>Full Name:</label>
                    <input type="text" name="fullname" required value="<?= htmlspecialchars($currentUser['full_name']); ?>">

                    <label>Email:</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($currentUser['email']); ?>">

                    <label>Phone:</label>
                    <input type="text" name="phone" required pattern="\d{8,15}" title="Enter a valid phone number" value="<?= htmlspecialchars($currentUser['phone'] ?? ''); ?>">

                    <label>Profile Photo:</label>
                    <input type="file" name="photo" accept="image/*">

                    <label>New Password (optional):</label>
                    <input type="password" name="password" minlength="6">

                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" minlength="6">

                    <button type="submit">Update</button>
                </form>
            </div>
            <script>
            document.querySelector('#updateProfile form').addEventListener('submit', function(e) {
                const pass = document.querySelector('input[name="password"]').value;
                const confirm = document.querySelector('input[name="confirm_password"]').value;
                if (pass !== confirm) {
                    alert("Passwords do not match!");
                    e.preventDefault();
                }
            });
            </script>
        </div>

    </div>

    <!-- PHOTO MODAL -->
    <div id="photoModal" class="photo-modal">
        <span class="photo-modal-close" onclick="closePhotoModal()">&times;</span>
        <img class="photo-modal-content" id="modalImage" src="<?= htmlspecialchars($profilePhoto); ?>">
    </div>

    <script>
        function showSection(id) {
            document.querySelectorAll('.section').forEach(sec => sec.style.display = 'none');
            document.getElementById(id).style.display = 'block';
            localStorage.setItem('student_active_tab', id);
        }

        // Initialize Persistence
        document.addEventListener('DOMContentLoaded', function() {
            const isProfileSuccess = <?php echo (isset($_GET['success']) && $_GET['success'] === 'profile_updated') ? 'true' : 'false'; ?>;

             if (isProfileSuccess) {
                showSection('updateProfile');
            } else {
                const storedTab = localStorage.getItem('student_active_tab');
                if (storedTab && document.getElementById(storedTab)) {
                    showSection(storedTab);
                } else {
                    showSection('myCourses');
                }
            }
        });

        // Auto-dismiss alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            });
        }, 4000);

        // Modal Logic
        function openPhotoModal() {
            document.getElementById("photoModal").style.display = "block";
        }

        function closePhotoModal() {
            document.getElementById("photoModal").style.display = "none";
        }

    </script>
    
    <!-- Custom Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-header">
                <h3>Confirm Action</h3>
                <span class="photo-modal-close" style="position:static; font-size: 24px; color: #555; height: auto; width: auto;" onclick="closeConfirmModal()">&times;</span>
            </div>
            <div class="confirm-modal-body">
                <p id="confirmMessage">Are you sure you want to proceed?</p>
            </div>
            <div class="confirm-modal-footer">
                <button class="btn-modal-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn-modal-confirm" id="confirmBtnAction">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        let formToSubmit = null;

        function confirmDrop(btn) {
            formToSubmit = btn.closest('form');
            document.getElementById('confirmMessage').textContent = 'Are you sure you want to drop this course? This action cannot be undone.';
            document.getElementById('confirmModal').style.display = 'block';
        }

        document.getElementById('confirmBtnAction').addEventListener('click', function() {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });

        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
            formToSubmit = null;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const photoModal = document.getElementById("photoModal");
            const confirmModal = document.getElementById("confirmModal");
            
            if (event.target == photoModal) {
                photoModal.style.display = "none";
            }
            if (event.target == confirmModal) {
                confirmModal.style.display = "none";
            }
        }
    </script>
</body>
</html>
