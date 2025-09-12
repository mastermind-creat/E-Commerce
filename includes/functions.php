<?php

// ==================== HERO SLIDES ==================== //
function get_hero_slides(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT * FROM hero_slides WHERE active = 1 ORDER BY order_num ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Hero slides error: " . $e->getMessage());
        return [];
    }
}

// ==================== PROMO TILES ==================== //
function get_promo_tiles(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT * FROM promo_tiles WHERE active = 1 ORDER BY order_num ASC LIMIT 3");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Promo tiles error: " . $e->getMessage());
        return [];
    }
}

// ==================== ASSET URL HELPERS ==================== //
/**
 * Generate a consistent root-relative URL for assets.
 * Example: asset_url("promo.jpg") => /assets/promo.jpg
 */
function asset_url(string $path): string {
    $cleanPath = ltrim($path, '/');
    if (strpos($cleanPath, 'assets/') === 0) {
        return '/' . $cleanPath; // Already has assets/ prefix
    }
    return '/assets/' . $cleanPath;
}

/**
 * Alias for asset_url (kept for backward compatibility)
 */
function image_url(string $path): string {
    return asset_url($path);
}

// ==================== PRICE FORMATTER ==================== //
function format_price(?string $price): string {
    return $price ? 'From ' . htmlspecialchars($price) : '';
}

// ==================== DEFAULT PROMO TILES (Fallback) ==================== //
function get_default_promo_tiles(): array {
    return [
        [
            'link' => '/shop.php?promo=brand',
            'image' => asset_url('promo-brand.jpg'),
            'title' => 'Super Brand Sale',
            'description' => 'Huge savings across categories',
            'price' => 'KSh 1,999'
        ],
        [
            'link' => '/shop.php?category=handbags',
            'image' => asset_url('promo-handbag.jpg'),
            'title' => 'Handbags & Small Bags',
            'description' => 'Stylish and versatile options',
            'price' => 'From KSh 1,499'
        ],
        [
            'link' => '/shop.php?category=clothing',
            'image' => asset_url('promo-clothing.jpg'),
            'title' => 'Men\'s T-Shirts & Jewelry',
            'description' => 'Quality apparel and accessories',
            'price' => 'From KSh 999'
        ]
    ];
}