<?php
session_start();
require_once __DIR__ . '/../includes/db.php';


if (isset($_SESSION['admin_id'])) {
header('Location: dashboard.php'); exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';


$stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ? AND role = "admin" LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if ($user && password_verify($password, $user['password'])) {
$_SESSION['admin_id'] = $user['id'];
header('Location: dashboard.php'); exit;
} else {
$error = 'Invalid credentials';
}
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Admin Login</title>
</head>

<body class="bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-96">
        <h2 class="text-2xl font-bold text-center mb-6">Admin Login</h2>
        <?php if(!empty($error)): ?>
        <div class="bg-red-100 text-red-700 p-2 mb-4 rounded"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm mb-1">Email</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300">
            </div>
            <div class="mb-4">
                <label class="block text-sm mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300">
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">Login</button>
        </form>
    </div>
</body>

</html>