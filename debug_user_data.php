<?php
// Debug script to check user data retrieval
session_start();
require_once __DIR__ . '/includes/db.php';

echo "<h2>Debug User Data Retrieval</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>No user logged in. Please log in first.</p>";
    exit;
}

echo "<p><strong>Session User ID:</strong> " . $_SESSION['user_id'] . "</p>";

try {
    // Test the exact query used in profile.php
    $stmt = $pdo->prepare("SELECT id, name, email, phone, default_address, password, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p style='color: red;'>No user found with ID: " . $_SESSION['user_id'] . "</p>";
    } else {
        echo "<h3>Raw Database Data:</h3>";
        echo "<pre>" . print_r($user, true) . "</pre>";
        
        echo "<h3>Field Values:</h3>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . ($user['id'] ?? 'NULL') . "</li>";
        echo "<li><strong>Name:</strong> " . ($user['name'] ?? 'NULL') . "</li>";
        echo "<li><strong>Email:</strong> " . ($user['email'] ?? 'NULL') . "</li>";
        echo "<li><strong>Phone:</strong> " . ($user['phone'] ?? 'NULL') . "</li>";
        echo "<li><strong>Address:</strong> " . ($user['default_address'] ?? 'NULL') . "</li>";
        echo "<li><strong>Role:</strong> " . ($user['role'] ?? 'NULL') . "</li>";
        echo "<li><strong>Created At:</strong> " . ($user['created_at'] ?? 'NULL') . "</li>";
        echo "</ul>";
        
        // Test array_merge
        $user_with_defaults = array_merge([
            'id' => null,
            'name' => '',
            'email' => '',
            'phone' => '',
            'default_address' => '',
            'password' => '',
            'role' => 'customer',
            'created_at' => null
        ], $user ?: []);
        
        echo "<h3>After array_merge:</h3>";
        echo "<pre>" . print_r($user_with_defaults, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='public/profile.php'>Back to Profile</a></p>";
echo "<p><a href='public/profile.php?debug=1'>Profile with Debug Info</a></p>";
?>
