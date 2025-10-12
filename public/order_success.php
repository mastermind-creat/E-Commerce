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
    
    // Get order items with product images
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, 
               COALESCE(pi.image_url, '') as image_url
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        LEFT JOIN product_images pi ON p.id = pi.product_id 
        WHERE oi.order_id = ?
        GROUP BY oi.id, oi.product_id
        ORDER BY oi.id
    ");
    $itemsStmt->execute([$orderId]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100">
    <!-- Enhanced Success Header -->
    <div class="bg-gradient-to-r from-green-500 via-green-600 to-emerald-600 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-8 shadow-2xl">
                <i data-feather="check" class="w-12 h-12"></i>
            </div>
            <h1 class="text-5xl sm:text-6xl font-bold mb-6">Order Confirmed!</h1>
            <p class="text-2xl text-green-100 mb-8">Thank you for your purchase. Your order has been successfully placed.</p>
            <div class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-sm rounded-full text-green-100 font-semibold">
                <i data-feather="package" class="w-5 h-5 mr-2"></i>
                Order #<?= $order['id'] ?> • <?= date('M j, Y', strtotime($order['created_at'])) ?>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Information -->
                <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-3">
                            <i data-feather="info" class="w-5 h-5 text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Order Information</h2>
                    </div>

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
                <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-3">
                            <i data-feather="truck" class="w-5 h-5 text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Shipping Information</h2>
                    </div>

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
                <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-3">
                            <i data-feather="shopping-bag" class="w-5 h-5 text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Order Items</h2>
                    </div>

                    <div class="space-y-4">
                        <?php foreach ($orderItems as $item): 
                            // Get proper image URL
                            $finalImgUrl = 'assets/images/placeholder.png'; // Default fallback
                            
                            if (!empty($item['image_url'])) {
                                // The correct path should be assets/products/ since we're in the public directory
                                $imgPath = 'assets/products/' . $item['image_url'];
                                $fullPath = __DIR__ . '/' . $imgPath;
                                
                                if (file_exists($fullPath)) {
                                    $finalImgUrl = $imgPath;
                                } else {
                                    // Fallback to placeholder if image doesn't exist
                                    $finalImgUrl = 'assets/images/placeholder.png';
                                }
                            }
                        ?>
                        <div class="flex items-center space-x-4 p-4 bg-white/60 backdrop-blur-sm rounded-xl border border-white/50 hover:shadow-lg transition-all duration-300">
                            <div class="relative">
                                <img src="<?= htmlspecialchars($finalImgUrl) ?>"
                                    alt="<?= htmlspecialchars($item['product_name']) ?>"
                                    class="w-20 h-20 object-cover rounded-xl shadow-lg"
                                    onerror="this.src='assets/images/placeholder.png'">
                                <div class="absolute -top-2 -right-2 bg-gradient-to-r from-primary-500 to-pink-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center shadow-lg">
                                    <?= $item['quantity'] ?>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                    <?= htmlspecialchars($item['product_name']) ?></h3>
                                <p class="text-gray-600 text-sm">Quantity: <?= $item['quantity'] ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-primary-600">KSh
                                    <?= number_format($item['subtotal'], 2) ?></p>
                                <p class="text-sm text-gray-500">KSh <?= number_format($item['price'], 2) ?> each</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-3xl p-8 border border-blue-200/50">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-3">
                            <i data-feather="arrow-right" class="w-5 h-5 text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">What's Next?</h2>
                    </div>
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
                <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 sticky top-24">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center mr-3">
                            <i data-feather="receipt" class="w-5 h-5 text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Order Summary</h2>
                    </div>

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

                    <div class="mt-8 space-y-4">
                        <a href="orders.php"
                            class="w-full bg-gradient-to-r from-primary-500 to-pink-600 text-white py-4 px-6 rounded-xl font-bold hover:from-primary-600 hover:to-pink-700 transition-all duration-300 text-center block shadow-lg hover:shadow-xl transform hover:scale-105">
                            <i data-feather="package" class="w-5 h-5 mr-2 inline"></i>
                            View All Orders
                        </a>
                        <a href="shop.php"
                            class="w-full border-2 border-gray-300 text-gray-700 py-4 px-6 rounded-xl font-bold hover:bg-gray-50 hover:border-gray-400 transition-all duration-300 text-center block shadow-lg hover:shadow-xl transform hover:scale-105">
                            <i data-feather="shopping-bag" class="w-5 h-5 mr-2 inline"></i>
                            Continue Shopping
                        </a>
                    </div>

                    <!-- Contact Support -->
                    <div class="mt-8 pt-6 border-t border-gray-200/50">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-3">
                                <i data-feather="help-circle" class="w-4 h-4 text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Need Help?</h3>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center p-3 bg-white/60 backdrop-blur-sm rounded-xl border border-white/50">
                                <i data-feather="phone" class="w-4 h-4 mr-3 text-primary-600"></i>
                                <a href="tel:+254712345678" class="hover:text-primary-600 font-medium">+254 712 345 678</a>
                            </div>
                            <div class="flex items-center p-3 bg-white/60 backdrop-blur-sm rounded-xl border border-white/50">
                                <i data-feather="mail" class="w-4 h-4 mr-3 text-primary-600"></i>
                                <a href="mailto:support@springsstore.com"
                                    class="hover:text-primary-600 font-medium">support@springsstore.com</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Custom CSS -->
<style>
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.animate-slide-in {
    animation: slideInUp 0.6s ease-out;
}

.animate-pulse-slow {
    animation: pulse 2s infinite;
}

.glass-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(45deg, #5a67d8, #6b46c1);
}
</style>

<!-- JavaScript -->
<script>
// Initialize Feather icons
feather.replace();

// Auto-scroll to top
window.scrollTo(0, 0);

// Add animation classes to elements
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.bg-white\\/80, .bg-gradient-to-br');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-slide-in');
    });
});

// Add hover effects
document.querySelectorAll('.hover-lift').forEach(element => {
    element.addEventListener('mouseenter', function() {
        this.style.transition = 'all 0.3s ease';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>