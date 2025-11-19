<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">MyPortal Admin</div>
        <div class="nav-buttons">
            <button onclick="showSection('updateProfile')">Update Profile</button>
            <button onclick="showSection('manageTeachers')">Manage Teachers</button>
            <button onclick="showSection('manageStudents')">Manage Students</button>
            <button onclick="showSection('assignCourses')">Assign Courses</button>
            <button onclick="showSection('viewGrades')">View Grades</button>
            <button onclick="logout()">Logout</button>
        </div>
    </nav>

    <div class="dashboard-container">
        <h2 class="welcome1">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>!</h2>
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
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]').value;
    const confirm = document.querySelector('input[name="confirm_password"]').value;

    if (password !== confirm) {
        alert("Passwords do not match!");
        e.preventDefault();
    }
});
</script>

</div>

        <div id="manageTeachers" class="section" style="display:none;">
            <h2>Manage Teachers</h2>
            <!--Add your form here-->
        </div>

        <div id="manageStudents" class="section" style="display:none;">
            <h2>Manage Students From second computer</h2>
            <!-- Add your form here-->
        </div>

        <div id="assignCourses" class="section" style="display:none;">
            <h2>Assign Courses</h2>
            <!-- Add your form here-->
        </div>

        <div id="viewGrades" class="section" style="display:none;">
            <h2>View Grades</h2>
            <!--Add your table or content here-->
        </div>
    </div>

    <script>
        function showSection(id) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(sec => sec.style.display = 'none');
            // Show the selected section
            document.getElementById(id).style.display = 'block';
        }

        function logout() {
            window.location.href = 'index.php';
        }
    </script>
</body>


</html>
