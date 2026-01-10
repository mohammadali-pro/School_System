<?php
session_start();
require '../config/db.php';

// Ensure user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user details
$stmtUser = $pdo->prepare("SELECT full_name, email, phone, photo_path FROM Users WHERE user_id = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

$profilePhoto = !empty($currentUser['photo_path']) ? '../uploads/' . $currentUser['photo_path'] : 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['full_name']) . '&background=random&color=fff';

// SEARCH LOGIC
$studentSearchResults = null;
$searchError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Student Search
    if (isset($_POST['search_student_email'])) {
        $email = trim($_POST['search_student_email']);
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM Users WHERE email = ? AND role = 'student'");
        $stmt->execute([$email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Check if student is enrolled in ANY course taught by this teacher
            $stmtCheck = $pdo->prepare("
                SELECT COUNT(*) 
                FROM enrollment e 
                JOIN courses c ON e.course_id = c.course_id 
                WHERE e.student_id = ? AND c.teacher_id = ?
            ");
            $stmtCheck->execute([$student['user_id'], $user_id]);
            $isMyStudent = $stmtCheck->fetchColumn();

            if ($isMyStudent > 0) {
                // Student belongs to this teacher, fetch details
                $searchedStudentName = $student['full_name'];
                $stmtE = $pdo->prepare("
                    SELECT c.course_name, c.course_code, g.grade
                    FROM enrollment e
                    JOIN courses c ON e.course_id = c.course_id
                    LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
                    WHERE e.student_id = ?
                ");
                $stmtE->execute([$student['user_id']]);
                $studentSearchResults = $stmtE->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $searchError = "Access Denied: This student is not enrolled in any of your courses.";
            }
        } else {
            $searchError = "Student not found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - School Management System</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">

</head>

<body class="dashboard-body">

    <nav class="navbar">
        <div class="logo">Teacher Panel</div>
        <div class="nav-buttons">
            <button onclick="showSection('myCourses')">My Courses</button>
            <button onclick="showSection('searchDetails')">Search Student</button>
            <button onclick="showSection('updateProfile')">Update Profile</button>
            <button onclick="window.location.href='../logout.php'">Logout</button>
        </div>
        <div class="navbar-profile">
            <img src="<?= htmlspecialchars($profilePhoto); ?>" alt="Profile" class="navbar-profile-img" onclick="openPhotoModal()" style="cursor: pointer;">
        </div>
    </nav>

    <div class="dashboard-container">

        <h2 class="welcome1">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>

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

        <!-- ================= MY COURSES ================= -->
        <div id="myCourses" class="section" style="display:none;">
            <h2>My Courses</h2>
            
            <?php
            // Fetch courses assigned to this teacher
            $stmtC = $pdo->prepare("SELECT course_id, course_name, course_code, credits FROM courses WHERE teacher_id = ?");
            $stmtC->execute([$user_id]);
            $myCourses = $stmtC->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if (count($myCourses) > 0): ?>
                <div class="courses-grid">
                    <?php foreach ($myCourses as $course): ?>
                    <div class="course-card">
                        <div class="course-header">
                            <h3><?= htmlspecialchars($course['course_name']); ?></h3>
                            <span class="code"><?= htmlspecialchars($course['course_code']); ?></span>
                        </div>
                        <div class="course-body">
                            <p><strong>Credits:</strong> <?= htmlspecialchars($course['credits']); ?></p>
                            <p>Manage grades and view enrolled students for this course.</p>
                        </div>
                        <div class="course-footer">
                            <a href="teacher_course_details.php?course_id=<?= $course['course_id']; ?>" class="btn-view-course">View Students & Grades &rarr;</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="background:#eef2f7; color:#333; padding:20px; border-radius:8px; display:inline-block;">
                    You have not been assigned any courses yet.
                </div>
            <?php endif; ?>
        </div>

        <!-- ================= SEARCH STUDENT ================= -->
        <div id="searchDetails" class="section" style="display:none;">
            <h2>Search Student</h2>
            
            <div class="search-details-wrapper">
                <div class="search-block" style="max-width: 600px; margin: 0 auto;">
                    <h3>Find Student Courses</h3>
                    <form method="POST" class="search-form">
                        <?php echo csrfTokenField(); ?>
                        <label>Student Email:</label>
                        <div class="input-group">
                            <input type="email" name="search_student_email" required placeholder="student@example.com">
                            <button type="submit" class="btn-search">Search</button>
                        </div>
                    </form>

                    <?php if (isset($studentSearchResults)): ?>
                        <div class="search-results">
                            <h4>Courses Taken by: <span class="highlight-name"><?= htmlspecialchars($searchedStudentName); ?></span></h4>
                            <?php if (count($studentSearchResults) > 0): ?>
                                <table class="data-table result-table">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($studentSearchResults as $sc): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($sc['course_name']); ?> <span class="code-badge"><?= htmlspecialchars($sc['course_code']); ?></span></td>
                                            <td>
                                                <?php if ($sc['grade']): ?>
                                                    <span class="grade-badge"><?= htmlspecialchars($sc['grade']); ?></span>
                                                <?php else: ?>
                                                    <span class="grade-badge not-graded">Not Graded</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-data">This student is not enrolled in any courses.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($searchError)): ?>
                        <div class="alert alert-error" style="margin-top: 20px;"><?= htmlspecialchars($searchError); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ================= UPDATE PROFILE ================= -->
        <div id="updateProfile" class="section" style="display:none;">
            <div class="update-container">
                <h2>Update Your Profile</h2>

                <form method="POST" action="update_profile.php" enctype="multipart/form-data" autocomplete="off">
                    <?php echo csrfTokenField(); ?>
                    <label>Full Name:</label>
                    <input type="text" name="fullname" placeholder="Leave empty to keep current">

                    <label>Email:</label>
                    <input type="email" name="email" placeholder="Leave empty to keep current" autocomplete="new-user">

                    <label>Phone:</label>
                    <input type="text" name="phone" pattern="\d{8,15}" title="Enter a valid phone number" placeholder="Leave empty to keep current">

                    <label>Profile Photo:</label>
                    <input type="file" name="photo" accept="image/*">

                    <label>New Password (optional):</label>
                    <input type="password" name="password" minlength="6" autocomplete="new-password">

                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" minlength="6" autocomplete="new-password">

                    <button type="submit">Update</button>
                </form>
            </div>
            <script>
            document.querySelector('#updateProfile form').addEventListener('submit', function(e) {
                const pass = document.querySelector('input[name="password"]').value;
                const confirm = document.querySelector('input[name="confirm_password"]').value;
                if (pass !== confirm) {
                    showNotification('Passwords do not match!', 'error');
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
            
            // Persist
            localStorage.setItem('teacher_active_tab', id);
        }

        // Initialize Tab on Load
        document.addEventListener('DOMContentLoaded', function() {
            // Check if PHP indicates a search result (override persistence)
            const isSearch = <?php echo (isset($studentSearchResults) || isset($searchError)) ? 'true' : 'false'; ?>;
            const isProfileSuccess = <?php echo (isset($_GET['success']) && $_GET['success'] === 'profile_updated') ? 'true' : 'false'; ?>;

            if (isSearch) {
                showSection('searchDetails');
            } else if (isProfileSuccess) {
                showSection('updateProfile');
            } else {
                const storedTab = localStorage.getItem('teacher_active_tab');
                if (storedTab && document.getElementById(storedTab)) {
                    showSection(storedTab);
                } else {
                    showSection('myCourses'); // Default
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

        // Close on click outside
        window.onclick = function(event) {
            const modal = document.getElementById("photoModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

    <!-- Notification Container -->
    <div id="notificationContainer"></div>

    <script>
        // Custom Notification System
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `custom-notification ${type}`;
            
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            
            notification.innerHTML = `
                <span class="notification-icon">${icons[type] || icons.info}</span>
                <span class="notification-message">${message}</span>
                <span class="notification-close" onclick="this.parentElement.remove()">×</span>
            `;
            
            container.appendChild(notification);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 4000);
            
            // Click to dismiss
            notification.addEventListener('click', function(e) {
                if (!e.target.classList.contains('notification-close')) {
                    this.remove();
                }
            });
        }
    </script>
</body>
</html>
