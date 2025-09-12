<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity   = max(1, intval($_POST['quantity'] ?? 1));

    try {
        $pdo->beginTransaction();

        // Fetch current stock & product details
        $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND status='active' FOR UPDATE");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $pdo->rollBack();
            $_SESSION['error'] = "Product not found or inactive.";
            header("Location: product.php?id=" . $product_id);
            exit;
        }

        // Check stock
        if ($product['stock'] < $quantity) {
            $pdo->rollBack();
            $_SESSION['error'] = "Only {$product['stock']} item(s) left in stock.";
            header("Location: product.php?id=" . $product_id);
            exit;
        }

        // Reduce stock
        $update = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $update->execute([$quantity, $product_id]);

        $pdo->commit();

        // Add to session cart
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

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

        $_SESSION['success'] = "Item added to cart.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Something went wrong: " . $e->getMessage();
    }
}

// Redirect back
header("Location: cart.php");
exit;