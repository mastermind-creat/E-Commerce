<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    if ($name && $email && $pass) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = "Email already registered.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;

            // Redirect if set
            $redirect = $_GET['redirect'] ?? 'index.php';
            header("Location: $redirect");
            exit;
        }
    } else {
        $message = "All fields are required.";
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Register - Aunt’s Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6">Create Account</h1>
        <?php if ($message): ?>
        <p class="text-red-500 mb-4"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <input type="text" name="name" placeholder="Full Name" class="w-full border p-2 rounded" required>
            <input type="email" name="email" placeholder="Email Address" class="w-full border p-2 rounded" required>
            <input type="password" name="password" placeholder="Password" class="w-full border p-2 rounded" required>
            <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 rounded">Register</button>
        </form>
        <p class="text-center text-sm mt-4">Already have an account?
            <a href="login.php" class="text-blue-600 hover:underline">Login</a>
        </p>
    </div>
</body>

</html>