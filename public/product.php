<?php
// public/product.php - Advanced Product Detail Page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/discount_functions.php';
require_once __DIR__ . '/../includes/functions.php';

$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$productStmt = $pdo->prepare('
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           p.discount_percentage, p.discount_start_date, p.discount_end_date, p.is_discounted
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = "active"
');
$productStmt->execute([$productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

// Fetch product images
$imagesStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC');
$imagesStmt->execute([$productId]);
$images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch product variants
$variantsStmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC');
$variantsStmt->execute([$productId]);
$variants = $variantsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get related products with their primary images
$relatedStmt = $pdo->prepare('
    SELECT p.*, 
           COALESCE(pi.image_url, (SELECT image_url FROM product_images WHERE product_id = p.id LIMIT 1)) as image_url
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.category_id = ? AND p.id != ? AND p.status = "active" 
    ORDER BY RAND() 
    LIMIT 4
');
$relatedStmt->execute([$product['category_id'], $productId]);
$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

// Get reviews
$reviewsStmt = $pdo->prepare('
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 10
');
$reviewsStmt->execute([$productId]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avgRatingStmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ?');
$avgRatingStmt->execute([$productId]);
$ratingData = $avgRatingStmt->fetch(PDO::FETCH_ASSOC);
$avgRating = round((float)($ratingData['avg_rating'] ?? 0), 1);
$totalReviews = (int)($ratingData['total_reviews'] ?? 0);

// Set page title
$pageTitle = $product['name'];

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="index.php" class="text-gray-500 hover:text-primary-600">Home</a>
                <i data-feather="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <a href="shop.php" class="text-gray-500 hover:text-primary-600">Shop</a>
                <?php if ($product['category_slug']): ?>
                <i data-feather="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <a href="shop.php?category=<?= urlencode($product['category_slug']) ?>"
                    class="text-gray-500 hover:text-primary-600">
                    <?= htmlspecialchars($product['category_name']) ?>
                </a>
                <?php endif; ?>
                <i data-feather="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <span class="text-gray-900 font-medium"><?= htmlspecialchars($product['name']) ?></span>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Product Images -->
            <div class="space-y-4">
                <!-- Main Image with Zoom -->
                <div class="aspect-square bg-white rounded-2xl overflow-hidden shadow-lg relative group">
                    <?php if (!empty($images)): ?>
                    <div class="relative overflow-hidden h-full">
                        <img id="mainImage" src="assets/products/<?= htmlspecialchars($images[0]['image_url']) ?>"
                            alt="<?= htmlspecialchars($product['name']) ?>"
                            class="w-full h-full object-cover cursor-zoom-in transition-transform duration-300 group-hover:scale-105"
                            onerror="this.src='assets/images/placeholder.png'">
                        
                        <!-- Zoom overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <i data-feather="zoom-in" class="w-8 h-8 text-white"></i>
                            </div>
                        </div>
                        
                        <!-- Zoom indicator -->
                        <div class="absolute top-4 right-4 bg-white bg-opacity-90 rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <i data-feather="zoom-in" class="w-4 h-4 text-gray-600"></i>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <i data-feather="image" class="w-16 h-16 text-gray-400"></i>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Thumbnail Images -->
                <?php if (count($images) > 1): ?>
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach ($images as $index => $image): ?>
                    <button
                        class="thumbnail-btn aspect-square bg-white rounded-lg overflow-hidden border-2 <?= $index === 0 ? 'border-primary-500' : 'border-gray-200' ?> hover:border-primary-300 transition-colors"
                        data-image="assets/products/<?= htmlspecialchars($image['image_url']) ?>">
                        <img src="assets/products/<?= htmlspecialchars($image['image_url']) ?>"
                            alt="Thumbnail <?= $index + 1 ?>" class="w-full h-full object-cover"
                            onerror="this.src='assets/images/placeholder.png'">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Details -->
            <div class="space-y-6">
                <!-- Product Info -->
                <div>
                    <div class="text-sm text-primary-600 font-medium mb-2">
                        <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                        <?= htmlspecialchars($product['name']) ?></h1>

                    <!-- Rating -->
                    <div class="flex items-center space-x-2 mb-4">
                        <?= render_stars($avgRating, 18) ?>
                        <span
                            class="text-gray-600"><?= htmlspecialchars(format_rating_text($avgRating, $totalReviews)) ?></span>
                    </div>

                    <!-- Price -->
                    <?php
                    $priceInfo = getEffectivePrice($product);
                    if ($priceInfo['is_discounted']):
                    ?>
                    <div class="mb-6">
                        <div class="flex items-center space-x-4">
                            <span class="text-4xl font-bold text-green-600">KSh
                                <?= number_format($priceInfo['discounted_price'], 2) ?>
                            </span>
                            <span class="text-2xl text-gray-500 line-through">KSh
                                <?= number_format($priceInfo['original_price'], 2) ?>
                            </span>
                        </div>
                        <div class="mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <i data-feather="tag" class="w-4 h-4 mr-1"></i>
                                Save KSh <?= number_format($priceInfo['discount_amount'], 2) ?> (<?= number_format($priceInfo['savings_percentage'], 0) ?>% off)
                            </span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-4xl font-bold text-gray-900 mb-6">KSh
                        <?= number_format((float)($product['price'] ?? 0), 2) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Stock Status -->
                    <div class="mb-6">
                        <?php $prodStock = (int)($product['stock'] ?? 0); ?>
                        <?php if ($prodStock > 10): ?>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i data-feather="check-circle" class="w-4 h-4 mr-1"></i>
                            In Stock (<?= $prodStock ?> available)
                        </span>
                        <?php elseif ($prodStock > 0): ?>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                            <i data-feather="alert-triangle" class="w-4 h-4 mr-1"></i>
                            Only <?= $prodStock ?> left in stock
                        </span>
                        <?php else: ?>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <i data-feather="x-circle" class="w-4 h-4 mr-1"></i>
                            Out of Stock
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Description -->
                <div class="prose max-w-none">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>

                <!-- Variants -->
                <?php if (!empty($variants)): ?>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Options</h3>
                    <div class="space-y-4">
                        <?php foreach ($variants as $variant): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">
                                        <?= htmlspecialchars($variant['color'] ?? 'Color') ?>
                                        <?php if (!empty($variant['size'])): ?>
                                        - <?= htmlspecialchars($variant['size']) ?>
                                        <?php endif; ?>
                                    </h4>
                                    <p class="text-sm text-gray-600">Stock: <?= (int)($variant['stock'] ?? 0) ?></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-gray-900">
                                        <?php $extra = (float)($variant['extra_price'] ?? 0); ?>
                                        KSh <?= number_format(((float)($product['price'] ?? 0)) + $extra, 2) ?>
                                    </div>
                                    <?php if ($extra > 0): ?>
                                    <div class="text-sm text-gray-500">+KSh
                                        <?= number_format($extra, 2) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Add to Cart Form -->
                <form action="add_to_cart.php" method="POST" class="space-y-4">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div class="flex items-center space-x-4">
                        <label for="quantity" class="text-sm font-medium text-gray-700">Quantity:</label>
                        <div class="flex items-center bg-white border-2 border-gray-200 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300">
                            <button type="button" id="decreaseQty" 
                                    class="group/btn p-3 hover:bg-gray-100 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                <div class="w-5 h-5 rounded-full bg-gray-100 group-hover/btn:bg-gray-200 transition-colors duration-300 flex items-center justify-center">
                                    <i data-feather="minus" class="w-3 h-3 text-gray-600 group-hover/btn:scale-110 transition-transform duration-300"></i>
                                </div>
                            </button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $prodStock ?>"
                                class="w-16 text-center border-0 focus:ring-0 bg-transparent font-semibold text-gray-900">
                            <button type="button" id="increaseQty" 
                                    class="group/btn p-3 hover:bg-gray-100 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                <div class="w-5 h-5 rounded-full bg-gray-100 group-hover/btn:bg-gray-200 transition-colors duration-300 flex items-center justify-center">
                                    <i data-feather="plus" class="w-3 h-3 text-gray-600 group-hover/btn:scale-110 transition-transform duration-300"></i>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit"
                            class="group/cart flex-1 bg-gradient-to-r from-primary-500 to-primary-600 text-white py-4 px-6 rounded-xl font-semibold hover:from-primary-600 hover:to-primary-700 hover:scale-105 transition-all duration-300 disabled:bg-gray-300 disabled:cursor-not-allowed disabled:hover:scale-100 shadow-lg hover:shadow-xl"
                            <?= $prodStock == 0 ? 'disabled' : '' ?>>
                            <div class="flex items-center justify-center space-x-2">
                                <div class="w-6 h-6 rounded-full bg-white/20 group-hover/cart:bg-white/30 transition-colors duration-300 flex items-center justify-center">
                                    <i data-feather="shopping-cart" class="w-4 h-4 group-hover/cart:scale-110 transition-transform duration-300"></i>
                                </div>
                                <span><?= $prodStock > 0 ? 'Add to Cart' : 'Out of Stock' ?></span>
                            </div>
                        </button>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="wishlist.php" class="inline">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit"
                                class="group/wishlist px-6 py-4 bg-white border-2 border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50 hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl"
                                title="Add to wishlist">
                                <div class="w-6 h-6 rounded-full bg-red-50 group-hover/wishlist:bg-red-100 transition-colors duration-300 flex items-center justify-center">
                                    <i data-feather="heart" class="w-4 h-4 text-red-500 group-hover/wishlist:scale-110 transition-transform duration-300"></i>
                                </div>
                            </button>
                        </form>
                        <?php else: ?>
                        <a href="login.php?redirect=product.php?id=<?= $product['id'] ?>"
                            class="group/wishlist px-6 py-4 bg-white border-2 border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50 hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl"
                            title="Login to add to wishlist">
                            <div class="w-6 h-6 rounded-full bg-red-50 group-hover/wishlist:bg-red-100 transition-colors duration-300 flex items-center justify-center">
                                <i data-feather="heart" class="w-4 h-4 text-red-500 group-hover/wishlist:scale-110 transition-transform duration-300"></i>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Product Features -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Features</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i data-feather="check" class="w-4 h-4 text-green-500 mr-2"></i>
                            High quality materials
                        </li>
                        <li class="flex items-center">
                            <i data-feather="check" class="w-4 h-4 text-green-500 mr-2"></i>
                            Fast and secure delivery
                        </li>
                        <li class="flex items-center">
                            <i data-feather="check" class="w-4 h-4 text-green-500 mr-2"></i>
                            30-day return policy
                        </li>
                        <li class="flex items-center">
                            <i data-feather="check" class="w-4 h-4 text-green-500 mr-2"></i>
                            Customer support
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="mt-16">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Customer Reviews</h2>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <?= render_stars($avgRating, 20) ?>
                            <span class="text-lg font-semibold text-gray-900"><?= $avgRating ?></span>
                        </div>
                        <span class="text-gray-600">(<?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?>)</span>
                    </div>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="toggleReviewForm()" 
                        class="mt-4 lg:mt-0 px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-medium">
                    <i data-feather="edit-3" class="w-4 h-4 mr-2 inline"></i>
                    Write a Review
                </button>
                <?php else: ?>
                <a href="login.php?redirect=product.php?id=<?= $productId ?>" 
                   class="mt-4 lg:mt-0 px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-medium">
                    <i data-feather="log-in" class="w-4 h-4 mr-2 inline"></i>
                    Login to Review
                </a>
                <?php endif; ?>
            </div>

            <!-- Review Form (Hidden by default) -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div id="reviewForm" class="hidden mb-8">
                <?php include 'review_form.php'; ?>
            </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <?php if (!empty($reviews)): ?>
            <div class="space-y-6">
                <?php foreach ($reviews as $review): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-start space-x-4">
                        <!-- User Avatar -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-primary-100 to-pink-100 rounded-full flex items-center justify-center">
                                <i data-feather="user" class="w-6 h-6 text-primary-600"></i>
                            </div>
                        </div>
                        
                        <!-- Review Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($review['user_name']) ?></h4>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <div class="flex items-center">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i data-feather="star"
                                                class="w-4 h-4 <?= $i <= $review['rating'] ? 'text-yellow-400 fill-current' : 'text-gray-300' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-sm text-gray-600"><?= $review['rating'] ?>/5</span>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500 mt-1 sm:mt-0">
                                    <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                </div>
                            </div>
                            
                            <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- No Reviews State -->
            <div class="text-center py-12 bg-white rounded-2xl shadow-lg">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-feather="star" class="w-8 h-8 text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No reviews yet</h3>
                <p class="text-gray-600 mb-6">Be the first to review this product!</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="toggleReviewForm()" 
                        class="px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-medium">
                    <i data-feather="edit-3" class="w-4 h-4 mr-2 inline"></i>
                    Write the First Review
                </button>
                <?php else: ?>
                <a href="login.php?redirect=product.php?id=<?= $productId ?>" 
                   class="px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-medium">
                    <i data-feather="log-in" class="w-4 h-4 mr-2 inline"></i>
                    Login to Review
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">You Might Also Like</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($relatedProducts as $related): ?>
                <div
                    class="group bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden">
                    <a href="product.php?id=<?= $related['id'] ?>" class="block">
                        <div class="relative overflow-hidden">
                            <?php
                                $imgUrl = product_image_url($related['image_url'] ?? null);
                                // Server-side path to check file existence (public folder)
                                $imgFile = __DIR__ . '/' . ($related['image_url'] ?? '');
                                if ($related['image_url']) {
                                    // normalize if stored with assets/products/ prefix
                                    $maybe = preg_replace('#^assets/products/#', '', $related['image_url']);
                                    $imgFile = __DIR__ . '/assets/products/' . $maybe;
                                }
                                $imgExists = is_file($imgFile);
                            ?>
                            <!-- IMG-DEBUG: resolved="<?= htmlspecialchars($imgUrl) ?>" file="<?= htmlspecialchars($imgFile) ?>" exists="<?= $imgExists ? '1' : '0' ?>" -->
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($related['name']) ?>"
                                data-img-resolved="<?= htmlspecialchars($imgUrl) ?>"
                                data-img-file="<?= htmlspecialchars($imgFile) ?>"
                                data-img-exists="<?= $imgExists ? '1' : '0' ?>"
                                class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                                onerror="this.src='<?= htmlspecialchars(product_image_url(null)) ?>'">
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                <?= htmlspecialchars($related['name']) ?></h3>
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-gray-900">KSh
                                    <?= number_format($related['price'], 2) ?></span>
                                <div class="flex items-center text-yellow-400">
                                    <?php
                                        // attempt to show rating for related product
                                        $rAvg = null; $rCount = 0;
                                        try {
                                            $rs = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ?');
                                            $rs->execute([$related['id']]);
                                            $rd = $rs->fetch(PDO::FETCH_ASSOC);
                                            if ($rd) { $rAvg = round((float)($rd['avg_rating'] ?? 0), 1); $rCount = (int)($rd['total_reviews'] ?? 0); }
                                        } catch (Exception $e) {}
                                    ?>
                                    <?= render_stars($rAvg, 14) ?>
                                    <span
                                        class="text-gray-500 text-sm ml-2"><?= htmlspecialchars(format_rating_text($rAvg, $rCount)) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Enhanced Image Modal with Zoom -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4">
    <div class="relative w-full h-full flex items-center justify-center">
        <!-- Close button -->
        <button id="closeModal" class="absolute top-4 right-4 text-white hover:text-gray-300 z-20 bg-black bg-opacity-50 rounded-full p-2">
            <i data-feather="x" class="w-6 h-6"></i>
        </button>
        
        <!-- Zoom controls -->
        <div class="absolute top-4 left-4 z-20 flex space-x-2">
            <button id="zoomIn" class="bg-black bg-opacity-50 text-white rounded-full p-2 hover:bg-opacity-70">
                <i data-feather="zoom-in" class="w-5 h-5"></i>
            </button>
            <button id="zoomOut" class="bg-black bg-opacity-50 text-white rounded-full p-2 hover:bg-opacity-70">
                <i data-feather="zoom-out" class="w-5 h-5"></i>
            </button>
            <button id="resetZoom" class="bg-black bg-opacity-50 text-white rounded-full p-2 hover:bg-opacity-70">
                <i data-feather="rotate-cw" class="w-5 h-5"></i>
            </button>
        </div>
        
        <!-- Image container with zoom functionality -->
        <div id="imageContainer" class="relative overflow-hidden max-w-full max-h-full cursor-move">
            <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain transition-transform duration-200">
        </div>
        
        <!-- Navigation arrows for multiple images -->
        <?php if (count($images) > 1): ?>
        <button id="prevImage" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 z-20 bg-black bg-opacity-50 rounded-full p-2">
            <i data-feather="chevron-left" class="w-6 h-6"></i>
        </button>
        <button id="nextImage" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 z-20 bg-black bg-opacity-50 rounded-full p-2">
            <i data-feather="chevron-right" class="w-6 h-6"></i>
        </button>
        <?php endif; ?>
        
        <!-- Image counter -->
        <?php if (count($images) > 1): ?>
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
            <span id="imageCounter">1</span> / <?= count($images) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript -->
<script>
// Image gallery functionality
const mainImage = document.getElementById('mainImage');
const thumbnailBtns = document.querySelectorAll('.thumbnail-btn');
const imageModal = document.getElementById('imageModal');
const modalImage = document.getElementById('modalImage');
const closeModal = document.getElementById('closeModal');
const imageContainer = document.getElementById('imageContainer');
const zoomIn = document.getElementById('zoomIn');
const zoomOut = document.getElementById('zoomOut');
const resetZoom = document.getElementById('resetZoom');
const prevImage = document.getElementById('prevImage');
const nextImage = document.getElementById('nextImage');
const imageCounter = document.getElementById('imageCounter');

// Zoom functionality
let currentZoom = 1;
let isDragging = false;
let dragStart = { x: 0, y: 0 };
let imagePosition = { x: 0, y: 0 };

// Get all image sources for navigation
const imageSources = Array.from(thumbnailBtns).map(btn => btn.dataset.image);
let currentImageIndex = 0;

// Thumbnail click handler
thumbnailBtns.forEach((btn, index) => {
    btn.addEventListener('click', () => {
        const imageSrc = btn.dataset.image;
        mainImage.src = imageSrc;
        currentImageIndex = index;

        // Update active thumbnail
        thumbnailBtns.forEach(b => b.classList.remove('border-primary-500'));
        thumbnailBtns.forEach(b => b.classList.add('border-gray-200'));
        btn.classList.remove('border-gray-200');
        btn.classList.add('border-primary-500');
    });
});

// Main image click to open modal
mainImage.addEventListener('click', () => {
    openImageModal();
});

// Open image modal
function openImageModal() {
    modalImage.src = mainImage.src;
    currentImageIndex = imageSources.indexOf(mainImage.src);
    imageModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    resetImageZoom();
    updateImageCounter();
}

// Close modal
function closeImageModal() {
    imageModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    resetImageZoom();
}

closeModal.addEventListener('click', closeImageModal);

// Close modal on background click
imageModal.addEventListener('click', (e) => {
    if (e.target === imageModal) {
        closeImageModal();
    }
});

// Zoom controls
zoomIn.addEventListener('click', () => {
    currentZoom = Math.min(currentZoom * 1.2, 5);
    updateImageTransform();
});

zoomOut.addEventListener('click', () => {
    currentZoom = Math.max(currentZoom / 1.2, 0.5);
    updateImageTransform();
});

resetZoom.addEventListener('click', () => {
    resetImageZoom();
});

// Reset zoom and position
function resetImageZoom() {
    currentZoom = 1;
    imagePosition = { x: 0, y: 0 };
    updateImageTransform();
}

// Update image transform
function updateImageTransform() {
    modalImage.style.transform = `scale(${currentZoom}) translate(${imagePosition.x}px, ${imagePosition.y}px)`;
}

// Image navigation
if (prevImage && nextImage) {
    prevImage.addEventListener('click', () => {
        currentImageIndex = (currentImageIndex - 1 + imageSources.length) % imageSources.length;
        modalImage.src = imageSources[currentImageIndex];
        mainImage.src = imageSources[currentImageIndex];
        updateActiveThumbnail();
        updateImageCounter();
        resetImageZoom();
    });

    nextImage.addEventListener('click', () => {
        currentImageIndex = (currentImageIndex + 1) % imageSources.length;
        modalImage.src = imageSources[currentImageIndex];
        mainImage.src = imageSources[currentImageIndex];
        updateActiveThumbnail();
        updateImageCounter();
        resetImageZoom();
    });
}

// Update active thumbnail
function updateActiveThumbnail() {
    thumbnailBtns.forEach((btn, index) => {
        btn.classList.remove('border-primary-500');
        btn.classList.add('border-gray-200');
        if (index === currentImageIndex) {
            btn.classList.remove('border-gray-200');
            btn.classList.add('border-primary-500');
        }
    });
}

// Update image counter
function updateImageCounter() {
    if (imageCounter) {
        imageCounter.textContent = currentImageIndex + 1;
    }
}

// Drag functionality for zoomed images
imageContainer.addEventListener('mousedown', (e) => {
    if (currentZoom > 1) {
        isDragging = true;
        dragStart = { x: e.clientX - imagePosition.x, y: e.clientY - imagePosition.y };
        imageContainer.style.cursor = 'grabbing';
    }
});

document.addEventListener('mousemove', (e) => {
    if (isDragging && currentZoom > 1) {
        imagePosition.x = e.clientX - dragStart.x;
        imagePosition.y = e.clientY - dragStart.y;
        updateImageTransform();
    }
});

document.addEventListener('mouseup', () => {
    isDragging = false;
    imageContainer.style.cursor = 'move';
});

// Touch support for mobile
let touchStart = { x: 0, y: 0 };
let lastTouch = { x: 0, y: 0 };

imageContainer.addEventListener('touchstart', (e) => {
    if (currentZoom > 1) {
        const touch = e.touches[0];
        touchStart = { x: touch.clientX, y: touch.clientY };
        lastTouch = { x: touch.clientX, y: touch.clientY };
    }
});

imageContainer.addEventListener('touchmove', (e) => {
    if (currentZoom > 1) {
        e.preventDefault();
        const touch = e.touches[0];
        const deltaX = touch.clientX - lastTouch.x;
        const deltaY = touch.clientY - lastTouch.y;
        
        imagePosition.x += deltaX;
        imagePosition.y += deltaY;
        updateImageTransform();
        
        lastTouch = { x: touch.clientX, y: touch.clientY };
    }
});

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (imageModal.classList.contains('hidden')) return;
    
    switch(e.key) {
        case 'Escape':
            closeImageModal();
            break;
        case 'ArrowLeft':
            if (prevImage) prevImage.click();
            break;
        case 'ArrowRight':
            if (nextImage) nextImage.click();
            break;
        case '+':
        case '=':
            zoomIn.click();
            break;
        case '-':
            zoomOut.click();
            break;
        case '0':
            resetZoom.click();
            break;
    }
});

// Quantity controls
const quantityInput = document.getElementById('quantity');
const decreaseBtn = document.getElementById('decreaseQty');
const increaseBtn = document.getElementById('increaseQty');

decreaseBtn.addEventListener('click', () => {
    const currentValue = parseInt(quantityInput.value);
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
    }
});

increaseBtn.addEventListener('click', () => {
    const currentValue = parseInt(quantityInput.value);
    const maxValue = parseInt(quantityInput.max);
    if (currentValue < maxValue) {
        quantityInput.value = currentValue + 1;
    }
});

// Review form toggle
function toggleReviewForm() {
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.classList.toggle('hidden');
        if (!reviewForm.classList.contains('hidden')) {
            reviewForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>