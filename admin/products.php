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

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
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

// Get products with pagination
$offset = ($page - 1) * $perPage;
$productsQuery = "
    SELECT p.*, c.name as category_name, pi.image_url,
           (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count
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
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6 lg:ml-64">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Product Management</h1>
                <p class="text-gray-600 mt-2">Manage your product catalog</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <a href="add_product.php"
                    class="flex items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <i data-feather="plus" class="w-5 h-5 mr-2 text-primary-600"></i>
                    <span class="text-sm font-medium text-gray-700">Add Product</span>
                </a>
                <button id="bulkActionsBtn"
                    class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors flex items-center"
                    disabled>
                    <i data-feather="layers" class="w-4 h-4 mr-2"></i>
                    Bulk Actions
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($message)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
            <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
            <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
        <div id="bulkActionsPanel" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 hidden">
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

        <!-- Products Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
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
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $product['stock'] > 10 ? 'bg-green-100 text-green-800' : 
                                       ($product['stock'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                    <?= $product['stock'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?= $displayStatus === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ucfirst($displayStatus) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $product['variant_count'] ?></td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="edit_product.php?id=<?= $product['id'] ?>"
                                        class="text-primary-600 hover:text-primary-900">
                                        <i data-feather="edit" class="w-4 h-4"></i>
                                    </a>
                                    <a href="../public/product.php?id=<?= $product['id'] ?>"
                                        class="text-blue-600 hover:text-blue-900" target="_blank">
                                        <i data-feather="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="delete_product.php?id=<?= $product['id'] ?>"
                                        class="text-red-600 hover:text-red-900"
                                        onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
                                    </a>
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
    </script>
</body>

</html>