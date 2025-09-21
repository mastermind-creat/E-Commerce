<?php
// includes/check_order_updates.php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderIds = $input['orderIds'] ?? [];
    
    if (empty($orderIds)) {
        echo json_encode(['updates' => []]);
        exit;
    }
    
    // Create placeholders for the SQL query
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    
    // Get current status of these orders
    $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id IN ($placeholders)");
    $stmt->execute($orderIds);
    $currentStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if any orders have changed status (you might want to compare with previous status)
    // For simplicity, we'll just return all current statuses
    // In a real app, you'd compare with the previous status stored in session or database
    
    $updates = [];
    foreach ($currentStatuses as $order) {
        $updates[] = [
            'order_id' => $order['id'],
            'new_status' => $order['status']
        ];
    }
    
    echo json_encode(['updates' => $updates]);
    exit;
}

echo json_encode(['updates' => []]);