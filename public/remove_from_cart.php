<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity   = intval($_POST['quantity'] ?? 0);

    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        try {
            $pdo->beginTransaction();

            // Restore stock
            $update = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $update->execute([$quantity, $product_id]);

            // Remove from cart
            unset($_SESSION['cart'][$product_id]);

            $pdo->commit();

            $_SESSION['success'] = "Item removed from cart.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error removing item: " . $e->getMessage();
        }
    }
}

// Redirect back to cart
header("Location: cart.php");
exit;