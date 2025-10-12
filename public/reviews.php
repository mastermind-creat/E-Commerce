<?php
// public/reviews.php - Product Reviews Management
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Set page title
$pageTitle = 'My Reviews';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=reviews.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reviewId = intval($_POST['review_id'] ?? 0);
    $productId = intval($_POST['product_id'] ?? 0);
    
    if ($action === 'delete' && $reviewId > 0) {
        try {
            // Verify the review belongs to the current user
            $checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$reviewId, $userId]);
            
            if ($checkStmt->fetch()) {
                $deleteStmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
                $deleteStmt->execute([$reviewId]);
                $_SESSION['success'] = "Review deleted successfully!";
            } else {
                $_SESSION['error'] = "Review not found or you don't have permission to delete it.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Something went wrong: " . $e->getMessage();
        }
    }
    
    // Redirect to prevent resubmission
    header("Location: reviews.php");
    exit;
}

// Get user's reviews
try {
    $reviewsStmt = $pdo->prepare("
        SELECT r.*, p.name as product_name, p.image_url, c.name as category_name,
               COALESCE(
                   (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
                   (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.id LIMIT 1)
               ) AS product_image
        FROM reviews r
        JOIN products p ON r.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $reviewsStmt->execute([$userId]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reviews = [];
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Reviews</h1>
                    <p class="text-gray-600 mt-2">Manage your product reviews and ratings</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">
                        <?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?>
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

        <?php if (empty($reviews)): ?>
        <!-- Empty Reviews -->
        <div class="text-center py-16">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-feather="star" class="w-12 h-12 text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No reviews yet</h3>
            <p class="text-gray-600 mb-8">You haven't written any product reviews yet.</p>
            <a href="shop.php" class="inline-flex items-center px-6 py-3 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-colors">
                <i data-feather="shopping-bag" class="w-5 h-5 mr-2"></i>
                Start Shopping
            </a>
        </div>
        <?php else: ?>
        <!-- Reviews List -->
        <div class="space-y-6">
            <?php foreach ($reviews as $review): ?>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex flex-col lg:flex-row lg:items-start space-y-4 lg:space-y-0 lg:space-x-6">
                    <!-- Product Image -->
                    <div class="flex-shrink-0">
                        <a href="product.php?id=<?= $review['product_id'] ?>" class="block">
                            <?php
                                $imgUrl = product_image_url($review['product_image'] ?? null);
                            ?>
                            <img src="<?= htmlspecialchars($imgUrl) ?>" 
                                 alt="<?= htmlspecialchars($review['product_name']) ?>"
                                 class="w-20 h-20 object-cover rounded-lg"
                                 onerror="this.src='<?= htmlspecialchars(product_image_url(null)) ?>'">
                        </a>
                    </div>
                    
                    <!-- Review Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                <!-- Product Name -->
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    <a href="product.php?id=<?= $review['product_id'] ?>" 
                                       class="hover:text-primary-600 transition-colors">
                                        <?= htmlspecialchars($review['product_name']) ?>
                                    </a>
                                </h3>
                                
                                <!-- Category -->
                                <p class="text-sm text-gray-500 mb-3">
                                    <?= htmlspecialchars($review['category_name'] ?? 'Uncategorized') ?>
                                </p>
                                
                                <!-- Rating -->
                                <div class="flex items-center space-x-2 mb-3">
                                    <div class="flex items-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i data-feather="star" 
                                           class="w-4 h-4 <?= $i <= $review['rating'] ? 'text-yellow-400 fill-current' : 'text-gray-300' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-gray-600">
                                        <?= $review['rating'] ?>/5 stars
                                    </span>
                                </div>
                                
                                <!-- Review Comment -->
                                <?php if (!empty($review['comment'])): ?>
                                <p class="text-gray-700 mb-4"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                <?php endif; ?>
                                
                                <!-- Review Date -->
                                <p class="text-sm text-gray-500">
                                    Reviewed on <?= date('F j, Y', strtotime($review['created_at'])) ?>
                                </p>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center space-x-2 mt-4 sm:mt-0">
                                <a href="product.php?id=<?= $review['product_id'] ?>" 
                                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                    View Product
                                </a>
                                <form method="POST" class="inline" 
                                      onsubmit="return confirm('Are you sure you want to delete this review?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                    <button type="submit" 
                                            class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium">
                                        <i data-feather="trash-2" class="w-4 h-4 mr-1 inline"></i>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
