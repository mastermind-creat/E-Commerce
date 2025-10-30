<?php
/**
 * M-Pesa Integration Setup Script
 * Run this once to set up the M-Pesa integration
 */

require_once __DIR__ . '/includes/db.php';

echo "=== M-Pesa Integration Setup ===\n\n";

// Check if mpesa_transactions table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'mpesa_transactions'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ mpesa_transactions table already exists\n";
    } else {
        echo "Creating mpesa_transactions table...\n";
        
        // Read and execute SQL file
        $sql = file_get_contents(__DIR__ . '/sql/mpesa_transactions.sql');
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo "✓ mpesa_transactions table created successfully\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Create logs directory
$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    echo "✓ Logs directory created\n";
} else {
    echo "✓ Logs directory already exists\n";
}

// Check if files exist
$requiredFiles = [
    'includes/mpesa_config.php',
    'includes/MpesaPayment.php',
    'api/mpesa_initiate.php',
    'api/mpesa_callback.php',
    'api/mpesa_timeout.php',
    'api/mpesa_status.php',
    'public/mpesa_payment.php'
];

echo "\nChecking required files:\n";
$allFilesExist = true;
foreach ($requiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✓ $file\n";
    } else {
        echo "✗ $file (MISSING)\n";
        $allFilesExist = false;
    }
}

if (!$allFilesExist) {
    echo "\n✗ Some files are missing. Please ensure all M-Pesa integration files are present.\n";
    exit(1);
}

// Test M-Pesa configuration
echo "\nTesting M-Pesa configuration:\n";
require_once __DIR__ . '/includes/mpesa_config.php';

echo "Consumer Key: " . substr(MPESA_CONSUMER_KEY, 0, 10) . "...\n";
echo "Environment: " . MPESA_ENV . "\n";
echo "Shortcode: " . MPESA_SHORTCODE . "\n";
echo "Callback URL: " . MPESA_CALLBACK_URL . "\n";

// Test access token
echo "\nTesting M-Pesa API authentication...\n";
$accessToken = getMpesaAccessToken();

if ($accessToken) {
    echo "✓ Successfully authenticated with M-Pesa API\n";
    echo "Access Token: " . substr($accessToken, 0, 20) . "...\n";
} else {
    echo "✗ Failed to authenticate with M-Pesa API\n";
    echo "Please check your Consumer Key and Secret\n";
}

// Summary
echo "\n=== Setup Summary ===\n";
echo "✓ Database table created/verified\n";
echo "✓ Logs directory created/verified\n";
echo "✓ All required files present\n";

if ($accessToken) {
    echo "✓ M-Pesa API authentication successful\n";
    echo "\n✓✓✓ M-Pesa integration is ready to use! ✓✓✓\n";
} else {
    echo "⚠ M-Pesa API authentication failed\n";
    echo "\nPlease verify your API credentials in includes/mpesa_config.php\n";
}

echo "\nNext steps:\n";
echo "1. Test the integration by making a test purchase\n";
echo "2. Select M-Pesa as payment method during checkout\n";
echo "3. Use a test phone number (e.g., 254708374149 for sandbox)\n";
echo "4. Check logs in /logs/ directory for debugging\n";
echo "\nFor more information, see MPESA_INTEGRATION_README.md\n";
