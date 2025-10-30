<?php
// public/loyalty_points.php - Loyalty Points Management
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=loyalty_points.php');
    exit;
}

$pageTitle = 'Loyalty Points';
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user's loyalty points
try {
    $stmt = $pdo->prepare("
        SELECT lp.*, u.first_name, u.last_name, u.email 
        FROM loyalty_points lp 
        JOIN users u ON lp.user_id = u.id 
        WHERE lp.user_id = ?
    ");
    $stmt->execute([$userId]);
    $loyaltyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loyaltyData) {
        // Initialize loyalty points if not exists
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_points (user_id, points, points_earned, points_redeemed, points_expired) 
            VALUES (?, 0, 0, 0, 0)
        ");
        $stmt->execute([$userId]);
        
        $loyaltyData = [
            'points' => 0,
            'points_earned' => 0,
            'points_redeemed' => 0,
            'points_expired' => 0,
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'email' => $_SESSION['email'] ?? ''
        ];
    }
} catch (Exception $e) {
    $loyaltyData = [
        'points' => 0,
        'points_earned' => 0,
        'points_redeemed' => 0,
        'points_expired' => 0,
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? '',
        'email' => $_SESSION['email'] ?? ''
    ];
}

// Get loyalty transactions
try {
    $stmt = $pdo->prepare("
        SELECT * FROM loyalty_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $transactions = [];
}

// Get referral data
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_referrals, SUM(points_awarded) as total_referral_points 
        FROM referrals 
        WHERE referrer_id = ? AND status = 'completed'
    ");
    $stmt->execute([$userId]);
    $referralData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $referralData = ['total_referrals' => 0, 'total_referral_points' => 0];
}

