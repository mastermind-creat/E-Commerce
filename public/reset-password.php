<?php
require_once __DIR__ . '/../includes/db.php';

$message = "";
$token = $_GET['token'] ?? '';

if ($token) {
    // Validate token immediately
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $message = "Invalid or expired token.";
        $token = ""; // clear token so form won't display
    }
} else {
    $message = "Invalid password reset link.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password === $confirm) {
        $email = $reset['email'];
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Update user password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);

        // Delete token after use
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

        $message = "✅ Password reset successfully. You can now <a href='login.php' class='text-blue-600 underline'>login</a>.";
        $token = ""; // hide form after success
    } else {
        $message = "⚠️ Passwords do not match.";
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex justify-center items-center h-screen">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6">Reset Password</h1>

        <?php if ($message): ?>
        <p class="text-center mb-4 <?= str_contains($message, 'successfully') ? 'text-green-600' : 'text-red-600' ?>">
            <?= $message ?>
        </p>
        <?php endif; ?>

        <?php if ($token): ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" name="password" placeholder="New Password"
                class="w-full border p-2 rounded focus:ring-2 focus:ring-pink-300" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password"
                class="w-full border p-2 rounded focus:ring-2 focus:ring-pink-300" required>
            <button class="w-full bg-pink-500 hover:bg-pink-600 text-white font-bold py-2 rounded">
                Reset Password
            </button>
        </form>
        <?php endif; ?>
    </div>
</body>

</html>