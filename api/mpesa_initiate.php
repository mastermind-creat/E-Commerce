<?php
/**
 * M-Pesa Payment Initiation Endpoint
 * Initiates STK Push for M-Pesa payment
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
        'message' => 'Unauthorized. Please login first.'
    ]);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$orderId = $data['order_id'] ?? null;
$phoneNumber = $data['phone_number'] ?? null;
$amount = $data['amount'] ?? null;

// Validate input
if (!$orderId || !$phoneNumber || !$amount) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: order_id, phone_number, amount'
    ]);
    exit;
}

// Verify order belongs to user
try {
    $stmt = $pdo->prepare("SELECT id, total_amount, payment_status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ]);
        exit;
    }
    
    // Check if already paid
    if ($order['payment_status'] === 'paid') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Order already paid'
        ]);
        exit;
    }
    
    // Verify amount matches
    if (abs($order['total_amount'] - $amount) > 0.01) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Amount mismatch'
        ]);
        exit;
    }
    
    // Initiate M-Pesa payment
    $mpesa = new MpesaPayment($pdo);
    $result = $mpesa->initiateSTKPush($orderId, $phoneNumber, $amount);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
