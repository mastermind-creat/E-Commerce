<?php

function get_hero_slides(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT * FROM hero_slides WHERE active = 1 ORDER BY order_num ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Hero slides error: " . $e->getMessage());
        return [];
    }
}

function get_promo_tiles(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT * FROM promo_tiles WHERE active = 1 ORDER BY order_num ASC LIMIT 3");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Promo tiles error: " . $e->getMessage());
        return [];
    }
}

function image_url(string $path): string {
    // Ensure path is relative to public/assets/ and avoid double /assets/ if already prefixed
    $cleanPath = ltrim($path, '/');
    if (strpos($cleanPath, 'assets/') === 0) {
        return '/' . $cleanPath; // Use as-is if it starts with assets/
    }
    return '/assets/' . $cleanPath; // Prepend /assets/ if not already included
}

function format_price(?string $price): string {
    return $price ? 'From ' . htmlspecialchars($price) : '';
}

function get_default_promo_tiles(): array {
    return [
        [
            'link' => '/shop.php?promo=brand',
            'image' => 'assets/promo-brand.jpg',
            'title' => 'Super Brand Sale',
            'description' => 'Huge savings across categories',
            'price' => 'KSh 1,999'
        ],
        [
            'link' => '/shop.php?category=handbags',
            'image' => 'assets/promo-handbag.jpg',
            'title' => 'Handbags & Small Bags',
            'description' => 'Stylish and versatile options',
            'price' => 'From KSh 1,499'
        ],
        [
            'link' => '/shop.php?category=clothing',
            'image' => 'assets/promo-clothing.jpg',
            'title' => 'Men\'s T-Shirts & Jewelry',
            'description' => 'Quality apparel and accessories',
            'price' => 'From KSh 999'
        ]
    ];
}