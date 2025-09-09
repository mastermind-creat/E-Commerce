<?php
session_start();

// Get cart items
$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>
<!DOCTYPE html>
<html>

<head>
    <title>Your Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-5xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">🛒 Your Cart</h1>

        <?php if (empty($cart)): ?>
        <p class="text-gray-600">Your cart is empty.</p>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow p-4 space-y-4">
            <?php foreach ($cart as $item): ?>
            <?php $lineTotal = $item['price'] * $item['quantity']; ?>
            <?php $total += $lineTotal; ?>

            <div class="flex justify-between items-center border-b pb-2">
                <div>
                    <p class="font-semibold"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="text-sm text-gray-500">KSh <?= number_format($item['price'], 2) ?> ×
                        <?= $item['quantity'] ?></p>
                </div>
                <div class="font-bold">KSh <?= number_format($lineTotal, 2) ?></div>
            </div>
            <?php endforeach; ?>

            <div class="flex justify-between items-center pt-4 text-xl font-bold">
                <span>Total</span>
                <span>KSh <?= number_format($total, 2) ?></span>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <a href="checkout.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Proceed to Checkout
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>