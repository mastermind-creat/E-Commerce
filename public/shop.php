<?php
// public/shop.php - Advanced Shop Page with Filters and Search
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

// Set page title
$pageTitle = 'Shop';

// Get product images from filesystem for carousel and new arrivals
$productsDir = __DIR__ . '/assets/products';
$productImages = [];
if (is_dir($productsDir)) {
    $files = glob($productsDir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });
    $productImages = array_map('basename', $files);
}


// Get filter parameters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$minPrice = floatval($_GET['min_price'] ?? 0);
$maxPrice = floatval($_GET['max_price'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

// Build WHERE clause
$whereConditions = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category)) {
    $whereConditions[] = "c.slug = :category";
    $params[':category'] = $category;
}

if ($minPrice > 0) {
    $whereConditions[] = "p.price >= :min_price";
    $params[':min_price'] = $minPrice;
}

if ($maxPrice > 0) {
    $whereConditions[] = "p.price <= :max_price";
    $params[':max_price'] = $maxPrice;
}

$whereClause = implode(' AND ', $whereConditions);

// Build ORDER BY clause
$orderBy = match($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
    'oldest' => 'p.created_at ASC',
    default => 'p.created_at DESC'
};

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $whereClause
";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products with pagination
$offset = ($page - 1) * $perPage;
$productsQuery = "
        SELECT p.*, c.name as category_name, c.slug as category_slug,
            COALESCE(
                (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
                (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.id LIMIT 1)
            ) AS image_url
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereClause
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

// Preload ratings (average and count) for displayed products to avoid N+1 queries
if (!empty($products)) {
    $prodIds = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($prodIds), '?'));
    $rStmt = $pdo->prepare("SELECT product_id, COUNT(*) as review_count, AVG(rating) as avg_rating FROM reviews WHERE product_id IN ($placeholders) GROUP BY product_id");
    $rStmt->execute($prodIds);
    $productRatings = [];
    foreach ($rStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $productRatings[$r['product_id']] = $r;
    }
} else {
    $productRatings = [];
}

// Get categories for filter
try {
    $categories = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get price range
try {
    $priceRange = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $priceRange = ['min_price' => 0, 'max_price' => 1000];
}

// Get product images from filesystem for carousel and new arrivals (fallback pool)
$productsDir = __DIR__ . '/assets/products';
$productImages = [];
if (is_dir($productsDir)) {
    $files = glob($productsDir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });
    $productImages = array_map('basename', $files);
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">
                        <?php if (!empty($search)): ?>
                        Search Results for "<?= htmlspecialchars($search) ?>"
                        <?php elseif (!empty($category)): ?>
                        <?= htmlspecialchars(ucfirst($category)) ?>
                        <?php else: ?>
                        Shop All Products
                        <?php endif; ?>
                    </h1>
                    <p class="text-gray-600 mt-2">
                        <?= $totalProducts ?> product<?= $totalProducts !== 1 ? 's' : '' ?> found
                    </p>
                </div>

                <!-- View Toggle -->
                <div class="mt-4 sm:mt-0 flex items-center space-x-2">
                    <button id="gridView" class="p-2 rounded-md bg-primary-100 text-primary-600">
                        <i data-feather="grid" class="w-4 h-4"></i>
                    </button>
                    <button id="listView" class="p-2 rounded-md text-gray-400 hover:text-gray-600">
                        <i data-feather="list" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filters -->
            <aside class="lg:w-80 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Filters</h3>

                    <form method="GET" id="filterForm" class="space-y-6">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search products..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>"
                                    <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                            <div class="space-y-2">
                                <input type="number" name="min_price" value="<?= $minPrice ?>" placeholder="Min price"
                                    min="0" step="0.01"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <input type="number" name="max_price" value="<?= $maxPrice ?>" placeholder="Max price"
                                    min="0" step="0.01"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Range: KSh <?= number_format($priceRange['min_price'], 2) ?> - KSh
                                <?= number_format($priceRange['max_price'], 2) ?>
                            </div>
                        </div>

                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <select name="sort"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to
                                    High</option>
                                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High
                                    to Low</option>
                                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A to Z</option>
                            </select>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex space-x-2">
                            <button type="submit"
                                class="flex-1 bg-primary-500 text-white py-2 px-4 rounded-lg hover:bg-primary-600 transition-colors">
                                Apply Filters
                            </button>
                            <a href="shop.php"
                                class="flex-1 bg-gray-200 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors text-center">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Results Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                    <div class="text-sm text-gray-600 mb-4 sm:mb-0">
                        Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalProducts) ?> of
                        <?= $totalProducts ?> products
                    </div>

                    <!-- Mobile Filter Toggle -->
                    <button id="mobileFilterToggle"
                        class="lg:hidden flex items-center space-x-2 bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i data-feather="filter" class="w-4 h-4"></i>
                        <span>Filters</span>
                    </button>
                </div>

                <!-- Products Grid -->
                <?php if (!empty($products)): ?>
                <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                    <div
                        class="product-card group bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden">
                        <a href="product.php?id=<?= $product['id'] ?>" class="block">
                            <div class="relative overflow-hidden">
                                <?php
                                    $imgUrl = product_image_url($product['image_url'] ?? null);
                                    // Server-side path to check file existence (public folder)
                                    $imgFile = __DIR__ . '/' . ($product['image_url'] ?? '');
                                    if ($product['image_url']) {
                                        // normalize if stored with assets/products/ prefix
                                        $maybe = preg_replace('#^assets/products/#', '', $product['image_url']);
                                        $imgFile = __DIR__ . '/assets/products/' . $maybe;
                                    }
                                    $imgExists = is_file($imgFile);
                                ?>
                                <!-- IMG-DEBUG: resolved="<?= htmlspecialchars($imgUrl) ?>" file="<?= htmlspecialchars($imgFile) ?>" exists="<?= $imgExists ? '1' : '0' ?>" -->
                                <img src="<?= htmlspecialchars($imgUrl) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    data-img-resolved="<?= htmlspecialchars($imgUrl) ?>"
                                    data-img-file="<?= htmlspecialchars($imgFile) ?>"
                                    data-img-exists="<?= $imgExists ? '1' : '0' ?>"
                                    class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                                    onerror="this.src='<?= htmlspecialchars(product_image_url(null)) ?>'">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors">
                                </div>
                                <div
                                    class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary-50">
                                        <i data-feather="heart" class="w-5 h-5 text-gray-600"></i>
                                    </button>
                                </div>
                                <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                <div
                                    class="absolute top-4 left-4 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                                    Only <?= $product['stock'] ?> left
                                </div>
                                <?php elseif ($product['stock'] == 0): ?>
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-medium">Out
                                        of Stock</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-6">
                                <div class="text-sm text-primary-600 font-medium mb-1">
                                    <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></div>
                                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                    <?= htmlspecialchars($product['name']) ?></h3>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                    <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-2xl font-bold text-gray-900">KSh
                                        <?= number_format($product['price'], 2) ?></span>
                                    <div class="flex items-center text-yellow-400">
                                        <?php
                                            $pid = $product['id'];
                                            $avg = isset($productRatings[$pid]) && $productRatings[$pid]['avg_rating'] !== null ? round($productRatings[$pid]['avg_rating'], 1) : null;
                                            $count = isset($productRatings[$pid]) ? (int)$productRatings[$pid]['review_count'] : 0;
                                        ?>
                                        <?= render_stars($avg, 14) ?>
                                        <span
                                            class="text-gray-500 text-sm ml-2">(<?= htmlspecialchars(format_rating_text($avg, $count)) ?>)</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-12 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i data-feather="chevron-left" class="w-4 h-4"></i>
                        </a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
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

                        <!-- Next Page -->
                        <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i data-feather="chevron-right" class="w-4 h-4"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- No Products Found -->
                <div class="text-center py-16">
                    <i data-feather="search" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                    <p class="text-gray-500 mb-6">Try adjusting your search or filter criteria</p>
                    <a href="shop.php"
                        class="inline-flex items-center px-6 py-3 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-colors">
                        <i data-feather="refresh-cw" class="w-4 h-4 mr-2"></i>
                        Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Mobile Filter Overlay -->
