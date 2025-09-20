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
     WHERE o.user_id = ? AND COALESCE(o.order_status, o.status) IN ('Pending','Processing','Shipped')
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
        echo "<p class='text-center text-gray-500'>No orders found.</p>";
        return;
    }

    foreach ($orders as $order) {
        ?>
<div class="bg-white rounded-xl shadow p-5 mb-6 order-card" data-order-id="<?= $order['id'] ?>">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">Order #<?= $order['id'] ?></h2>
        <span
            class="order-status-badge px-3 py-1 rounded-full text-sm flex items-center gap-1
                    <?= $order['display_status'] == 'Completed' ? 'bg-green-100 text-green-700' : 
                       ($order['display_status'] == 'Shipped' ? 'bg-blue-100 text-blue-700' : 
                       ($order['display_status'] == 'Processing' ? 'bg-purple-100 text-purple-700' :
                       ($order['display_status'] == 'Cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'))) ?>">
            <?= $order['display_status'] ?>
        </span>
    </div>
    <p class="text-gray-600 text-sm mt-1">Placed on <?= date("M d, Y", strtotime($order['created_at'])) ?></p>

    <?php
            $totalStmt = $pdo->prepare("SELECT SUM(quantity * price) as total FROM order_items WHERE order_id = ?");
            $totalStmt->execute([$order['id']]);
            $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);
            $orderTotal = $totalRow['total'] ?? 0;
            ?>
    <p class="mt-2 font-bold text-blue-600">Total: KSh <?= number_format($orderTotal, 2) ?></p>

    <!-- Modern Tracking Steps -->
    <?php if ($order['display_status'] !== 'Cancelled'): 
                $steps = ['Pending', 'Processing', 'Shipped', 'Completed'];
                $currentStep = array_search($order['display_status'], $steps);
                $currentStep = $currentStep !== false ? $currentStep : 0;
            ?>
    <div class="mt-6 mb-4">
        <div class="flex items-center justify-between relative order-tracker" data-current-step="<?= $currentStep ?>">
            <?php foreach ($steps as $index => $step): 
                $isCompleted = $index < $currentStep;
                $isCurrent = $index === $currentStep;
            ?>
            <div class="flex flex-col items-center relative z-10 step" data-step-index="<?= $index ?>">
                <div class="w-10 h-10 rounded-full flex items-center justify-center step-circle <?= $isCompleted ? 'bg-blue-600 text-white' : ($isCurrent ? 'bg-blue-100 border-2 border-blue-600 text-blue-600' : 'bg-gray-100 text-gray-400') ?> transition-all duration-300">
                    <?php if ($isCompleted): ?>
                    <i data-feather="check" class="w-5 h-5"></i>
                    <?php else: ?>
                    <span class="font-semibold"><?= $index + 1 ?></span>
                    <?php endif; ?>
                </div>
                <span class="mt-2 text-xs font-medium text-center step-label <?= $isCompleted || $isCurrent ? 'text-blue-600' : 'text-gray-500' ?>">
                    <?= $step ?>
                </span>
                <?php if ($isCurrent): ?>
                <span class="mt-1 text-xs text-blue-600 font-medium animate-pulse">Current</span>
                <?php endif; ?>
            </div>

            <?php if ($index < count($steps) - 1): ?>
            <div class="flex-1 mx-2 h-1 progress-bar-bg relative overflow-hidden">
                <div class="absolute top-0 left-0 h-full progress-bar-fill bg-blue-600 transition-all duration-500 ease-in-out" style="width: <?= $index < $currentStep ? '100%' : ($index === $currentStep ? '50%' : '0%') ?>"></div>
            </div>
            <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="mt-4 p-3 bg-red-50 rounded-lg text-red-700 font-medium flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        This order was cancelled
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
    <div class="mt-4 border-t pt-4 space-y-3">
        <?php foreach ($items as $item): 
                    $thumb = $item['image_url'] ? "assets/products/".$item['image_url'] : "assets/images/placeholder.png";
                ?>
        <div class="flex items-center gap-4">
            <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                class="w-16 h-16 object-cover rounded">
            <div>
                <h3 class="font-medium"><?= htmlspecialchars($item['name']) ?></h3>
                <p class="text-gray-500 text-sm">Qty: <?= $item['quantity'] ?> | KSh
                    <?= number_format($item['price'],2) ?></p>
            </div>
            <?php if ($order['display_status'] == 'Completed'): ?>
            <a href="review.php?product_id=<?= $item['product_id'] ?>"
                class="ml-auto text-blue-600 text-sm underline hover:text-blue-800">Leave a Review</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
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
            background-color: #fff;
        }

        50% {
            background-color: #f0f9ff;
        }

        100% {
            background-color: #fff;
        }
    }

    .progress-bar {
        transition: width 0.5s ease-in-out;
    }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-5xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6"><i data-feather="package" class="inline w-6 h-6 mr-2"></i>My Orders</h1>

        <!-- Active Orders -->
        <div class="flex items-center mb-4">
            <h2 class="text-xl font-semibold"><i data-feather="clock" class="inline w-5 h-5 mr-2 text-yellow-500"></i>Active Orders</h2>
            <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                <?= count($activeOrders) ?>
            </span>
        </div>
        <div id="activeOrders">
            <?php renderOrders($activeOrders, $pdo); ?>
        </div>

        <!-- Order History -->
        <div class="flex items-center mt-10 mb-4">
            <h2 class="text-xl font-semibold"><i data-feather="archive" class="inline w-5 h-5 mr-2 text-gray-600"></i>Order History</h2>
            <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">
                <?= count($historyOrders) ?>
            </span>
        </div>
        <div id="historyOrders">
            <?php renderOrders($historyOrders, $pdo); ?>
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