<?php
// public/cart.php - Enhanced Shopping Cart
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

// Set page title
$pageTitle = 'Shopping Cart';

// Get cart items
$cart = $_SESSION['cart'] ?? [];
$total = 0;
$cartItems = [];

// Process cart items and calculate totals
foreach ($cart as $productId => $item) {
    try {
        // Get current product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Use the price from cart (which should be the discounted price)
            $lineTotal = $item['price'] * $item['quantity'];
            $total += $lineTotal;
            
            // Get product image
            $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1");
            $imgStmt->execute([$productId]);
            $imageUrl = $imgStmt->fetchColumn();
            
            $cartItems[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $item['quantity'],
                'line_total' => $lineTotal,
                'image' => $imageUrl ? 'assets/products/' . $imageUrl : 'assets/images/placeholder.png',
                'stock' => $product['stock']
            ];
        } else {
            // Remove invalid product from cart
            unset($_SESSION['cart'][$productId]);
        }
    } catch (Exception $e) {
        // Remove invalid product from cart
        unset($_SESSION['cart'][$productId]);
    }
}

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $productId = intval($_POST['product_id']);
    $action = $_POST['action'];
    
    if ($action === 'update' && isset($_POST['quantity'])) {
        $quantity = max(1, intval($_POST['quantity']));
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        }
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);
    }
    
    header('Location: cart.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Shopping Cart</h1>
            <p class="text-gray-600 mt-2">Review your items before checkout</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Notification Toast -->
        <div id="cartNotification" class="fixed top-4 right-4 z-50 hidden">
            <div class="bg-white rounded-lg shadow-lg border-l-4 border-primary-500 p-4 max-w-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i data-feather="check-circle" class="w-5 h-5 text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900" id="notificationMessage">Cart updated!</p>
                    </div>
                    <button onclick="hideNotification()" class="ml-auto text-gray-400 hover:text-gray-600">
                        <i data-feather="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($cartItems)): ?>
        <!-- Empty Cart -->
        <div class="text-center py-16">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-feather="shopping-cart" class="w-12 h-12 text-gray-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Your cart is empty</h2>
            <p class="text-gray-600 mb-8">Looks like you haven't added any items to your cart yet.</p>
            <a href="shop.php"
                class="inline-flex items-center px-6 py-3 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-colors">
                <i data-feather="shopping-bag" class="w-5 h-5 mr-2"></i>
                Start Shopping
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 cart-items-count">Cart Items (<?= count($cartItems) ?>)</h2>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="p-6 cart-item transition-all duration-300" data-product-id="<?= $item['id'] ?>">
                            <div class="flex items-center space-x-4">
                                <!-- Product Image -->
                                <div class="flex-shrink-0">
                                    <img src="<?= htmlspecialchars($item['image']) ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>"
                                        class="w-20 h-20 object-cover rounded-lg"
                                        onerror="this.src='assets/images/placeholder.png'">
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                        <?= htmlspecialchars($item['name']) ?></h3>
                                    <?php if (isset($item['discount_applied']) && $item['discount_applied']): ?>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-green-600 font-semibold">KSh <?= number_format($item['price'], 2) ?> each</span>
                                        <span class="text-gray-500 line-through text-sm">KSh <?= number_format($item['original_price'], 2) ?></span>
                                        <span class="text-red-600 text-sm font-medium">Save KSh <?= number_format($item['discount_amount'], 2) ?></span>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-gray-600">KSh <?= number_format($item['price'], 2) ?> each</p>
                                    <?php endif; ?>

                                    <!-- Stock Warning -->
                                    <?php if ($item['stock'] < $item['quantity']): ?>
                                    <p class="text-orange-600 text-sm mt-1">
                                        <i data-feather="alert-triangle" class="w-4 h-4 inline mr-1"></i>
                                        Only <?= $item['stock'] ?> available in stock
                                    </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Quantity Controls -->
                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                        <button type="button" class="quantity-btn p-2 hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                            data-action="decrease" data-product="<?= $item['id'] ?>"
                                            <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                            <i data-feather="minus" class="w-4 h-4"></i>
                                        </button>
                                        <div class="relative">
                                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1"
                                                max="<?= $item['stock'] ?>"
                                                class="w-16 text-center border-0 focus:ring-0 quantity-input bg-transparent"
                                                data-product="<?= $item['id'] ?>">
                                            <div class="quantity-spinner hidden absolute inset-0 flex items-center justify-center bg-white">
                                                <div class="animate-spin rounded-full h-4 w-4 border-2 border-primary-500 border-t-transparent"></div>
                                            </div>
                                        </div>
                                        <button type="button" class="quantity-btn p-2 hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                            data-action="increase" data-product="<?= $item['id'] ?>"
                                            <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>
                                            <i data-feather="plus" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-gray-900">KSh
                                        <?= number_format($item['line_total'], 2) ?></div>
                                    <div class="text-sm text-gray-500"><?= $item['quantity'] ?> × KSh
                                        <?= number_format($item['price'], 2) ?></div>
                                </div>

                                <!-- Remove Button -->
                                <div class="flex-shrink-0">
                                    <button type="button" 
                                            class="remove-item text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition-all duration-200 hover:scale-110"
                                            data-product="<?= $item['id'] ?>"
                                            title="Remove from cart">
                                        <i data-feather="trash-2" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Continue Shopping -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <a href="shop.php"
                        class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium">
                        <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
                        Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Order Summary</h2>

                    <div class="space-y-4">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal (<?= count($cartItems) ?> items)</span>
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

                    <div class="mt-6 space-y-3">
                        <a href="checkout.php"
                            class="w-full bg-primary-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-primary-600 transition-colors text-center block">
                            Proceed to Checkout
                        </a>
                        <button
                            class="w-full border border-gray-300 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-50 transition-colors">
                            Save for Later
                        </button>
                    </div>

                    <!-- Security Badges -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-center space-x-4 text-gray-500">
                            <div class="flex items-center">
                                <i data-feather="shield" class="w-4 h-4 mr-1"></i>
                                <span class="text-xs">Secure Checkout</span>
                            </div>
                            <div class="flex items-center">
                                <i data-feather="truck" class="w-4 h-4 mr-1"></i>
                                <span class="text-xs">Free Shipping</span>
                            </div>
                            <div class="flex items-center">
                                <i data-feather="refresh-cw" class="w-4 h-4 mr-1"></i>
                                <span class="text-xs">Easy Returns</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- JavaScript -->
<script>
// Cart state
let isUpdating = false;

// Quantity controls with animations
document.querySelectorAll('.quantity-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (isUpdating) return;
        
        const action = this.dataset.action;
        const productId = this.dataset.product;
        const input = document.querySelector(`input[data-product="${productId}"]`);
        const currentValue = parseInt(input.value);
        const maxValue = parseInt(input.max);

        if (action === 'increase' && currentValue < maxValue) {
            input.value = currentValue + 1;
            updateQuantity(productId, input.value);
        } else if (action === 'decrease' && currentValue > 1) {
            input.value = currentValue - 1;
            updateQuantity(productId, input.value);
        }
    });
});

