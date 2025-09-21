<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if ($email) {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Store token
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);

            $resetLink = "http://localhost/E-Commerce/public/reset-password.php?token=$token";

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->SMTPDebug  = 0; // Enable verbose debug output
                $mail->Debugoutput = 'html';
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'kennedywambia6@gmail.com'; // Same as SMTP
                $mail->Password   = 'ejsu bvit usxl mqvt'; // Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('kennedywambia6@gmail.com', 'Springs Ministries Online Shop'); // ✅ Match Gmail account
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Reset Your Password - Springs Ministries Online Store';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>
                        <h2 style='text-align:center; color:#ec4899;'>Springs Ministries Online Store</h2>
                        <p style='font-size: 16px; color:#333;'>Hi there,</p>
                        <p style='font-size: 16px; color:#333;'>We received a request to reset your password. Click the button below to reset it. This link will expire in 1 hour.</p>
                        <div style='text-align:center; margin: 30px 0;'>
                            <a href='$resetLink' style='background-color:#ec4899; color:white; padding:12px 24px; text-decoration:none; font-size:16px; border-radius:6px; display:inline-block;'>Reset Password</a>
                        </div>
                        <p style='font-size: 14px; color:#666;'>If you did not request this, please ignore this email.</p>
                        <p style='text-align:center; font-size: 14px; color:#999;'>© " . date('Y') . " Springs Ministries Online Store. All rights reserved.</p>
                    </div>";
                $mail->AltBody = "We received a password reset request. Use this link: $resetLink (expires in 1 hour)";

                $mail->send();
                $message = "We have sent a password reset link to your email.";
            } catch (Exception $e) {
                $message = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

        } else {
            $message = "No account found with that email.";
        }
    } else {
        $message = "Please enter your email.";
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex justify-center items-center h-screen">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6">Forgot Password</h1>
        <?php if ($message): ?>
        <p class="text-center mb-4 text-blue-600"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="email" name="email" placeholder="Enter your email"
                class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-300" required>
            <button class="w-full bg-pink-500 hover:bg-pink-600 text-white font-bold py-2 rounded">
                Send Reset Link
            </button>
        </form>
    </div>
</body>

</html>