<?php
// admin/products.php - Enhanced Product Management
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once 'auth.php';

// Set page title
$pageTitle = 'Product Management';

// Handle bulk operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selectedProducts = $_POST['selected_products'] ?? [];
    
    if (!empty($selectedProducts)) {
        try {
            $pdo->beginTransaction();
            
            switch ($action) {
                case 'activate':
                    $stmt = $pdo->prepare("UPDATE products SET status = 'active' WHERE id IN (" . implode(',', array_fill(0, count($selectedProducts), '?')) . ")");
                    $stmt->execute($selectedProducts);
                    $message = count($selectedProducts) . ' products activated successfully.';
                    break;
                    
                case 'deactivate':
                    $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id IN (" . implode(',', array_fill(0, count($selectedProducts), '?')) . ")");
                    $stmt->execute($selectedProducts);
                    $message = count($selectedProducts) . ' products deactivated successfully.';
                    break;
                    
                case 'delete':
                    // Delete product images first
                    foreach ($selectedProducts as $productId) {
                        $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
                        $imgStmt->execute([$productId]);
                        $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($images as $image) {
                            $filepath = "../public/assets/products/" . $image;
                            if (file_exists($filepath)) {
                                unlink($filepath);
                            }
                        }
                        
                        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$productId]);
                        $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$productId]);
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id IN (" . implode(',', array_fill(0, count($selectedProducts), '?')) . ")");
                    $stmt->execute($selectedProducts);
                    $message = count($selectedProducts) . ' products deleted successfully.';
                    break;
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Bulk operation failed: ' . $e->getMessage();
        }
    } else {
        $error = 'Please select at least one product.';
    }
}

