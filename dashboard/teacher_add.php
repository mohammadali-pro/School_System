<?php
require '../config/db.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only admin can add teachers
requireRole('admin');

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFFromPost()) {
    redirectWithError('admin_dashboard.php', 'Invalid security token');
}
// Get form data
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$department = trim($_POST['department'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate required fields
if (empty($fullname) || empty($email) || empty($phone) || empty($department) || empty($specialization) || empty($password)) {
    redirectWithError('admin_dashboard.php', 'All fields are required');
}

// Validate email format
if (!validateEmail($email)) {
    redirectWithError('admin_dashboard.php', 'Invalid email format');
}

// Validate password strength
$passwordValidation = validatePasswordStrength($password);
if (!$passwordValidation['valid']) {
    redirectWithError('admin_dashboard.php', implode(', ', $passwordValidation['errors']));
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    redirectWithError('admin_dashboard.php', 'Email already exists');
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();
    
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
    
    $pdo->commit();

// SEND EMAIL WITH LOGIN DETAILS
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = EMAIL_HOST; 
    $mail->SMTPAuth = true;
    $mail->Username = EMAIL_USERNAME;
    $mail->Password = EMAIL_PASSWORD;
    $mail->SMTPSecure = EMAIL_ENCRYPTION;
    $mail->Port = EMAIL_PORT;

    // Recipients
    $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);

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
redirectWithSuccess('admin_dashboard.php', 'Teacher added successfully');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirectWithError('admin_dashboard.php', 'Error adding teacher: ' . $e->getMessage());
}

?>

