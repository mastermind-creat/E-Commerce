<?php
// admin/orders.php - Enhanced Order Management
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once 'auth.php';

// Set page title
$pageTitle = 'Order Management';

$error = "";
$success = "";

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int) $_POST['order_id'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    
    try {
    $stmt = $pdo->prepare("SELECT COALESCE(order_status, status) as current_status FROM orders WHERE id=?");
    $stmt->execute([$order_id]);
    $current_status = $stmt->fetchColumn();

        if (!$current_status) {
            $error = "Order not found.";
        } elseif (in_array(strtolower($current_status), ['completed', 'cancelled'])) {
            $error = "Order #$order_id is already $current_status. Changes not allowed.";
        } else {
            // Update both `order_status` and legacy `status` columns so public pages reflect the change
            if (strtolower($status) === 'completed') {
                $stmt = $pdo->prepare("UPDATE orders SET order_status=?, status=?, payment_status='paid', updated_at=NOW() WHERE id=?");
                $stmt->execute([$status, $status, $order_id]);
                $success = "Order #$order_id marked as completed and payment confirmed.";
            } elseif (strtolower($status) === 'cancelled') {
                $stmt = $pdo->prepare("UPDATE orders SET order_status=?, status=?, payment_status='failed', updated_at=NOW() WHERE id=?");
                $stmt->execute([$status, $status, $order_id]);
                $success = "Order #$order_id has been cancelled.";
            } else {
                $stmt = $pdo->prepare("UPDATE orders SET order_status=?, status=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$status, $status, $order_id]);
                $success = "Order #$order_id status updated to " . ucfirst($status) . ".";
            }

            // Log status change
            if ($notes) {
                $logStmt = $pdo->prepare("INSERT INTO order_notes (order_id, note, created_at) VALUES (?, ?, NOW())");
                $logStmt->execute([$order_id, $notes]);
            }
            
            // Refresh the page to show updated status
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
            exit;
        }
    } catch (Exception $e) {
        $error = "Failed to update order: " . $e->getMessage();
    }
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($filter !== 'all') {
    $whereConditions[] = "o.order_status = :filter";
    $params[':filter'] = $filter;
}

if (!empty($search)) {
    $whereConditions[] = "(o.id LIKE :search OR o.customer_name LIKE :search OR o.customer_email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) FROM orders o $whereClause";
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Get orders with pagination
$offset = ($page - 1) * $perPage;
$ordersQuery = "
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    $whereClause 
    ORDER BY o.created_at DESC 
    LIMIT :limit OFFSET :offset
";

$ordersStmt = $pdo->prepare($ordersQuery);
foreach ($params as $key => $value) {
    $ordersStmt->bindValue($key, $value);
}
$ordersStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$ordersStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$ordersStmt->execute();
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
        SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue
    FROM orders
";
$stats = $pdo->query($statsQuery)->fetch(PDO::FETCH_ASSOC) ?: [];
// Normalize nulls to 0 to avoid deprecated warnings in number_format
$stats = array_merge([
    'total_orders' => 0,
    'pending_orders' => 0,
    'confirmed_orders' => 0,
    'shipped_orders' => 0,
    'completed_orders' => 0,
    'cancelled_orders' => 0,
    'total_revenue' => 0,
], array_map(function($v){ return $v === null ? 0 : $v; }, $stats));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Springs Store Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
    .hidden {
        display: none;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <!-- Main Content -->
    <main class="flex-1 p-6 lg:ml-64">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Order Management</h1>
            <p class="text-gray-600 mt-2">Track and manage customer orders</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
            <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
            <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_orders']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i data-feather="shopping-cart" class="w-6 h-6 text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending Orders</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= number_format($stats['pending_orders']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i data-feather="clock" class="w-6 h-6 text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Completed Orders</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($stats['completed_orders']) ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i data-feather="check-circle" class="w-6 h-6 text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">KSh <?= number_format($stats['total_revenue'], 0) ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i data-feather="dollar-sign" class="w-6 h-6 text-green-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                    <select name="filter"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Orders</option>
                        <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="shipped" <?= $filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="completed" <?= $filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Order ID, customer name, email..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                        class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors">
                        <i data-feather="search" class="w-4 h-4 mr-2 inline"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">#<?= $order['id'] ?></div>
                                    <div class="text-sm text-gray-500"><?= $order['item_count'] ?> item(s)</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($order['customer_name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_email']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_phone']) ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">KSh
                                <?= number_format((float)($order['total_amount'] ?? 0), 2) ?></td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $order['order_status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($order['order_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($order['order_status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) ?>">
                                    <?= ucfirst($order['order_status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                       ($order['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                    <?= ucfirst($order['payment_status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewOrder(<?= $order['id'] ?>)"
                                        class="text-blue-600 hover:text-blue-900">
                                        <i data-feather="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button
                                        onclick="updateOrderStatus(<?= $order['id'] ?>, '<?= $order['order_status'] ?>')"
                                        class="text-blue-600 hover:text-blue-900">
                                        <i data-feather="edit" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="flex items-center space-x-2">
                <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i data-feather="chevron-left" class="w-4 h-4"></i>
                </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                    class="px-3 py-2 text-sm font-medium rounded-lg <?= $i === $page ? 'bg-blue-500 text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i data-feather="chevron-right" class="w-4 h-4"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

        <!-- Summary -->
        <div class="mt-6 text-center text-sm text-gray-500">
            Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalOrders) ?> of <?= $totalOrders ?> orders
        </div>
    </main>

    <!-- Order Details Modal -->
    <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Order Details</h3>
                    <button onclick="closeOrderModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-feather="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            <div id="orderDetails" class="p-6">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Update Order Status</h3>
            </div>
            <form method="POST" id="statusForm">
                <div class="p-6 space-y-4">
                    <input type="hidden" name="order_id" id="statusOrderId">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                        <select name="status" id="statusSelect"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea name="notes" rows="3" placeholder="Add a note about this status change..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                    <button type="button" onclick="closeStatusModal()"
                        class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
    function viewOrder(orderId) {
        // Simple implementation - in a real app, you'd fetch order details from the server
        const orderDetails = document.getElementById('orderDetails');
        orderDetails.innerHTML = `
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Order Information</h4>
                        <p class="mt-1 text-sm text-gray-900">Order #${orderId}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Customer Information</h4>
                        <p class="mt-1 text-sm text-gray-900">Loading...</p>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Order Items</h4>
                    <p class="mt-1 text-sm text-gray-900">Loading items...</p>
                </div>
            </div>
        `;
        document.getElementById('orderModal').classList.remove('hidden');
        feather.replace();
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.add('hidden');
    }

    function updateOrderStatus(orderId, currentStatus) {
        document.getElementById('statusOrderId').value = orderId;
        document.getElementById('statusSelect').value = currentStatus;
        document.getElementById('statusModal').classList.remove('hidden');
    }

    function closeStatusModal() {
        document.getElementById('statusModal').classList.add('hidden');
    }

    // Close modals on background click
    document.getElementById('orderModal').addEventListener('click', function(e) {
        if (e.target === this) closeOrderModal();
    });

    document.getElementById('statusModal').addEventListener('click', function(e) {
        if (e.target === this) closeStatusModal();
    });

    // Initialize Feather icons
    feather.replace();
    </script>
</body>

</html>