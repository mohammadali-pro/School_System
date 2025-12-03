<?php
session_start();
require '../config/db.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get form data
$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);

$new_password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);

$photo = $_FILES['photo'] ?? null;

// ------------------ VALIDATE PASSWORD ------------------
if ($new_password !== "" && $new_password !== $confirm_password) {
    die("<script>alert('Passwords do not match!'); history.back();</script>");
}

// ------------------ HANDLE PHOTO UPLOAD ------------------
$photoName = null;

if ($photo && $photo['error'] === 0) {

    $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
    $photoName = "profile_" . $user_id . "_" . time() . "." . $ext;

    // Save to uploads folder
    move_uploaded_file($photo['tmp_name'], "../uploads/$photoName");
}

// ------------------ BUILD UPDATE QUERY ------------------
$updateFields = "full_name = ?, email = ?, phone = ?";
$params = [$fullname, $email, $phone];

// If photo uploaded → update photo_path column
if ($photoName !== null) {
    $updateFields .= ", photo_path = ?";
    $params[] = $photoName;
}

// If password provided → hash and update
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

header("Location: $redirectPage?success=profile_updated");
exit;

?>
