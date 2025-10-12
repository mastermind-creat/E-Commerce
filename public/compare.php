<?php
// public/compare.php - Product Comparison Page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/discount_functions.php';

// Set page title
$pageTitle = 'Compare Products';

// Get products to compare from session
$compareList = $_SESSION['compare'] ?? [];
$maxCompare = 4; // Maximum products to compare

// Handle add/remove from comparison
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = intval($_POST['product_id'] ?? 0);
    
    if ($action === 'add' && $productId > 0) {
        if (count($compareList) < $maxCompare) {
            if (!in_array($productId, $compareList)) {
                $compareList[] = $productId;
                $_SESSION['compare'] = $compareList;
            }
        }
    } elseif ($action === 'remove' && $productId > 0) {
        $compareList = array_filter($compareList, function($id) use ($productId) {
            return $id !== $productId;
        });
        $_SESSION['compare'] = array_values($compareList);
    } elseif ($action === 'clear') {
        $_SESSION['compare'] = [];
        $compareList = [];
    }
    
    header("Location: compare.php");
    exit;
}

// Get product details for comparison
$products = [];
if (!empty($compareList)) {
    $placeholders = str_repeat('?,', count($compareList) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name,
               COALESCE(
                   (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
                   (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.id LIMIT 1)
               ) AS image_url,
               (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
               (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id IN ($placeholders) AND p.status = 'active'
        ORDER BY FIELD(p.id, " . implode(',', $compareList) . ")
    ");
    $stmt->execute($compareList);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Compare Products</h1>
                    <p class="text-gray-600 mt-2">Compare up to <?= $maxCompare ?> products side by side</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">
                        <?= count($products) ?> of <?= $maxCompare ?> products
                    </span>
                    <?php if (!empty($products)): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" 
                                class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                onclick="return confirm('Clear all products from comparison?')">
                            <i data-feather="trash-2" class="w-4 h-4 mr-1 inline"></i>
                            Clear All
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (empty($products)): ?>
        <!-- Empty Comparison -->
        <div class="text-center py-16">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-feather="git-compare" class="w-12 h-12 text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No products to compare</h3>
            <p class="text-gray-600 mb-8">Add products to your comparison list to see them side by side.</p>
            <a href="shop.php" class="inline-flex items-center px-6 py-3 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-colors">
                <i data-feather="shopping-bag" class="w-5 h-5 mr-2"></i>
                Start Shopping
            </a>
        </div>
        <?php else: ?>
        <!-- Comparison Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <?php foreach ($products as $product): ?>
                            <th class="px-6 py-4 text-center min-w-[250px]">
                                <div class="relative">
                                    <!-- Product Image -->
                                    <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-4">
                                        <?php
                                            $imgUrl = product_image_url($product['image_url'] ?? null);
                                        ?>
                                        <img src="<?= htmlspecialchars($imgUrl) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             class="w-full h-full object-cover"
                                             onerror="this.src='<?= htmlspecialchars(product_image_url(null)) ?>'">
                                    </div>
                                    
                                    <!-- Remove Button -->
                                    <form method="POST" class="absolute top-2 right-2">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" 
                                                class="bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full p-1 shadow-lg transition-all duration-200 hover:scale-110"
                                                title="Remove from comparison">
                                            <i data-feather="x" class="w-4 h-4 text-gray-600"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Product Name -->
                                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                        <a href="product.php?id=<?= $product['id'] ?>" 
                                           class="hover:text-primary-600 transition-colors">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h3>
                                    
                                    <!-- Price -->
                                    <?php
                                    $priceInfo = getEffectivePrice($product);
                                    if ($priceInfo['is_discounted']):
                                    ?>
                                    <div class="mb-2">
                                        <div class="text-2xl font-bold text-green-600">KSh <?= number_format($priceInfo['discounted_price'], 2) ?></div>
                                        <div class="text-lg text-gray-500 line-through">KSh <?= number_format($priceInfo['original_price'], 2) ?></div>
                                        <div class="text-sm text-red-600 font-medium">Save KSh <?= number_format($priceInfo['discount_amount'], 2) ?></div>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-2xl font-bold text-gray-900 mb-2">
                                        KSh <?= number_format($product['price'], 2) ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Rating -->
                                    <div class="flex items-center justify-center space-x-1 mb-4">
                                        <?= render_stars($product['avg_rating'], 16) ?>
                                        <span class="text-sm text-gray-600">
                                            (<?= $product['review_count'] ?>)
                                        </span>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="space-y-2">
                                        <a href="product.php?id=<?= $product['id'] ?>" 
                                           class="block w-full bg-primary-500 text-white py-2 px-4 rounded-lg hover:bg-primary-600 transition-colors text-center text-sm font-medium">
                                            View Details
                                        </a>
                                        <form action="add_to_cart.php" method="POST" class="w-full">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" 
                                                    class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                                <i data-feather="shopping-cart" class="w-4 h-4 mr-1 inline"></i>
                                                Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <!-- Category -->
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-500">Category</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4 text-center text-sm text-gray-900">
                                <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Price -->
                        <tr class="bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-500">Price</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4 text-center text-sm text-gray-900 font-semibold">
                                KSh <?= number_format($product['price'], 2) ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Rating -->
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-500">Rating</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4 text-center text-sm text-gray-900">
                                <div class="flex items-center justify-center space-x-1">
                                    <?= render_stars($product['avg_rating'], 16) ?>
                                    <span class="ml-2"><?= number_format($product['avg_rating'], 1) ?></span>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Stock -->
                        <tr class="bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-500">Stock</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4 text-center text-sm">
                                <?php $stock = (int)($product['stock'] ?? 0); ?>
                                <?php if ($stock > 10): ?>
                                <span class="text-green-600 font-medium">In Stock (<?= $stock ?>)</span>
                                <?php elseif ($stock > 0): ?>
                                <span class="text-orange-600 font-medium">Low Stock (<?= $stock ?>)</span>
                                <?php else: ?>
                                <span class="text-red-600 font-medium">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Description -->
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-500">Description</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <p class="line-clamp-3"><?= htmlspecialchars($product['description']) ?></p>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Features -->
                        <tr class="bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-500">Features</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <ul class="space-y-1">
                                    <li class="flex items-center">
                                        <i data-feather="check" class="w-4 h-4 text-green-500 mr-2"></i>
                                        High quality materials
                                    </li>
                                    <li class="flex items-center">
                                        <i data-feather="check" class="w-4 h-4 text-green-500 mr-2"></i>
                                        Fast delivery
                                    </li>
                                    <li class="flex items-center">
                                        <i data-feather="check" class="w-4 h-4 text-green-500 mr-2"></i>
                                        30-day returns
                                    </li>
                                </ul>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Add More Products -->
        <?php if (count($products) < $maxCompare): ?>
        <div class="mt-8 text-center">
            <p class="text-gray-600 mb-4">Add more products to compare (<?= count($products) ?>/<?= $maxCompare ?>)</p>
            <a href="shop.php" 
               class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i data-feather="plus" class="w-5 h-5 mr-2"></i>
                Browse More Products
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
