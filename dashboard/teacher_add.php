<?php
session_start();
require '../config/db.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Only admin can add teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
// Get form data
$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$department = trim($_POST['department']);
$specialization = trim($_POST['specialization']);
$password = trim($_POST['password']);

// Check if email already exists
$stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    die("<script>alert('Email already exists!'); history.back();</script>");
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into Users table
$stmt = $pdo->prepare("
    INSERT INTO Users (full_name, email, phone, role, password) 
    VALUES (?, ?, ?, 'teacher', ?)
");

$stmt->execute([$fullname, $email, $phone, $hashedPassword]);

$teacher_id = $pdo->lastInsertId();

// Insert into teachers table
$stmt2 = $pdo->prepare("
    INSERT INTO teachers (user_id, department, specialization)
    VALUES (?, ?, ?)
");

$stmt2->execute([$teacher_id, $department, $specialization]);

// SEND EMAIL WITH LOGIN DETAILS
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com"; 
    $mail->SMTPAuth = true;
    $mail->Username = "maazm691@gmail.com"; // <-- replace
    $mail->Password = "imca ypng bhzu xzqy";     // <-- replace (Google App Password)
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;

    // Recipients
$mail->setFrom("maazm691@gmail.com", "School System");

    $mail->addAddress($email, $fullname);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = "Welcome to School Portal";
    $mail->Body = "
        <h3>Hello $fullname,</h3>
        <p>Your teacher account has been created.</p>
        <p><b>Login Email:</b> $email</p>
        <p><b>Password:</b> $password</p>
        <p>Please log in and update your password.</p>
        <br>
        <p>Regards,<br>School Admin</p>
    ";

    $mail->send();

} catch (Exception $e) {
    // If email fails, still continue
}

// Redirect back with success message
header("Location: admin_dashboard.php?success=teacher_added");
exit;

?>

