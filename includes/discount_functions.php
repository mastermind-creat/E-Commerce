<?php
// includes/discount_functions.php - Discount calculation helper functions

/**
 * Calculate the discounted price for a product
 * 
 * @param float $originalPrice The original price of the product
 * @param float $discountPercentage The discount percentage (0-100)
 * @return array Array containing original_price, discounted_price, discount_amount, savings_percentage
 */
function calculateDiscountedPrice($originalPrice, $discountPercentage) {
    $originalPrice = (float) $originalPrice;
    $discountPercentage = (float) $discountPercentage;
    
    if ($discountPercentage <= 0 || $discountPercentage > 100) {
        return [
            'original_price' => $originalPrice,
            'discounted_price' => $originalPrice,
            'discount_amount' => 0,
            'savings_percentage' => 0,
            'is_discounted' => false
        ];
    }
    
    $discountAmount = ($originalPrice * $discountPercentage) / 100;
    $discountedPrice = $originalPrice - $discountAmount;
    
    return [
        'original_price' => $originalPrice,
        'discounted_price' => $discountedPrice,
        'discount_amount' => $discountAmount,
        'savings_percentage' => $discountPercentage,
        'is_discounted' => true
    ];
}

/**
 * Check if a discount is currently active based on start and end dates
 * 
 * @param string|null $startDate Discount start date (Y-m-d H:i:s format)
 * @param string|null $endDate Discount end date (Y-m-d H:i:s format)
 * @return bool True if discount is currently active
 */
function isDiscountActive($startDate, $endDate) {
    $now = new DateTime();
    
    // If no start date, consider it active from now
    if ($startDate) {
        $start = new DateTime($startDate);
        if ($now < $start) {
            return false;
        }
    }
    
    // If no end date, consider it active indefinitely
    if ($endDate) {
        $end = new DateTime($endDate);
        if ($now > $end) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get the effective price for a product (discounted if applicable)
 * 
 * @param array $product Product array with discount fields
 * @return array Price information with discount details
 */
function getEffectivePrice($product) {
    $originalPrice = (float) $product['price'];
    $discountPercentage = (float) ($product['discount_percentage'] ?? 0);
    $startDate = $product['discount_start_date'] ?? null;
    $endDate = $product['discount_end_date'] ?? null;
    
    // Check if discount is active
    $isActive = isDiscountActive($startDate, $endDate);
    
    if ($isActive && $discountPercentage > 0) {
        return calculateDiscountedPrice($originalPrice, $discountPercentage);
    }
    
    return [
        'original_price' => $originalPrice,
        'discounted_price' => $originalPrice,
        'discount_amount' => 0,
        'savings_percentage' => 0,
        'is_discounted' => false
    ];
}

/**
 * Format price for display with discount information
 * 
 * @param array $priceInfo Price information from getEffectivePrice()
 * @return string Formatted price display
 */
function formatPriceDisplay($priceInfo) {
    if ($priceInfo['is_discounted']) {
        return sprintf(
            '<span class="text-gray-500 line-through text-sm">KSh %s</span> <span class="text-green-600 font-bold text-lg">KSh %s</span>',
            number_format($priceInfo['original_price'], 2),
            number_format($priceInfo['discounted_price'], 2)
        );
    }
    
    return sprintf('<span class="font-semibold">KSh %s</span>', number_format($priceInfo['original_price'], 2));
}

/**
 * Generate discount badge HTML
 * 
 * @param float $discountPercentage Discount percentage
 * @return string HTML for discount badge
 */
function generateDiscountBadge($discountPercentage) {
    if ($discountPercentage <= 0) {
        return '';
    }
    
    return sprintf(
        '<div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg animate-pulse">
            -%s%%
        </div>',
        number_format($discountPercentage, 0)
    );
}
?>
