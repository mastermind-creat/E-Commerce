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
                        <th class="p-2 md:p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products): ?>
                    <?php foreach ($products as $product): ?>
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-2 md:p-3">
                            <?php if ($product['image_url']): ?>
                            <img src="../public/assets/products/<?= htmlspecialchars($product['image_url']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="w-12 h-12 md:w-16 md:h-16 object-cover rounded mx-auto">
                            <?php else: ?>
                            <span class="text-gray-400 text-xs md:text-sm">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2 md:p-3 font-medium"><?= htmlspecialchars($product['name']) ?></td>
                        <td class="p-2 md:p-3">Ksh <?= number_format($product['price'], 2) ?></td>
                        <td class="p-2 md:p-3"><?= (int)$product['stock'] ?></td>
                        <td class="p-2 md:p-3"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
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
                        <td colspan="6" class="p-4 text-center text-gray-500">No products found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>

</html>