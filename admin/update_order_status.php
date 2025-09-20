<?php
// admin/update_order_status.php
require_once __DIR__ . '/../includes/db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id = (int) ($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$response = ['success' => false, 'message' => ''];

if (!$order_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check current status (support legacy `status` column)
    $stmt = $pdo->prepare("SELECT COALESCE(order_status, status) as current_status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $current_status = $stmt->fetchColumn();

    if (!$current_status) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if (in_array(strtolower($current_status), ['completed', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => "Order #$order_id is already $current_status. Changes not allowed."]);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Update both `order_status` and legacy `status` to keep views in sync
    if (strtolower($status) === 'completed') {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, status = ?, payment_status = 'paid', updated_at = NOW() WHERE id = ?");
    } elseif (strtolower($status) === 'cancelled') {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, status = ?, payment_status = 'failed', updated_at = NOW() WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, status = ?, updated_at = NOW() WHERE id = ?");
    }

    $stmt->execute([$status, $status, $order_id]);

    // Add note if provided
    if ($notes) {
        $noteStmt = $pdo->prepare("INSERT INTO order_notes (order_id, note, created_at) VALUES (?, ?, NOW())");
        $noteStmt->execute([$order_id, $notes]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Order #$order_id status updated to " . ucfirst($status)]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()]);
}