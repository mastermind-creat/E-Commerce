<?php
require_once '../includes/db.php';
require_once 'auth.php';

if (!isset($_GET['id'])) {
    die("Product ID is required.");
}

$product_id = (int) $_GET['id'];

// Fetch product images
$stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Delete image files
foreach ($images as $img) {
    $filepath = "../public/assets/products/" . $img['image_url'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Delete images from DB
$pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$product_id]);

// Delete product itself
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$product_id]);

header("Location: products.php");
exit;