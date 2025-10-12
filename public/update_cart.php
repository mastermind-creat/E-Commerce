<?php
// public/update_cart.php - Enhanced Cart Update with AJAX Support
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$productId = intval($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';
$quantity = intval($_POST['quantity'] ?? 1);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    if ($action === 'update') {
        $quantity = max(1, $quantity);
        
        // Check stock availability
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
            exit;
        }
        
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit;
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        }
        
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    // Calculate updated totals
    $cartCount = 0;
    $total = 0;
    
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $cartCount += $item['quantity'];
        $total += $item['price'] * $item['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'cartCount' => $cartCount,
        'total' => $total,
        'message' => $action === 'remove' ? 'Item removed from cart' : 'Cart updated'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Something went wrong: ' . $e->getMessage()]);
}
?>