// Handle individual product actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $productId = intval($_GET['id']);
    
    try {
        switch ($action) {
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE products SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
                $stmt->execute([$productId]);
                $message = 'Product status updated successfully.';
                break;
                
            case 'duplicate':
                // Get original product
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $originalProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($originalProduct) {
                    // Insert duplicate with modified name
                    $stmt = $pdo->prepare("
                        INSERT INTO products (name, description, price, category_id, stock, status, color, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $originalProduct['name'] . ' (Copy)',
                        $originalProduct['description'],
                        $originalProduct['price'],
                        $originalProduct['category_id'],
                        $originalProduct['stock'],
                        'inactive', // Set as inactive by default
                        $originalProduct['color']
                    ]);
                    $message = 'Product duplicated successfully.';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Action failed: ' . $e->getMessage();
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$stockStatus = $_GET['stock_status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Build WHERE clause
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category)) {
    $whereConditions[] = "p.category_id = :category";
    $params[':category'] = $category;
}

if (!empty($status)) {
    $whereConditions[] = "p.status = :status";
    $params[':status'] = $status;
}

if (!empty($stockStatus)) {
    switch ($stockStatus) {
        case 'in_stock':
            $whereConditions[] = "p.stock > 10";
            break;
        case 'low_stock':
            $whereConditions[] = "p.stock > 0 AND p.stock <= 10";
            break;
        case 'out_of_stock':
            $whereConditions[] = "p.stock <= 0";
            break;
    }
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Build ORDER BY clause
$orderBy = match($sort) {
    'name' => 'p.name ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'stock_low' => 'p.stock ASC',
    'oldest' => 'p.created_at ASC',
    default => 'p.created_at DESC'
};

// Get total count
$countQuery = "SELECT COUNT(*) FROM products p $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products with pagination and enhanced data
$offset = ($page - 1) * $perPage;
$productsQuery = "
    SELECT p.*, c.name as category_name, pi.image_url,
           (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
           (SELECT SUM(variant_stock) FROM product_variants WHERE product_id = p.id) as total_variant_stock,
           (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count,
           CASE 
               WHEN p.stock <= 0 THEN 'out_of_stock'
               WHEN p.stock <= 10 THEN 'low_stock'
               ELSE 'in_stock'
           END as stock_status
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    $whereClause 
    ORDER BY $orderBy 
    LIMIT :limit OFFSET :offset
";

$productsStmt = $pdo->prepare($productsQuery);
foreach ($params as $key => $value) {
    $productsStmt->bindValue($key, $value);
}
$productsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$productsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$productsStmt->execute();
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
        @media print {
            body * {
                visibility: hidden;
            }
            .printable-content, .printable-content * {
                visibility: visible;
            }
            .printable-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .print-header {
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            .print-table {
                width: 100%;
                border-collapse: collapse;
            }
            .print-table th,
            .print-table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            .print-table th {
                background-color: #f3f4f6;
                font-weight: bold;
            }
            .print-actions {
                display: none;
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6 lg:ml-64">
        <!-- Header -->
        <div class="mb-8 no-print">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 text-white relative overflow-hidden">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-3xl sm:text-4xl font-bold mb-2">Product Management</h1>
                            <p class="text-blue-100 text-lg">Advanced product catalog management with variants and inventory tracking</p>
                        </div>
                        <div class="mt-4 sm:mt-0 flex items-center space-x-4">
                            <div class="text-right">
                                <p class="text-blue-100 text-sm">Total Products</p>
                                <p class="text-2xl font-bold"><?= $totalProducts ?></p>
                                <p class="text-blue-100 text-xs">Page <?= $page ?> of <?= $totalPages ?></p>
                            </div>
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                                <i data-feather="package" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Decorative elements -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-12 -translate-x-12"></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3 mb-6 no-print">
            <a href="add_product.php"
                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center shadow-lg">
                <i data-feather="plus" class="w-5 h-5 mr-2"></i>
                Add Product
            </a>
            <button id="bulkActionsBtn"
                class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors flex items-center shadow-lg"
                disabled>
                <i data-feather="layers" class="w-5 h-5 mr-2"></i>
                Bulk Actions
            </button>
            <a href="?export=csv"
                class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center shadow-lg">
                <i data-feather="download" class="w-5 h-5 mr-2"></i>
                Export CSV
            </a>
            <button onclick="window.print()"
                class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors flex items-center shadow-lg">
                <i data-feather="printer" class="w-5 h-5 mr-2"></i>
                Print
            </button>
        </div>

        <!-- Messages -->
        <?php if (isset($message)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center no-print">
            <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center no-print">
            <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 no-print">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search products..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock Status</label>
                    <select name="stock_status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">All Stock Levels</option>
                        <option value="in_stock" <?= ($_GET['stock_status'] ?? '') === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                        <option value="low_stock" <?= ($_GET['stock_status'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                        <option value="out_of_stock" <?= ($_GET['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <select name="sort"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price Low-High</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price High-Low
                        </option>
                        <option value="stock_low" <?= $sort === 'stock_low' ? 'selected' : '' ?>>Stock Low-High</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit"
                        class="w-full bg-primary-500 text-white py-2 px-4 rounded-lg hover:bg-primary-600 transition-colors">
                        <i data-feather="search" class="w-4 h-4 mr-2 inline"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Bulk Actions Panel -->
        <div id="bulkActionsPanel" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 hidden no-print">
            <form method="POST" id="bulkActionsForm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-sm font-medium text-yellow-800 mr-4">
                            <span id="selectedCount">0</span> products selected
                        </span>
                        <button type="button" id="selectAllBtn" class="text-sm text-yellow-600 hover:text-yellow-800">
                            Select All
                        </button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <select name="bulk_action" class="px-3 py-1 border border-yellow-300 rounded text-sm">
                            <option value="">Choose Action</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit"
                            class="bg-yellow-600 text-white px-4 py-1 rounded text-sm hover:bg-yellow-700">
                            Apply
                        </button>
                        <button type="button" id="clearSelection" class="text-yellow-600 hover:text-yellow-800 text-sm">
                            Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Printable Content -->
        <div class="printable-content">
            <!-- Print Header -->
            <div class="print-header">
                <h1 class="text-2xl font-bold text-center mb-2">Springs Store - Product Inventory Report</h1>
                <div class="text-center text-sm text-gray-600">
                    <p>Generated on: <?= date('F j, Y \a\t g:i A') ?></p>
                    <p>Total Products: <?= $totalProducts ?></p>
                    <?php if (!empty($search) || !empty($category) || !empty($status) || !empty($stockStatus)): ?>
                    <p class="mt-2 text-xs">
                        Filters Applied: 
                        <?php
                        $filters = [];
                        if (!empty($search)) $filters[] = "Search: '$search'";
                        if (!empty($category)) {
                            $catName = '';
                            foreach ($categories as $cat) {
                                if ($cat['id'] == $category) {
                                    $catName = $cat['name'];
                                    break;
                                }
                            }
                            $filters[] = "Category: '$catName'";
                        }
                        if (!empty($status)) $filters[] = "Status: " . ucfirst($status);
                        if (!empty($stockStatus)) {
                            $stockLabels = [
                                'in_stock' => 'In Stock',
                                'low_stock' => 'Low Stock',
                                'out_of_stock' => 'Out of Stock'
                            ];
                            $filters[] = "Stock: " . ($stockLabels[$stockStatus] ?? $stockStatus);
                        }
                        echo implode(', ', $filters);
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Table -->
            <table class="print-table w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left no-print">
                                <input type="checkbox" id="selectAll"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Variants</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider print-actions">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 no-print">
                                <input type="checkbox" name="selected_products[]" value="<?= $product['id'] ?>"
                                    class="product-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php
                                        // Safely prepare display values to avoid deprecated warnings when null
                                        $displayImage = $product['image_url'] ? '../public/assets/products/' . htmlspecialchars($product['image_url']) : '../public/assets/images/placeholder.png';
                                        $displayName = isset($product['name']) ? $product['name'] : 'Unnamed product';
                                        $displayCategory = isset($product['category_name']) ? $product['category_name'] : 'Uncategorized';
                                        $displayPrice = isset($product['price']) ? (float)$product['price'] : 0.0;
                                        $displayStatus = isset($product['status']) ? $product['status'] : 'inactive';
                                        $displaySku = isset($product['sku']) ? $product['sku'] : 'N/A';
                                    ?>
                                    <img src="<?= $displayImage ?>" alt="<?= htmlspecialchars($displayName) ?>"
                                        class="w-12 h-12 object-cover rounded-lg mr-4"
                                        onerror="this.src='../public/assets/images/placeholder.png'">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($displayName) ?></div>
                                        <div class="text-sm text-gray-500">ID: <?= htmlspecialchars($product['id']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                    <?= htmlspecialchars($displaySku) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= htmlspecialchars($displayCategory) ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">KSh
                                <?= number_format($displayPrice, 2) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex flex-col space-y-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        switch ($product['stock_status']) {
                                            case 'out_of_stock':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'low_stock':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            default:
                                                echo 'bg-green-100 text-green-800';
                                        }
                                        ?>">
                                        <?= $product['stock'] ?>
                                        <?php if ($product['total_variant_stock'] > 0): ?>
                                        <span class="ml-1 text-xs opacity-75">
                                            (+<?= $product['total_variant_stock'] ?> variants)
                                        </span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($product['stock_status'] === 'low_stock'): ?>
                                    <span class="text-xs text-yellow-600 flex items-center">
                                        <i data-feather="alert-triangle" class="w-3 h-3 mr-1"></i>
                                        Low Stock
                                    </span>
                                    <?php elseif ($product['stock_status'] === 'out_of_stock'): ?>
                                    <span class="text-xs text-red-600 flex items-center">
                                        <i data-feather="x-circle" class="w-3 h-3 mr-1"></i>
                                        Out of Stock
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?= $displayStatus === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ucfirst($displayStatus) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= $product['variant_count'] ?> variants
                                    </span>
                                    <?php if ($product['image_count'] > 0): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <i data-feather="image" class="w-3 h-3 mr-1"></i>
                                        <?= $product['image_count'] ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium print-actions">
                                <div class="relative inline-block text-left">
                                    <button type="button" 
                                        class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs"
                                        onclick="toggleDropdown('dropdown-<?= $product['id'] ?>')">
                                        <i data-feather="more-horizontal" class="w-4 h-4 mr-1"></i>
                                        Actions
                                        <i data-feather="chevron-down" class="w-3 h-3 ml-1"></i>
                                    </button>
                                    
                                    <div id="dropdown-<?= $product['id'] ?>" 
                                        class="hidden absolute right-0 z-10 mt-2 w-48 origin-top-right bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <div class="py-1" role="menu">
                                            <a href="edit_product.php?id=<?= $product['id'] ?>"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                role="menuitem">
                                                <i data-feather="edit" class="w-4 h-4 mr-3"></i>
                                                Edit Product
                                            </a>
                                            <a href="../public/product.php?id=<?= $product['id'] ?>"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                target="_blank" role="menuitem">
                                                <i data-feather="eye" class="w-4 h-4 mr-3"></i>
                                                View Product
                                            </a>
                                            <a href="?action=duplicate&id=<?= $product['id'] ?>"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                role="menuitem">
                                                <i data-feather="copy" class="w-4 h-4 mr-3"></i>
                                                Duplicate Product
                                            </a>
                                            <a href="?action=toggle_status&id=<?= $product['id'] ?>"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                role="menuitem">
                                                <i data-feather="<?= $displayStatus === 'active' ? 'pause' : 'play' ?>" class="w-4 h-4 mr-3"></i>
                                                <?= $displayStatus === 'active' ? 'Deactivate' : 'Activate' ?> Product
                                            </a>
                                            <div class="border-t border-gray-100"></div>
                                            <a href="delete_product.php?id=<?= $product['id'] ?>"
                                                class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                                onclick="return confirm('Are you sure you want to delete this product?')"
                                                role="menuitem">
                                                <i data-feather="trash-2" class="w-4 h-4 mr-3"></i>
                                                Delete Product
                                            </a>
                                        </div>
                                    </div>
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
        <div class="mt-6 flex justify-center no-print">
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
                    class="px-3 py-2 text-sm font-medium rounded-lg <?= $i === $page ? 'bg-primary-500 text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50' ?>">
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
            Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalProducts) ?> of <?= $totalProducts ?> products
        </div>
    </main>

    <!-- JavaScript -->
    <script>
    // Bulk selection functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActionsBtn = document.getElementById('bulkActionsBtn');
    const bulkActionsPanel = document.getElementById('bulkActionsPanel');
    const selectedCountSpan = document.getElementById('selectedCount');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const clearSelectionBtn = document.getElementById('clearSelection');

    function updateSelection() {
        const selectedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
        const count = selectedCheckboxes.length;

        selectedCountSpan.textContent = count;
        bulkActionsBtn.disabled = count === 0;
        bulkActionsPanel.classList.toggle('hidden', count === 0);

        // Update select all checkbox
        selectAllCheckbox.indeterminate = count > 0 && count < productCheckboxes.length;
        selectAllCheckbox.checked = count === productCheckboxes.length;
    }

    selectAllCheckbox.addEventListener('change', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelection();
    });

    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelection);
    });

    selectAllBtn.addEventListener('click', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        updateSelection();
    });

    clearSelectionBtn.addEventListener('click', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        updateSelection();
    });

    // Bulk actions form
    document.getElementById('bulkActionsForm').addEventListener('submit', function(e) {
        const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
        const action = this.bulk_action.value;

        if (selectedProducts.length === 0) {
            e.preventDefault();
            alert('Please select at least one product.');
            return;
        }

        if (!action) {
            e.preventDefault();
            alert('Please select an action.');
            return;
        }

        if (action === 'delete') {
            if (!confirm(
                    `Are you sure you want to delete ${selectedProducts.length} product(s)? This action cannot be undone.`
                )) {
                e.preventDefault();
                return;
            }
        }

        // Add selected products to form
        selectedProducts.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_products[]';
            input.value = checkbox.value;
            this.appendChild(input);
        });
    });

    // Initialize Feather icons
    feather.replace();

    // Dropdown functionality
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const isHidden = dropdown.classList.contains('hidden');
        
        // Close all other dropdowns
        document.querySelectorAll('[id^="dropdown-"]').forEach(dd => {
            if (dd.id !== dropdownId) {
                dd.classList.add('hidden');
            }
        });
        
        // Toggle current dropdown
        if (isHidden) {
            dropdown.classList.remove('hidden');
        } else {
            dropdown.classList.add('hidden');
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('[id^="dropdown-"]') && !event.target.closest('button[onclick*="toggleDropdown"]')) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }
    });

    // Close dropdowns when pressing Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }
    });
    </script>
</body>

</html>