<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$productId = $_GET['product_id'] ?? null;
$orderId = $_GET['order_id'] ?? null;

// Validate that user has a completed order for this product
$canReview = false;
$productName = '';
if ($productId) {
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
    } else {
        // User doesn't have a completed order for this product
        header("Location: orders.php?error=no_completed_order");
        exit;
    }
} else {
    header("Location: orders.php?error=invalid_product");
    exit;
}

// Check if user has already reviewed this product
$existingReview = null;
if ($canReview) {
    $checkStmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND product_id = ?");
    $checkStmt->execute([$userId, $productId]);
    $existingReview = $checkStmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $canReview) {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a valid rating (1-5 stars).";
    } elseif (empty(trim($comment))) {
        $error = "Please write a review comment.";
    } else {
        try {
            if ($existingReview) {
                // Update existing review
                $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?");
                $stmt->execute([$rating, $comment, $existingReview['id']]);
                $success = "Review updated successfully!";
            } else {
                // Create new review
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$userId, $productId, $rating, $comment]);
                $success = "Review submitted successfully!";
            }

            $redirectUrl = $orderId ? "orders.php" : "product.php?id=" . $productId;
            header("Location: " . $redirectUrl);
            exit;
        } catch (Exception $e) {
            $error = "Something went wrong: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?= $existingReview ? 'Update Review' : 'Leave Review' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        .star-rating {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .star {
            cursor: pointer;
            font-size: 2rem;
            color: #e5e7eb;
            transition: all 0.2s ease;
        }
        
        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }
        
        .star.selected {
            color: #f59e0b;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="glass-card max-w-lg w-full p-8 rounded-3xl">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl">
                    <i data-feather="star" class="w-8 h-8 text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= $existingReview ? 'Update Your Review' : 'Leave a Review' ?></h1>
                <p class="text-gray-600"><?= $existingReview ? 'You can update your existing review below' : 'Share your experience with this product' ?></p>
                <div class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-200">
                    <p class="text-blue-800 font-medium">Reviewing: <span class="font-bold"><?= htmlspecialchars($productName) ?></span></p>
                    <p class="text-blue-600 text-sm mt-1">This review is only available because you have a completed order for this product.</p>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-center">
                    <i data-feather="alert-circle" class="w-5 h-5 text-red-600 mr-2"></i>
                    <p class="text-red-800 font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                <div class="flex items-center">
                    <i data-feather="check-circle" class="w-5 h-5 text-green-600 mr-2"></i>
                    <p class="text-green-800 font-medium"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="post" class="space-y-6">
                <!-- Star Rating -->
                <div class="space-y-3">
                    <label class="block text-lg font-semibold text-gray-700 text-center">Rating</label>
                    <div class="star-rating" id="starRating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?= $existingReview && $existingReview['rating'] >= $i ? 'selected' : '' ?>" data-rating="<?= $i ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="<?= $existingReview['rating'] ?? 0 ?>" required>
                </div>
                
                <!-- Comment -->
                <div class="space-y-3">
                    <label class="block text-lg font-semibold text-gray-700">Your Review</label>
                    <textarea name="comment" rows="4" 
                        class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900 placeholder-gray-500"
                        placeholder="Tell us about your experience with this product..." required><?= htmlspecialchars($existingReview['comment'] ?? '') ?></textarea>
                </div>
                
                <!-- Submit Button -->
                <div class="flex space-x-4">
                    <a href="<?= $orderId ? 'orders.php' : 'product.php?id=' . $productId ?>" 
                        class="flex-1 px-6 py-3 bg-white/60 backdrop-blur-sm border border-white/30 text-gray-700 rounded-2xl hover:bg-white/80 transition-all duration-300 shadow-lg hover:shadow-xl text-center font-semibold">
                        Cancel
                    </a>
                    <button type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-primary-500 to-pink-600 text-white rounded-2xl hover:from-primary-600 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl font-semibold hover:scale-105">
                        <?= $existingReview ? 'Update Review' : 'Submit Review' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        feather.replace();
        
        // Star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                const rating = index + 1;
                ratingInput.value = rating;
                
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('selected');
                        s.classList.remove('active');
                    } else {
                        s.classList.remove('selected', 'active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', () => {
                const rating = index + 1;
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        document.getElementById('starRating').addEventListener('mouseleave', () => {
            stars.forEach(star => {
                star.classList.remove('active');
            });
        });
    </script>
</body>

</html>