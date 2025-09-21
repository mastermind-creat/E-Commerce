<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $sql = "SELECT p.id, p.name, COALESCE((SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),(SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.id LIMIT 1)) AS image_url FROM products p ORDER BY p.id";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo sprintf("%d\t%s\t%s\n", $r['id'], $r['name'], $r['image_url'] ?? '(null)');
    }
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage();
}