<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ? AND role = "admin" LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <!-- Logo / Title -->
        <div class="text-center mb-6">
            <i data-feather="shield" class="mx-auto w-12 h-12 text-blue-600"></i>
            <h2 class="text-2xl font-extrabold text-gray-800 mt-2">Admin Panel</h2>
            <p class="text-gray-500 text-sm">Sign in to manage the store</p>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg mb-4 flex items-center gap-2">
            <i data-feather="alert-triangle" class="w-4 h-4"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" class="space-y-4">
            <!-- Email -->
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <div class="relative">
                    <i data-feather="mail" class="absolute left-3 top-3 text-gray-400 w-4 h-4"></i>
                    <input type="email" name="email" required
                        class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-300 focus:outline-none"
                        placeholder="admin@example.com">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <div class="relative">
                    <i data-feather="lock" class="absolute left-3 top-3 text-gray-400 w-4 h-4"></i>
                    <input type="password" name="password" required
                        class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-300 focus:outline-none"
                        placeholder="••••••••">
                </div>
            </div>

            <!-- Submit -->
            <button type="submit"
                class="w-full bg-blue-600 text-white font-medium py-2 rounded-lg hover:bg-blue-700 transition shadow-md">
                <i data-feather="log-in" class="inline-block w-4 h-4 mr-1"></i> Login
            </button>
        </form>

        <!-- Back to Store -->
        <p class="text-center mt-4 text-sm text-gray-600">
            <a href="../public/index.php" class="hover:underline text-blue-600">
                ← Back to Store
            </a>
        </p>
    </div>

    <script>
    feather.replace();
    </script>
</body>

</html>