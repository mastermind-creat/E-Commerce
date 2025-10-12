<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Active orders (use COALESCE to support either `order_status` or legacy `status`)
$activeStmt = $pdo->prepare(
    "SELECT o.*, COALESCE(o.order_status, o.status) AS display_status
     FROM orders o
     WHERE o.user_id = ? AND COALESCE(o.order_status, o.status) IN ('Pending','confirmed','Processing','Shipped')
     ORDER BY o.created_at DESC"
);
$activeStmt->execute([$userId]);
$activeOrders = $activeStmt->fetchAll(PDO::FETCH_ASSOC);
// Normalize display_status casing
foreach ($activeOrders as &$a) {
    if (isset($a['display_status'])) $a['display_status'] = ucfirst(strtolower($a['display_status']));
}
unset($a);

// History orders
$historyStmt = $pdo->prepare(
    "SELECT o.*, COALESCE(o.order_status, o.status) AS display_status
     FROM orders o
     WHERE o.user_id = ? AND COALESCE(o.order_status, o.status) IN ('Completed','Cancelled')
     ORDER BY o.created_at DESC"
);
$historyStmt->execute([$userId]);
$historyOrders = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($historyOrders as &$h) {
    if (isset($h['display_status'])) $h['display_status'] = ucfirst(strtolower($h['display_status']));
}
unset($h);

