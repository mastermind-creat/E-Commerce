<?php
// public/returns.php - Returns and Refunds Management
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=returns.php');
    exit;
}

$pageTitle = 'Returns & Refunds';
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_return') {
        $orderId = $_POST['order_id'] ?? null;
        $reason = $_POST['reason'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $returnItems = $_POST['return_items'] ?? [];
        
        if (!$orderId || !$reason || empty($returnItems)) {
            $error = 'Please fill in all required fields and select items to return.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Generate return number
                $returnNumber = 'RET' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Create return record
                $stmt = $pdo->prepare("
                    INSERT INTO returns (order_id, user_id, return_number, reason, description) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$orderId, $userId, $returnNumber, $reason, $description]);
                $returnId = $pdo->lastInsertId();
                
                // Add return items
                foreach ($returnItems as $itemId => $itemData) {
                    if (isset($itemData['selected']) && $itemData['selected'] === 'on') {
                        $quantity = (int)$itemData['quantity'];
                        $itemReason = $itemData['reason'] ?? '';
                        $condition = $itemData['condition'] ?? 'used';
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO return_items (return_id, order_item_id, product_id, quantity, reason, condition) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$returnId, $itemId, $itemData['product_id'], $quantity, $itemReason, $condition]);
                    }
                }
                
                $pdo->commit();
                $success = 'Return request submitted successfully! Return number: ' . $returnNumber;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error creating return request: ' . $e->getMessage();
            }
        }
    }
}

