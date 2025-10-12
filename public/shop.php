<?php
// public/shop.php - Advanced Shop Page with Filters and Search
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/discount_functions.php';
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
            p.discount_percentage, p.discount_start_date, p.discount_end_date, p.is_discounted,
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

// Get categories for filter with product counts
try {
    $categories = $pdo->query("
        SELECT c.id, c.name, c.slug, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
        GROUP BY c.id, c.name, c.slug 
        ORDER BY c.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
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

<style>
    /* Custom scrollbar for category filter */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.5);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.7);
    }

    /* Glassmorphism enhancements */
    .glass-card {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }

    /* Enhanced animations */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-slide-in-up {
        animation: slideInUp 0.6s ease-out;
    }

    /* Gradient text */
    .gradient-text {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Enhanced hover effects */
    .hover-lift {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hover-lift:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    /* Pulse animation for loading states */
    @keyframes pulse-slow {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    .animate-pulse-slow {
        animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Line clamp utility */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Recently viewed products responsive grid */
    @media (max-width: 640px) {
        #recentlyViewedProducts {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 641px) and (max-width: 768px) {
        #recentlyViewedProducts {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
        #recentlyViewedProducts {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (min-width: 1025px) {
        #recentlyViewedProducts {
            grid-template-columns: repeat(5, 1fr);
        }
    }
</style>

<main class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100">
    <!-- Enhanced Page Header -->
    <div class="bg-white/80 backdrop-blur-md border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <div class="flex items-center justify-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-xl mr-4">
                        <i data-feather="shopping-bag" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl sm:text-5xl font-bold gradient-text">
                            <?php if (!empty($search)): ?>
                            Search Results
                            <?php elseif (!empty($category)): ?>
                            <?= htmlspecialchars(ucfirst($category)) ?>
                            <?php else: ?>
                            Shop All Products
                            <?php endif; ?>
                        </h1>
                        <?php if (!empty($search)): ?>
                        <p class="text-xl text-gray-600 mt-2">for "<?= htmlspecialchars($search) ?>"</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center justify-center space-x-6 mb-6">
                    <div class="bg-white/60 backdrop-blur-sm rounded-2xl px-6 py-3 border border-white/30">
                        <span class="text-2xl font-bold text-primary-600"><?= $totalProducts ?></span>
                        <span class="text-gray-600 ml-2">product<?= $totalProducts !== 1 ? 's' : '' ?> found</span>
                    </div>
                </div>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Discover amazing products at great prices with our enhanced shopping experience
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Enhanced Sidebar Filters -->
            <aside class="lg:w-80 flex-shrink-0">
                <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 sticky top-24">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-pink-600 rounded-xl flex items-center justify-center mr-3">
                            <i data-feather="filter" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Filters & Search</h3>
                    </div>
                    
                    <form method="GET" id="filterForm" class="space-y-6">
                        <!-- Enhanced Search -->
                        <div class="space-y-3">
                            <label class="block text-lg font-semibold text-gray-700">Search Products</label>
                            <div class="relative">
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Search products, brands, categories..."
                                    class="w-full pl-12 pr-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900 placeholder-gray-500">
                                <i data-feather="search" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Category Filter with Radio Buttons -->
                        <div class="space-y-3">
                            <label class="block text-lg font-semibold text-gray-700">Categories</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                                <label class="flex items-center p-3 bg-white/60 rounded-xl hover:bg-white/80 transition-all duration-300 cursor-pointer">
                                    <input type="radio" name="category" value="" <?= empty($category) ? 'checked' : '' ?> 
                                        class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <span class="ml-3 text-gray-700 font-medium">All Categories</span>
                                    <span class="ml-auto text-sm text-gray-500"><?= $totalProducts ?></span>
                                </label>
                                <?php foreach ($categories as $cat): ?>
                                <label class="flex items-center p-3 bg-white/60 rounded-xl hover:bg-white/80 transition-all duration-300 cursor-pointer">
                                    <input type="radio" name="category" value="<?= htmlspecialchars($cat['slug']) ?>" 
                                        <?= $category === $cat['slug'] ? 'checked' : '' ?>
                                        class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <span class="ml-3 text-gray-700 font-medium"><?= htmlspecialchars($cat['name']) ?></span>
                                    <span class="ml-auto text-sm text-gray-500"><?= $cat['product_count'] ?? 0 ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Price Range with Enhanced UI -->
                        <div class="space-y-4">
                            <label class="block text-lg font-semibold text-gray-700">Price Range</label>
                            <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-4 border border-white/30">
                                <div class="flex items-center space-x-4 mb-4">
                                    <div class="flex-1">
                                        <label class="block text-sm text-gray-600 mb-1">Min Price</label>
                                        <input type="number" name="min_price" value="<?= $minPrice ?>" placeholder="0"
                                            min="0" step="0.01"
                                            class="w-full px-3 py-2 bg-white/80 border border-white/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 text-gray-900">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm text-gray-600 mb-1">Max Price</label>
                                        <input type="number" name="max_price" value="<?= $maxPrice ?>" placeholder="10000"
                                            min="0" step="0.01"
                                            class="w-full px-3 py-2 bg-white/80 border border-white/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 text-gray-900">
                                    </div>
                                </div>
                                <div class="text-center">
                                    <span class="text-sm text-gray-600">
                                        Range: <span class="font-semibold text-primary-600">KSh <?= number_format($priceRange['min_price'], 0) ?></span> - 
                                        <span class="font-semibold text-primary-600">KSh <?= number_format($priceRange['max_price'], 0) ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Sort Options with Icons -->
                        <div class="space-y-3">
                            <label class="block text-lg font-semibold text-gray-700">Sort By</label>
                            <select name="sort"
                                class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>🆕 Newest First</option>
                                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>📅 Oldest First</option>
                                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>💰 Price: Low to High</option>
                                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>💎 Price: High to Low</option>
                                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>🔤 Name: A to Z</option>
                            </select>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex space-x-3 pt-4">
                            <button type="submit"
                                class="flex-1 bg-gradient-to-r from-primary-500 to-pink-600 text-white py-3 px-6 rounded-2xl hover:from-primary-600 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl font-semibold hover:scale-105">
                                <i data-feather="search" class="w-4 h-4 inline mr-2"></i>
                                Apply Filters
                            </button>
                            <a href="shop.php"
                                class="flex-1 bg-white/60 backdrop-blur-sm border border-white/30 text-gray-700 py-3 px-6 rounded-2xl hover:bg-white/80 transition-all duration-300 shadow-lg hover:shadow-xl text-center font-semibold">
                                <i data-feather="refresh-cw" class="w-4 h-4 inline mr-2"></i>
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Enhanced Results Header -->
                <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6 mb-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                        <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                                <i data-feather="grid" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Products</h2>
                                <p class="text-gray-600">
                                    Showing <span class="font-semibold text-primary-600"><?= $offset + 1 ?>-<?= min($offset + $perPage, $totalProducts) ?></span> of 
                                    <span class="font-semibold text-primary-600"><?= $totalProducts ?></span> products
                                </p>
                            </div>
                        </div>

                        <!-- Mobile Filter Toggle -->
                        <button id="mobileFilterToggle"
                            class="lg:hidden flex items-center space-x-2 bg-gradient-to-r from-primary-500 to-pink-600 text-white rounded-xl px-6 py-3 font-semibold hover:from-primary-600 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                            <i data-feather="filter" class="w-4 h-4"></i>
                            <span>Filters</span>
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="flex-1">
                    <?php if (!empty($products)): ?>
                    <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                    <div
                        class="product-card group bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden"
                        data-product-id="<?= $product['id'] ?>"
                        data-product-price="<?= $product['price'] ?>"
                        data-product-name="<?= htmlspecialchars($product['name']) ?>"
                        data-product-image="<?= htmlspecialchars($imgUrl) ?>">
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
                                    
                                    $priceInfo = getEffectivePrice($product);
                                ?>
                                <?php if ($priceInfo['is_discounted']): ?>
                                <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg animate-pulse z-10">
                                    -<?= number_format($priceInfo['savings_percentage'], 0) ?>%
                                </div>
                                <?php endif; ?>
                                <!-- IMG-DEBUG: resolved="<?= htmlspecialchars($imgUrl) ?>" file="<?= htmlspecialchars($imgFile) ?>" exists="<?= $imgExists ? '1' : '0' ?>" -->
                                <img src="<?= htmlspecialchars($imgUrl) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    data-img-resolved="<?= htmlspecialchars($imgUrl) ?>"
                                    data-img-file="<?= htmlspecialchars($imgFile) ?>"
                                    data-img-exists="<?= $imgExists ? '1' : '0' ?>"
                                    class="w-full h-48 sm:h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                                    onerror="this.src='<?= htmlspecialchars(product_image_url(null)) ?>'">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors">
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-all duration-500 flex space-x-3">
                                    <button onclick="openQuickView(<?= $product['id'] ?>)" 
                                            class="group/btn bg-white/95 backdrop-blur-sm text-gray-700 px-4 py-2.5 rounded-xl shadow-xl hover:shadow-2xl hover:bg-white hover:scale-105 transition-all duration-300 text-sm font-semibold border border-gray-200/50 hover:border-primary-300">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-5 h-5 rounded-full bg-primary-100 group-hover/btn:bg-primary-200 transition-colors duration-300 flex items-center justify-center">
                                                <i data-feather="eye" class="w-3 h-3 text-primary-600 group-hover/btn:scale-110 transition-transform duration-300"></i>
                                            </div>
                                            <span class="group-hover/btn:text-primary-700 transition-colors duration-300">Quick View</span>
                                        </div>
                                    </button>
                                    <button onclick="toggleCompare(<?= $product['id'] ?>)" 
                                            class="group/btn bg-white/95 backdrop-blur-sm text-gray-700 px-4 py-2.5 rounded-xl shadow-xl hover:shadow-2xl hover:bg-white hover:scale-105 transition-all duration-300 text-sm font-semibold border border-gray-200/50 hover:border-orange-300"
                                            id="compare-btn-<?= $product['id'] ?>">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-5 h-5 rounded-full bg-orange-100 group-hover/btn:bg-orange-200 transition-colors duration-300 flex items-center justify-center">
                                                <i data-feather="git-compare" class="w-3 h-3 text-orange-600 group-hover/btn:scale-110 transition-transform duration-300"></i>
                                            </div>
                                            <span class="group-hover/btn:text-orange-700 transition-colors duration-300">Compare</span>
                                        </div>
                                    </button>
                                </div>
                                <div
                                    class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-all duration-500">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                    <form method="POST" action="wishlist.php" class="inline">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit"
                                            class="group/wishlist w-12 h-12 bg-white/95 backdrop-blur-sm rounded-full flex items-center justify-center shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 border border-gray-200/50 hover:border-red-300"
                                            title="Add to wishlist">
                                            <div class="w-6 h-6 rounded-full bg-red-50 group-hover/wishlist:bg-red-100 transition-colors duration-300 flex items-center justify-center">
                                                <i data-feather="heart" class="w-4 h-4 text-red-500 group-hover/wishlist:scale-110 transition-transform duration-300"></i>
                                            </div>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <a href="login.php?redirect=shop.php"
                                        class="group/wishlist w-12 h-12 bg-white/95 backdrop-blur-sm rounded-full flex items-center justify-center shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 border border-gray-200/50 hover:border-red-300"
                                        title="Login to add to wishlist">
                                        <div class="w-6 h-6 rounded-full bg-red-50 group-hover/wishlist:bg-red-100 transition-colors duration-300 flex items-center justify-center">
                                            <i data-feather="heart" class="w-4 h-4 text-red-500 group-hover/wishlist:scale-110 transition-transform duration-300"></i>
                                        </div>
                                    </a>
                                    <?php endif; ?>
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
                            <div class="p-3 sm:p-6">
                                <div class="text-xs sm:text-sm text-primary-600 font-medium mb-1">
                                    <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></div>
                                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 text-sm sm:text-base">
                                    <?= htmlspecialchars($product['name']) ?></h3>
                                <p class="text-gray-600 text-xs sm:text-sm mb-3 sm:mb-4 line-clamp-2">
                                    <?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...</p>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <?php
                                    $priceInfo = getEffectivePrice($product);
                                    if ($priceInfo['is_discounted']):
                                    ?>
                                    <div class="flex flex-col">
                                        <span class="text-lg sm:text-2xl font-bold text-green-600">KSh
                                            <?= number_format($priceInfo['discounted_price'], 2) ?></span>
                                        <span class="text-sm text-gray-500 line-through">KSh
                                            <?= number_format($priceInfo['original_price'], 2) ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-lg sm:text-2xl font-bold text-gray-900">KSh
                                        <?= number_format($product['price'], 2) ?></span>
                                    <?php endif; ?>
                                    <div class="flex items-center text-yellow-400">
                                        <?php
                                            $pid = $product['id'];
                                            $avg = isset($productRatings[$pid]) && $productRatings[$pid]['avg_rating'] !== null ? round($productRatings[$pid]['avg_rating'], 1) : null;
                                            $count = isset($productRatings[$pid]) ? (int)$productRatings[$pid]['review_count'] : 0;
                                        ?>
                                        <?= render_stars($avg, 12) ?>
                                        <span
                                            class="text-gray-500 text-xs sm:text-sm ml-1 sm:ml-2">(<?= htmlspecialchars(format_rating_text($avg, $count)) ?>)</span>
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
        </div>
    </div>

    <!-- Recently Viewed Products Section -->
    <div class="bg-gradient-to-r from-gray-50 to-blue-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="flex items-center justify-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-xl mr-4">
                        <i data-feather="clock" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Recently Viewed</h2>
                        <p class="text-lg text-gray-600 mt-2">Products you've been browsing</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8">
                <div id="recentlyViewedProducts" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <!-- Recently viewed products will be loaded here via JavaScript -->
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <i data-feather="eye" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i>
                        <p class="text-lg">No recently viewed products</p>
                        <p class="text-sm text-gray-400 mt-2">Start browsing products to see them here</p>
                    </div>
                </div>
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

<!-- Quick View Modal -->
<div id="quickViewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="flex flex-col lg:flex-row">
            <!-- Product Image -->
            <div class="lg:w-1/2 bg-gray-100 flex items-center justify-center p-8">
                <img id="quickViewImage" src="" alt="" class="max-w-full max-h-96 object-contain">
            </div>
            
            <!-- Product Details -->
            <div class="lg:w-1/2 p-8 flex flex-col justify-between">
                <div>
                    <!-- Close Button -->
                    <button onclick="closeQuickView()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                        <i data-feather="x" class="w-6 h-6"></i>
                    </button>
                    
                    <!-- Category -->
                    <div id="quickViewCategory" class="text-sm text-primary-600 font-medium mb-2"></div>
                    
                    <!-- Product Name -->
                    <h2 id="quickViewName" class="text-2xl font-bold text-gray-900 mb-4"></h2>
                    
                    <!-- Price -->
                    <div id="quickViewPrice" class="text-3xl font-bold text-gray-900 mb-4"></div>
                    
                    <!-- Stock Status -->
                    <div id="quickViewStock" class="mb-6"></div>
                    
                    <!-- Description -->
                    <p id="quickViewDescription" class="text-gray-600 mb-6 line-clamp-3"></p>
                </div>
                
                <!-- Actions -->
                <div class="space-y-4">
                    <form action="add_to_cart.php" method="POST" class="flex items-center space-x-4">
                        <input type="hidden" name="product_id" id="quickViewProductId">
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button type="button" onclick="decreaseQuickViewQty()" class="p-2 hover:bg-gray-100">
                                <i data-feather="minus" class="w-4 h-4"></i>
                            </button>
                            <input type="number" id="quickViewQuantity" name="quantity" value="1" min="1" 
                                   class="w-16 text-center border-0 focus:ring-0">
                            <button type="button" onclick="increaseQuickViewQty()" class="p-2 hover:bg-gray-100">
                                <i data-feather="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <button type="submit" 
                                class="flex-1 bg-primary-500 text-white py-3 px-6 rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                            <i data-feather="shopping-cart" class="w-5 h-5 mr-2 inline"></i>
                            Add to Cart
                        </button>
                    </form>
                    
                    <div class="flex space-x-2">
                        <a id="quickViewLink" href="" 
                           class="flex-1 bg-gray-100 text-gray-700 py-3 px-6 rounded-lg font-semibold text-center hover:bg-gray-200 transition-colors">
                            View Full Details
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="wishlist.php" class="flex-1">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" id="quickViewWishlistId">
                            <button type="submit" 
                                    class="w-full bg-red-50 text-red-600 py-3 px-6 rounded-lg font-semibold hover:bg-red-100 transition-colors">
                                <i data-feather="heart" class="w-5 h-5 mr-2 inline"></i>
                                Add to Wishlist
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
    document.getElementById('productsGrid').className = 'grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6';
    document.getElementById('gridView').className = 'p-2 rounded-md bg-primary-100 text-primary-600';
    document.getElementById('listView').className = 'p-2 rounded-md text-gray-400 hover:text-gray-600';
});

document.getElementById('listView')?.addEventListener('click', () => {
    document.getElementById('productsGrid').className = 'grid grid-cols-2 sm:grid-cols-1 gap-4 sm:gap-6';
    document.getElementById('listView').className = 'p-2 rounded-md bg-primary-100 text-primary-600';
    document.getElementById('gridView').className = 'p-2 rounded-md text-gray-400 hover:text-gray-600';
});

// Quick View functionality
function openQuickView(productId) {
    // Find the product data from the current page
    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    if (!productCard) return;
    
    // Get product data from data attributes or make an AJAX call
    // For now, we'll use the existing product data from the page
    const products = <?= json_encode($products) ?>;
    const product = products.find(p => p.id == productId);
    
    if (!product) return;
    
    // Populate modal with product data
    document.getElementById('quickViewImage').src = product.image_url || 'assets/images/placeholder.png';
    document.getElementById('quickViewCategory').textContent = product.category_name || 'Uncategorized';
    document.getElementById('quickViewName').textContent = product.name;
    document.getElementById('quickViewPrice').textContent = `KSh ${parseFloat(product.price).toLocaleString('en-KE', {minimumFractionDigits: 2})}`;
    document.getElementById('quickViewDescription').textContent = product.description;
    document.getElementById('quickViewProductId').value = productId;
    document.getElementById('quickViewWishlistId').value = productId;
    document.getElementById('quickViewLink').href = `product.php?id=${productId}`;
    
    // Stock status
    const stock = parseInt(product.stock) || 0;
    const stockElement = document.getElementById('quickViewStock');
    if (stock > 10) {
        stockElement.innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"><i data-feather="check-circle" class="w-4 h-4 mr-1"></i>In Stock</span>';
    } else if (stock > 0) {
        stockElement.innerHTML = `<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800"><i data-feather="alert-triangle" class="w-4 h-4 mr-1"></i>Only ${stock} left</span>`;
    } else {
        stockElement.innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"><i data-feather="x-circle" class="w-4 h-4 mr-1"></i>Out of Stock</span>';
    }
    
    // Set max quantity
    document.getElementById('quickViewQuantity').max = stock;
    if (stock === 0) {
        document.getElementById('quickViewQuantity').value = 0;
    }
    
    // Show modal
    document.getElementById('quickViewModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Reinitialize Feather icons
    feather.replace();
}

function closeQuickView() {
    document.getElementById('quickViewModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function increaseQuickViewQty() {
    const input = document.getElementById('quickViewQuantity');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQuickViewQty() {
    const input = document.getElementById('quickViewQuantity');
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}

// Close modal on background click
document.getElementById('quickViewModal')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('quickViewModal')) {
        closeQuickView();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeQuickView();
    }
});

// Comparison functionality
function toggleCompare(productId) {
    fetch('compare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}`
    })
    .then(response => response.text())
    .then(() => {
        // Update button state with modern animation
        const btn = document.getElementById(`compare-btn-${productId}`);
        if (btn) {
            // Add success animation
            btn.classList.add('animate-pulse');
            
            // Update content with modern styling
            btn.innerHTML = `
                <div class="flex items-center space-x-2">
                    <div class="w-5 h-5 rounded-full bg-green-100 animate-bounce flex items-center justify-center">
                        <i data-feather="check" class="w-3 h-3 text-green-600"></i>
                    </div>
                    <span class="text-green-700 font-semibold">Added</span>
                </div>
            `;
            
            // Update button styling
            btn.classList.remove('hover:border-orange-300', 'hover:scale-105');
            btn.classList.add('border-green-300', 'bg-green-50/95', 'hover:bg-green-100/95');
            
            // Remove animation after completion
            setTimeout(() => {
                btn.classList.remove('animate-pulse');
            }, 1000);
            
            feather.replace();
        }
        
        // Show notification
        showCompareNotification('Product added to comparison');
    })
    .catch(error => {
        console.error('Error:', error);
        showCompareNotification('Failed to add to comparison', 'error');
    });
}

