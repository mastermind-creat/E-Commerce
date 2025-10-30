<?php
/**
 * M-Pesa Daraja API Configuration
 * Safaricom M-Pesa STK Push Integration
 */

// Load environment variables
require_once __DIR__ . '/load_env.php';

// M-Pesa API Credentials (loaded from .env file)
define('MPESA_CONSUMER_KEY', env('MPESA_CONSUMER_KEY', 'xck8DVIsQpA2O32uRoNkezK5AsNhUG4cqmQE4HePRIgxALC2'));
define('MPESA_CONSUMER_SECRET', env('MPESA_CONSUMER_SECRET', 'yOhA43rpjvKcAIyOGZytlpazek38Ay0cEGO2TAo6N9BQ9cmwPWGokTNnG0WF5Ajk'));

// M-Pesa Environment (sandbox or production)
define('MPESA_ENV', env('MPESA_ENV', 'sandbox')); // Change to 'production' for live environment

// M-Pesa API URLs
if (MPESA_ENV === 'sandbox') {
    define('MPESA_BASE_URL', 'https://sandbox.safaricom.co.ke');
} else {
    define('MPESA_BASE_URL', 'https://api.safaricom.co.ke');
}

// API Endpoints
define('MPESA_AUTH_URL', MPESA_BASE_URL . '/oauth/v1/generate?grant_type=client_credentials');
define('MPESA_STK_PUSH_URL', MPESA_BASE_URL . '/mpesa/stkpush/v1/processrequest');
define('MPESA_STK_QUERY_URL', MPESA_BASE_URL . '/mpesa/stkpushquery/v1/query');

// Business Configuration (loaded from .env file)
define('MPESA_SHORTCODE', env('MPESA_SHORTCODE', '174379')); // Paybill/Till Number (Sandbox default)
define('MPESA_PASSKEY', env('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919')); // Sandbox passkey
define('MPESA_ACCOUNT_REFERENCE', env('MPESA_ACCOUNT_REFERENCE', 'E-Commerce')); // Your business name
define('MPESA_TRANSACTION_DESC', env('MPESA_TRANSACTION_DESC', 'Payment for Order')); // Transaction description

// Callback URLs (Update these with your actual domain)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host . '/E-Commerce';

define('MPESA_CALLBACK_URL', $baseUrl . '/api/mpesa_callback.php');
define('MPESA_TIMEOUT_URL', $baseUrl . '/api/mpesa_timeout.php');

// Transaction Types
define('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'); // For Paybill
// define('MPESA_TRANSACTION_TYPE', 'CustomerBuyGoodsOnline'); // For Till Number

/**
 * Get M-Pesa Access Token
 * @return string|false Access token or false on failure
 */
function getMpesaAccessToken() {
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
    
    $ch = curl_init(MPESA_AUTH_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['access_token'] ?? false;
    }
    
    return false;
}

/**
 * Generate M-Pesa Password
 * @param string $timestamp Timestamp in format YmdHis
 * @return string Base64 encoded password
 */
function generateMpesaPassword($timestamp) {
    return base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
}

/**
 * Format phone number to M-Pesa format (254XXXXXXXXX)
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function formatMpesaPhone($phone) {
    // Remove spaces, dashes, and plus signs
    $phone = preg_replace('/[\s\-\+]/', '', $phone);
    
    // Remove leading zeros
    $phone = ltrim($phone, '0');
    
    // Add country code if not present
    if (substr($phone, 0, 3) !== '254') {
        $phone = '254' . $phone;
    }
    
    return $phone;
}
