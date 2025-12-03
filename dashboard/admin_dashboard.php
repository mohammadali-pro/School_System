<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
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
        <div class="logo">MyPortal Admin</div>
        <div class="nav-buttons">
            <button onclick="showSection('updateProfile')">Update Profile</button>
            <button onclick="showSection('manageTeachers')">Manage Teachers</button>
            <button onclick="showSection('manageStudents')">Manage Students</button>
            <button onclick="showSection('assignCourses')">Assign Courses</button>
            <button onclick="showSection('viewGrades')">View Grades</button>
            <button onclick="window.location.href='../logout.php'">Logout</button>
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
        </div>

        <div id="assignCourses" class="section" style="display:none;">
            <h2>Assign Courses</h2>
        </div>

        <div id="viewGrades" class="section" style="display:none;">
            <h2>View Grades</h2>
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

    </script>

</body>
</html>
