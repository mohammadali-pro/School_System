<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Fetch current user details
$stmtUser = $pdo->prepare("SELECT full_name, photo_path FROM Users WHERE user_id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

$profilePhoto = !empty($currentUser['photo_path']) ? '../uploads/' . $currentUser['photo_path'] : 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['full_name']) . '&background=random&color=fff';

// SEARCH LOGIC
$teacherSearchResults = null;
$studentSearchResults = null;
$searchError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Teacher Search
    if (isset($_POST['search_teacher_email'])) {
        $email = trim($_POST['search_teacher_email']);
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM Users WHERE email = ? AND role = 'teacher'");
        $stmt->execute([$email]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            $searchedTeacherName = $teacher['full_name'];
            $stmtC = $pdo->prepare("SELECT course_name, course_code FROM courses WHERE teacher_id = ?");
            $stmtC->execute([$teacher['user_id']]);
            $teacherSearchResults = $stmtC->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $searchError = "Teacher not found with that email.";
        }
    }

    // Student Search
    if (isset($_POST['search_student_email'])) {
        $email = trim($_POST['search_student_email']);
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM Users WHERE email = ? AND role = 'student'");
        $stmt->execute([$email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
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
    <title>Admin Dashboard - School Management System</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>

<body class="dashboard-body">

    <nav class="navbar">
        <div class="logo"></div>
        <div class="nav-buttons">
            <button onclick="showSection('updateProfile')">Update Profile</button>
            <button onclick="showSection('manageTeachers')">Manage Teachers</button>
            <button onclick="showSection('manageStudents')">Manage Students</button>
            <button onclick="showSection('manageCourses')">Manage Courses</button>
            <button onclick="showSection('assignCourses')">Assign Courses</button>
            <button onclick="showSection('searchDetails')">Search Details</button>
            <button onclick="window.location.href='../logout.php'">Logout</button>
        </div>
        <div class="navbar-profile">
            <img src="<?= htmlspecialchars($profilePhoto); ?>" alt="Profile" class="navbar-profile-img" onclick="openPhotoModal()">
        </div>
    </nav>

    <div class="dashboard-container">


        <h2 class="welcome1">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>!</h2>

        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] === 'teacher_added'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Teacher added successfully! Email with login credentials has been sent.
            </div>
            <?php elseif ($_GET['success'] === 'teacher_updated'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Teacher updated successfully!
            </div>
            <?php elseif ($_GET['success'] === 'profile_updated'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Profile updated successfully!
            </div>
            <?php elseif ($_GET['success'] === 'student_added'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Student added successfully! Email with login credentials has been sent.
            </div>
            <?php elseif ($_GET['success'] === 'student_updated'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Student updated successfully!
            </div>
            <?php elseif ($_GET['success'] === 'enrollment_created'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Student successfully enrolled in the course!
            </div>
            <?php elseif ($_GET['success'] === 'course_added'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Course added successfully!
            </div>
            <?php elseif ($_GET['success'] === 'course_updated'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Course updated successfully!
            </div>
            <?php elseif ($_GET['success'] === 'course_deleted'): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Course deleted successfully!
            </div>
            <?php elseif (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'course_exists'): ?>
                <div class="alert alert-error" style="max-width: 600px; margin: 20px auto; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
                    Error: A course with this Course Code already exists.
                </div>
                <?php elseif ($_GET['error'] === 'already_enrolled'): ?>
                <div class="alert alert-error" style="max-width: 600px; margin: 20px auto; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
                    Error: Student is already enrolled in this course.
                </div>
                <?php elseif ($_GET['error'] === 'email_exists'): ?>
                <div class="alert alert-error" style="max-width: 600px; margin: 20px auto; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
                    Error: A student with this email address already exists.
                </div>
                <?php elseif ($_GET['error'] === 'missing_fields'): ?>
                <div class="alert alert-error" style="max-width: 600px; margin: 20px auto; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
                    Error: Please select both a student and a course.
                </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ================= UPDATE PROFILE ================= -->
        <div id="updateProfile" class="section" style="display:none;">
            <div class="update-container">
                <h2>Update Your Profile</h2>

                <form method="POST" action="update_profile.php" enctype="multipart/form-data">
                    <label>Full Name:</label>
                    <input type="text" name="fullname" required>

                    <label>Email:</label>
                    <input type="email" name="email" required>

                    <label>Phone:</label>
                    <input type="text" name="phone" required pattern="\d{8,15}" title="Enter a valid phone number">

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

        <!-- ================= MANAGE TEACHERS ================= -->
        <div id="manageTeachers" class="section" style="display:none;">
            <h2>Manage Teachers</h2>

            <!-- TOP BUTTONS -->
            <div class="button-group">
                <button class="btn-primary" onclick="showAddTeacher()">Add Teacher</button>
                <button class="btn-view" onclick="showTeacherList()">View Teachers</button>
            </div>

            <!-- ADD TEACHER FORM -->
            <div id="addTeacherSection" class="update-container" style="display:none;">
                <h3>Add New Teacher</h3>

                <form method="POST" action="teacher_add.php">
                    <label>Full Name:</label>
                    <input type="text" name="fullname" required>

                    <label>Email:</label>
                    <input type="email" name="email" required>

                    <label>Phone:</label>
                    <input type="text" name="phone" pattern="\d{8,15}" required>

                    <label>Department:</label>
                    <input type="text" name="department" required>

                    <label>Specialization:</label>
                    <input type="text" name="specialization" required>

                    <label>Password:</label>
                    <input type="password" name="password" minlength="6" required>

                    <button type="submit">Add Teacher</button>
                </form>
            </div>


            <!-- TEACHER LIST -->
            <div id="teacherListSection" style="display:none;">
                <h3>All Teachers</h3>

                <!-- SEARCH BOX -->
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="teacherSearch" placeholder="Search teachers by name, email, department, or specialization..." onkeyup="filterTeachers()">
                    </div>
                    <div class="search-stats">
                        Showing <span class="highlight" id="visibleCount">0</span> of <span class="highlight" id="totalCount">0</span> teachers
                    </div>
                </div>

                <?php
                // FETCH TEACHERS
                $stmt = $pdo->prepare("
                    SELECT u.user_id, u.full_name, u.email, u.phone, u.photo_path,
                           t.department, t.specialization
                    FROM Users u
                    LEFT JOIN teachers t ON u.user_id = t.user_id
                    WHERE u.role = 'teacher'
                    ORDER BY u.full_name ASC
                ");
                $stmt->execute();
                $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div style="overflow-x: auto; background: #ffffff; padding: 10px; border-radius: 8px;">
                    <table class="data-table" id="teachersTable" style="background: #ffffff !important;">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Specialization</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teachersTableBody">
                            <?php foreach ($teachers as $t): ?>
                            <tr data-name="<?= strtolower(htmlspecialchars($t['full_name'])); ?>" 
                                data-email="<?= strtolower(htmlspecialchars($t['email'])); ?>" 
                                data-department="<?= strtolower(htmlspecialchars($t['department'] ?? '')); ?>" 
                                data-specialization="<?= strtolower(htmlspecialchars($t['specialization'] ?? '')); ?>">
                                <td>
                                    <?php if ($t['photo_path']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($t['photo_path']); ?>" width="50" height="50" alt="Profile Photo">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #6c757d; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; margin: 0 auto; border: 2px solid #dee2e6;">
                                            <?= strtoupper(substr($t['full_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td><strong><?= htmlspecialchars($t['full_name']); ?></strong></td>
                                <td><?= htmlspecialchars($t['email']); ?></td>
                                <td><?= htmlspecialchars($t['phone'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($t['department'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($t['specialization'] ?? 'N/A'); ?></td>

                                <td>
                                    <button class="btn-action btn-edit" onclick="editTeacher(<?= $t['user_id']; ?>)">Edit</button>
                                    <button class="btn-action btn-delete" onclick="deleteTeacher(<?= $t['user_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= OTHER SECTIONS (UNCHANGED) ================= -->
        <div id="manageStudents" class="section" style="display:none;">
            <h2>Manage Students</h2>
            <!-- Top Buttons-->
             <div class ="button-group">
                <button class ="btn-primary" onclick="showAddStudent()">Add Student</button>
                <button class="btn-view" onclick="showStudentList()">View Students</button>
                </div>
                <!-- Add student form-->
                 <div id="addStudentSection" class ="update-container"style="display:none;">
                    <h3>Add New Student</h3>
                    <form method="POST" action="student_add.php">
                    <label>Full Name:</label>
                    <input type="text" name="fullname" required>

                    <label>Email:</label>
                    <input type="email" name="email" required>

                    <label>Phone:</label>
                    <input type="text" name="phone" pattern="\d{8,15}" required>

                    <label>Major:</label>
                    <input type="text" name="Major" required>
                   <label for="YearOfStudy">Year Of Study:</label>

                    <div class="select-wrapper">
                    <select id="YearOfStudy" name="YearOfStudy" required>
                    <option value="">-- Select a year of study --</option>
                    <option value="2025/2026">2025 / 2026</option>
                    <option value="2026/2027">2026 / 2027</option>
                    </select>
                    </div>


                    <label>Password:</label>
                    <input type="password" name="password" minlength="6" required>
                    <button type="submit">Add Student</button>
                </form>
            </div>

            <!-- STUDENT LIST -->
            <div id="studentListSection" style="display:none;">
                <h3>All Students</h3>

                <!-- SEARCH BOX -->
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="studentSearch" placeholder="Search students by name, email, major, or year..." onkeyup="filterStudents()">
                    </div>
                    <div class="search-stats">
                        Showing <span class="highlight" id="visibleStudentCount">0</span> of <span class="highlight" id="totalStudentCount">0</span> students
                    </div>
                </div>

                <?php
                // FETCH STUDENTS
                $stmtS = $pdo->prepare("
                    SELECT u.user_id, u.full_name, u.email, u.phone, u.photo_path,
                           s.major, s.year_of_study
                    FROM Users u
                    LEFT JOIN students s ON u.user_id = s.user_id
                    WHERE u.role = 'student'
                    ORDER BY u.full_name ASC
                ");
                $stmtS->execute();
                $students = $stmtS->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div style="overflow-x: auto; background: #ffffff; padding: 10px; border-radius: 8px;">
                    <table class="data-table" id="studentsTable" style="background: #ffffff !important;">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Major</th>
                                <th>Year of Study</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody">
                            <?php foreach ($students as $s): ?>
                            <tr data-name="<?= strtolower(htmlspecialchars($s['full_name'])); ?>" 
                                data-email="<?= strtolower(htmlspecialchars($s['email'])); ?>" 
                                data-major="<?= strtolower(htmlspecialchars($s['major'] ?? '')); ?>" 
                                data-year="<?= strtolower(htmlspecialchars($s['year_of_study'] ?? '')); ?>">
                                <td>
                                    <?php if ($s['photo_path']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($s['photo_path']); ?>" width="50" height="50" alt="Profile Photo">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #6c757d; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; margin: 0 auto; border: 2px solid #dee2e6;">
                                            <?= strtoupper(substr($s['full_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td><strong><?= htmlspecialchars($s['full_name']); ?></strong></td>
                                <td><?= htmlspecialchars($s['email']); ?></td>
                                <td><?= htmlspecialchars($s['phone'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($s['major'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($s['year_of_study'] ?? 'N/A'); ?></td>

                                <td>
                                    <button class="btn-action btn-edit" onclick="editStudent(<?= $s['user_id']; ?>)">Edit</button>
                                    <button class="btn-action btn-delete" onclick="deleteStudent(<?= $s['user_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="assignCourses" class="section" style="display:none;">
            <h2>Assign Courses to Students</h2>
            <div class="update-container">
                <h3>Enroll Student in a Course</h3>
                
                <?php
                // Fetch All Students (ID, Name, Email)
                $stmtAllStudents = $pdo->prepare("SELECT user_id, full_name, email FROM Users WHERE role='student' ORDER BY full_name ASC");
                $stmtAllStudents->execute();
                $allStudents = $stmtAllStudents->fetchAll(PDO::FETCH_ASSOC);

                // Fetch All Courses (ID, Name, Code)
                $stmtAllCourses = $pdo->prepare("SELECT course_id, course_name, course_code FROM courses ORDER BY course_name ASC");
                $stmtAllCourses->execute();
                $allCourses = $stmtAllCourses->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <form method="POST" action="enroll_student.php">
                    <label>Select Student:</label>
                    <div class="select-wrapper">
                        <select name="student_id" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach ($allStudents as $st): ?>
                                <option value="<?= $st['user_id']; ?>">
                                    <?= htmlspecialchars($st['full_name']); ?> (<?= htmlspecialchars($st['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label>Select Course:</label>
                    <div class="select-wrapper">
                        <select name="course_id" required>
                            <option value="">-- Choose Course --</option>
                            <?php foreach ($allCourses as $cr): ?>
                                <option value="<?= $cr['course_id']; ?>">
                                    <?= htmlspecialchars($cr['course_name']); ?> (<?= htmlspecialchars($cr['course_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit">Enroll Student</button>
                </form>
            </div>
        </div>

        <!-- ================= MANAGE COURSES ================= -->
        <div id="manageCourses" class="section" style="display:none;">
            <h2>Manage Courses</h2>

            <div class="button-group">
                <button class="btn-primary" onclick="showAddCourse()">Add Course</button>
                <button class="btn-view" onclick="showCourseList()">View Courses</button>
            </div>

            <!-- ADD COURSE FORM -->
            <div id="addCourseSection" class="update-container" style="display:none;">
                <h3>Add New Course</h3>
                <form method="POST" action="course_add.php">
                    <label>Course Name:</label>
                    <input type="text" name="course_name" required>

                    <label>Course Code:</label>
                    <input type="text" name="course_code" required>

                    <label>Credits:</label>
                    <input type="number" name="credits" required>

                    <label>Assign Teacher (Optional):</label>
                    <?php
                    // Fetch teachers for dropdown
                    $stmtT = $pdo->prepare("SELECT user_id, full_name FROM Users WHERE role='teacher' ORDER BY full_name ASC");
                    $stmtT->execute();
                    $teachersList = $stmtT->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="select-wrapper">
                        <select name="teacher_id">
                            <option value="">-- No Teacher --</option>
                            <?php foreach ($teachersList as $tl): ?>
                                <option value="<?= $tl['user_id']; ?>"><?= htmlspecialchars($tl['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit">Add Course</button>
                </form>
            </div>

            <!-- COURSE LIST -->
            <div id="courseListSection" style="display:none;">
                <h3>All Courses</h3>
                
                <?php
                // Fetch with teacher names
                $stmtC = $pdo->prepare("
                    SELECT c.course_id, c.course_name, c.course_code, c.credits, u.full_name as teacher_name
                    FROM courses c
                    LEFT JOIN users u ON c.teacher_id = u.user_id
                    ORDER BY c.course_name ASC
                ");
                $stmtC->execute();
                $coursesList = $stmtC->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div style="overflow-x: auto; background: #ffffff; padding: 10px; border-radius: 8px;">
                    <table class="data-table" style="background: #ffffff !important;">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Credits</th>
                                <th>Teacher</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coursesList as $cl): ?>
                            <tr>
                                <td><?= htmlspecialchars($cl['course_code']); ?></td>
                                <td><strong><?= htmlspecialchars($cl['course_name']); ?></strong></td>
                                <td><?= htmlspecialchars($cl['credits']); ?></td>
                                <td><?= htmlspecialchars($cl['teacher_name'] ?? 'Unassigned'); ?></td>
                                <td>
                                    <button class="btn-action btn-edit" onclick="editCourse(<?= $cl['course_id']; ?>)">Edit</button>
                                    <button class="btn-action btn-delete" onclick="deleteCourse(<?= $cl['course_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= SEARCH DETAILS ================= -->
        <div id="searchDetails" class="section" style="display:<?php echo (isset($teacherSearchResults) || isset($studentSearchResults) || isset($searchError)) ? 'block' : 'none'; ?>;">
            <h2>Search Details</h2>
            
            <div class="search-details-wrapper">
                
                <!-- TEACHER SEARCH BLOCK -->
                <div class="search-block">
                    <h3>Search Teacher</h3>
                    <form method="POST" class="search-form">
                        <label>Teacher Email:</label>
                        <div class="input-group">
                            <input type="email" name="search_teacher_email" required placeholder="teacher@example.com">
                            <button type="submit" class="btn-search">Search</button>
                        </div>
                    </form>

                    <?php if (isset($teacherSearchResults)): ?>
                        <div class="search-results">
                            <h4>Courses Taught by: <span class="highlight-name"><?= htmlspecialchars($searchedTeacherName); ?></span></h4>
                            <?php if (count($teacherSearchResults) > 0): ?>
                                <ul class="course-list">
                                <?php foreach ($teacherSearchResults as $tc): ?>
                                    <li>
                                        <span class="course-code"><?= htmlspecialchars($tc['course_code']); ?></span>
                                        <span class="course-name"><?= htmlspecialchars($tc['course_name']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="no-data">No courses assigned to this teacher.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- STUDENT SEARCH BLOCK -->
                <div class="search-block">
                    <h3>Search Student</h3>
                    <form method="POST" class="search-form">
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
                </div>

            </div>
            
            <?php if (isset($searchError)): ?>
                <div class="alert alert-error" style="margin-top: 20px;"><?= htmlspecialchars($searchError); ?></div>
            <?php endif; ?>
        </div>

    </div><!-- END dashboard-container -->

    <!-- ================= JAVASCRIPT ================= -->
    <script>

        function showSection(id) {
            document.querySelectorAll('.section').forEach(sec => sec.style.display = 'none');
            document.getElementById(id).style.display = 'block';
        }

        function showAddTeacher() {
            document.getElementById("addTeacherSection").style.display = "block";
            document.getElementById("teacherListSection").style.display = "none";
        }

        // Initialize count on page load
        function showTeacherList() {
            document.getElementById("addTeacherSection").style.display = "none";
            document.getElementById("teacherListSection").style.display = "block";
            
            // Set initial count
            const rows = document.getElementById('teachersTableBody').getElementsByTagName('tr');
            document.getElementById('visibleCount').textContent = rows.length;
            document.getElementById('totalCount').textContent = rows.length;
            
            // Clear search
            document.getElementById('teacherSearch').value = '';
        }

        function editTeacher(id) {
            window.location.href = "teacher_edit.php?id=" + id;
        }

        function deleteTeacher(id) {
            if (confirm("Are you sure you want to delete this teacher?")) {
                window.location.href = "teacher_delete.php?id=" + id;
            }
        }

        // Search/Filter functionality
        function filterTeachers() {
            const searchInput = document.getElementById('teacherSearch');
            const filter = searchInput.value.toLowerCase();
            const table = document.getElementById('teachersTableBody');
            const rows = table.getElementsByTagName('tr');
            
            let visibleCount = 0;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const department = row.getAttribute('data-department') || '';
                const specialization = row.getAttribute('data-specialization') || '';
                
                const searchText = name + ' ' + email + ' ' + department + ' ' + specialization;
                
                if (searchText.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Update count
            document.getElementById('visibleCount').textContent = visibleCount;
            document.getElementById('totalCount').textContent = rows.length;
        }

        // Student JS
        function showStudentList() {
            document.getElementById("addStudentSection").style.display = "none";
            document.getElementById("studentListSection").style.display = "block";
            
            // Set initial count
            const rows = document.getElementById('studentsTableBody').getElementsByTagName('tr');
            document.getElementById('visibleStudentCount').textContent = rows.length;
            document.getElementById('totalStudentCount').textContent = rows.length;
            
            // Clear search
            document.getElementById('studentSearch').value = '';
        }

        function showAddStudent(){
            document.getElementById("addStudentSection").style.display="block";
            document.getElementById("studentListSection").style.display="none";
        }

        function editStudent(id) {
            window.location.href = "student_edit.php?id=" + id;
        }

        function deleteStudent(id) {
            if (confirm("Are you sure you want to delete this student?")) {
                window.location.href = "student_delete.php?id=" + id;
            }
        }

        function filterStudents() {
            const searchInput = document.getElementById('studentSearch');
            const filter = searchInput.value.toLowerCase();
            const table = document.getElementById('studentsTableBody');
            const rows = table.getElementsByTagName('tr');
            
            let visibleCount = 0;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const major = row.getAttribute('data-major') || '';
                const year = row.getAttribute('data-year') || '';
                
                const searchText = name + ' ' + email + ' ' + major + ' ' + year;
                
                if (searchText.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Update count
            document.getElementById('visibleStudentCount').textContent = visibleCount;
            document.getElementById('totalStudentCount').textContent = rows.length;
        }

        // Course JS
        function showAddCourse() {
            document.getElementById("addCourseSection").style.display = "block";
            document.getElementById("courseListSection").style.display = "none";
        }

        function showCourseList() {
            document.getElementById("addCourseSection").style.display = "none";
            document.getElementById("courseListSection").style.display = "block";
        }

        function editCourse(id) {
            window.location.href = "course_edit.php?id=" + id;
        }

        function deleteCourse(id) {
            if (confirm("Are you sure you want to delete this course?")) {
                window.location.href = "course_delete.php?id=" + id;
            }
        }

        // Auto-dismiss alerts after 4 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(function() {
                    alert.style.display = "none";
                }, 500); // Wait for fade out
            });
        }, 4000);

    </script>


    <!-- PHOTO MODAL -->
    <div id="photoModal" class="photo-modal">
        <span class="photo-modal-close" onclick="closePhotoModal()">&times;</span>
        <img class="photo-modal-content" id="modalImage" src="<?= htmlspecialchars($profilePhoto); ?>">
    </div>

    <script>
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

</body>
</html>
