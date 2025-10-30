<?php
/**
 * M-Pesa Integration Test Page
 * Quick test to verify M-Pesa integration is working
 */

require_once __DIR__ . '/includes/mpesa_config.php';
require_once __DIR__ . '/includes/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Integration Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">M-Pesa Integration Test</h1>
            
            <!-- Configuration Check -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i data-feather="settings" class="w-5 h-5 mr-2"></i>
                    Configuration
                </h2>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Environment:</span>
                        <span class="font-medium"><?= MPESA_ENV ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Consumer Key:</span>
                        <span class="font-mono text-sm"><?= substr(MPESA_CONSUMER_KEY, 0, 20) ?>...</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shortcode:</span>
                        <span class="font-medium"><?= MPESA_SHORTCODE ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Callback URL:</span>
                        <span class="font-mono text-xs break-all"><?= MPESA_CALLBACK_URL ?></span>
                    </div>
                </div>
            </div>

            <!-- API Authentication Test -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i data-feather="key" class="w-5 h-5 mr-2"></i>
                    API Authentication
                </h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php
                    $accessToken = getMpesaAccessToken();
                    if ($accessToken):
                    ?>
                    <div class="flex items-center text-green-600">
                        <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
                        <span class="font-medium">Authentication Successful</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <span>Access Token: </span>
                        <span class="font-mono"><?= substr($accessToken, 0, 30) ?>...</span>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center text-red-600">
                        <i data-feather="x-circle" class="w-5 h-5 mr-2"></i>
                        <span class="font-medium">Authentication Failed</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        Please check your Consumer Key and Secret in includes/mpesa_config.php
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Database Check -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i data-feather="database" class="w-5 h-5 mr-2"></i>
                    Database
                </h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php
                    try {
                        $stmt = $pdo->query("SHOW TABLES LIKE 'mpesa_transactions'");
                        $tableExists = $stmt->rowCount() > 0;
                        
                        if ($tableExists):
                            $countStmt = $pdo->query("SELECT COUNT(*) FROM mpesa_transactions");
                            $transactionCount = $countStmt->fetchColumn();
                    ?>
                    <div class="flex items-center text-green-600 mb-2">
                        <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
                        <span class="font-medium">mpesa_transactions table exists</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        Total transactions: <?= $transactionCount ?>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center text-red-600">
                        <i data-feather="x-circle" class="w-5 h-5 mr-2"></i>
                        <span class="font-medium">mpesa_transactions table not found</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        Run: php setup_mpesa.php
                    </p>
                    <?php
                        endif;
                    } catch (Exception $e) {
                        echo '<div class="text-red-600">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Files Check -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i data-feather="file-text" class="w-5 h-5 mr-2"></i>
                    Required Files
                </h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php
                    $requiredFiles = [
                        'includes/mpesa_config.php' => 'Configuration',
                        'includes/MpesaPayment.php' => 'Payment Handler',
                        'api/mpesa_initiate.php' => 'Initiate Endpoint',
                        'api/mpesa_callback.php' => 'Callback Handler',
                        'api/mpesa_timeout.php' => 'Timeout Handler',
                        'api/mpesa_status.php' => 'Status Endpoint',
                        'public/mpesa_payment.php' => 'Payment Page'
                    ];
                    
                    $allExist = true;
                    foreach ($requiredFiles as $file => $description):
                        $exists = file_exists(__DIR__ . '/' . $file);
                        if (!$exists) $allExist = false;
                    ?>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-sm text-gray-600"><?= $description ?></span>
                        <?php if ($exists): ?>
                        <span class="text-green-600 flex items-center">
                            <i data-feather="check" class="w-4 h-4"></i>
                        </span>
                        <?php else: ?>
                        <span class="text-red-600 flex items-center">
                            <i data-feather="x" class="w-4 h-4"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Logs Directory Check -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i data-feather="folder" class="w-5 h-5 mr-2"></i>
                    Logs Directory
                </h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php
                    $logsDir = __DIR__ . '/logs';
                    $logsExist = is_dir($logsDir);
                    $logsWritable = $logsExist && is_writable($logsDir);
                    ?>
                    <div class="flex items-center <?= $logsExist ? 'text-green-600' : 'text-red-600' ?>">
                        <i data-feather="<?= $logsExist ? 'check-circle' : 'x-circle' ?>" class="w-5 h-5 mr-2"></i>
                        <span class="font-medium">
                            <?= $logsExist ? 'Logs directory exists' : 'Logs directory not found' ?>
                        </span>
                    </div>
                    <?php if ($logsExist): ?>
                    <div class="mt-2 text-sm text-gray-600">
                        Writable: <?= $logsWritable ? 'Yes' : 'No' ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Phone Number Format Test -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i data-feather="smartphone" class="w-5 h-5 mr-2"></i>
                    Phone Number Formatting Test
                </h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php
                    $testNumbers = [
                        '0712345678' => '254712345678',
                        '0112345678' => '254112345678',
                        '712345678' => '254712345678',
                        '254712345678' => '254712345678',
                        '+254712345678' => '254712345678'
                    ];
                    ?>
                    <div class="space-y-2">
                        <?php foreach ($testNumbers as $input => $expected): ?>
                        <?php
                        $formatted = formatMpesaPhone($input);
                        $isCorrect = $formatted === $expected;
                        ?>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-mono text-gray-600"><?= $input ?></span>
                            <span class="mx-2">→</span>
                            <span class="font-mono <?= $isCorrect ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $formatted ?>
                            </span>
                            <?php if ($isCorrect): ?>
                            <i data-feather="check" class="w-4 h-4 text-green-600 ml-2"></i>
                            <?php else: ?>
                            <i data-feather="x" class="w-4 h-4 text-red-600 ml-2"></i>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Overall Status -->
            <div class="border-t pt-6">
                <?php
                $isReady = $accessToken && $tableExists && $allExist && $logsExist;
                ?>
                <div class="text-center">
                    <?php if ($isReady): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                        <i data-feather="check-circle" class="w-16 h-16 text-green-600 mx-auto mb-4"></i>
                        <h3 class="text-2xl font-bold text-green-600 mb-2">All Systems Go!</h3>
                        <p class="text-gray-600 mb-4">M-Pesa integration is ready to use</p>
                        <a href="public/shop.php" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                            Start Shopping
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <i data-feather="alert-triangle" class="w-16 h-16 text-yellow-600 mx-auto mb-4"></i>
                        <h3 class="text-2xl font-bold text-yellow-600 mb-2">Setup Required</h3>
                        <p class="text-gray-600 mb-4">Please complete the setup steps above</p>
                        <button onclick="location.reload()" class="bg-yellow-600 text-white px-6 py-3 rounded-lg hover:bg-yellow-700 transition-colors">
                            Refresh Test
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="mt-8 border-t pt-6">
                <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                <div class="grid grid-cols-2 gap-4">
                    <a href="MPESA_INTEGRATION_README.md" class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                        <i data-feather="book" class="w-5 h-5 mb-2 text-blue-600"></i>
                        <div class="font-medium">Integration Guide</div>
                        <div class="text-sm text-gray-600">Full documentation</div>
                    </a>
                    <a href="MPESA_SETUP_SUMMARY.md" class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                        <i data-feather="file-text" class="w-5 h-5 mb-2 text-blue-600"></i>
                        <div class="font-medium">Setup Summary</div>
                        <div class="text-sm text-gray-600">Quick overview</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>
