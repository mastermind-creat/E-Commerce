<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once 'auth.php';

// Fetch products with category and first image
$stmt = $pdo->query("
    SELECT p.*, 
           c.name AS category_name,
           (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id LIMIT 1) AS image_url
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch variants for all products in one query for performance
$product_ids = array_column($products, 'id');
$variantsByProduct = [];
if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmtVariants = $pdo->prepare("SELECT * FROM product_variants WHERE product_id IN ($placeholders)");
    $stmtVariants->execute($product_ids);
    foreach ($stmtVariants->fetchAll(PDO::FETCH_ASSOC) as $variant) {
        $variantsByProduct[$variant['product_id']][] = $variant;
    }
}

// Get products with low stock (threshold <= 5)
$low_stock_products = array_filter($products, fn($p) => $p['stock'] <= 5);

// ✅ Trigger email notification if there are low-stock products
if (!empty($low_stock_products)) {
    $adminEmail = "kennyleyy00@gmail.com"; // CHANGE THIS to your real email
    $subject = "⚠️ Low Stock Alert - " . count($low_stock_products) . " Product(s)";

    $productList = "";
    foreach ($low_stock_products as $p) {
        $productList .= "<li>{$p['name']} (Stock: {$p['stock']})</li>";
    }

    $message = "
    <html>
    <head><title>Low Stock Notification</title></head>
    <body>
        <h2>Low Stock Alert</h2>
        <p>The following products are running low (≤ 5 in stock):</p>
        <ul>$productList</ul>
        <p><a href='https://yourdomain.com/admin/products.php'>Click here to manage inventory</a></p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Store Alerts <no-reply@yourdomain.com>\r\n";

    if (empty($_SESSION['low_stock_alert_sent'])) {
        mail($adminEmail, $subject, $message, $headers);
        $_SESSION['low_stock_alert_sent'] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">

    <?php include 'sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>

    <main class="p-6 md:ml-64">

        <!-- 🔔 Low Stock Notification -->
        <?php if (!empty($low_stock_products)): ?>
        <div class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 rounded-lg shadow">
            <div class="flex items-center gap-2 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-semibold">Low Stock Alert!</span>
            </div>
            <p class="text-sm">
                The following products are running low:
                <strong>
                    <?= implode(', ', array_map(fn($p) => htmlspecialchars($p['name']), $low_stock_products)) ?>
                </strong>.
                Consider restocking soon.
            </p>
        </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold text-gray-700">Products</h1>
            <a href="add_product.php"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition w-full md:w-auto text-center">
                + Add Product
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm md:text-base">
                <thead class="bg-gray-200 text-gray-600 uppercase text-xs md:text-sm">
                    <tr>
                        <th class="p-2 md:p-3">Image</th>
                        <th class="p-2 md:p-3">Name</th>
                        <th class="p-2 md:p-3">Price</th>
                        <th class="p-2 md:p-3">Stock</th>
                        <th class="p-2 md:p-3">Category</th>
                        <th class="p-2 md:p-3">Variants</th>
                        <th class="p-2 md:p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products): ?>
                    <?php foreach ($products as $product): ?>
                    <?php $lowStock = $product['stock'] <= 5; ?>
                    <tr class="border-b hover:bg-gray-50 transition <?= $lowStock ? 'bg-yellow-50' : '' ?>">
                        <td class="p-2 md:p-3">
                            <?php if ($product['image_url']): ?>
                            <img src="../public/assets/products/<?= htmlspecialchars($product['image_url']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="w-12 h-12 md:w-16 md:h-16 object-cover rounded mx-auto">
                            <?php else: ?>
                            <span class="text-gray-400 text-xs md:text-sm">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2 md:p-3 font-medium flex items-center gap-2">
                            <?= htmlspecialchars($product['name']) ?>
                            <?php if ($lowStock): ?>
                            <span title="Low Stock">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2 md:p-3">Ksh <?= number_format($product['price'], 2) ?></td>
                        <td class="p-2 md:p-3 <?= $lowStock ? 'text-red-600 font-bold' : '' ?>">
                            <?= (int)$product['stock'] ?>
                        </td>
                        <td class="p-2 md:p-3"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                        </td>

                        <!-- VARIANTS COLUMN -->
                        <td class="p-2 md:p-3">
                            <?php if (!empty($variantsByProduct[$product['id']])): ?>
                            <ul class="space-y-1">
                                <?php foreach ($variantsByProduct[$product['id']] as $v): ?>
                                <li class="text-xs md:text-sm bg-gray-100 px-2 py-1 rounded">
                                    <?= htmlspecialchars($v['variant_name']) ?>:
                                    <span class="font-semibold"><?= (int)$v['variant_stock'] ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs md:text-sm">No variants</span>
                            <?php endif; ?>
                        </td>

                        <td class="p-2 md:p-3 flex flex-col sm:flex-row gap-2">
                            <a href="edit_product.php?id=<?= $product['id'] ?>"
                                class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 text-center">Edit</a>
                            <a href="delete_product.php?id=<?= $product['id'] ?>"
                                onclick="return confirm('Are you sure you want to delete this product?');"
                                class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-center">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500">No products found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>

</html>