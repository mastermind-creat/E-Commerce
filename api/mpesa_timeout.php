<?php
/**
 * M-Pesa Timeout Handler
 * Handles timeout notifications from Safaricom
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/MpesaPayment.php';

// Log timeout for debugging
$logFile = __DIR__ . '/../logs/mpesa_timeout_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$timeoutData = file_get_contents('php://input');
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Timeout received:\n" . $timeoutData . "\n\n", FILE_APPEND);

// Parse timeout data
$timeout = json_decode($timeoutData, true);

if ($timeout) {
    try {
        $checkoutRequestId = $timeout['CheckoutRequestID'] ?? null;
        
        if ($checkoutRequestId) {
            $mpesa = new MpesaPayment($pdo);
            $mpesa->updateTransactionStatus($checkoutRequestId, '1', 'Transaction timeout', null);
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Transaction marked as timeout: $checkoutRequestId\n\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n\n", FILE_APPEND);
    }
}

// Send success response
http_response_code(200);
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Success'
]);
