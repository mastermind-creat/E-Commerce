<?php
// public/review_form.php - Review Form Component
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

$productId = intval($_GET['product_id'] ?? 0);
$userId = $_SESSION['user_id'] ?? null;

if (!$productId) {
    header('Location: index.php');
    exit;
}

// Check if user is logged in
if (!$userId) {
    header("Location: login.php?redirect=product.php?id=" . $productId);
    exit;
}

// Validate that user has a completed order for this product
$canReview = false;
$productName = '';
try {
    // Check if user has a completed order containing this product
    $orderCheckStmt = $pdo->prepare("
        SELECT DISTINCT o.id, p.name 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE o.user_id = ? 
        AND oi.product_id = ? 
        AND COALESCE(o.order_status, o.status) = 'Completed'
    ");
    $orderCheckStmt->execute([$userId, $productId]);
    $completedOrder = $orderCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($completedOrder) {
        $canReview = true;
        $productName = $completedOrder['name'];
    }
} catch (Exception $e) {
    // Handle error
}

// Get product details
try {
    $productStmt = $pdo->prepare('SELECT id, name FROM products WHERE id = ? AND status = "active"');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// Check if user has already reviewed this product
$existingReview = null;
try {
    $reviewStmt = $pdo->prepare('SELECT * FROM reviews WHERE user_id = ? AND product_id = ?');
    $reviewStmt->execute([$userId, $productId]);
    $existingReview = $reviewStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle error
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canReview) {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a valid rating (1-5 stars).";
    } elseif (empty($comment)) {
        $error = "Please write a review comment.";
    } else {
        try {
            if ($existingReview) {
                // Update existing review
                $updateStmt = $pdo->prepare('UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?');
                $updateStmt->execute([$rating, $comment, $existingReview['id']]);
                $success = "Review updated successfully!";
            } else {
                // Create new review
                $insertStmt = $pdo->prepare('INSERT INTO reviews (user_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())');
                $insertStmt->execute([$userId, $productId, $rating, $comment]);
                $success = "Review submitted successfully!";
            }
            
            // Refresh the page to show updated review
            header("Location: product.php?id=" . $productId);
            exit;
        } catch (Exception $e) {
            $error = "Something went wrong: " . $e->getMessage();
        }
    }
}
?>

<div class="bg-white rounded-2xl shadow-lg p-6">
    <?php if (!$canReview): ?>
    <!-- No Completed Order Message -->
    <div class="text-center py-8">
        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-feather="shopping-bag" class="w-8 h-8 text-orange-600"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Review Not Available</h3>
        <p class="text-gray-600 mb-4">You can only review products from completed orders.</p>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-blue-800 text-sm">
                <strong>To write a review:</strong><br>
                1. Complete your order for this product<br>
                2. Wait for the order status to be "Completed"<br>
                3. Return to this page to write your review
            </p>
        </div>
        <a href="orders.php" class="inline-flex items-center mt-4 px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-medium">
            <i data-feather="package" class="w-4 h-4 mr-2"></i>
            View My Orders
        </a>
    </div>
    <?php else: ?>
    <!-- Review Form -->
    <h3 class="text-xl font-bold text-gray-900 mb-6">
        <?= $existingReview ? 'Update Your Review' : 'Write a Review' ?>
    </h3>
    
    <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-blue-800 text-sm">
            <strong>Reviewing:</strong> <?= htmlspecialchars($productName) ?><br>
            <span class="text-blue-600">This review is only available because you have a completed order for this product.</span>
        </p>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center">
            <i data-feather="alert-circle" class="w-5 h-5 text-red-600 mr-2"></i>
            <span class="text-red-800"><?= htmlspecialchars($error) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center">
            <i data-feather="check-circle" class="w-5 h-5 text-green-600 mr-2"></i>
            <span class="text-green-800"><?= htmlspecialchars($success) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-6">
        <!-- Rating -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Rating *</label>
            <div class="flex items-center space-x-1" id="ratingContainer">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" 
                        class="rating-star w-8 h-8 text-gray-300 hover:text-yellow-400 transition-colors"
                        data-rating="<?= $i ?>"
                        onclick="setRating(<?= $i ?>)">
                    <i data-feather="star" class="w-full h-full"></i>
                </button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="<?= $existingReview['rating'] ?? 0 ?>">
            <p class="text-sm text-gray-500 mt-1">Click on a star to rate this product</p>
        </div>
        
        <!-- Comment -->
        <div>
            <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">Review Comment *</label>
            <textarea id="comment" name="comment" rows="4" 
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                      placeholder="Share your experience with this product..."
                      required><?= htmlspecialchars($existingReview['comment'] ?? '') ?></textarea>
            <p class="text-sm text-gray-500 mt-1">Minimum 10 characters</p>
        </div>
        
        <!-- Submit Button -->
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">
                Reviewing: <span class="font-medium"><?= htmlspecialchars($product['name']) ?></span>
            </p>
            <div class="flex space-x-3">
                <?php if ($existingReview): ?>
                <a href="product.php?id=<?= $productId ?>" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <?php endif; ?>
                <button type="submit" 
                        class="px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-medium">
                    <?= $existingReview ? 'Update Review' : 'Submit Review' ?>
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
// Rating functionality
function setRating(rating) {
    const stars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('ratingInput');
    
    stars.forEach((star, index) => {
        const starIcon = star.querySelector('i');
        if (index < rating) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-yellow-400');
            starIcon.classList.add('fill-current');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-300');
            starIcon.classList.remove('fill-current');
        }
    });
    
    ratingInput.value = rating;
}

// Initialize rating if editing existing review
document.addEventListener('DOMContentLoaded', function() {
    const existingRating = <?= $existingReview['rating'] ?? 0 ?>;
    if (existingRating > 0) {
        setRating(existingRating);
    }
    
    // Initialize Feather icons
    feather.replace();
});

// Hover effects for rating stars
document.querySelectorAll('.rating-star').forEach(star => {
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        const stars = document.querySelectorAll('.rating-star');
        
        stars.forEach((s, index) => {
            const starIcon = s.querySelector('i');
            if (index < rating) {
                s.classList.add('text-yellow-400');
                starIcon.classList.add('fill-current');
            }
        });
    });
    
    star.addEventListener('mouseleave', function() {
        const ratingInput = document.getElementById('ratingInput');
        const currentRating = parseInt(ratingInput.value) || 0;
        
        const stars = document.querySelectorAll('.rating-star');
        stars.forEach((s, index) => {
            const starIcon = s.querySelector('i');
            if (index < currentRating) {
                s.classList.add('text-yellow-400');
                starIcon.classList.add('fill-current');
            } else {
                s.classList.remove('text-yellow-400');
                starIcon.classList.remove('fill-current');
            }
        });
    });
});
</script>
