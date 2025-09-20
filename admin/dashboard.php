<?php
// admin/dashboard.php - Modern Admin Dashboard
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../includes/db.php";
require_once 'auth.php';

// Set page title
$pageTitle = 'Admin Dashboard';

// Fetch dashboard statistics
try {
    // Basic counts
    $productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $customersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
    $categoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

    // Sales data
    $totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
    $monthlySales = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetchColumn();
    $todaySales = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid' AND DATE(created_at) = CURDATE()")->fetchColumn();

    // Order status distribution
    $orderStatusData = $pdo->query("
        SELECT 
            CASE 
                WHEN order_status = 'pending' THEN 'Pending'
                WHEN order_status = 'confirmed' THEN 'Confirmed'
                WHEN order_status = 'shipped' THEN 'Shipped'
                WHEN order_status = 'completed' THEN 'Completed'
                WHEN order_status = 'cancelled' THEN 'Cancelled'
                ELSE 'Unknown'
            END as status,
            COUNT(*) as count
        FROM orders 
        GROUP BY order_status
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Payment status distribution
    $paymentStatusData = $pdo->query("
        SELECT 
            CASE 
                WHEN payment_status = 'pending' THEN 'Pending'
                WHEN payment_status = 'paid' THEN 'Paid'
                WHEN payment_status = 'failed' THEN 'Failed'
                ELSE 'Unknown'
            END as status,
            COUNT(*) as count
        FROM orders 
        GROUP BY payment_status
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Recent orders
    $recentOrders = $pdo->query("
        SELECT o.*, u.name as customer_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Top selling products
    $topProducts = $pdo->query("
        SELECT p.name, p.price, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as total_revenue
        FROM products p 
        JOIN order_items oi ON p.id = oi.product_id 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.payment_status = 'paid'
        GROUP BY p.id, p.name, p.price 
        ORDER BY total_sold DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Monthly sales data for chart
    $monthlySalesData = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as sales
        FROM orders 
        WHERE payment_status = 'paid' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $productsCount = $ordersCount = $customersCount = $categoriesCount = 0;
    $totalSales = $monthlySales = $todaySales = 0;
    $orderStatusData = $paymentStatusData = $recentOrders = $topProducts = $monthlySalesData = [];
}

// Calculate growth percentages
$lastMonthSales = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetchColumn();
$salesGrowth = $lastMonthSales > 0 ? (($monthlySales - $lastMonthSales) / $lastMonthSales) * 100 : 0;

$lastMonthOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetchColumn();
$currentMonthOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetchColumn();
$ordersGrowth = $lastMonthOrders > 0 ? (($currentMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Springs Store Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card:nth-child(2) {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card:nth-child(3) {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card:nth-child(4) {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
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
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Overview</h1>
            <p class="text-gray-600 mt-2">Welcome back! Here's what's happening with your store.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Total Products</p>
                        <p class="text-3xl font-bold"><?= number_format($productsCount) ?></p>
                        <p class="text-white/80 text-sm mt-1">Active products</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <i data-feather="package" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Total Orders</p>
                        <p class="text-3xl font-bold"><?= number_format($ordersCount) ?></p>
                        <p class="text-white/80 text-sm mt-1">
                            <?= $ordersGrowth >= 0 ? '+' : '' ?><?= number_format($ordersGrowth, 1) ?>% this month
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <i data-feather="shopping-cart" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Total Sales</p>
                        <p class="text-3xl font-bold">KSh <?= number_format($totalSales, 0) ?></p>
                        <p class="text-white/80 text-sm mt-1">
                            <?= $salesGrowth >= 0 ? '+' : '' ?><?= number_format($salesGrowth, 1) ?>% this month
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <i data-feather="dollar-sign" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Customers</p>
                        <p class="text-3xl font-bold"><?= number_format($customersCount) ?></p>
                        <p class="text-white/80 text-sm mt-1">Registered users</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <i data-feather="users" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Sales Chart -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Overview</h3>
                <div class="h-64">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Order Status Chart -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Status Distribution</h3>
                <div class="h-64">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Orders -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Orders</h3>
                    <a href="orders.php" class="text-primary-600 hover:text-primary-700 text-sm font-medium">View
                        all</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-2 font-medium text-gray-600">Order #</th>
                                <th class="text-left py-3 px-2 font-medium text-gray-600">Customer</th>
                                <th class="text-left py-3 px-2 font-medium text-gray-600">Amount</th>
                                <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                                <th class="text-left py-3 px-2 font-medium text-gray-600">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-2 font-medium text-gray-900">#<?= $order['id'] ?></td>
                                <td class="py-3 px-2 text-gray-600">
                                    <?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></td>
                                <td class="py-3 px-2 font-medium text-gray-900">KSh
                                    <?= number_format($order['total_amount'], 2) ?></td>
                                <td class="py-3 px-2">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?= $order['order_status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($order['order_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($order['order_status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-gray-600">
                                    <?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Top Selling Products</h3>

                <div class="space-y-4">
                    <?php foreach ($topProducts as $index => $product): ?>
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center text-sm font-semibold">
                            <?= $index + 1 ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?= htmlspecialchars($product['name']) ?></p>
                            <p class="text-xs text-gray-600"><?= $product['total_sold'] ?> sold</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">KSh
                                <?= number_format($product['total_revenue'], 0) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="add_product.php"
                    class="flex items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <i data-feather="plus" class="w-5 h-5 mr-2 text-primary-600"></i>
                    <span class="text-sm font-medium text-gray-700">Add Product</span>
                </a>
                <a href="categories.php"
                    class="flex items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <i data-feather="folder-plus" class="w-5 h-5 mr-2 text-primary-600"></i>
                    <span class="text-sm font-medium text-gray-700">Add Category</span>
                </a>
                <a href="orders.php"
                    class="flex items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <i data-feather="shopping-cart" class="w-5 h-5 mr-2 text-primary-600"></i>
                    <span class="text-sm font-medium text-gray-700">View Orders</span>
                </a>
                <a href="admin_hero.php"
                    class="flex items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <i data-feather="image" class="w-5 h-5 mr-2 text-primary-600"></i>
                    <span class="text-sm font-medium text-gray-700">Manage Hero</span>
                </a>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesData = <?= json_encode($monthlySalesData) ?>;

    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesData.map(item => item.month),
            datasets: [{
                label: 'Sales (KSh)',
                data: salesData.map(item => item.sales),
                borderColor: 'rgb(236, 72, 153)',
                backgroundColor: 'rgba(236, 72, 153, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'KSh ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Order Status Chart
    const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
    const orderStatusData = <?= json_encode($orderStatusData) ?>;

    new Chart(orderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: orderStatusData.map(item => item.status),
            datasets: [{
                data: orderStatusData.map(item => item.count),
                backgroundColor: [
                    '#fbbf24',
                    '#3b82f6',
                    '#10b981',
                    '#ef4444',
                    '#8b5cf6'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Initialize Feather icons
    feather.replace();
    </script>
</body>

</html>