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

// Chart Data: Sales by Status (bar chart)
$statusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$statusData = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR); // status => count
$statuses = array_keys($statusData);
$statusCounts = array_values($statusData);

// Chart Data: Payment Status (pie chart)
$paymentStmt = $pdo->query("SELECT payment_status, COUNT(*) as count FROM orders GROUP BY payment_status");
$paymentData = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
$paymentLabels = [];
$paymentCounts = [];
foreach ($paymentData as $row) {
    $paymentLabels[] = ucfirst(str_replace('_', ' ', $row['payment_status']));
    $paymentCounts[] = (int)$row['count'];
}

// Pagination for Recent Orders
$perPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $perPage;

// Total orders count for pagination
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Fetch recent orders with pagination
$stmt = $pdo->prepare("
    SELECT o.*, u.name, u.email 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination helper function
function renderPagination($total, $currentPage, $perPage) {
    if ($total <= $perPage) return '';
    $totalPages = ceil($total / $perPage);
    $html = "<div class='flex justify-center mt-4 space-x-2'>";
    // Previous
    if ($currentPage > 1) {
        $prev = $currentPage - 1;
        $html .= "<a href='?page=$prev' class='px-3 py-2 bg-gray-300 rounded hover:bg-gray-400 text-sm'>Previous</a>";
    }
    // Page numbers (simple: show up to 5 around current)
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    if ($start > 1) $html .= "<a href='?page=1' class='px-3 py-2 bg-gray-300 rounded hover:bg-gray-400 text-sm'>1</a>";
    if ($start > 2) $html .= "<span class='px-2 py-2 text-gray-500'>...</span>";
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? 'bg-blue-600 text-white' : 'bg-gray-300';
        $html .= "<a href='?page=$i' class='px-3 py-2 $active rounded text-sm'>$i</a>";
    }
    if ($end < $totalPages - 1) $html .= "<span class='px-2 py-2 text-gray-500'>...</span>";
    if ($end < $totalPages) $html .= "<a href='?page=$totalPages' class='px-3 py-2 bg-gray-300 rounded hover:bg-gray-400 text-sm'>$totalPages</a>";
    // Next
    if ($currentPage < $totalPages) {
        $next = $currentPage + 1;
        $html .= "<a href='?page=$next' class='px-3 py-2 bg-gray-300 rounded hover:bg-gray-400 text-sm'>Next</a>";
    }
    $html .= "</div>";
    $showingFrom = ($currentPage - 1) * $perPage + 1;
    $showingTo = min($currentPage * $perPage, $total);
    $html = "<p class='text-xs sm:text-sm text-gray-600 mb-2 text-center'>Showing $showingFrom-$showingTo of $total orders</p>" . $html;
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-100 min-h-screen flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-3 sm:p-6 md:ml-64 transition-all duration-300">
        <h1 class="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6 text-gray-800">Dashboard Overview</h1>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-6">
            <div
                class="bg-white rounded-xl shadow hover:shadow-xl transition-all duration-300 p-3 sm:p-6 flex items-center gap-2 sm:gap-4">
                <i class="fa-solid fa-box text-xl sm:text-4xl text-blue-500 flex-shrink-0"></i>
                <div class="min-w-0">
                    <h3 class="text-gray-500 text-xs sm:text-sm truncate">Products</h3>
                    <p class="text-lg sm:text-3xl font-bold text-blue-600 m-0"><?php echo $productsCount ?></p>
                </div>
            </div>

            <div
                class="bg-white rounded-xl shadow hover:shadow-xl transition-all duration-300 p-3 sm:p-6 flex items-center gap-2 sm:gap-4">
                <i class="fa-solid fa-cart-shopping text-xl sm:text-4xl text-green-500 flex-shrink-0"></i>
                <div class="min-w-0">
                    <h3 class="text-gray-500 text-xs sm:text-sm truncate">Orders</h3>
                    <p class="text-lg sm:text-3xl font-bold text-green-600 m-0"><?php echo $ordersCount ?></p>
                </div>
            </div>

            <div
                class="bg-white rounded-xl shadow hover:shadow-xl transition-all duration-300 p-3 sm:p-6 flex items-center gap-2 sm:gap-4">
                <i class="fa-solid fa-money-bill-trend-up text-xl sm:text-4xl text-purple-500 flex-shrink-0"></i>
                <div class="min-w-0">
                    <h3 class="text-gray-500 text-xs sm:text-sm truncate">Sales</h3>
                    <p class="text-lg sm:text-3xl font-bold text-purple-600 m-0">KSh
                        <?php echo number_format($salesSum, 2) ?></p>
                </div>
            </div>

            <div
                class="bg-white rounded-xl shadow hover:shadow-xl transition-all duration-300 p-3 sm:p-6 flex items-center gap-2 sm:gap-4">
                <i class="fa-solid fa-users text-xl sm:text-4xl text-orange-500 flex-shrink-0"></i>
                <div class="min-w-0">
                    <h3 class="text-gray-500 text-xs sm:text-sm truncate">Customers</h3>
                    <p class="text-lg sm:text-3xl font-bold text-orange-600 m-0"><?php echo $customersCount ?></p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="mt-6 sm:mt-10 grid grid-cols-2 gap-3 sm:gap-6">
            <div class="bg-white rounded-xl shadow p-2 sm:p-4 h-32 sm:h-48 max-h-48 flex flex-col overflow-hidden">
                <h3 class="text-sm sm:text-lg font-semibold mb-1 sm:mb-3 text-gray-800 flex-shrink-0">Orders by Status
                </h3>
                <div class="relative w-full h-full flex-1">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-2 sm:p-4 h-32 sm:h-48 max-h-48 flex flex-col overflow-hidden">
                <h3 class="text-sm sm:text-lg font-semibold mb-1 sm:mb-3 text-gray-800 flex-shrink-0">Payment Status
                    Distribution</h3>
                <div class="relative w-full h-full flex-1">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="mt-6 sm:mt-10">
            <h2 class="text-lg sm:text-2xl font-semibold mb-3 sm:mb-4 text-gray-800">Recent Orders</h2>
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-medium">
                        <tr>
                            <th class="p-1.5 sm:p-3 whitespace-nowrap">Order #</th>
                            <th class="p-1.5 sm:p-3 whitespace-nowrap">Customer</th>
                            <th class="p-1.5 sm:p-3 whitespace-nowrap">Total</th>
                            <th class="p-1.5 sm:p-3 whitespace-nowrap">Status</th>
                            <th class="p-1.5 sm:p-3 whitespace-nowrap">Payment</th>
                            <th class="p-1.5 sm:p-3 whitespace-nowrap">Date</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs sm:text-sm">
                        <?php if ($orders): ?>
                        <?php foreach($orders as $o): ?>
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition-all duration-200">
                            <td class="p-1.5 sm:p-3 font-medium whitespace-nowrap">#<?php echo $o['id'] ?></td>
                            <td class="p-1.5 sm:p-3">
                                <div class="font-medium"><?php echo htmlspecialchars($o['customer_name'] ?? 'Guest') ?>
                                </div>
                                <div class="text-xs text-gray-500 truncate max-w-[120px] sm:max-w-none">
                                    <?php echo htmlspecialchars($o['customer_email'] ?? 'N/A') ?></div>
                            </td>
                            <td class="p-1.5 sm:p-3 font-semibold text-gray-700 whitespace-nowrap">KSh
                                <?php echo number_format($o['total_amount'], 2) ?></td>
                            <td class="p-1.5 sm:p-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded font-medium 
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
                            <td class="p-1.5 sm:p-3 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs rounded font-medium 
                                    <?php echo $o['payment_status']=='paid'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'; ?>">
                                    <?php echo ucfirst($o['payment_status']) ?>
                                </span>
                            </td>
                            <td class="p-1.5 sm:p-3 text-xs sm:text-sm whitespace-nowrap">
                                <?php echo date("M d, Y H:i", strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500 text-xs sm:text-sm">No recent orders
                                found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= renderPagination($totalOrders, $currentPage, $perPage) ?>
        </div>

    </main>

    <script>
    // Status Bar Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($statuses); ?>,
            datasets: [{
                label: 'Order Count',
                data: <?php echo json_encode($statusCounts); ?>,
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)', // yellow for Pending
                    'rgba(59, 130, 246, 0.8)', // blue for Processing
                    'rgba(99, 102, 241, 0.8)', // indigo for Shipped
                    'rgba(34, 197, 94, 0.8)', // green for Completed
                    'rgba(239, 68, 68, 0.8)' // red for Cancelled
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(59, 130, 246, 1)',
                    'rgba(99, 102, 241, 1)',
                    'rgba(34, 197, 94, 1)',
                    'rgba(239, 68, 68, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 0
            },
            aspectRatio: 2,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 10
                        },
                        maxRotation: 45
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Payment Pie Chart
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($paymentLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($paymentCounts); ?>,
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)', // green for Paid
                    'rgba(239, 68, 68, 0.8)' // red for others
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 0
            },
            aspectRatio: 1.2,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 9
                        },
                        padding: 8,
                        usePointStyle: true
                    }
                }
            }
        }
    });
    </script>
</body>

</html>