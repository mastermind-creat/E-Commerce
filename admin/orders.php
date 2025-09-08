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

    header("Location: orders.php");
    exit;
}

// Fetch orders with customer info
$stmt = $pdo->query("
    SELECT o.*, u.name, u.email 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">

    <?php include 'sidebar.php'; ?>

    <div class="p-6 ml-64">
        <h1 class="text-2xl font-bold text-gray-700 mb-6">Orders</h1>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="p-3">Order ID</th>
                        <th class="p-3">Customer</th>
                        <th class="p-3">Total</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders): ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3">#<?= $order['id'] ?></td>
                        <td class="p-3">
                            <?= htmlspecialchars($order['username']) ?><br>
                            <span class="text-sm text-gray-500"><?= htmlspecialchars($order['email']) ?></span>
                        </td>
                        <td class="p-3">Ksh <?= number_format($order['total_amount'], 2) ?></td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-white 
                  <?php if ($order['status'] === 'Pending') echo 'bg-yellow-500';
                        elseif ($order['status'] === 'Processing') echo 'bg-blue-500';
                        elseif ($order['status'] === 'Shipped') echo 'bg-indigo-500';
                        elseif ($order['status'] === 'Completed') echo 'bg-green-600';
                        elseif ($order['status'] === 'Cancelled') echo 'bg-red-600';
                  ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td class="p-3"><?= date("d M Y", strtotime($order['created_at'])) ?></td>
                        <td class="p-3">
                            <form method="POST" class="flex items-center gap-2">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" class="border rounded p-1">
                                    <option value="Pending" <?= $order['status']=='Pending'?'selected':'' ?>>Pending
                                    </option>
                                    <option value="Processing" <?= $order['status']=='Processing'?'selected':'' ?>>
                                        Processing</option>
                                    <option value="Shipped" <?= $order['status']=='Shipped'?'selected':'' ?>>Shipped
                                    </option>
                                    <option value="Completed" <?= $order['status']=='Completed'?'selected':'' ?>>
                                        Completed</option>
                                    <option value="Cancelled" <?= $order['status']=='Cancelled'?'selected':'' ?>>
                                        Cancelled</option>
                                </select>
                                <button type="submit"
                                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
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