// Quantity input change with debouncing
document.querySelectorAll('.quantity-input').forEach(input => {
    let timeout;
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const productId = this.dataset.product;
            const value = Math.max(1, Math.min(parseInt(this.value) || 1, parseInt(this.max)));
            this.value = value;
            updateQuantity(productId, value);
        }, 500);
    });
});

// Remove item with animation
document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        if (isUpdating) return;
        
        const productId = this.dataset.product;
        const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
        
        if (confirm('Remove this item from cart?')) {
            // Add removal animation
            cartItem.style.transform = 'translateX(-100%)';
            cartItem.style.opacity = '0';
            
            setTimeout(() => {
                removeItem(productId);
            }, 300);
        }
    });
});

// Update quantity function with loading state
async function updateQuantity(productId, quantity) {
    if (isUpdating) return;
    
    isUpdating = true;
    const input = document.querySelector(`input[data-product="${productId}"]`);
    const spinner = input.parentElement.querySelector('.quantity-spinner');
    
    // Show loading spinner
    input.style.opacity = '0.5';
    spinner.classList.remove('hidden');
    
    try {
        const response = await fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}&action=update`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update UI without full reload
            updateCartUI(data);
            showNotification('Quantity updated!');
        } else {
            showNotification('Failed to update quantity', 'error');
            // Revert input value
            input.value = input.dataset.originalValue || 1;
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Something went wrong', 'error');
        input.value = input.dataset.originalValue || 1;
    } finally {
        // Hide loading spinner
        input.style.opacity = '1';
        spinner.classList.add('hidden');
        isUpdating = false;
    }
}

// Remove item function
async function removeItem(productId) {
    try {
        const response = await fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&action=remove`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Item removed from cart');
            // Remove from UI
            const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
            cartItem.remove();
            
            // Update cart count and total
            updateCartUI(data);
            
            // Check if cart is empty
            if (data.cartCount === 0) {
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        } else {
            showNotification('Failed to remove item', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Something went wrong', 'error');
    }
}

// Update cart UI
function updateCartUI(data) {
    // Update cart count in header if exists
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = data.cartCount > 99 ? '99+' : data.cartCount;
    }
    
    // Update order summary
    const subtotalElement = document.querySelector('.order-summary .text-gray-600 span:last-child');
    if (subtotalElement) {
        subtotalElement.textContent = `KSh ${parseFloat(data.total).toLocaleString('en-KE', {minimumFractionDigits: 2})}`;
    }
    
    const totalElement = document.querySelector('.order-summary .text-lg.font-semibold span:last-child');
    if (totalElement) {
        totalElement.textContent = `KSh ${parseFloat(data.total).toLocaleString('en-KE', {minimumFractionDigits: 2})}`;
    }
    
    // Update item count
    const itemCountElement = document.querySelector('.cart-items-count');
    if (itemCountElement) {
        itemCountElement.textContent = `Cart Items (${data.cartCount})`;
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.getElementById('cartNotification');
    const messageElement = document.getElementById('notificationMessage');
    const icon = notification.querySelector('i');
    
    messageElement.textContent = message;
    
    if (type === 'error') {
        notification.querySelector('.border-l-4').classList.remove('border-primary-500');
        notification.querySelector('.border-l-4').classList.add('border-red-500');
        icon.classList.remove('text-green-500');
        icon.classList.add('text-red-500');
        icon.setAttribute('data-feather', 'alert-circle');
    } else {
        notification.querySelector('.border-l-4').classList.remove('border-red-500');
        notification.querySelector('.border-l-4').classList.add('border-primary-500');
        icon.classList.remove('text-red-500');
        icon.classList.add('text-green-500');
        icon.setAttribute('data-feather', 'check-circle');
    }
    
    feather.replace();
    notification.classList.remove('hidden');
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        hideNotification();
    }, 3000);
}

// Hide notification
function hideNotification() {
    const notification = document.getElementById('cartNotification');
    notification.classList.add('hidden');
}

// Store original values for input fields
document.querySelectorAll('.quantity-input').forEach(input => {
    input.dataset.originalValue = input.value;
});

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>