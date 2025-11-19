<?php
require 'config/db.php';

// Create admin account
$fullName = "Admin";
$email = "admin@gmail.com";
$plainPassword = "1234";
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

try {
    // Check if admin already exists
    $checkStmt = $pdo->prepare("SELECT user_id FROM Users WHERE email = ? AND role = 'admin'");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        echo "Admin account already exists!";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO Users (full_name, email, password, role)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$fullName, $email, $hashedPassword, "admin"])) {
            echo "Admin account created successfully!";
        } else {
            echo "Error: Failed to create admin account.";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
