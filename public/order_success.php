<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// If order_id not passed, redirect
if (!isset($_GET['order_id'])) {
    header("Location: shop.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: shop.php");
    exit();
}

// Fetch order items
$itemStmt = $pdo->prepare("
    SELECT oi.*, p.name, pi.image_url 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE oi.order_id = ?
");
$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Clear cart after successful order
unset($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Aunt’s Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-900">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <h1 class="text-3xl font-bold text-green-600 mb-4">🎉 Order Placed Successfully!</h1>
            <p class="text-gray-700 mb-6">Thank you for shopping with us. Your order
                <strong>#<?= $order['id']; ?></strong> has been placed and is currently <span
                    class="font-semibold text-blue-600"><?= htmlspecialchars($order['status']); ?></span>.</p>

            <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2">Product</th>
                            <th class="px-4 py-2">Price</th>
                            <th class="px-4 py-2">Qty</th>
                            <th class="px-4 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <tr class="border-t">
                            <td class="px-4 py-2 flex items-center">
                                <img src="<?= $it['image_url'] ?: 'assets/images/placeholder.png'; ?>"
                                    alt="<?= htmlspecialchars($it['name']); ?>"
                                    class="w-12 h-12 object-cover rounded mr-3">
                                <?= htmlspecialchars($it['name']); ?>
                            </td>
                            <td class="px-4 py-2">KSh <?= number_format($it['price'], 2); ?></td>
                            <td class="px-4 py-2"><?= (int)$it['quantity']; ?></td>
                            <td class="px-4 py-2">KSh <?= number_format($it['price'] * $it['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h3 class="text-lg font-bold mt-6">Grand Total: KSh <?= number_format($order['total_amount'], 2); ?></h3>

            <div class="mt-8 flex justify-center gap-4">
                <a href="shop.php" class="px-5 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">🛍 Continue
                    Shopping</a>
                <a href="orders.php" class="px-5 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">📦 View My
                    Orders</a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>