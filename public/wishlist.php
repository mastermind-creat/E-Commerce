<?php
// public/wishlist.php - Wishlist Management
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Set page title
$pageTitle = 'Wishlist';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=wishlist.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Handle wishlist actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = intval($_POST['product_id'] ?? 0);
    
    if ($productId > 0) {
        try {
            if ($action === 'add') {
                // Check if already in wishlist
                $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                $checkStmt->execute([$userId, $productId]);
                
                if (!$checkStmt->fetch()) {
                    $insertStmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
                    $insertStmt->execute([$userId, $productId]);
                    $_SESSION['success'] = "Product added to wishlist!";
                } else {
                    $_SESSION['info'] = "Product already in wishlist!";
                }
            } elseif ($action === 'remove') {
                $deleteStmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $deleteStmt->execute([$userId, $productId]);
                $_SESSION['success'] = "Product removed from wishlist!";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Something went wrong: " . $e->getMessage();
        }
    }
    
    // Redirect to prevent resubmission
    header("Location: wishlist.php");
    exit;
}

// Get wishlist items
try {
    $wishlistStmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, w.created_at as added_date,
               COALESCE(
                   (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
                   (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.id LIMIT 1)
               ) AS image_url
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE w.user_id = ? AND p.status = 'active'
        ORDER BY w.created_at DESC
    ");
    $wishlistStmt->execute([$userId]);
    $wishlistItems = $wishlistStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $wishlistItems = [];
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Wishlist</h1>
                    <p class="text-gray-600 mt-2">Your saved favorite products</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">
                        <?= count($wishlistItems) ?> item<?= count($wishlistItems) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!empty($_SESSION['success'])): ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <i data-feather="check-circle" class="w-5 h-5 text-green-600 mr-2"></i>
                <span class="text-green-800"><?= htmlspecialchars($_SESSION['success']) ?></span>
            </div>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i data-feather="alert-circle" class="w-5 h-5 text-red-600 mr-2"></i>
                <span class="text-red-800"><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <?php if (!empty($_SESSION['info'])): ?>
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <i data-feather="info" class="w-5 h-5 text-blue-600 mr-2"></i>
                <span class="text-blue-800"><?= htmlspecialchars($_SESSION['info']) ?></span>
            </div>
        </div>
        <?php unset($_SESSION['info']); endif; ?>

        <?php if (empty($wishlistItems)): ?>
        <!-- Empty Wishlist -->
        <div class="text-center py-16">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-feather="heart" class="w-12 h-12 text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Your wishlist is empty</h3>
            <p class="text-gray-600 mb-8">Start adding products you love to your wishlist!</p>
            <a href="shop.php" class="inline-flex items-center px-6 py-3 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-colors">
                <i data-feather="shopping-bag" class="w-5 h-5 mr-2"></i>
                Start Shopping
            </a>
        </div>
        <?php else: ?>
        <!-- Wishlist Items -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($wishlistItems as $item): ?>
            <div class="group bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden">
                <!-- Product Image -->
                <div class="relative overflow-hidden">
                    <a href="product.php?id=<?= $item['id'] ?>" class="block">
                        <?php
                            $imgUrl = product_image_url($item['image_url'] ?? null);
                            $imgFile = __DIR__ . '/assets/products/' . ($item['image_url'] ?? '');
                            if ($item['image_url']) {
                                $maybe = preg_replace('#^assets/products/#', '', $item['image_url']);
                                $imgFile = __DIR__ . '/assets/products/' . $maybe;
                            }
                            $imgExists = is_file($imgFile);
                        ?>
                        <img src="<?= htmlspecialchars($imgUrl) ?>" 
                             alt="<?= htmlspecialchars($item['name']) ?>"
                             class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                             onerror="this.src='<?= htmlspecialchars(product_image_url(null)) ?>'">
                    </a>
                    
                    <!-- Remove from wishlist button -->
                    <form method="POST" class="absolute top-3 right-3">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                        <button type="submit" 
                                class="bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all duration-200 hover:scale-110"
                                onclick="return confirm('Remove from wishlist?')"
                                title="Remove from wishlist">
                            <i data-feather="x" class="w-4 h-4 text-gray-600"></i>
                        </button>
                    </form>
                    
                    <!-- Quick add to cart -->
                    <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" 
                                    class="bg-primary-500 hover:bg-primary-600 text-white rounded-full p-2 shadow-lg transition-all duration-200 hover:scale-110"
                                    title="Add to cart">
                                <i data-feather="shopping-cart" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="p-4">
                    <div class="text-sm text-primary-600 font-medium mb-1">
                        <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                        <a href="product.php?id=<?= $item['id'] ?>" class="hover:text-primary-600 transition-colors">
                            <?= htmlspecialchars($item['name']) ?>
                        </a>
                    </h3>
                    
                    <!-- Price -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-lg font-bold text-gray-900">
                            KSh <?= number_format($item['price'], 2) ?>
                        </span>
                        <span class="text-xs text-gray-500">
                            Added <?= date('M j', strtotime($item['added_date'])) ?>
                        </span>
                    </div>
                    
                    <!-- Stock Status -->
                    <div class="mb-4">
                        <?php $stock = (int)($item['stock'] ?? 0); ?>
                        <?php if ($stock > 10): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i data-feather="check-circle" class="w-3 h-3 mr-1"></i>
                            In Stock
                        </span>
                        <?php elseif ($stock > 0): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            <i data-feather="alert-triangle" class="w-3 h-3 mr-1"></i>
                            Low Stock
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i data-feather="x-circle" class="w-3 h-3 mr-1"></i>
                            Out of Stock
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-2">
                        <a href="product.php?id=<?= $item['id'] ?>" 
                           class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-lg text-sm font-medium text-center transition-colors">
                            View Details
                        </a>
                        <?php if ($stock > 0): ?>
                        <form action="add_to_cart.php" method="POST" class="flex-1">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" 
                                    class="w-full bg-primary-500 hover:bg-primary-600 text-white py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                                Add to Cart
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
</style>

<script>
// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
