<?php
/**
 * M-Pesa Payment Handler Class
 * Handles M-Pesa STK Push and payment processing
 */

require_once __DIR__ . '/mpesa_config.php';
require_once __DIR__ . '/db.php';

class MpesaPayment {
    private $pdo;
    private $accessToken;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->accessToken = getMpesaAccessToken();
    }
    
    /**
     * Initiate STK Push
     * @param int $orderId Order ID
     * @param string $phone Customer phone number
     * @param float $amount Amount to pay
     * @return array Response with success status and message
     */
    public function initiateSTKPush($orderId, $phone, $amount) {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with M-Pesa API'
            ];
        }
        
        // Format phone number
        $phone = formatMpesaPhone($phone);
        
        // Validate phone number
        if (!preg_match('/^254[17]\d{8}$/', $phone)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number. Please use format: 0712345678 or 0112345678'
            ];
        }
        
        // Generate timestamp and password
        $timestamp = date('YmdHis');
        $password = generateMpesaPassword($timestamp);
        
        // Prepare request payload
        $payload = [
            'BusinessShortCode' => MPESA_SHORTCODE,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => MPESA_TRANSACTION_TYPE,
            'Amount' => round($amount),
            'PartyA' => $phone,
            'PartyB' => MPESA_SHORTCODE,
            'PhoneNumber' => $phone,
            'CallBackURL' => MPESA_CALLBACK_URL,
            'AccountReference' => MPESA_ACCOUNT_REFERENCE . ' #' . $orderId,
            'TransactionDesc' => MPESA_TRANSACTION_DESC . ' #' . $orderId
        ];
        
        // Make API request
        $ch = curl_init(MPESA_STK_PUSH_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        // Log the transaction
        $this->logTransaction($orderId, $phone, $amount, $result);
        
        if ($httpCode === 200 && isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
            return [
                'success' => true,
                'message' => 'STK Push sent successfully. Please check your phone.',
                'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $result['MerchantRequestID'] ?? null
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'Failed to initiate payment',
                'error_code' => $result['errorCode'] ?? $result['ResponseCode'] ?? null
            ];
        }
    }
    
    /**
     * Query STK Push status
     * @param string $checkoutRequestId Checkout Request ID
     * @return array Transaction status
     */
    public function querySTKPushStatus($checkoutRequestId) {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with M-Pesa API'
            ];
        }
        
        $timestamp = date('YmdHis');
        $password = generateMpesaPassword($timestamp);
        
        $payload = [
            'BusinessShortCode' => MPESA_SHORTCODE,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];
        
        $ch = curl_init(MPESA_STK_QUERY_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Log M-Pesa transaction
     * @param int $orderId Order ID
     * @param string $phone Phone number
     * @param float $amount Amount
     * @param array $response API response
     */
    private function logTransaction($orderId, $phone, $amount, $response) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO mpesa_transactions 
                (order_id, phone_number, amount, merchant_request_id, checkout_request_id, 
                 response_code, response_description, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $orderId,
                $phone,
                $amount,
                $response['MerchantRequestID'] ?? null,
                $response['CheckoutRequestID'] ?? null,
                $response['ResponseCode'] ?? $response['errorCode'] ?? null,
                $response['ResponseDescription'] ?? $response['errorMessage'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log M-Pesa transaction: " . $e->getMessage());
        }
    }
    
    /**
     * Update transaction status from callback
     * @param string $checkoutRequestId Checkout Request ID
     * @param string $resultCode Result code
     * @param string $resultDesc Result description
     * @param string $mpesaReceiptNumber M-Pesa receipt number
     * @return bool Success status
     */
    public function updateTransactionStatus($checkoutRequestId, $resultCode, $resultDesc, $mpesaReceiptNumber = null) {
        try {
            $status = ($resultCode == '0') ? 'completed' : 'failed';
            
            $stmt = $this->pdo->prepare("
                UPDATE mpesa_transactions 
                SET status = ?, 
                    result_code = ?, 
                    result_description = ?, 
                    mpesa_receipt_number = ?,
                    updated_at = NOW()
                WHERE checkout_request_id = ?
            ");
            
            $stmt->execute([
                $status,
                $resultCode,
                $resultDesc,
                $mpesaReceiptNumber,
                $checkoutRequestId
            ]);
            
            // Update order payment status
            if ($status === 'completed') {
                $orderStmt = $this->pdo->prepare("
                    SELECT order_id FROM mpesa_transactions WHERE checkout_request_id = ?
                ");
                $orderStmt->execute([$checkoutRequestId]);
                $orderId = $orderStmt->fetchColumn();
                
                if ($orderId) {
                    $updateOrderStmt = $this->pdo->prepare("
                        UPDATE orders 
                        SET payment_status = 'paid', 
                            order_status = 'confirmed',
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $updateOrderStmt->execute([$orderId]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to update M-Pesa transaction status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get transaction by checkout request ID
     * @param string $checkoutRequestId Checkout Request ID
     * @return array|false Transaction data or false
     */
    public function getTransaction($checkoutRequestId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM mpesa_transactions WHERE checkout_request_id = ?
            ");
            $stmt->execute([$checkoutRequestId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get transactions by order ID
     * @param int $orderId Order ID
     * @return array Transactions
     */
    public function getOrderTransactions($orderId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM mpesa_transactions 
                WHERE order_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
