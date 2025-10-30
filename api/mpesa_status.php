<?php
/**
 * M-Pesa Payment Status Check Endpoint
 * Checks the status of an M-Pesa transaction
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/MpesaPayment.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Get checkout request ID
$checkoutRequestId = $_GET['checkout_request_id'] ?? null;

if (!$checkoutRequestId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing checkout_request_id parameter'
    ]);
    exit;
}

try {
    $mpesa = new MpesaPayment($pdo);
    $transaction = $mpesa->getTransaction($checkoutRequestId);
    
    if (!$transaction) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Transaction not found'
        ]);
        exit;
    }
    
    // Verify transaction belongs to user's order
    $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
    $stmt->execute([$transaction['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order || $order['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied'
        ]);
        exit;
    }
    
    // Return transaction status
    echo json_encode([
        'success' => true,
        'status' => $transaction['status'],
        'result_code' => $transaction['result_code'],
        'result_description' => $transaction['result_description'],
        'mpesa_receipt_number' => $transaction['mpesa_receipt_number'],
        'amount' => $transaction['amount'],
        'created_at' => $transaction['created_at'],
        'updated_at' => $transaction['updated_at']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
