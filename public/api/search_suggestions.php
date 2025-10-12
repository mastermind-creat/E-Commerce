<?php
// public/api/search_suggestions.php - Search Suggestions API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../includes/db.php';

$query = trim($_GET['q'] ?? '');
$limit = min(10, max(1, intval($_GET['limit'] ?? 5)));

if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search products
    $productsStmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, c.name as category_name,
               COALESCE(
                   (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
                   (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.id LIMIT 1)
               ) AS image_url
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active' 
        AND (p.name LIKE :query OR p.description LIKE :query OR c.name LIKE :query)
        ORDER BY 
            CASE 
                WHEN p.name LIKE :exact_query THEN 1
                WHEN p.name LIKE :start_query THEN 2
                WHEN p.name LIKE :query THEN 3
                ELSE 4
            END,
            p.name
        LIMIT :limit
    ");
    
    $searchQuery = "%$query%";
    $exactQuery = "$query%";
    $startQuery = "$query%";
    
    $productsStmt->bindParam(':query', $searchQuery);
    $productsStmt->bindParam(':exact_query', $exactQuery);
    $productsStmt->bindParam(':start_query', $startQuery);
    $productsStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $productsStmt->execute();
    
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format suggestions
    $suggestions = [];
    foreach ($products as $product) {
        $suggestions[] = [
            'type' => 'product',
            'id' => $product['id'],
            'title' => $product['name'],
            'subtitle' => $product['category_name'],
            'price' => number_format($product['price'], 2),
            'image' => $product['image_url'] ? 'assets/products/' . $product['image_url'] : 'assets/images/placeholder.png',
            'url' => "product.php?id=" . $product['id']
        ];
    }
    
    // Search categories if we have space
    if (count($suggestions) < $limit) {
        $remainingLimit = $limit - count($suggestions);
        
        $categoriesStmt = $pdo->prepare("
            SELECT id, name, slug
            FROM categories
            WHERE name LIKE :query
            ORDER BY 
                CASE 
                    WHEN name LIKE :exact_query THEN 1
                    WHEN name LIKE :start_query THEN 2
                    ELSE 3
                END,
                name
            LIMIT :limit
        ");
        
        $categoriesStmt->bindParam(':query', $searchQuery);
        $categoriesStmt->bindParam(':exact_query', $exactQuery);
        $categoriesStmt->bindParam(':start_query', $startQuery);
        $categoriesStmt->bindParam(':limit', $remainingLimit, PDO::PARAM_INT);
        $categoriesStmt->execute();
        
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as $category) {
            $suggestions[] = [
                'type' => 'category',
                'id' => $category['id'],
                'title' => $category['name'],
                'subtitle' => 'Category',
                'url' => "shop.php?category=" . urlencode($category['slug'])
            ];
        }
    }
    
    echo json_encode($suggestions);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>
