<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity   = max(1, intval($_POST['quantity'] ?? 1));

    // Fetch product
    $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ? AND status='active'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // If cart is empty, create
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // If already in cart, increase qty
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id'       => $product['id'],
                'name'     => $product['name'],
                'price'    => $product['price'],
                'quantity' => $quantity
            ];
        }
    }
}

// Redirect back
header("Location: cart.php");
exit;