<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Active orders
$activeStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status IN ('Pending','Processing','Shipped') ORDER BY created_at DESC");
$activeStmt->execute([$userId]);
$activeOrders = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

// History orders
$historyStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status IN ('Completed','Cancelled') ORDER BY created_at DESC");
$historyStmt->execute([$userId]);
$historyOrders = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

function renderOrders($orders, $pdo) {
    if (!$orders) {
        echo "<p class='text-center text-gray-500'>No orders found.</p>";
        return;
    }

    foreach ($orders as $order) {
        ?>
<div class="bg-white rounded-xl shadow p-5 mb-6" data-order-id="<?= $order['id'] ?>">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">Order #<?= $order['id'] ?></h2>
        <span
            class="px-3 py-1 rounded-full text-sm flex items-center gap-1
                    <?= $order['status'] == 'Completed' ? 'bg-green-100 text-green-700' : 
                       ($order['status'] == 'Shipped' ? 'bg-blue-100 text-blue-700' : 
                       ($order['status'] == 'Cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')) ?>">
            <?= $order['status'] ?>
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

    <!-- Tracking Steps -->
    <?php if ($order['status'] !== 'Cancelled'): 
                $steps = ['Pending','Processing','Shipped','Completed'];
                $currentStep = array_search($order['status'], $steps);
            ?>
    <div class="flex items-center mt-4 order-tracker">
        <?php foreach ($steps as $index => $step): ?>
        <div class="flex items-center">
            <div class="w-8 h-8 flex items-center justify-center rounded-full 
                        <?= $index <= $currentStep ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500' ?>">
                <?= $index + 1 ?>
            </div>
            <span
                class="ml-2 mr-4 text-sm <?= $index <= $currentStep ? 'text-blue-600 font-medium' : 'text-gray-500' ?>">
                <?= $step ?>
            </span>
            <?php if ($index < count($steps) - 1): ?>
            <div class="w-10 h-1 <?= $index < $currentStep ? 'bg-blue-600' : 'bg-gray-300' ?>"></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="mt-4 text-red-600 font-semibold">❌ This order was cancelled</div>
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
            <?php if ($order['status'] == 'Completed'): ?>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-50 text-gray-800">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-5xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">📦 My Orders</h1>

        <!-- Active Orders -->
        <h2 class="text-xl font-semibold mb-4">🟡 Active Orders</h2>
        <div id="activeOrders">
            <?php renderOrders($activeOrders, $pdo); ?>
        </div>

        <!-- Order History -->
        <h2 class="text-xl font-semibold mt-10 mb-4">📜 Order History</h2>
        <div id="historyOrders">
            <?php renderOrders($historyOrders, $pdo); ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
    setInterval(function() {
        $.get("refresh_orders.php?type=active", function(data) {
            $("#activeOrders").html(data);
        });
        $.get("refresh_orders.php?type=history", function(data) {
            $("#historyOrders").html(data);
        });
    }, 10000);
    </script>
</body>

</html>