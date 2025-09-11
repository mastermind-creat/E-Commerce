<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Ensure cart exists
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

// Calculate total
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        header("Location: login.php?redirect=checkout.php");
        exit();
    }

    $shipping_address = trim($_POST['shipping_address']);
    $delivery_phone   = trim($_POST['delivery_phone']);

    try {
        $pdo->beginTransaction();

        // Fetch customer details from users table
        $userStmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
        $userStmt->execute([$user_id]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        $customer_name  = $user['name'] ?? '';
        $customer_email = $user['email'] ?? '';
        // Prefer the delivery phone entered during checkout
        $customer_phone = !empty($delivery_phone) ? $delivery_phone : ($user['phone'] ?? '');

        // Insert order with default payment_method = 'cash'
        $stmt = $pdo->prepare("
            INSERT INTO orders 
                (user_id, customer_name, customer_email, customer_phone, shipping_address, total_amount, status, payment_method, payment_status, order_status, created_at) 
            VALUES 
                (?, ?, ?, ?, ?, ?, 'pending', 'cash', 'pending', 'pending', NOW())
        ");

        $stmt->execute([$user_id, $customer_name, $customer_email, $customer_phone, $shipping_address, $total]);
        $order_id = $pdo->lastInsertId();

        // Insert order items
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }

        $pdo->commit();

        // Clear cart
        unset($_SESSION['cart']);

        header("Location: order_success.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Checkout failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Checkout - Aunt’s Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-900">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-4xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Checkout</h1>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
            <?php foreach ($cart as $item): ?>
            <div class="flex justify-between border-b py-2">
                <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                <span>KSh <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="flex justify-between mt-4 text-xl font-bold">
                <span>Total:</span>
                <span>KSh <?= number_format($total, 2) ?></span>
            </div>
        </div>

        <form method="POST" class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold mb-4">Shipping Details</h2>

            <textarea name="shipping_address" placeholder="Enter shipping address" class="w-full border p-3 rounded"
                required></textarea>

            <input type="text" name="delivery_phone" placeholder="Enter phone number for delivery"
                class="w-full border p-3 rounded" required>

            <h2 class="text-lg font-semibold mb-4">Payment Method</h2>
            <p class="text-sm text-gray-600 mb-4">Currently, only <strong>Cash on Delivery</strong> is supported.</p>

            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition">
                Confirm Order
            </button>
        </form>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>