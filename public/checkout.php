<?php
// public/checkout.php - Enhanced Checkout Process
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

// Set page title
$pageTitle = 'Checkout';

// Initialize $paymentMethod with a default value
$paymentMethod = 'cash';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : null;
$userEmail = $isLoggedIn ? $_SESSION['user_email'] : null;

// Get cart items
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

// Calculate total
$total = 0;
$cartItems = [];
foreach ($cart as $productId => $item) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $lineTotal = $product['price'] * $item['quantity'];
            $total += $lineTotal;
            
            $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1");
            $imgStmt->execute([$productId]);
            $imageUrl = $imgStmt->fetchColumn();
            
            $cartItems[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $item['quantity'],
                'line_total' => $lineTotal,
                'image' => $imageUrl ? 'assets/products/' . $imageUrl : 'assets/images/placeholder.png'
            ];
        }
    } catch (Exception $e) {
        // Handle error
    }
}

// Get user details if logged in
$userDetails = [];
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Handle error
    }
}

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn) {
        header("Location: login.php?redirect=checkout.php");
        exit;
    }
    
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $deliveryPhone = trim($_POST['delivery_phone'] ?? '');
    $paymentMethod = strtolower(trim($_POST['payment_method'] ?? 'cash'));
    $allowedMethods = ['cash','mpesa','paypal'];
    // Ensure $paymentMethod is strictly one of the allowed ENUM values
    if (!in_array($paymentMethod, $allowedMethods, true)) {
        $paymentMethod = 'cash'; // Default to 'cash' if invalid
    }
    $notes = trim($_POST['notes'] ?? '');
    
    // Fallback to user's saved details when missing
    if (empty($shippingAddress)) {
        $shippingAddress = trim($userDetails['default_address'] ?? '');
    }
    if (empty($deliveryPhone)) {
        $deliveryPhone = trim($userDetails['phone'] ?? '');
    }

    if (empty($shippingAddress) || empty($deliveryPhone)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get user details
            $userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $userStmt->execute([$_SESSION['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $customerName = $user['name'] ?? '';
            $customerEmail = $user['email'] ?? '';
            
            // Create order
            $orderStmt = $pdo->prepare("
                INSERT INTO orders 
                (user_id, customer_name, customer_email, customer_phone, shipping_address, 
                 total_amount, payment_method, notes, created_at) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $orderStmt->execute([
                $_SESSION['user_id'], 
                $customerName, 
                $customerEmail, 
                $deliveryPhone, 
                $shippingAddress, 
                $total, 
                $paymentMethod,
                $notes
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Add order items
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    $orderId, 
                    $item['id'], 
                    $item['quantity'], 
                    $item['price'], 
                    $item['line_total']
                ]);
            }
            
            $pdo->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            // Redirect to success page
            header("Location: order_success.php?order_id=" . $orderId);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Checkout failed: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Checkout</h1>
            <p class="text-gray-600 mt-2">Complete your order securely</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <div class="flex items-center">
                <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="lg:col-span-2">
                <form method="POST" class="space-y-8">
                    <!-- Customer Information -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Customer Information</h2>

                        <?php if ($isLoggedIn): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <i data-feather="check-circle" class="w-5 h-5 text-green-600 mr-2"></i>
                                <span class="text-green-800 font-medium">Logged in as
                                    <?= htmlspecialchars($userName) ?></span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i data-feather="alert-triangle" class="w-5 h-5 text-yellow-600 mr-2"></i>
                                    <span class="text-yellow-800">You need to be logged in to checkout</span>
                                </div>
                                <a href="login.php?redirect=checkout.php"
                                    class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                    Login
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" value="<?= htmlspecialchars($userDetails['name'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" value="<?= htmlspecialchars($userDetails['email'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Shipping Information</h2>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Delivery Address <span class="text-red-500">*</span>
                                </label>
                                <textarea name="shipping_address" rows="4" required
                                    placeholder="Enter your complete delivery address including street, city, and postal code"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"><?= htmlspecialchars($userDetails['default_address'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" name="delivery_phone" required
                                    value="<?= htmlspecialchars($userDetails['phone'] ?? '') ?>"
                                    placeholder="Enter your phone number for delivery updates"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order Notes
                                    (Optional)</label>
                                <textarea name="notes" rows="3" placeholder="Any special instructions for your order"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Payment Method</h2>

                        <div class="space-y-4">
                            <label
                                class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="cash"
                                    <?= ($paymentMethod === 'cash') ? 'checked' : '' ?>
                                    class="text-primary-500 focus:ring-primary-500">
                                <div class="ml-4">
                                    <div class="flex items-center">
                                        <i data-feather="credit-card" class="w-5 h-5 mr-2"></i>
                                        <span class="font-medium">Cash on Delivery</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Pay when your order is delivered</p>
                                </div>
                            </label>

                            <label
                                class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="mpesa"
                                    <?= ($paymentMethod === 'mpesa') ? 'checked' : '' ?>
                                    class="text-primary-500 focus:ring-primary-500">
                                <div class="ml-4">
                                    <div class="flex items-center">
                                        <i data-feather="smartphone" class="w-5 h-5 mr-2"></i>
                                        <span class="font-medium">M-Pesa</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Pay via M-Pesa (Coming Soon)</p>
                                </div>
                            </label>

                            <label
                                class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="paypal"
                                    <?= ($paymentMethod === 'paypal') ? 'checked' : '' ?>
                                    class="text-primary-500 focus:ring-primary-500">
                                <div class="ml-4">
                                    <div class="flex items-center">
                                        <i data-feather="paypal" class="w-5 h-5 mr-2"></i>
                                        <span class="font-medium">PayPal</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Pay via PayPal (Coming Soon)</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Place Order Button -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <button type="submit"
                            class="w-full bg-primary-500 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:bg-primary-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                            <?= !$isLoggedIn ? 'disabled' : '' ?>>
                            <i data-feather="lock" class="w-5 h-5 mr-2 inline"></i>
                            Place Order - KSh <?= number_format($total, 2) ?>
                        </button>

                        <p class="text-sm text-gray-600 text-center mt-4">
                            <i data-feather="shield" class="w-4 h-4 inline mr-1"></i>
                            Your payment information is secure and encrypted
                        </p>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Order Summary</h2>

                    <!-- Cart Items -->
                    <div class="space-y-4 mb-6">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="flex items-center space-x-3">
                            <img src="<?= htmlspecialchars($item['image']) ?>"
                                alt="<?= htmlspecialchars($item['name']) ?>" class="w-12 h-12 object-cover rounded-lg"
                                onerror="this.src='assets/images/placeholder.png'">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 truncate">
                                    <?= htmlspecialchars($item['name']) ?></h4>
                                <p class="text-sm text-gray-600">Qty: <?= $item['quantity'] ?></p>
                            </div>
                            <div class="text-sm font-medium text-gray-900">KSh
                                <?= number_format($item['line_total'], 2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Totals -->
                    <div class="space-y-2 border-t pt-4">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>KSh <?= number_format($total, 2) ?></span>
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
                            <span>KSh <?= number_format($total, 2) ?></span>
                        </div>
                    </div>

                    <!-- Security Features -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Why shop with us?</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center">
                                <i data-feather="shield" class="w-4 h-4 text-green-500 mr-2"></i>
                                Secure checkout
                            </li>
                            <li class="flex items-center">
                                <i data-feather="truck" class="w-4 h-4 text-green-500 mr-2"></i>
                                Free delivery
                            </li>
                            <li class="flex items-center">
                                <i data-feather="refresh-cw" class="w-4 h-4 text-green-500 mr-2"></i>
                                Easy returns
                            </li>
                            <li class="flex items-center">
                                <i data-feather="headphones" class="w-4 h-4 text-green-500 mr-2"></i>
                                24/7 support
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- JavaScript -->
<script>
// Form validation
const form = document.querySelector('form');
const submitBtn = form.querySelector('button[type="submit"]');

form.addEventListener('submit', function(e) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('border-red-500');
        } else {
            field.classList.remove('border-red-500');
        }
    });

    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
    }
});

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>