// Get user's referral code
try {
    $stmt = $pdo->prepare("
        SELECT referral_code FROM user_referral_codes 
        WHERE user_id = ? AND is_active = 1
    ");
    $stmt->execute([$userId]);
    $referralCode = $stmt->fetchColumn();
} catch (Exception $e) {
    $referralCode = null;
}

// Points redemption rates (configurable)
$pointsValue = 0.01; // 1 point = 1 cent
$minRedemptionPoints = 100; // Minimum points to redeem
$maxRedemptionPoints = $loyaltyData['points']; // Maximum points user can redeem

include __DIR__ . '/../includes/header.php';
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }
    
    .points-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .points-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    .transaction-item {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .transaction-item:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .points-badge {
        background: linear-gradient(45deg, #ff6b6b, #feca57);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
</style>

<main class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-xl">
                        <i data-feather="award" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Loyalty Points</h1>
                        <p class="text-lg text-gray-600 mt-2">Earn and redeem points for rewards</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-primary-600">
                        <?= number_format($loyaltyData['points']) ?> Points
                    </div>
                    <div class="text-sm text-gray-600">
                        Worth KSh <?= number_format($loyaltyData['points'] * $pointsValue, 2) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Points Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Points Card -->
            <div class="points-card rounded-2xl shadow-2xl p-6 relative">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <i data-feather="star" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold"><?= number_format($loyaltyData['points']) ?></div>
                            <div class="text-sm opacity-90">Current Points</div>
                        </div>
                    </div>
                    <div class="text-sm opacity-90">
                        KSh <?= number_format($loyaltyData['points'] * $pointsValue, 2) ?> value
                    </div>
                </div>
            </div>

            <!-- Points Earned Card -->
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i data-feather="trending-up" class="w-6 h-6 text-green-600"></i>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-green-600"><?= number_format($loyaltyData['points_earned']) ?></div>
                        <div class="text-sm text-gray-600">Points Earned</div>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    Lifetime total
                </div>
            </div>

            <!-- Points Redeemed Card -->
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i data-feather="gift" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600"><?= number_format($loyaltyData['points_redeemed']) ?></div>
                        <div class="text-sm text-gray-600">Points Redeemed</div>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    KSh <?= number_format($loyaltyData['points_redeemed'] * $pointsValue, 2) ?> saved
                </div>
            </div>

            <!-- Referrals Card -->
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i data-feather="users" class="w-6 h-6 text-purple-600"></i>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-purple-600"><?= $referralData['total_referrals'] ?></div>
                        <div class="text-sm text-gray-600">Referrals</div>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    <?= $referralData['total_referral_points'] ?> points earned
                </div>
            </div>
        </div>

        <!-- How to Earn Points -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i data-feather="target" class="w-6 h-6 text-primary-600 mr-3"></i>
                How to Earn Points
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex items-start space-x-4 p-4 bg-green-50 rounded-xl">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="shopping-cart" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Make Purchases</h3>
                        <p class="text-sm text-gray-600">Earn 1 point for every KSh 10 spent</p>
                        <div class="text-xs text-green-600 font-semibold mt-1">+10 points per KSh 100</div>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-blue-50 rounded-xl">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="user-plus" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Refer Friends</h3>
                        <p class="text-sm text-gray-600">Earn 100 points when they make their first purchase</p>
                        <div class="text-xs text-blue-600 font-semibold mt-1">+100 points per referral</div>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-purple-50 rounded-xl">
                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="star" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Write Reviews</h3>
                        <p class="text-sm text-gray-600">Earn 25 points for each product review</p>
                        <div class="text-xs text-purple-600 font-semibold mt-1">+25 points per review</div>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-orange-50 rounded-xl">
                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="calendar" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Account Signup</h3>
                        <p class="text-sm text-gray-600">Get 50 points for creating your account</p>
                        <div class="text-xs text-orange-600 font-semibold mt-1">+50 points (one-time)</div>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-pink-50 rounded-xl">
                    <div class="w-10 h-10 bg-pink-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="heart" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Social Sharing</h3>
                        <p class="text-sm text-gray-600">Share products and earn 10 points</p>
                        <div class="text-xs text-pink-600 font-semibold mt-1">+10 points per share</div>
                    </div>
                </div>

                <div class="flex items-start space-x-4 p-4 bg-indigo-50 rounded-xl">
                    <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i data-feather="mail" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Newsletter Signup</h3>
                        <p class="text-sm text-gray-600">Subscribe to our newsletter for 20 points</p>
                        <div class="text-xs text-indigo-600 font-semibold mt-1">+20 points (one-time)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referral Program -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i data-feather="share-2" class="w-6 h-6 text-primary-600 mr-3"></i>
                Referral Program
            </h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Referral Code</h3>
                    <div class="bg-gradient-to-r from-primary-500 to-pink-600 rounded-2xl p-6 text-white">
                        <div class="text-center">
                            <div class="text-2xl font-bold mb-2"><?= htmlspecialchars($referralCode) ?></div>
                            <p class="text-sm opacity-90 mb-4">Share this code with friends to earn points</p>
                            <button onclick="copyReferralCode()" 
                                    class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl font-semibold transition-colors duration-200">
                                Copy Code
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">How It Works</h3>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-primary-500 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</div>
                            <p class="text-gray-700">Share your referral code with friends</p>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-primary-500 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</div>
                            <p class="text-gray-700">They sign up using your code</p>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-primary-500 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</div>
                            <p class="text-gray-700">You both earn 100 points when they make their first purchase</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Points Redemption -->
        <?php if ($loyaltyData['points'] >= $minRedemptionPoints): ?>
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i data-feather="gift" class="w-6 h-6 text-primary-600 mr-3"></i>
                Redeem Points
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-primary-300 transition-colors duration-200">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-feather="credit-card" class="w-8 h-8 text-primary-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Store Credit</h3>
                    <p class="text-sm text-gray-600 mb-4">Convert points to store credit</p>
                    <div class="text-lg font-bold text-primary-600">
                        <?= number_format($loyaltyData['points'] * $pointsValue, 2) ?> KSh available
                    </div>
                </div>
                
                <div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-primary-300 transition-colors duration-200">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-feather="percent" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Discount Coupons</h3>
                    <p class="text-sm text-gray-600 mb-4">Get percentage discounts</p>
                    <div class="text-sm text-gray-500">
                        Coming soon
                    </div>
                </div>
                
                <div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-primary-300 transition-colors duration-200">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-feather="award" class="w-8 h-8 text-purple-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Exclusive Rewards</h3>
                    <p class="text-sm text-gray-600 mb-4">Special member-only items</p>
                    <div class="text-sm text-gray-500">
                        Coming soon
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transaction History -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i data-feather="history" class="w-6 h-6 text-primary-600 mr-3"></i>
                Recent Activity
            </h2>
            
            <?php if (empty($transactions)): ?>
            <div class="text-center py-12">
                <i data-feather="activity" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No activity yet</h3>
                <p class="text-gray-600">Start earning points by making purchases or referring friends!</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($transactions as $transaction): ?>
                <div class="transaction-item bg-white/60 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $transaction['transaction_type'] === 'earned' ? 'bg-green-100' : ($transaction['transaction_type'] === 'redeemed' ? 'bg-blue-100' : 'bg-gray-100') ?>">
                                <i data-feather="<?= $transaction['transaction_type'] === 'earned' ? 'plus' : ($transaction['transaction_type'] === 'redeemed' ? 'minus' : 'edit') ?>" 
                                   class="w-5 h-5 <?= $transaction['transaction_type'] === 'earned' ? 'text-green-600' : ($transaction['transaction_type'] === 'redeemed' ? 'text-blue-600' : 'text-gray-600') ?>"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($transaction['description']) ?></h4>
                                <p class="text-sm text-gray-600">
                                    <?= ucfirst(str_replace('_', ' ', $transaction['reference_type'])) ?>
                                    • <?= date('M j, Y g:i A', strtotime($transaction['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold <?= $transaction['transaction_type'] === 'earned' ? 'text-green-600' : ($transaction['transaction_type'] === 'redeemed' ? 'text-blue-600' : 'text-gray-600') ?>">
                                <?= $transaction['transaction_type'] === 'earned' ? '+' : ($transaction['transaction_type'] === 'redeemed' ? '-' : '') ?><?= number_format($transaction['points']) ?>
                            </div>
                            <div class="text-sm text-gray-500">points</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function copyReferralCode() {
    const referralCode = '<?= htmlspecialchars($referralCode) ?>';
    navigator.clipboard.writeText(referralCode).then(() => {
        // Show success notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-2xl shadow-xl flex items-center space-x-2';
        notification.innerHTML = `
            <i data-feather="check" class="w-5 h-5"></i>
            <span>Referral code copied!</span>
        `;
        document.body.appendChild(notification);
        feather.replace();
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    });
}

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