// Get user's orders that are eligible for returns (delivered within last 30 days)
try {
    $stmt = $pdo->prepare("
        SELECT o.*, oi.id as order_item_id, oi.product_id, oi.quantity, oi.price, oi.product_name,
               p.name as product_name_full, p.image_url,
               COALESCE(
                   (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
                   (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.id LIMIT 1)
               ) AS product_image
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ? 
        AND o.status = 'delivered' 
        AND o.delivered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND o.id NOT IN (SELECT order_id FROM returns WHERE user_id = ?)
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    $eligibleOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by order
    $ordersGrouped = [];
    foreach ($eligibleOrders as $item) {
        $orderId = $item['id'];
        if (!isset($ordersGrouped[$orderId])) {
            $ordersGrouped[$orderId] = [
                'order' => $item,
                'items' => []
            ];
        }
        $ordersGrouped[$orderId]['items'][] = $item;
    }
} catch (Exception $e) {
    $ordersGrouped = [];
}

// Get user's return requests
try {
    $stmt = $pdo->prepare("
        SELECT r.*, o.order_number, COUNT(ri.id) as item_count
        FROM returns r
        JOIN orders o ON r.order_id = o.id
        LEFT JOIN return_items ri ON r.id = ri.return_id
        WHERE r.user_id = ?
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$userId]);
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $returns = [];
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }
    
    .status-badge {
        position: relative;
        overflow: hidden;
    }
    
    .status-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }
    
    .status-badge:hover::before {
        left: 100%;
    }
    
    .return-item {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .return-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
</style>

<main class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-xl">
                        <i data-feather="rotate-ccw" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Returns & Refunds</h1>
                        <p class="text-lg text-gray-600 mt-2">Manage your return requests and refunds</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl flex items-center space-x-2">
            <i data-feather="check-circle" class="w-5 h-5"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-2xl flex items-center space-x-2">
            <i data-feather="alert-circle" class="w-5 h-5"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Return Policy -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i data-feather="info" class="w-6 h-6 text-primary-600 mr-3"></i>
                Return Policy
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex items-start space-x-4 p-4 bg-blue-50 rounded-xl">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="clock" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">30-Day Window</h3>
                        <p class="text-sm text-gray-600">Returns accepted within 30 days of delivery</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-green-50 rounded-xl">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="package" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Original Packaging</h3>
                        <p class="text-sm text-gray-600">Items must be in original condition and packaging</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-purple-50 rounded-xl">
                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="credit-card" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Full Refund</h3>
                        <p class="text-sm text-gray-600">100% refund for eligible returns</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create New Return -->
        <?php if (!empty($ordersGrouped)): ?>
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i data-feather="plus-circle" class="w-6 h-6 text-primary-600 mr-3"></i>
                Create Return Request
            </h2>
            
            <div class="space-y-6">
                <?php foreach ($ordersGrouped as $orderId => $orderData): ?>
                <div class="border border-gray-200 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Order #<?= htmlspecialchars($orderData['order']['order_number']) ?></h3>
                            <p class="text-sm text-gray-600">
                                Delivered on <?= date('M j, Y', strtotime($orderData['order']['delivered_at'])) ?>
                            </p>
                        </div>
                        <button onclick="toggleReturnForm(<?= $orderId ?>)" 
                                class="bg-primary-500 text-white px-4 py-2 rounded-xl font-semibold hover:bg-primary-600 transition-colors duration-200">
                            Return Items
                        </button>
                    </div>
                    
                    <div id="returnForm-<?= $orderId ?>" class="hidden">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="create_return">
                            <input type="hidden" name="order_id" value="<?= $orderId ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Return Reason *</label>
                                    <select name="reason" required
                                            class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                                        <option value="">Select a reason</option>
                                        <option value="defective">Defective/Damaged</option>
                                        <option value="wrong_item">Wrong Item</option>
                                        <option value="not_as_described">Not as Described</option>
                                        <option value="changed_mind">Changed Mind</option>
                                        <option value="damaged_shipping">Damaged in Shipping</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Additional Details</label>
                                    <textarea name="description" rows="3" placeholder="Please provide more details about your return..."
                                              class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900 resize-none"></textarea>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-4">Select Items to Return</label>
                                <div class="space-y-4">
                                    <?php foreach ($orderData['items'] as $item): ?>
                                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl">
                                        <input type="checkbox" name="return_items[<?= $item['order_item_id'] ?>][selected]" 
                                               class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                        <input type="hidden" name="return_items[<?= $item['order_item_id'] ?>][product_id]" value="<?= $item['product_id'] ?>">
                                        
                                        <img src="<?= product_image_url($item['product_image']) ?>" 
                                             alt="<?= htmlspecialchars($item['product_name']) ?>"
                                             class="w-16 h-16 object-cover rounded-lg">
                                        
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($item['product_name']) ?></h4>
                                            <p class="text-sm text-gray-600">Quantity: <?= $item['quantity'] ?></p>
                                            <p class="text-sm text-gray-600">Price: KSh <?= number_format($item['price'], 2) ?></p>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Return Qty</label>
                                                <input type="number" name="return_items[<?= $item['order_item_id'] ?>][quantity]" 
                                                       min="1" max="<?= $item['quantity'] ?>" value="1"
                                                       class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Condition</label>
                                                <select name="return_items[<?= $item['order_item_id'] ?>][condition]"
                                                        class="w-24 px-2 py-1 border border-gray-300 rounded text-sm">
                                                    <option value="new">New</option>
                                                    <option value="used">Used</option>
                                                    <option value="damaged">Damaged</option>
                                                    <option value="defective">Defective</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="flex space-x-4">
                                <button type="button" onclick="toggleReturnForm(<?= $orderId ?>)" 
                                        class="flex-1 bg-gray-100 text-gray-700 py-3 px-6 rounded-2xl font-semibold hover:bg-gray-200 transition-colors duration-200">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="flex-1 bg-gradient-to-r from-primary-500 to-pink-600 text-white py-3 px-6 rounded-2xl font-semibold hover:from-primary-600 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl">
                                    Submit Return Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Return Requests History -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i data-feather="history" class="w-6 h-6 text-primary-600 mr-3"></i>
                Return Requests
            </h2>
            
            <?php if (empty($returns)): ?>
            <div class="text-center py-12">
                <i data-feather="package" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No return requests</h3>
                <p class="text-gray-600">You haven't submitted any return requests yet.</p>
            </div>
            <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($returns as $return): ?>
                <div class="return-item bg-white/60 backdrop-blur-sm rounded-2xl p-6 border border-white/30">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Return #<?= htmlspecialchars($return['return_number']) ?></h3>
                            <p class="text-sm text-gray-600">Order #<?= htmlspecialchars($return['order_number']) ?></p>
                            <p class="text-sm text-gray-500">
                                Submitted on <?= date('M j, Y g:i A', strtotime($return['created_at'])) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="status-badge inline-block px-4 py-2 rounded-full text-sm font-semibold
                                <?= $return['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                    ($return['status'] === 'approved' ? 'bg-blue-100 text-blue-800' : 
                                    ($return['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                    ($return['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-gray-100 text-gray-800'))) ?>">
                                <?= ucfirst($return['status']) ?>
                            </div>
                            <div class="text-sm text-gray-500 mt-1"><?= $return['item_count'] ?> item(s)</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Reason</h4>
                            <p class="text-sm text-gray-600 capitalize"><?= str_replace('_', ' ', $return['reason']) ?></p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Description</h4>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($return['description']) ?></p>
                        </div>
                    </div>
                    
                    <?php if ($return['admin_notes']): ?>
                    <div class="mt-4 p-4 bg-blue-50 rounded-xl">
                        <h4 class="font-semibold text-blue-900 mb-2">Admin Notes</h4>
                        <p class="text-sm text-blue-800"><?= htmlspecialchars($return['admin_notes']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function toggleReturnForm(orderId) {
    const form = document.getElementById(`returnForm-${orderId}`);
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        form.classList.add('hidden');
    }
}

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
