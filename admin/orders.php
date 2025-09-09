<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once 'auth.php';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int) $_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$status, $order_id]);

    header("Location: orders.php?status=" . ($_GET['status'] ?? 'all'));
    exit;
}

// Handle filter
$filter_status = $_GET['status'] ?? 'all';
$where_clause = '';
$params = [];
if ($filter_status !== 'all') {
    $where_clause = 'WHERE o.status = ?';
    $params[] = $filter_status;
}

// Fetch orders with customer info
$stmt = $pdo->prepare("
    SELECT o.*, u.name, u.email 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    $where_clause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Possible statuses for filter dropdown
$statuses = ['all', 'Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">

    <?php include 'sidebar.php'; ?>

    <div class="p-4 sm:p-6 md:p-6 ml-0 md:ml-64">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-700">Orders</h1>

            <!-- Filter Dropdown -->
            <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                <label for="status" class="text-gray-700 font-medium text-sm sm:text-base">Filter:</label>
                <select name="status" id="status" class="border rounded p-1 flex-1 sm:flex-none">
                    <?php foreach ($statuses as $status): ?>
                    <option value="<?= $status ?>" <?= $filter_status == $status ? 'selected' : '' ?>>
                        <?= ucfirst($status) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"
                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-sm sm:text-base">
                    Apply
                </button>
            </form>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md overflow-x-auto">
            <table class="w-full min-w-[600px] border-collapse text-sm sm:text-base">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="p-2 sm:p-3">Order ID</th>
                        <th class="p-2 sm:p-3">Customer</th>
                        <th class="p-2 sm:p-3">Total</th>
                        <th class="p-2 sm:p-3">Status</th>
                        <th class="p-2 sm:p-3">Date</th>
                        <th class="p-2 sm:p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders): ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-1 sm:p-3">#<?= $order['id'] ?></td>
                        <td class="p-1 sm:p-3">
                            <?= htmlspecialchars($order['name'] ?? 'Guest') ?><br>
                            <span
                                class="text-xs sm:text-sm text-gray-500"><?= htmlspecialchars($order['email'] ?? 'N/A') ?></span>
                        </td>
                        <td class="p-1 sm:p-3">Ksh <?= number_format($order['total_amount'], 2) ?></td>
                        <td class="p-1 sm:p-3">
                            <span class="px-2 py-1 rounded text-white text-xs sm:text-sm
                                        <?= $order['status'] === 'Pending' ? 'bg-yellow-500' : 
                                           ($order['status'] === 'Processing' ? 'bg-blue-500' :
                                           ($order['status'] === 'Shipped' ? 'bg-indigo-500' :
                                           ($order['status'] === 'Completed' ? 'bg-green-600' : 
                                           ($order['status'] === 'Cancelled' ? 'bg-red-600' : 'bg-gray-400')))) ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td class="p-1 sm:p-3"><?= date("d M Y", strtotime($order['created_at'])) ?></td>
                        <td class="p-1 sm:p-3">
                            <form method="POST" class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" class="border rounded p-1 text-xs sm:text-sm flex-1 sm:flex-none">
                                    <?php foreach (array_slice($statuses, 1) as $statusOption): ?>
                                    <option value="<?= $statusOption ?>"
                                        <?= $order['status'] == $statusOption ? 'selected' : '' ?>>
                                        <?= $statusOption ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit"
                                    class="bg-blue-600 text-white px-2 sm:px-3 py-1 rounded hover:bg-blue-700 text-xs sm:text-sm">
                                    Update
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="p-3 text-center text-gray-500">No orders found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>