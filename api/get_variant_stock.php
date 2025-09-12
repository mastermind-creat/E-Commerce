<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$variantId = intval($_GET['variant_id'] ?? 0);

if ($variantId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid variant ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT variant_stock FROM product_variants WHERE id = ?");
$stmt->execute([$variantId]);
$variant = $stmt->fetch();

if (!$variant) {
    echo json_encode(['success' => false, 'message' => 'Variant not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'stock' => (int) $variant['variant_stock']
]);