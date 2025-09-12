<?php
require_once __DIR__ . '/../includes/db.php';
require_once 'auth.php';


$error = "";
$perPage = 10; // Orders per page
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $perPage;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int) $_POST['order_id'];
    $status   = $_POST['status'];

    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id=?");
    $stmt->execute([$order_id]);
    $current_status = $stmt->fetchColumn();

    if (!$current_status) {
        $error = "Order not found.";
    } elseif (in_array($current_status, ['Completed', 'Cancelled'])) {
        $error = "Order #$order_id is already $current_status. Changes not allowed.";
    } else {
        if ($status === 'Completed') {
            $stmt = $pdo->prepare("UPDATE orders SET status=?, payment_status='Paid' WHERE id=?");
            $stmt->execute([$status, $order_id]);
        } elseif ($status === 'Cancelled') {
            $stmt = $pdo->prepare("UPDATE orders SET status=?, payment_status='Failed' WHERE id=?");
            $stmt->execute([$status, $order_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
            $stmt->execute([$status, $order_id]);
        }
    }
}

// Fetch Active Orders with filter and pagination
$activeWhere = "status NOT IN ('Completed','Cancelled')";
$activeParams = [];
$activeBindings = [];
if ($filter !== 'All') {
    $activeWhere .= " AND status = :filter";
    $activeBindings[':filter'] = $filter;
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE $activeWhere");
if (!empty($activeBindings)) {
    $countStmt->execute($activeBindings);
} else {
    $countStmt->execute();
}
$totalActive = $countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE $activeWhere ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
// Bind filter if present
foreach ($activeBindings as $key => $value) {
    $stmt->bindValue($key, $value);
}
// Always bind LIMIT and OFFSET as integers
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$activeOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Active statuses for filter
$activeStatuses = ['All', 'Pending', 'Processing', 'Shipped'];

// Fetch Order History with filter and pagination
$historyWhere = "status IN ('Completed','Cancelled')";
$historyParams = [];
$historyBindings = [];
if ($filter !== 'All') {
    $historyWhere .= " AND status = :filter";
    $historyBindings[':filter'] = $filter;
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE $historyWhere");
if (!empty($historyBindings)) {
    $countStmt->execute($historyBindings);
} else {
    $countStmt->execute();
}
$totalHistory = $countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE $historyWhere ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
// Bind filter if present
foreach ($historyBindings as $key => $value) {
    $stmt->bindValue($key, $value);
}
// Always bind LIMIT and OFFSET as integers
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$historyOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// History statuses for filter
$historyStatuses = ['All', 'Completed', 'Cancelled'];

// If request for order items (AJAX)
if (isset($_GET['fetch_items']) && isset($_GET['order_id'])) {
    $stmt = $pdo->prepare("
        SELECT oi.id, oi.quantity, oi.price, oi.subtotal,
               p.name AS product_name,
               v.name AS variant_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN variants v ON oi.variant_id = v.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$_GET['order_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Helper for status colors
function statusBadge($status) {
    $classes = [
        'Pending' => 'bg-yellow-100 text-yellow-800',
        'Processing' => 'bg-blue-100 text-blue-800',
        'Shipped' => 'bg-indigo-100 text-indigo-800',
        'Completed' => 'bg-green-100 text-green-800',
        'Cancelled' => 'bg-red-100 text-red-800'
    ];
    $class = $classes[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class='px-2 py-1 rounded text-xs font-semibold $class'>$status</span>";
}

// Pagination helper
function renderPagination($total, $currentPage, $perPage, $filter, $section) {
    if ($total <= $perPage) return '';
    $totalPages = ceil($total / $perPage);
    $queryString = http_build_query(['filter' => $filter]);
    $html = "<div class='flex justify-center mt-4 space-x-2'>";
    // Previous
    if ($currentPage > 1) {
        $prev = $currentPage - 1;
        $html .= "<a href='?{$queryString}&page=$prev' class='px-3 py-2 bg-gray-300 rounded'>Previous</a>";
    }
    // Page numbers (show 1, current-2 to current+2, last if needed)
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    if ($start > 1) $html .= "<a href='?{$queryString}&page=1' class='px-3 py-2 bg-gray-300 rounded'>1</a>";
    if ($start > 2) $html .= "<span>...</span>";
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? 'bg-blue-600 text-white' : 'bg-gray-300';
        $html .= "<a href='?{$queryString}&page=$i' class='px-3 py-2 $active rounded'>$i</a>";
    }
    if ($end < $totalPages - 1) $html .= "<span>...</span>";
    if ($end < $totalPages) $html .= "<a href='?{$queryString}&page=$totalPages' class='px-3 py-2 bg-gray-300 rounded'>$totalPages</a>";
    // Next
    if ($currentPage < $totalPages) {
        $next = $currentPage + 1;
        $html .= "<a href='?{$queryString}&page=$next' class='px-3 py-2 bg-gray-300 rounded'>Next</a>";
    }
    $html .= "</div>";
    $showingFrom = ($currentPage - 1) * $perPage + 1;
    $showingTo = min($currentPage * $perPage, $total);
    $html = "<p class='text-sm text-gray-600 mb-2'>Showing $showingFrom-$showingTo of $total $section orders</p>" . $html;
    return $html;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- JQUERY -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main -->
    <main class="flex-1 p-2 sm:p-6 md:ml-64">
        <h1 class="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6">Order Management</h1>

        <?php if ($error): ?>
        <div class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Active Orders -->
        <div class="mb-6 sm:mb-10">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 sm:mb-4">
                <h2 class="text-xl sm:text-2xl font-semibold">Active Orders</h2>
                <form method="get" class="flex flex-col sm:flex-row gap-2 mt-2 sm:mt-0">
                    <label class="text-sm font-medium">Filter by Status:</label>
                    <select name="filter" onchange="this.form.submit()"
                        class="border rounded p-2 text-sm w-full sm:w-auto">
                        <?php foreach ($activeStatuses as $status): ?>
                        <option value="<?= $status ?>" <?= $filter === $status ? 'selected' : '' ?>>
                            <?= $status ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="page" value="1">
                </form>
            </div>
            <?php if ($totalActive > 0): ?>
            <div class="bg-white shadow-md rounded-lg">
                <!-- Mobile Cards -->
                <div class="overflow-x-auto md:hidden">
                    <div class="block md:hidden">
                        <?php foreach ($activeOrders as $order): ?>
                        <div class="border border-gray-200 rounded-md p-3 mb-3 bg-gray-50">
                            <div class="block md:table-row-group">
                                <div class="block md:table-row mb-2 md:mb-0">
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Order
                                            #</span>
                                        <span class="text-sm font-semibold"><?= $order['id'] ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Customer</span>
                                        <span class="text-sm"><?= htmlspecialchars($order['customer_name']) ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Email</span>
                                        <span class="text-sm"><?= htmlspecialchars($order['customer_email']) ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Shipping
                                            Address</span>
                                        <span class="text-sm"><?= htmlspecialchars($order['shipping_address']) ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Total</span>
                                        <span class="text-sm font-semibold">Ksh
                                            <?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Payment</span>
                                        <span class="text-sm"><?= htmlspecialchars($order['payment_method']) ?>
                                            (<?= htmlspecialchars($order['payment_status']) ?>)</span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Status</span>
                                        <form method="post" class="inline-block md:inline">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="status" onchange="this.form.submit()"
                                                class="border rounded p-1 text-sm md:text-xs">
                                                <option value="Pending"
                                                    <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending
                                                </option>
                                                <option value="Processing"
                                                    <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>
                                                    Processing</option>
                                                <option value="Shipped"
                                                    <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped
                                                </option>
                                                <option value="Completed">Completed</option>
                                                <option value="Cancelled">Cancelled</option>
                                            </select>
                                        </form>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Date</span>
                                        <span class="text-sm"><?= $order['created_at'] ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Actions</span>
                                        <button onclick="viewItems(<?= $order['id'] ?>)"
                                            class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">View
                                            Items</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Desktop Table -->
                <table class="hidden md:table w-full text-xs sm:text-sm">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-2 md:p-3">#</th>
                            <th class="p-2 md:p-3">Customer</th>
                            <th class="p-2 md:p-3">Email</th>
                            <th class="p-2 md:p-3">Shipping Address</th>
                            <th class="p-2 md:p-3">Total</th>
                            <th class="p-2 md:p-3">Payment</th>
                            <th class="p-2 md:p-3">Status</th>
                            <th class="p-2 md:p-3">Date</th>
                            <th class="p-2 md:p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeOrders as $order): ?>
                        <tr class="border-t">
                            <td class="p-2 md:p-3"><?= $order['id'] ?></td>
                            <td class="p-2 md:p-3"><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td class="p-2 md:p-3"><?= htmlspecialchars($order['customer_email']) ?></td>
                            <td class="p-2 md:p-3"><?= htmlspecialchars($order['shipping_address']) ?></td>
                            <td class="p-2 md:p-3 font-semibold">Ksh <?= number_format($order['total_amount'], 2) ?>
                            </td>
                            <td class="p-2 md:p-3"><?= htmlspecialchars($order['payment_method']) ?>
                                (<?= htmlspecialchars($order['payment_status']) ?>)</td>
                            <td class="p-2 md:p-3">
                                <form method="post" class="inline">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" class="border rounded p-1">
                                        <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>
                                            Pending</option>
                                        <option value="Processing"
                                            <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>Processing
                                        </option>
                                        <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>
                                            Shipped</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td class="p-2 md:p-3"><?= $order['created_at'] ?></td>
                            <td class="p-2 md:p-3">
                                <button onclick="viewItems(<?= $order['id'] ?>)"
                                    class="px-2 py-1 bg-blue-600 text-white rounded">View Items</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= renderPagination($totalActive, $currentPage, $perPage, $filter, 'active') ?>
            <?php else: ?>
            <p class="text-gray-500 text-sm">No active orders.</p>
            <?php endif; ?>
        </div>

        <!-- Order History -->
        <div>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 sm:mb-4">
                <h2 class="text-xl sm:text-2xl font-semibold">Order History</h2>
                <form method="get" class="flex flex-col sm:flex-row gap-2 mt-2 sm:mt-0">
                    <label class="text-sm font-medium">Filter by Status:</label>
                    <select name="filter" onchange="this.form.submit()"
                        class="border rounded p-2 text-sm w-full sm:w-auto">
                        <?php foreach ($historyStatuses as $status): ?>
                        <option value="<?= $status ?>" <?= $filter === $status ? 'selected' : '' ?>>
                            <?= $status ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="page" value="1">
                </form>
            </div>
            <?php if ($totalHistory > 0): ?>
            <div class="bg-white shadow-md rounded-lg">
                <!-- Mobile Cards -->
                <div class="overflow-x-auto md:hidden">
                    <div class="block md:hidden">
                        <?php foreach ($historyOrders as $order): ?>
                        <div class="border border-gray-200 rounded-md p-3 mb-3 bg-gray-50">
                            <div class="block md:table-row-group">
                                <div class="block md:table-row mb-2 md:mb-0">
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Order
                                            #</span>
                                        <span class="text-sm font-semibold"><?= $order['id'] ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Customer</span>
                                        <span class="text-sm"><?= htmlspecialchars($order['customer_name']) ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Email</span>
                                        <span class="text-sm"><?= htmlspecialchars($order['customer_email']) ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Shipping
                                            Address</span>
                                        <span class="text-sm"><?= htmlspecialchars($order['shipping_address']) ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Total</span>
                                        <span class="text-sm font-semibold">Ksh
                                            <?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Payment</span>
                                        <span class="text-sm"><?= htmlspecialchars($order['payment_method']) ?>
                                            (<?= htmlspecialchars($order['payment_status']) ?>)</span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Status</span>
                                        <span class="text-sm"><?= statusBadge($order['status']) ?> <span
                                                class="text-xs text-gray-400">(Locked)</span></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Date</span>
                                        <span class="text-sm"><?= $order['created_at'] ?></span>
                                    </div>
                                    <div class="block md:table-cell p-2 md:p-3 align-top md:align-middle">
                                        <span
                                            class="block md:hidden font-semibold text-xs text-gray-500 mb-1">Actions</span>
                                        <button onclick="viewItems(<?= $order['id'] ?>)"
                                            class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">View
                                            Items</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Desktop Table -->
                <table class="hidden md:table w-full text-xs sm:text-sm">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-2 md:p-3">#</th>
                            <th class="p-2 md:p-3">Customer</th>
                            <th class="p-2 md:p-3">Email</th>
                            <th class="p-2 md:p-3">Shipping Address</th>
                            <th class="p-2 md:p-3">Total</th>
                            <th class="p-2 md:p-3">Payment</th>
                            <th class="p-2 md:p-3">Status</th>
                            <th class="p-2 md:p-3">Date</th>
                            <th class="p-2 md:p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyOrders as $order): ?>
                        <tr class="border-t">
                            <td class="p-2 md:p-3"><?= $order['id'] ?></td>
                            <td class="p-2 md:p-3"><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td class="p-2 md:p-3"><?= htmlspecialchars($order['customer_email']) ?></td>
                            <td class="p-2 md:p-3"><?= htmlspecialchars($order['shipping_address']) ?></td>
                            <td class="p-2 md:p-3 font-semibold">Ksh <?= number_format($order['total_amount'], 2) ?>
                            </td>
                            <td class="p-2 md:p-3"><?= htmlspecialchars($order['payment_method']) ?>
                                (<?= htmlspecialchars($order['payment_status']) ?>)</td>
                            <td class="p-2 md:p-3"><?= statusBadge($order['status']) ?> <span
                                    class="text-xs text-gray-400">(Locked)</span></td>
                            <td class="p-2 md:p-3"><?= $order['created_at'] ?></td>
                            <td class="p-2 md:p-3">
                                <button onclick="viewItems(<?= $order['id'] ?>)"
                                    class="px-2 py-1 bg-blue-600 text-white rounded">View Items</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= renderPagination($totalHistory, $currentPage, $perPage, $filter, 'history') ?>
            <?php else: ?>
            <p class="text-gray-500 text-sm">No completed or cancelled orders yet.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal -->
    <div id="itemsModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-[9999] p-4"
        style="display: none;">
        <div class="bg-white rounded-lg p-4 sm:p-6 max-w-sm sm:max-w-lg w-full shadow-lg">
            <h3 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4">Order Items</h3>
            <div id="itemsContent" class="space-y-2 text-xs sm:text-sm"></div>
            <div class="mt-3 sm:mt-4 flex justify-end">
                <button onclick="closeModal()" class="px-3 sm:px-4 py-2 bg-gray-600 text-white rounded">Close</button>
            </div>
        </div>
    </div>

    <script>
    function viewItems(orderId) {
        fetch(`orders.php?fetch_items=1&order_id=${orderId}`)
            .then(res => res.json())
            .then(items => {
                let html = "";
                if (items.length > 0) {
                    html += `<div class="overflow-x-auto"><table class="w-full text-xs sm:text-sm border">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-1 sm:p-2">Product</th>
                            <th class="p-1 sm:p-2">Variant</th>
                            <th class="p-1 sm:p-2">Qty</th>
                            <th class="p-1 sm:p-2">Price</th>
                            <th class="p-1 sm:p-2">Subtotal</th>
                        </tr>
                    </thead><tbody>`;
                    items.forEach(it => {
                        html += `<tr class="border-t">
                        <td class="p-1 sm:p-2">${it.product_name ?? 'N/A'}</td>
                        <td class="p-1 sm:p-2">${it.variant_name ?? '-'}</td>
                        <td class="p-1 sm:p-2">${it.quantity}</td>
                        <td class="p-1 sm:p-2">Ksh ${parseFloat(it.price).toFixed(2)}</td>
                        <td class="p-1 sm:p-2">Ksh ${parseFloat(it.subtotal).toFixed(2)}</td>
                    </tr>`;
                    });
                    html += "</tbody></table></div>";
                } else {
                    html = "<p class='text-gray-500'>No items found for this order.</p>";
                }
                document.getElementById("itemsContent").innerHTML = html;
                document.getElementById("itemsModal").classList.remove("hidden");
                document.getElementById("itemsModal").classList.add("flex");
            });
    }

    function closeModal() {
        document.getElementById("itemsModal").classList.add("hidden");
        document.getElementById("itemsModal").classList.remove("flex");
    }
    </script>
</body>

</html>