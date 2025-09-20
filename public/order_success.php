<?php
// public/order_success.php - Order Success Page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

// Set page title
$pageTitle = 'Order Confirmation';

$orderId = intval($_GET['order_id'] ?? 0);

if (!$orderId) {
    header('Location: index.php');
    exit;
}

// Get order details
try {
    $orderStmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: index.php');
        exit;
    }
    
    // Get order items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, pi.image_url 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$orderId]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Success Header -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-feather="check" class="w-10 h-10"></i>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">Order Confirmed!</h1>
            <p class="text-xl text-green-100">Thank you for your purchase. Your order has been successfully placed.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Information -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Order Number</h3>
                            <p class="text-lg font-semibold text-gray-900">#<?= $order['id'] ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Order Date</h3>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= date('M j, Y', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Payment Method</h3>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Status</h3>
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                <?= ucfirst($order['order_status']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Shipping Information</h2>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Delivery Address</h3>
                            <p class="text-gray-900"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Contact Phone</h3>
                            <p class="text-gray-900"><?= htmlspecialchars($order['customer_phone']) ?></p>
                        </div>
                        <?php if (!empty($order['notes'])): ?>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Order Notes</h3>
                            <p class="text-gray-900"><?= htmlspecialchars($order['notes']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Items</h2>

                    <div class="space-y-4">
                        <?php foreach ($orderItems as $item): ?>
                        <div class="flex items-center space-x-4 py-4 border-b border-gray-200 last:border-b-0">
                            <img src="<?= $item['image_url'] ? 'assets/products/' . htmlspecialchars($item['image_url']) : 'assets/images/placeholder.png' ?>"
                                alt="<?= htmlspecialchars($item['product_name']) ?>"
                                class="w-16 h-16 object-cover rounded-lg"
                                onerror="this.src='assets/images/placeholder.png'">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?= htmlspecialchars($item['product_name']) ?></h3>
                                <p class="text-gray-600">Quantity: <?= $item['quantity'] ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-gray-900">KSh
                                    <?= number_format($item['subtotal'], 2) ?></p>
                                <p class="text-sm text-gray-600">KSh <?= number_format($item['price'], 2) ?> each</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-blue-50 rounded-2xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">What's Next?</h2>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <div
                                class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3 mt-0.5">
                                1</div>
                            <div>
                                <h3 class="font-medium text-gray-900">Order Confirmation</h3>
                                <p class="text-sm text-gray-600">You'll receive an email confirmation shortly.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div
                                class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3 mt-0.5">
                                2</div>
                            <div>
                                <h3 class="font-medium text-gray-900">Order Processing</h3>
                                <p class="text-sm text-gray-600">We'll prepare your order for shipment.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div
                                class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3 mt-0.5">
                                3</div>
                            <div>
                                <h3 class="font-medium text-gray-900">Delivery</h3>
                                <p class="text-sm text-gray-600">Your order will be delivered within 2-3 business days.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Order Summary</h2>

                    <div class="space-y-4">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>KSh <?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span class="text-green-600">Free</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax</span>
                            <span>KSh 0.00</span>
                        </div>
                        <hr class="border-gray-200">
                        <div class="flex justify-between text-lg font-semibold text-gray-900">
                            <span>Total</span>
                            <span>KSh <?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <a href="orders.php"
                            class="w-full bg-primary-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-primary-600 transition-colors text-center block">
                            <i data-feather="package" class="w-4 h-4 mr-2 inline"></i>
                            View All Orders
                        </a>
                        <a href="shop.php"
                            class="w-full border border-gray-300 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-50 transition-colors text-center block">
                            <i data-feather="shopping-bag" class="w-4 h-4 mr-2 inline"></i>
                            Continue Shopping
                        </a>
                    </div>

                    <!-- Contact Support -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Need Help?</h3>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-center">
                                <i data-feather="phone" class="w-4 h-4 mr-2"></i>
                                <a href="tel:+254712345678" class="hover:text-primary-600">+254 712 345 678</a>
                            </div>
                            <div class="flex items-center">
                                <i data-feather="mail" class="w-4 h-4 mr-2"></i>
                                <a href="mailto:support@springsstore.com"
                                    class="hover:text-primary-600">support@springsstore.com</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- JavaScript -->
<script>
// Initialize Feather icons
feather.replace();

// Auto-scroll to top
window.scrollTo(0, 0);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>