function renderOrders($orders, $pdo) {
    if (!$orders) {
        echo "<div class='text-center py-12'>
                <div class='w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center'>
                    <i data-feather='package' class='w-12 h-12 text-gray-400'></i>
                </div>
                <p class='text-gray-500 text-lg'>No orders found</p>
                <p class='text-gray-400 text-sm mt-2'>Your orders will appear here once you make a purchase</p>
              </div>";
        return;
    }

    foreach ($orders as $order) {
        ?>
<div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20 p-6 mb-6 order-card hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]" data-order-id="<?= $order['id'] ?>">
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                <i data-feather="package" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Order #<?= $order['id'] ?></h2>
                <p class="text-gray-500 text-sm">Placed on <?= date("M d, Y", strtotime($order['created_at'])) ?></p>
            </div>
        </div>
        <span
            class="order-status-badge px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 shadow-lg
                    <?= $order['display_status'] == 'Completed' ? 'bg-green-100/80 text-green-700 border border-green-200' : 
                       ($order['display_status'] == 'Shipped' ? 'bg-blue-100/80 text-blue-700 border border-blue-200' : 
                       ($order['display_status'] == 'Processing' ? 'bg-purple-100/80 text-purple-700 border border-purple-200' :
                       ($order['display_status'] == 'Cancelled' ? 'bg-red-100/80 text-red-700 border border-red-200' : 'bg-yellow-100/80 text-yellow-700 border border-yellow-200'))) ?>">
            <div class="w-2 h-2 rounded-full <?= $order['display_status'] == 'Completed' ? 'bg-green-500' : 
                       ($order['display_status'] == 'Shipped' ? 'bg-blue-500' : 
                       ($order['display_status'] == 'Processing' ? 'bg-purple-500' :
                       ($order['display_status'] == 'Cancelled' ? 'bg-red-500' : 'bg-yellow-500'))) ?>"></div>
            <?= $order['display_status'] ?>
        </span>
    </div>

    <?php
            $totalStmt = $pdo->prepare("SELECT SUM(quantity * price) as total FROM order_items WHERE order_id = ?");
            $totalStmt->execute([$order['id']]);
            $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);
            $orderTotal = $totalRow['total'] ?? 0;
            ?>
    <div class="bg-gradient-to-r from-primary-50 to-pink-50 rounded-xl p-4 mb-6 border border-primary-100">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i data-feather="dollar-sign" class="w-5 h-5 text-primary-600"></i>
                <span class="text-gray-600 font-medium">Total Amount</span>
            </div>
            <span class="text-2xl font-bold text-primary-700">KSh <?= number_format($orderTotal, 2) ?></span>
        </div>
    </div>

    <!-- Modern Tracking Steps -->
    <?php if ($order['display_status'] !== 'Cancelled'): 
                $steps = ['Pending', 'Processing', 'Shipped', 'Completed'];
                $currentStep = array_search($order['display_status'], $steps);
                $currentStep = $currentStep !== false ? $currentStep : 0;
            ?>
    <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 mb-6 border border-white/30 shadow-lg">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <i data-feather="truck" class="w-5 h-5 mr-2 text-primary-600"></i>
            Order Progress
        </h3>
        <div class="flex items-center justify-between relative order-tracker" data-current-step="<?= $currentStep ?>">
            <?php foreach ($steps as $index => $step): 
                $isCompleted = $index < $currentStep;
                $isCurrent = $index === $currentStep;
            ?>
            <div class="flex flex-col items-center relative z-10 step" data-step-index="<?= $index ?>">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center step-circle shadow-lg <?= $isCompleted ? 'bg-gradient-to-br from-green-500 to-green-600 text-white' : ($isCurrent ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white border-2 border-blue-300' : 'bg-white/80 text-gray-400 border-2 border-gray-200') ?> transition-all duration-300 hover:scale-110">
                    <?php if ($isCompleted): ?>
                    <i data-feather="check" class="w-6 h-6"></i>
                    <?php else: ?>
                    <span class="font-bold text-sm"><?= $index + 1 ?></span>
                    <?php endif; ?>
                </div>
                <span class="mt-3 text-sm font-semibold text-center step-label <?= $isCompleted || $isCurrent ? 'text-blue-700' : 'text-gray-500' ?>">
                    <?= $step ?>
                </span>
                <?php if ($isCurrent): ?>
                <span class="mt-1 text-xs text-blue-600 font-bold animate-pulse bg-blue-100 px-2 py-1 rounded-full">Current</span>
                <?php endif; ?>
            </div>

            <?php if ($index < count($steps) - 1): ?>
            <div class="flex-1 mx-3 h-2 progress-bar-bg relative overflow-hidden rounded-full bg-gray-200">
                <div class="absolute top-0 left-0 h-full progress-bar-fill bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-500 ease-in-out" style="width: <?= $index < $currentStep ? '100%' : ($index === $currentStep ? '50%' : '0%') ?>"></div>
            </div>
            <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-red-50/80 backdrop-blur-sm rounded-2xl p-4 mb-6 border border-red-200 shadow-lg">
        <div class="flex items-center text-red-700 font-semibold">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center mr-3">
                <i data-feather="x" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-lg">Order Cancelled</div>
                <div class="text-sm text-red-600">This order was cancelled and will not be processed</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Items -->
    <?php
            $itemsStmt = $pdo->prepare("
                SELECT oi.*, p.name, pi.image_url
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id
                WHERE oi.order_id = ?
                GROUP BY oi.id
            ");
            $itemsStmt->execute([$order['id']]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
    <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 border border-white/30 shadow-lg">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <i data-feather="shopping-bag" class="w-5 h-5 mr-2 text-primary-600"></i>
            Order Items
        </h3>
        <div class="space-y-4">
            <?php foreach ($items as $item): 
                        $thumb = $item['image_url'] ? "assets/products/".$item['image_url'] : "assets/images/placeholder.png";
                        
                        // Check if user has already reviewed this product
                        $hasReviewed = false;
                        if ($order['display_status'] == 'Completed') {
                            $reviewCheckStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
                            $reviewCheckStmt->execute([$_SESSION['user_id'], $item['product_id']]);
                            $hasReviewed = $reviewCheckStmt->fetch() !== false;
                        }
                    ?>
            <div class="flex items-center gap-4 p-4 bg-white/80 rounded-xl border border-white/50 hover:shadow-md transition-all duration-300">
                <div class="relative">
                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                        class="w-20 h-20 object-cover rounded-xl shadow-lg">
                    <div class="absolute -top-2 -right-2 bg-primary-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
                        <?= $item['quantity'] ?>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 text-lg"><?= htmlspecialchars($item['name']) ?></h3>
                    <p class="text-gray-600 text-sm">Quantity: <?= $item['quantity'] ?></p>
                    <p class="text-primary-600 font-bold text-lg">KSh <?= number_format($item['price'], 2) ?></p>
                </div>
                <?php if ($order['display_status'] == 'Completed'): ?>
                <div class="flex flex-col items-end space-y-2">
                    <?php if ($hasReviewed): ?>
                    <div class="flex items-center space-x-2 px-3 py-2 bg-green-100 text-green-700 rounded-xl text-sm font-medium">
                        <i data-feather="check-circle" class="w-4 h-4"></i>
                        <span>Reviewed</span>
                    </div>
                    <a href="reviews.php" class="text-blue-600 text-sm hover:text-blue-800 underline">View Review</a>
                    <?php else: ?>
                    <a href="review.php?product_id=<?= $item['product_id'] ?>&order_id=<?= $order['id'] ?>"
                        class="group flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                        <i data-feather="star" class="w-4 h-4 group-hover:scale-110 transition-transform duration-300"></i>
                        <span class="font-medium">Leave Review</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    .order-card {
        transition: all 0.3s ease;
    }

    .status-update {
        animation: pulse 1.5s ease-in-out;
    }

    @keyframes pulse {
        0% {
            background-color: rgba(255, 255, 255, 0.8);
            transform: scale(1);
        }

        50% {
            background-color: rgba(240, 249, 255, 0.9);
            transform: scale(1.02);
        }

        100% {
            background-color: rgba(255, 255, 255, 0.8);
            transform: scale(1);
        }
    }

    .progress-bar {
        transition: width 0.5s ease-in-out;
    }

    /* Glassmorphism enhancements */
    .glass-card {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }

    .glass-button {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }

    .glass-button:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.5);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.7);
    }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100">
        <div class="max-w-6xl mx-auto p-6">
            
            <!-- Error Messages -->
            <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl">
                <div class="flex items-center">
                    <i data-feather="alert-circle" class="w-6 h-6 text-red-600 mr-3"></i>
                    <div>
                        <h3 class="text-red-800 font-semibold">
                            <?php if ($_GET['error'] === 'no_completed_order'): ?>
                                Review Not Available
                            <?php elseif ($_GET['error'] === 'invalid_product'): ?>
                                Invalid Product
                            <?php else: ?>
                                Error
                            <?php endif; ?>
                        </h3>
                        <p class="text-red-600 text-sm mt-1">
                            <?php if ($_GET['error'] === 'no_completed_order'): ?>
                                You can only review products from completed orders. Please complete your order first.
                            <?php elseif ($_GET['error'] === 'invalid_product'): ?>
                                The product you're trying to review is invalid or doesn't exist.
                            <?php else: ?>
                                An error occurred while processing your request.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <!-- Page Header -->
            <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-xl">
                            <i data-feather="package" class="w-8 h-8 text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-gray-900">My Orders</h1>
                            <p class="text-gray-600 text-lg">Track and manage your orders</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-primary-600"><?= count($activeOrders) + count($historyOrders) ?></div>
                        <div class="text-gray-500">Total Orders</div>
                    </div>
                </div>
            </div>

            <!-- Active Orders -->
            <div class="mb-12">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg mr-4">
                        <i data-feather="clock" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Active Orders</h2>
                        <p class="text-gray-600">Orders currently being processed</p>
                    </div>
                    <div class="ml-auto">
                        <span class="px-4 py-2 bg-gradient-to-r from-yellow-100 to-orange-100 text-yellow-800 text-sm font-bold rounded-full border border-yellow-200">
                            <?= count($activeOrders) ?> Active
                        </span>
                    </div>
                </div>
                <div id="activeOrders">
                    <?php renderOrders($activeOrders, $pdo); ?>
                </div>
            </div>

            <!-- Order History -->
            <div class="mb-8">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center shadow-lg mr-4">
                        <i data-feather="archive" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Order History</h2>
                        <p class="text-gray-600">Completed and cancelled orders</p>
                    </div>
                    <div class="ml-auto">
                        <span class="px-4 py-2 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 text-sm font-bold rounded-full border border-gray-300">
                            <?= count($historyOrders) ?> Total
                        </span>
                    </div>
                </div>
                <div id="historyOrders">
                    <?php renderOrders($historyOrders, $pdo); ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Real-time order updates -->
    <script>
    // Function to update order status automatically
    function checkOrderUpdates() {
        const activeOrders = document.querySelectorAll('#activeOrders .order-card');

        if (activeOrders.length === 0) return;

        // Get all active order IDs
        const orderIds = Array.from(activeOrders).map(card => card.dataset.orderId);

        // Make AJAX request to check for updates
        fetch('../includes/check_order_updates.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    orderIds: orderIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.updates && data.updates.length > 0) {
                    data.updates.forEach(update => {
                        const orderCard = document.querySelector(
                            `.order-card[data-order-id="${update.order_id}"]`);
                        if (orderCard) {
                            // Add animation class to highlight the update
                            orderCard.classList.add('status-update');

                            // Update the status badge
                            const statusBadge = orderCard.querySelector('.order-status-badge');
                            if (statusBadge) {
                                statusBadge.textContent = update.new_status;

                                // Update badge color based on status
                                statusBadge.className = `order-status-badge px-3 py-1 rounded-full text-sm flex items-center gap-1 ${
                                    update.new_status == 'Completed' ? 'bg-green-100 text-green-700' : 
                                    update.new_status == 'Shipped' ? 'bg-blue-100 text-blue-700' : 
                                    update.new_status == 'Processing' ? 'bg-purple-100 text-purple-700' : 
                                    'bg-yellow-100 text-yellow-700'
                                }`;
                            }

                            // Update the tracking steps if needed
                            if (update.new_status !== 'Cancelled') {
                                const steps = ['Pending', 'Processing', 'Shipped', 'Completed'];
                                const currentStep = steps.indexOf(update.new_status);

                                if (currentStep >= 0) {
                                    // Update progress bars
                                    const progressBars = orderCard.querySelectorAll('.bg-blue-600');
                                    progressBars.forEach((bar, index) => {
                                        if (index < currentStep) {
                                            bar.style.width = '100%';
                                        } else if (index === currentStep) {
                                            bar.style.width = '50%';
                                        }
                                    });

                                    // Update step indicators
                                    const stepIndicators = orderCard.querySelectorAll(
                                        '.flex-col.items-center');
                                    stepIndicators.forEach((indicator, index) => {
                                        const circle = indicator.querySelector('.rounded-full');
                                        const label = indicator.querySelector('.text-xs');
                                        const currentLabel = indicator.querySelector(
                                            '.text-blue-600');

                                        if (index < currentStep) {
                                            // Completed step
                                            circle.className =
                                                'w-10 h-10 rounded-full flex items-center justify-center bg-blue-600 text-white transition-all duration-300';
                                            circle.innerHTML =
                                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                                            if (label) label.className =
                                                'mt-2 text-xs font-medium text-center text-blue-600';
                                            if (currentLabel) currentLabel.remove();
                                        } else if (index === currentStep) {
                                            // Current step
                                            circle.className =
                                                'w-10 h-10 rounded-full flex items-center justify-center bg-blue-100 border-2 border-blue-600 text-blue-600 transition-all duration-300';
                                            circle.innerHTML = '<span class="font-semibold">' + (
                                                index + 1) + '</span>';
                                            if (label) label.className =
                                                'mt-2 text-xs font-medium text-center text-blue-600';

                                            // Add current label if not exists
                                            if (!currentLabel) {
                                                const currentSpan = document.createElement('span');
                                                currentSpan.className =
                                                    'mt-1 text-xs text-blue-600 font-medium animate-pulse';
                                                currentSpan.textContent = 'Current';
                                                indicator.appendChild(currentSpan);
                                            }
                                        } else {
                                            // Future step
                                            circle.className =
                                                'w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 text-gray-400 transition-all duration-300';
                                            circle.innerHTML = '<span class="font-semibold">' + (
                                                index + 1) + '</span>';
                                            if (label) label.className =
                                                'mt-2 text-xs font-medium text-center text-gray-500';
                                            if (currentLabel) currentLabel.remove();
                                        }
                                    });
                                }
                            }

                            // Remove animation class after animation completes
                            setTimeout(() => {
                                orderCard.classList.remove('status-update');
                            }, 1500);

                            // If order is completed or cancelled, move it to history after a delay
                            if (update.new_status === 'Completed' || update.new_status === 'Cancelled') {
                                setTimeout(() => {
                                    const historySection = document.getElementById('historyOrders');
                                    if (historySection) {
                                        historySection.prepend(orderCard);

                                        // Update counts
                                        const activeCount = document.querySelector('#activeOrders')
                                            .querySelectorAll('.order-card').length;
                                        const historyCount = document.querySelector(
                                                '#historyOrders').querySelectorAll('.order-card')
                                            .length;

                                        document.querySelector('#activeOrders + .flex .text-xs')
                                            .textContent = activeCount;
                                        document.querySelector('#historyOrders + .flex .text-xs')
                                            .textContent = historyCount;
                                    }
                                }, 2000);
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Error checking order updates:', error));
    }

    // Check for updates every 30 seconds
    setInterval(checkOrderUpdates, 30000);

    // Also check when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Initial check after 2 seconds
        setTimeout(checkOrderUpdates, 2000);
    });
    </script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script>feather.replace();</script>
</body>

</html>