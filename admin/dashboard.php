<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../includes/db.php"; // adjust path if needed

// Get counts
$productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$salesSum = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
$customersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

// Get recent orders
$sql = "SELECT id, customer_name, total_amount, order_status, created_at 
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 10";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Admin Dashboard</title>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main content -->
    <main class="flex-1 p-6 md:ml-64 transition-all duration-300">
        <!-- Mobile toggle button -->
        <!-- <div class="md:hidden mb-4">
            <button id="sidebarToggle"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                ☰ Menu
            </button>
        </div> -->

        <h1 class="text-2xl font-bold mb-6 text-gray-800">Dashboard Overview</h1>

        <!-- Stats grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow p-6 text-center">
                <h3 class="text-gray-500">Products</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo $productsCount ?></p>
            </div>
            <div class="bg-white rounded-xl shadow p-6 text-center">
                <h3 class="text-gray-500">Orders</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo $ordersCount ?></p>
            </div>
            <div class="bg-white rounded-xl shadow p-6 text-center">
                <h3 class="text-gray-500">Sales</h3>
                <p class="text-3xl font-bold text-purple-600">KSh <?php echo number_format($salesSum,2) ?></p>
            </div>
            <div class="bg-white rounded-xl shadow p-6 text-center">
                <h3 class="text-gray-500">Customers</h3>
                <p class="text-3xl font-bold text-orange-600"><?php echo $customersCount ?></p>
            </div>
        </div>

        <!-- Recent orders -->
        <div class="mt-10">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Recent Orders</h2>
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="p-3">Order #</th>
                            <th class="p-3">Customer</th>
                            <th class="p-3">Total</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $o): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3"><?php echo $o['id'] ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($o['customer_name']) ?></td>
                            <td class="p-3">KSh <?php echo number_format($o['total_amount'],2) ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 text-xs rounded 
                                    <?php echo $o['order_status'] == 'completed' ? 'bg-green-100 text-green-700' : 
                                               ($o['order_status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                               'bg-red-100 text-red-700'); ?>">
                                    <?php echo ucfirst($o['order_status']) ?>
                                </span>
                            </td>
                            <td class="p-3"><?php echo date("M d, Y H:i", strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500">No recent orders found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    // Sidebar toggle for mobile
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
    </script>
</body>

</html>