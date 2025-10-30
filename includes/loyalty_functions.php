<?php
// includes/loyalty_functions.php - Loyalty Points System Helper Functions

/**
 * Award loyalty points to a user
 */
function awardLoyaltyPoints($pdo, $userId, $points, $description, $referenceType, $referenceId = null, $expiresAt = null, $useTransaction = true) {
    try {
        if ($useTransaction) {
            $pdo->beginTransaction();
        }
        
        // Add transaction record
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_transactions 
            (user_id, transaction_type, points, description, reference_type, reference_id, expires_at) 
            VALUES (?, 'earned', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $points, $description, $referenceType, $referenceId, $expiresAt]);
        
        // Update user's loyalty points
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_points (user_id, points, points_earned, points_redeemed, points_expired) 
            VALUES (?, ?, ?, 0, 0)
            ON DUPLICATE KEY UPDATE 
            points = points + VALUES(points),
            points_earned = points_earned + VALUES(points),
            last_activity = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$userId, $points]);
        
        if ($useTransaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if ($useTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error awarding loyalty points: " . $e->getMessage());
        return false;
    }
}

/**
 * Redeem loyalty points from a user
 */
function redeemLoyaltyPoints($pdo, $userId, $points, $description, $referenceType, $referenceId = null) {
    try {
        // Check if user has enough points
        $stmt = $pdo->prepare("SELECT points FROM loyalty_points WHERE user_id = ?");
        $stmt->execute([$userId]);
        $currentPoints = $stmt->fetchColumn();
        
        if ($currentPoints < $points) {
            return false; // Insufficient points
        }
        
        $pdo->beginTransaction();
        
        // Add transaction record
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_transactions 
            (user_id, transaction_type, points, description, reference_type, reference_id) 
            VALUES (?, 'redeemed', ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $points, $description, $referenceType, $referenceId]);
        
        // Update user's loyalty points
        $stmt = $pdo->prepare("
            UPDATE loyalty_points 
            SET points = points - ?, 
                points_redeemed = points_redeemed + ?,
                last_activity = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        $stmt->execute([$points, $points, $userId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error redeeming loyalty points: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate loyalty points for a purchase
 */
function calculatePurchasePoints($orderTotal) {
    // 1 point per KSh 10 spent
    return floor($orderTotal / 10);
}

/**
 * Process referral and award points
 */
function processReferral($pdo, $referrerId, $referredId, $referralCode) {
    try {
        $pdo->beginTransaction();
        
        // Check if referral already exists
        $stmt = $pdo->prepare("
            SELECT id FROM referrals 
            WHERE referrer_id = ? AND referred_id = ?
        ");
        $stmt->execute([$referrerId, $referredId]);
        
        if ($stmt->fetch()) {
            $pdo->rollBack();
            return false; // Referral already exists
        }
        
        // Create referral record
        $stmt = $pdo->prepare("
            INSERT INTO referrals (referrer_id, referred_id, referral_code, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$referrerId, $referredId, $referralCode]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error processing referral: " . $e->getMessage());
        return false;
    }
}

/**
 * Complete referral when referred user makes first purchase
 */
function completeReferral($pdo, $referredId, $useTransaction = true) {
    try {
        if ($useTransaction) {
            $pdo->beginTransaction();
        }
        
        // Find pending referral
        $stmt = $pdo->prepare("
            SELECT id, referrer_id, referral_code 
            FROM referrals 
            WHERE referred_id = ? AND status = 'pending'
        ");
        $stmt->execute([$referredId]);
        $referral = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$referral) {
            if ($useTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
        
        $referralId = $referral['id'];
        $referrerId = $referral['referrer_id'];
        $referralCode = $referral['referral_code'];
        $points = 100; // 100 points for successful referral
        
        // Update referral status
        $stmt = $pdo->prepare("
            UPDATE referrals 
            SET status = 'completed', points_awarded = ?, completed_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$points, $referralId]);
        
        // Award points to referrer (skip transaction as we're already in one)
        awardLoyaltyPoints($pdo, $referrerId, $points, "Referral bonus for $referralCode", 'referral', $referralId, null, false);
        
        // Award points to referred user (skip transaction as we're already in one)
        awardLoyaltyPoints($pdo, $referredId, $points, "Welcome bonus from referral", 'referral', $referralId, null, false);
        
        // Update referral counts
        $stmt = $pdo->prepare("
            UPDATE user_referral_codes 
            SET total_referrals = total_referrals + 1, 
                total_points_earned = total_points_earned + ? 
            WHERE user_id = ?
        ");
        $stmt->execute([$points, $referrerId]);
        
        if ($useTransaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if ($useTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error completing referral: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's referral code
 */
function getUserReferralCode($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT referral_code FROM user_referral_codes 
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting referral code: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate unique referral code
 */
function generateReferralCode($pdo, $userId) {
    $code = 'REF' . str_pad($userId, 6, '0', STR_PAD_LEFT);
    
    // Check if code already exists
    $stmt = $pdo->prepare("SELECT id FROM user_referral_codes WHERE referral_code = ?");
    $stmt->execute([$code]);
    
    if ($stmt->fetch()) {
        // If exists, add random suffix
        $code .= rand(100, 999);
    }
    
    return $code;
}

/**
 * Check if user was referred by someone
 */
function checkReferralOnSignup($pdo, $userId, $referralCode = null) {
    if (!$referralCode) {
        return false;
    }
    
    try {
        // Find referrer by code
        $stmt = $pdo->prepare("
            SELECT user_id FROM user_referral_codes 
            WHERE referral_code = ? AND is_active = 1
        ");
        $stmt->execute([$referralCode]);
        $referrerId = $stmt->fetchColumn();
        
        if (!$referrerId) {
            return false;
        }
        
        // Process referral
        return processReferral($pdo, $referrerId, $userId, $referralCode);
    } catch (Exception $e) {
        error_log("Error checking referral: " . $e->getMessage());
        return false;
    }
}

/**
 * Award points for review
 */
function awardReviewPoints($pdo, $userId, $productId) {
    $points = 25;
    $description = "Product review bonus";
    return awardLoyaltyPoints($pdo, $userId, $points, $description, 'review', $productId);
}

/**
 * Award points for social sharing
 */
function awardSharingPoints($pdo, $userId, $productId) {
    $points = 10;
    $description = "Social sharing bonus";
    return awardLoyaltyPoints($pdo, $userId, $points, $description, 'sharing', $productId);
}

/**
 * Award signup bonus
 */
function awardSignupBonus($pdo, $userId) {
    $points = 50;
    $description = "Account signup bonus";
    return awardLoyaltyPoints($pdo, $userId, $points, $description, 'signup', $userId);
}

/**
 * Award newsletter signup points
 */
function awardNewsletterPoints($pdo, $userId) {
    $points = 20;
    $description = "Newsletter signup bonus";
    return awardLoyaltyPoints($pdo, $userId, $points, $description, 'newsletter', $userId);
}

/**
 * Get loyalty points value in currency
 */
function getPointsValue($points, $rate = 0.01) {
    return $points * $rate; // 1 point = 1 cent by default
}

/**
 * Check if user can redeem points
 */
function canRedeemPoints($pdo, $userId, $points) {
    try {
        $stmt = $pdo->prepare("SELECT points FROM loyalty_points WHERE user_id = ?");
        $stmt->execute([$userId]);
        $currentPoints = $stmt->fetchColumn();
        return $currentPoints >= $points;
    } catch (Exception $e) {
        error_log("Error checking redeemable points: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's loyalty summary
 */
function getLoyaltySummary($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT lp.*, u.first_name, u.last_name, u.email 
            FROM loyalty_points lp 
            JOIN users u ON lp.user_id = u.id 
            WHERE lp.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting loyalty summary: " . $e->getMessage());
        return null;
    }
}

/**
 * Get recent loyalty transactions
 */
function getLoyaltyTransactions($pdo, $userId, $limit = 20) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM loyalty_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting loyalty transactions: " . $e->getMessage());
        return [];
    }
}
