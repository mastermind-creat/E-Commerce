<?php
session_start();
require_once '../includes/db.php'; // PDO connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepare and execute query
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role']; // 'admin' or 'customer'

        // Redirect
        $redirect = $_GET['redirect'] ?? 'index.php';
        header("Location: $redirect");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - Aunt’s Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-pink-50 flex justify-center items-center min-h-screen px-4">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-sm">
        <h2 class="text-3xl font-bold mb-6 text-center text-pink-600">Welcome Back</h2>

        <?php if (isset($error)): ?>
        <p class="text-red-500 mb-4 text-center"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block mb-1 text-gray-700 font-medium">Email</label>
                <input type="email" name="email" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400">
            </div>

            <div>
                <label class="block mb-1 text-gray-700 font-medium">Password</label>
                <input type="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400">
            </div>

            <button type="submit"
                class="w-full bg-pink-500 text-white py-2 rounded-lg hover:bg-pink-600 transition duration-200">
                Login
            </button>
        </form>

        <div class="mt-4 flex justify-between text-sm text-gray-600">
            <a href="forgot-password.php" class="hover:text-pink-600 transition">Forgot Password?</a>
            <a href="register.php" class="hover:text-pink-600 transition">Sign Up</a>
        </div>
    </div>
</body>

</html>