<div id="mobileFilterOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

<!-- Mobile Filter Sidebar -->
<div id="mobileFilterSidebar"
    class="fixed inset-y-0 left-0 w-80 bg-white shadow-xl z-50 transform -translate-x-full transition-transform duration-300 ease-in-out lg:hidden">
    <div class="flex items-center justify-between p-4 border-b">
        <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
        <button id="mobileFilterClose" class="p-2 rounded-md text-gray-400 hover:text-gray-600">
            <i data-feather="x" class="w-6 h-6"></i>
        </button>
    </div>

    <div class="p-4">
        <form method="GET" id="mobileFilterForm" class="space-y-6">
            <!-- Same filter form as desktop -->
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
                    <option value="<?= htmlspecialchars($cat['slug']) ?>"
                        <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                <div class="space-y-2">
                    <input type="number" name="min_price" value="<?= $minPrice ?>" placeholder="Min price" min="0"
                        step="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <input type="number" name="max_price" value="<?= $maxPrice ?>" placeholder="Max price" min="0"
                        step="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <select name="sort"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low
                    </option>
                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A to Z</option>
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit"
                    class="flex-1 bg-primary-500 text-white py-2 px-4 rounded-lg hover:bg-primary-600 transition-colors">
                    Apply Filters
                </button>
                <a href="shop.php"
                    class="flex-1 bg-gray-200 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors text-center">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
// Mobile filter toggle
document.getElementById('mobileFilterToggle')?.addEventListener('click', () => {
    document.getElementById('mobileFilterSidebar').classList.remove('-translate-x-full');
    document.getElementById('mobileFilterOverlay').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
});

document.getElementById('mobileFilterClose')?.addEventListener('click', () => {
    document.getElementById('mobileFilterSidebar').classList.add('-translate-x-full');
    document.getElementById('mobileFilterOverlay').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
});

document.getElementById('mobileFilterOverlay')?.addEventListener('click', () => {
    document.getElementById('mobileFilterSidebar').classList.add('-translate-x-full');
    document.getElementById('mobileFilterOverlay').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
});

// View toggle
document.getElementById('gridView')?.addEventListener('click', () => {
    document.getElementById('productsGrid').className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6';
    document.getElementById('gridView').className = 'p-2 rounded-md bg-primary-100 text-primary-600';
    document.getElementById('listView').className = 'p-2 rounded-md text-gray-400 hover:text-gray-600';
});

document.getElementById('listView')?.addEventListener('click', () => {
    document.getElementById('productsGrid').className = 'grid grid-cols-1 gap-6';
    document.getElementById('listView').className = 'p-2 rounded-md bg-primary-100 text-primary-600';
    document.getElementById('gridView').className = 'p-2 rounded-md text-gray-400 hover:text-gray-600';
});

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>