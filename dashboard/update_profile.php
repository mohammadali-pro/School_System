<?php
require '../config/db.php';
require '../config/FileUploadHandler.php';

// Make sure user is logged in
requireAuth();

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFFromPost()) {
    redirectWithError($_SESSION['role'] . '_dashboard.php', 'Invalid security token');
}

$user_id = $_SESSION['user_id'];

// Get current user data first
$stmt = $pdo->prepare("SELECT full_name, email, phone FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Get form data
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$new_password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

// Use current values if fields are empty
$fullname = !empty($fullname) ? $fullname : $currentUser['full_name'];
$email = !empty($email) ? $email : $currentUser['email'];
$phone = !empty($phone) ? $phone : $currentUser['phone'];

// Validate email format if email is being changed
if ($email !== $currentUser['email'] && !validateEmail($email)) {
    $role = $_SESSION['role'] ?? 'admin';
    redirectWithError($role . '_dashboard.php', 'Invalid email format');
}

$photo = $_FILES['photo'] ?? null;

// Validate password if provided
if ($new_password !== "" && $new_password !== $confirm_password) {
    $role = $_SESSION['role'] ?? 'admin';
    redirectWithError($role . '_dashboard.php', 'Passwords do not match');
}

// Validate password strength if new password provided
if ($new_password !== "") {
    $passwordValidation = validatePasswordStrength($new_password);
    if (!$passwordValidation['valid']) {
        $role = $_SESSION['role'] ?? 'admin';
        redirectWithError($role . '_dashboard.php', implode(', ', $passwordValidation['errors']));
    }
}

// Check email uniqueness only if email is being changed
if ($email !== $currentUser['email']) {
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->rowCount() > 0) {
        $role = $_SESSION['role'] ?? 'admin';
        redirectWithError($role . '_dashboard.php', 'Email already in use by another user');
    }
}

// Handle photo upload with secure validation
$photoName = null;

if ($photo && $photo['error'] !== UPLOAD_ERR_NO_FILE) {
    $uploader = new FileUploadHandler();
    $result = $uploader->upload($photo, 'profile_' . $user_id);
    
    if ($result['success']) {
        // Get old photo to delete
        $stmt = $pdo->prepare("SELECT photo_path FROM Users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $oldPhoto = $stmt->fetchColumn();
        
        // Delete old photo if exists
        if ($oldPhoto) {
            $uploader->delete($oldPhoto);
        }
        
        $photoName = $result['filename'];
    } else {
        $role = $_SESSION['role'] ?? 'admin';
        redirectWithError($role . '_dashboard.php', implode(', ', $result['errors']));
    }
}

// ------------------ BUILD UPDATE QUERY ------------------
$updateFields = "full_name = ?, email = ?, phone = ?";
$params = [$fullname, $email, $phone];

// If photo uploaded → update photo_path column
if ($photoName !== null) {
    $updateFields .= ", photo_path = ?";
    $params[] = $photoName;
}

// If password provided, validate and hash
if (!empty($new_password)) {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $updateFields .= ", password = ?";
    $params[] = $hashed;
}

// Add user_id to params
$params[] = $user_id;

// Final SQL
$sql = "UPDATE Users SET $updateFields WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);


// Update session name so dashboard updates instantly
$_SESSION['full_name'] = $fullname;

// Determine redirect based on user role
$role = $_SESSION['role'] ?? 'admin';
$redirectPage = $role . '_dashboard.php';

redirectWithSuccess($redirectPage, 'Profile updated successfully');

?>
