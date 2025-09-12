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
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center animate-fadeIn">
            <div class="flex justify-center mb-4">
                <div class="bg-green-100 p-4 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <h1 class="text-3xl font-bold text-green-600 mb-2">Order Placed Successfully 🎉</h1>
            <p class="text-gray-600 mb-6">
                Thank you for shopping with us! Your order
                <span class="font-semibold text-gray-900">#<?= $order['id']; ?></span>
                is currently
                <span class="font-semibold text-blue-600"><?= htmlspecialchars($order['status']); ?></span>.
            </p>

            <div class="bg-gray-50 border rounded-xl p-6 shadow-inner mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800 text-left">Order Summary</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2">Product</th>
                                <th class="px-4 py-2">Price</th>
                                <th class="px-4 py-2 text-center">Qty</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3 flex items-center">
                                    <img src="<?= $it['image_url'] ?: 'assets/images/placeholder.png'; ?>"
                                        alt="<?= htmlspecialchars($it['name']); ?>"
                                        class="w-12 h-12 object-cover rounded mr-3 shadow">
                                    <span class="font-medium"><?= htmlspecialchars($it['name']); ?></span>
                                </td>
                                <td class="px-4 py-3">KSh <?= number_format($it['price'], 2); ?></td>
                                <td class="px-4 py-3 text-center"><?= (int)$it['quantity']; ?></td>
                                <td class="px-4 py-3 text-right font-semibold">
                                    KSh <?= number_format($it['price'] * $it['quantity'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-between items-center text-lg font-bold">
                    <span>Grand Total</span>
                    <span class="text-green-600">KSh <?= number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="shop.php"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md">
                    🛍 Continue Shopping
                </a>
                <a href="orders.php"
                    class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition shadow-md">
                    📦 View My Orders
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeIn {
        animation: fadeIn 0.6s ease-in-out;
    }
    </style>
</body>

</html>