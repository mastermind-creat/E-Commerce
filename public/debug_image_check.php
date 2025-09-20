<?php
// Temporary debug page — remove when finished
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/html; charset=utf-8');
echo '<h2>Product Image Debug</h2>';

echo '<p>This page lists a sample of products and their primary image filenames and whether the file exists under <code>public/assets/products/</code>. Remove this file when done.</p>';

try {
    $stmt = $pdo->query("SELECT p.id, p.name, pi.image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 ORDER BY p.id DESC LIMIT 200");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<p style="color:red">DB error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

if (empty($rows)) {
    echo '<p>No products found.</p>';
    exit;
}

echo '<table border="1" cellpadding="6" cellspacing="0">';
echo '<tr><th>ID</th><th>Name</th><th>image_url (DB)</th><th>resolved path</th><th>file exists?</th></tr>';
foreach ($rows as $r) {
    $dbImg = $r['image_url'];
    $maybe = $dbImg ? preg_replace('#^assets/products/#', '', $dbImg) : '';
    $resolved = $maybe ? 'public/assets/products/' . $maybe : 'public/assets/images/placeholder.png';
    $full = __DIR__ . '/' . ($maybe ? 'assets/products/' . $maybe : 'assets/images/placeholder.png');
    $exists = is_file($full) ? 'YES' : 'NO';
    echo '<tr>';
    echo '<td>' . htmlspecialchars($r['id']) . '</td>';
    echo '<td>' . htmlspecialchars($r['name']) . '</td>';
    echo '<td>' . htmlspecialchars($dbImg) . '</td>';
    echo '<td>' . htmlspecialchars($resolved) . '</td>';
    echo '<td>' . $exists . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<p>Note: If files are missing (NO), either upload images via admin or copy files into <code>public/assets/products/</code>. If files exist but images still fail to load in browser, check the page source for <code>data-img-resolved</code> and confirm the URL matches the server path.</p>';