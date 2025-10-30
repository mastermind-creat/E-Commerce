<?php
/**
 * M-Pesa Callback Handler
 * Receives payment confirmation from Safaricom
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/MpesaPayment.php';

// Log callback for debugging
$logFile = __DIR__ . '/../logs/mpesa_callback_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$callbackData = file_get_contents('php://input');
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Callback received:\n" . $callbackData . "\n\n", FILE_APPEND);

// Parse callback data
$callback = json_decode($callbackData, true);

if (!$callback) {
    http_response_code(400);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Invalid callback data'
    ]);
    exit;
}

try {
    // Extract callback data
    $stkCallback = $callback['Body']['stkCallback'] ?? null;
    
    if (!$stkCallback) {
        throw new Exception('Invalid callback structure');
    }
    
    $merchantRequestId = $stkCallback['MerchantRequestID'] ?? null;
    $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
    $resultCode = $stkCallback['ResultCode'] ?? null;
    $resultDesc = $stkCallback['ResultDesc'] ?? null;
    
    // Extract callback metadata
    $mpesaReceiptNumber = null;
    $amount = null;
    $phoneNumber = null;
    $transactionDate = null;
    
    if (isset($stkCallback['CallbackMetadata']['Item'])) {
        foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'];
                    break;
                case 'Amount':
                    $amount = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $phoneNumber = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transactionDate = $item['Value'];
                    break;
            }
        }
    }
    
    // Update transaction status
    $mpesa = new MpesaPayment($pdo);
    $mpesa->updateTransactionStatus($checkoutRequestId, $resultCode, $resultDesc, $mpesaReceiptNumber);
    
    // Log success
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Transaction updated: $checkoutRequestId - Result: $resultCode\n\n", FILE_APPEND);
    
    // Send success response to Safaricom
    http_response_code(200);
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Success'
    ]);
    
} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Internal server error'
    ]);
}
