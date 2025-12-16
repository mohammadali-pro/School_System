<?php
session_start();
require '../config/db.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only admin can add students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Get form data
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$major = trim($_POST['Major'] ?? '');
$yearOfStudy = trim($_POST['YearOfStudy'] ?? '');
$password = trim($_POST['password'] ?? '');

// Check if email already exists
$stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo "<script>alert('Error: A student with this email address already exists!'); window.location.href='admin_dashboard.php';</script>";
    exit;
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into Users table
$stmt = $pdo->prepare("
    INSERT INTO Users (full_name, email, phone, role, password) 
    VALUES (?, ?, ?, 'student', ?)
");

try {
    $pdo->beginTransaction();

    $stmt->execute([$fullname, $email, $phone, $hashedPassword]);
    $student_id = $pdo->lastInsertId();

    
    $stmt2 = $pdo->prepare("
        INSERT INTO students (user_id, major, year_of_study)
        VALUES (?, ?, ?)
    ");

    $stmt2->execute([$student_id, $major, intval($yearOfStudy)]); // intval to ensure we send an int

    $pdo->commit();

    // SEND EMAIL WITH LOGIN DETAILS
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com"; 
        $mail->SMTPAuth = true;
        $mail->Username = "maazm691@gmail.com"; 
        $mail->Password = "imca ypng bhzu xzqy";    
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
            <p>Your student account has been created.</p>
            <p><b>Login Email:</b> $email</p>
            <p><b>Password:</b> $password</p>
            <p>Please log in and update your password.</p>
            <br>
            <p>Regards,<br>School Admin</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        // If email fails, still continue (or log error)
    }

    // Redirect back with success message
    header("Location: admin_dashboard.php?success=student_added");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error adding student: " . $e->getMessage());
}
?>
