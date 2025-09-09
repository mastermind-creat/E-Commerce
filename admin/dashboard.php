<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../includes/db.php";

// Fetch counts
$productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Correct sales sum: only include paid orders
$salesSum = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid'")->fetchColumn();

$customersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

// Get recent orders
$stmt = $pdo->query("
    SELECT o.*, u.name, u.email 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-6 md:ml-64 transition-all duration-300">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Dashboard Overview</h1>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow hover:shadow-xl transition p-6 flex items-center gap-4">
                <i class="fa-solid fa-box text-4xl text-blue-500"></i>
                <div>
                    <h3 class="text-gray-500 text-sm">Products</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $productsCount ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow hover:shadow-xl transition p-6 flex items-center gap-4">
                <i class="fa-solid fa-cart-shopping text-4xl text-green-500"></i>
                <div>
                    <h3 class="text-gray-500 text-sm">Orders</h3>
                    <p class="text-3xl font-bold text-green-600"><?php echo $ordersCount ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow hover:shadow-xl transition p-6 flex items-center gap-4">
                <i class="fa-solid fa-money-bill-trend-up text-4xl text-purple-500"></i>
                <div>
                    <h3 class="text-gray-500 text-sm">Sales</h3>
                    <p class="text-3xl font-bold text-purple-600">KSh <?php echo number_format($salesSum, 2) ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow hover:shadow-xl transition p-6 flex items-center gap-4">
                <i class="fa-solid fa-users text-4xl text-orange-500"></i>
                <div>
                    <h3 class="text-gray-500 text-sm">Customers</h3>
                    <p class="text-3xl font-bold text-orange-600"><?php echo $customersCount ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="mt-10">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Recent Orders</h2>
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="p-3">Order #</th>
                            <th class="p-3">Customer</th>
                            <th class="p-3">Total</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Payment</th>
                            <th class="p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders): ?>
                        <?php foreach($orders as $o): ?>
                        <tr class="border-t hover:bg-gray-50 transition">
                            <td class="p-3 font-medium">#<?php echo $o['id'] ?></td>
                            <td class="p-3">
                                <?php echo htmlspecialchars($o['customer_name'] ?? 'Guest') ?><br>
                                <span
                                    class="text-xs text-gray-500"><?php echo htmlspecialchars($o['customer_email'] ?? 'N/A') ?></span>
                            </td>
                            <td class="p-3 font-semibold text-gray-700">KSh
                                <?php echo number_format($o['total_amount'], 2) ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 text-xs rounded 
                                    <?php 
                                        switch ($o['status']) {
                                            case 'Pending': echo 'bg-yellow-100 text-yellow-700'; break;
                                            case 'Processing': echo 'bg-blue-100 text-blue-700'; break;
                                            case 'Shipped': echo 'bg-indigo-100 text-indigo-700'; break;
                                            case 'Completed': echo 'bg-green-100 text-green-700'; break;
                                            case 'Cancelled': echo 'bg-red-100 text-red-700'; break;
                                            default: echo 'bg-gray-100 text-gray-700';
                                        }
                                    ?>">
                                    <?php echo htmlspecialchars($o['status']) ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <span
                                    class="px-2 py-1 text-xs rounded 
                                    <?php echo $o['payment_status']=='paid'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'; ?>">
                                    <?php echo ucfirst($o['payment_status']) ?>
                                </span>
                            </td>
                            <td class="p-3"><?php echo date("M d, Y H:i", strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500">No recent orders found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>

</html>