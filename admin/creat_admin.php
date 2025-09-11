<?php
require_once '../includes/db.php';

// Change these values
$name = "Admin User"; 
$email = "admin@gmail.com";
$password = "admin123";  // Choose a strong password
$phone = "0700000000";   // Optional, can leave blank
$role = "admin";

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, phone, password, role, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$name, $email, $phone, $hashedPassword, $role]);

    echo "✅ Admin user created successfully.<br>";
    echo "Email: $email<br>Password: $password<br>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}