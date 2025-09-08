<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=cart.php");
    exit();
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle removing item
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit();
}

// Handle updating quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['quantities'] as $id => $qty) {
        if ($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Aunt’s Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-900">

    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Shopping Cart</h1>

        <?php if (empty($_SESSION['cart'])): ?>
        <p class="text-gray-600">Your cart is empty. <a href="shop.php" class="text-blue-600 hover:underline">Go
                shopping</a>.</p>
        <?php else: ?>
        <form method="POST">
            <table class="w-full bg-white shadow rounded-lg overflow-hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Product</th>
                        <th class="px-4 py-2">Price</th>
                        <th class="px-4 py-2">Quantity</th>
                        <th class="px-4 py-2">Total</th>
                        <th class="px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grandTotal = 0;
                    foreach ($_SESSION['cart'] as $id => $qty):
                        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                        $stmt->execute([$id]);
                        $product = $stmt->fetch();
                        if (!$product) continue;

                        $total = $product['price'] * $qty;
                        $grandTotal += $total;
                    ?>
                    <tr class="border-b">
                        <td class="px-4 py-2 flex items-center">
                            <img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>"
                                class="w-16 h-16 object-cover rounded mr-4">
                            <span><?= htmlspecialchars($product['name']) ?></span>
                        </td>
                        <td class="px-4 py-2">Ksh <?= number_format($product['price'], 2) ?></td>
                        <td class="px-4 py-2">
                            <input type="number" name="quantities[<?= $id ?>]" value="<?= $qty ?>" min="1"
                                class="w-16 border rounded text-center">
                        </td>
                        <td class="px-4 py-2">Ksh <?= number_format($total, 2) ?></td>
                        <td class="px-4 py-2">
                            <a href="cart.php?remove=<?= $id ?>" class="text-red-600 hover:underline">Remove</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="flex justify-between items-center mt-6">
                <h2 class="text-xl font-bold">Grand Total: Ksh <?= number_format($grandTotal, 2) ?></h2>
                <div>
                    <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Update
                        Cart</button>
                    <a href="checkout.php"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Checkout</a>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

</body>

</html>