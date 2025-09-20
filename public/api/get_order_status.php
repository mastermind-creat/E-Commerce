<?php
// File: /public/api/get_order_status.php
require_once __DIR__ . '/../../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$orderId = (int)$_GET['order_id'];
$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, COALESCE(order_status, status) AS display_status, updated_at FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Normalize casing to Title case for consistent comparisons
    $display = isset($order['display_status']) ? ucfirst(strtolower($order['display_status'])) : '';

    echo json_encode([
        'success' => true,
        'order' => [
            'id' => $order['id'],
            'display_status' => $display,
            'updated_at' => $order['updated_at']
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}