function showCompareNotification(message, type = 'success') {
    // Create notification element with modern styling
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200/50 p-4 max-w-sm transform transition-all duration-500 translate-x-full ${
        type === 'error' ? 'border-red-200' : 'border-green-200'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-full ${type === 'error' ? 'bg-red-100' : 'bg-green-100'} flex items-center justify-center animate-pulse">
                    <i data-feather="${type === 'error' ? 'alert-circle' : 'check-circle'}" 
                       class="w-4 h-4 ${type === 'error' ? 'text-red-600' : 'text-green-600'}"></i>
                </div>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" 
                    class="ml-auto text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-1 transition-all duration-200">
                <i data-feather="x" class="w-4 h-4"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    feather.replace();
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 4 seconds with animation
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }, 4000);
}

// Recently Viewed Products Functionality
function loadRecentlyViewedProducts() {
    const recentlyViewed = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
    const container = document.getElementById('recentlyViewedProducts');
    
    if (recentlyViewed.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-12 text-gray-500">
                <i data-feather="eye" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i>
                <p class="text-lg">No recently viewed products</p>
                <p class="text-sm text-gray-400 mt-2">Start browsing products to see them here</p>
            </div>
        `;
        feather.replace();
        return;
    }
    
    // Get unique products (remove duplicates)
    const uniqueProducts = recentlyViewed.filter((product, index, self) => 
        index === self.findIndex(p => p.id === product.id)
    ).slice(0, 10); // Show max 10 products for horizontal layout
    
    container.innerHTML = uniqueProducts.map(product => `
        <div class="group bg-white/60 backdrop-blur-sm rounded-2xl p-4 hover:bg-white/80 transition-all duration-300 border border-white/30 hover:shadow-xl hover:-translate-y-1">
            <a href="product.php?id=${product.id}" class="block">
                <div class="relative mb-4">
                    <img src="${product.image}" alt="${product.name}" 
                         class="w-full h-32 sm:h-40 object-cover rounded-xl shadow-md group-hover:shadow-lg transition-all duration-300">
                    <div class="absolute top-2 right-2 w-6 h-6 bg-primary-500 text-white text-xs rounded-full flex items-center justify-center font-bold shadow-lg">
                        <i data-feather="clock" class="w-3 h-3"></i>
                    </div>
                </div>
                <div class="text-center">
                    <h4 class="text-sm font-semibold text-gray-900 group-hover:text-primary-600 transition-colors duration-300 line-clamp-2 mb-2">
                        ${product.name}
                    </h4>
                    <p class="text-lg font-bold text-primary-600 mb-1">KSh ${product.price.toLocaleString()}</p>
                    <p class="text-xs text-gray-500">Viewed ${product.viewedAt}</p>
                </div>
            </a>
        </div>
    `).join('');
    
    feather.replace();
}

function addToRecentlyViewed(productId, productName, productPrice, productImage) {
    const recentlyViewed = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
    
    // Remove if already exists
    const filtered = recentlyViewed.filter(p => p.id !== productId);
    
    // Add to beginning
    const newProduct = {
        id: productId,
        name: productName,
        price: productPrice,
        image: productImage,
        viewedAt: new Date().toLocaleDateString()
    };
    
    filtered.unshift(newProduct);
    
    // Keep only last 10 products
    const updated = filtered.slice(0, 10);
    
    localStorage.setItem('recentlyViewed', JSON.stringify(updated));
    loadRecentlyViewedProducts();
}

// Track product views
document.addEventListener('DOMContentLoaded', function() {
    loadRecentlyViewedProducts();
    
    // Track clicks on product cards
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function(e) {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName || this.querySelector('h3')?.textContent || '';
            const productPrice = parseFloat(this.dataset.productPrice) || 0;
            const productImage = this.dataset.productImage || this.querySelector('img')?.src || '';
            
            addToRecentlyViewed(productId, productName, productPrice, productImage);
        });
    });
});

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>