<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM order_history WHERE user_id = ? ORDER BY moved_at DESC");
$stmt->execute([$userId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Order History</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-5xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">📜 Order History</h1>

        <?php if ($history): ?>
        <div class="space-y-6">
            <?php foreach ($history as $order): ?>
            <div class="bg-white rounded-xl shadow p-5">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Order #<?= $order['order_id'] ?></h2>
                    <span
                        class="px-3 py-1 rounded-full text-sm 
                                <?= $order['status'] == 'Completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= $order['status'] ?>
                    </span>
                </div>
                <p class="mt-2 font-bold text-blue-600">Total: KSh <?= number_format($order['total'], 2) ?></p>
                <p class="text-gray-500 text-sm">Moved on <?= date("M d, Y H:i", strtotime($order['moved_at'])) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-gray-500">No order history yet.</p>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>