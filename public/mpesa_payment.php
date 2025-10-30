<?php
// public/mpesa_payment.php - M-Pesa Payment Processing Page
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/MpesaPayment.php';

// Set page title
$pageTitle = 'M-Pesa Payment';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

// Get order ID from URL
$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header("Location: orders.php");
    exit;
}

// Verify order belongs to user
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header("Location: orders.php");
        exit;
    }
    
    // Check if already paid
    if ($order['payment_status'] === 'paid') {
        header("Location: order_success.php?order_id=" . $orderId);
        exit;
    }
    
    // Get order items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$orderId]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing M-Pesa transactions
    $mpesa = new MpesaPayment($pdo);
    $transactions = $mpesa->getOrderTransactions($orderId);
    
} catch (Exception $e) {
    $error = "Error loading order: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">M-Pesa Payment</h1>
                    <p class="text-gray-600 mt-2">Order #<?= htmlspecialchars($orderId) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Amount to Pay</p>
                    <p class="text-2xl font-bold text-green-600">KSh <?= number_format($order['total_amount'], 2) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <div class="flex items-center">
                <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Payment Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- M-Pesa Payment Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center mb-6">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i data-feather="smartphone" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Pay with M-Pesa</h2>
                            <p class="text-sm text-gray-600">Enter your M-Pesa phone number to complete payment</p>
                        </div>
                    </div>

                    <form id="mpesaForm" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                M-Pesa Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" id="phoneNumber" name="phone_number" required
                                value="<?= htmlspecialchars($order['phone'] ?? $order['customer_phone']) ?>"
                                placeholder="0712345678 or 0112345678"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Enter the phone number registered with M-Pesa</p>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="font-medium text-blue-900 mb-2">How to pay:</h3>
                            <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                                <li>Enter your M-Pesa phone number above</li>
                                <li>Click "Pay Now" button</li>
                                <li>You'll receive an STK push on your phone</li>
                                <li>Enter your M-Pesa PIN to complete payment</li>
                                <li>Wait for confirmation</li>
                            </ol>
                        </div>

                        <button type="submit" id="payButton"
                            class="w-full bg-green-600 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:bg-green-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <i data-feather="smartphone" class="w-5 h-5 mr-2 inline"></i>
                            Pay KSh <?= number_format($order['total_amount'], 2) ?> Now
                        </button>
                    </form>
                </div>

                <!-- Payment Status -->
                <div id="paymentStatus" class="hidden bg-white rounded-2xl shadow-lg p-6">
                    <div class="text-center">
                        <div id="statusIcon" class="mx-auto mb-4"></div>
                        <h3 id="statusTitle" class="text-xl font-semibold mb-2"></h3>
                        <p id="statusMessage" class="text-gray-600 mb-4"></p>
                        <div id="statusActions"></div>
                    </div>
                </div>

                <!-- Transaction History -->
                <?php if (!empty($transactions)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction History</h3>
                    <div class="space-y-3">
                        <?php foreach ($transactions as $txn): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        <?php
                                        $statusColors = [
                                            'completed' => 'text-green-600',
                                            'pending' => 'text-yellow-600',
                                            'failed' => 'text-red-600'
                                        ];
                                        $statusColor = $statusColors[$txn['status']] ?? 'text-gray-600';
                                        ?>
                                        <span class="<?= $statusColor ?>">
                                            <?= ucfirst($txn['status']) ?>
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($txn['phone_number']) ?></p>
                                </div>
                                <p class="font-semibold">KSh <?= number_format($txn['amount'], 2) ?></p>
                            </div>
                            <?php if ($txn['mpesa_receipt_number']): ?>
                            <p class="text-sm text-gray-600">Receipt: <?= htmlspecialchars($txn['mpesa_receipt_number']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-1"><?= date('M d, Y H:i', strtotime($txn['created_at'])) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                    
                    <div class="space-y-3 mb-4">
                        <?php foreach ($orderItems as $item): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600"><?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?></span>
                            <span class="font-medium">KSh <?= number_format($item['subtotal'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between text-lg font-semibold">
                            <span>Total</span>
                            <span class="text-green-600">KSh <?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t">
                        <p class="text-sm text-gray-600 mb-2">Delivery to:</p>
                        <p class="text-sm font-medium"><?= htmlspecialchars($order['shipping_address']) ?></p>
                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($order['customer_phone']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const orderId = <?= $orderId ?>;
const orderAmount = <?= $order['total_amount'] ?>;
let checkoutRequestId = null;
let statusCheckInterval = null;

document.getElementById('mpesaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const phoneNumber = document.getElementById('phoneNumber').value.trim();
    const payButton = document.getElementById('payButton');
    
    if (!phoneNumber) {
        alert('Please enter your M-Pesa phone number');
        return;
    }
    
    // Disable button and show loading
    payButton.disabled = true;
    payButton.innerHTML = '<i data-feather="loader" class="w-5 h-5 mr-2 inline animate-spin"></i> Processing...';
    feather.replace();
    
    try {
        const response = await fetch('/E-Commerce/api/mpesa_initiate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId,
                phone_number: phoneNumber,
                amount: orderAmount
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            checkoutRequestId = result.checkout_request_id;
            showStatus('processing', 'STK Push Sent!', 'Please check your phone and enter your M-Pesa PIN to complete the payment.');
            startStatusCheck();
        } else {
            showStatus('error', 'Payment Failed', result.message);
            payButton.disabled = false;
            payButton.innerHTML = '<i data-feather="smartphone" class="w-5 h-5 mr-2 inline"></i> Pay KSh ' + orderAmount.toFixed(2) + ' Now';
            feather.replace();
        }
    } catch (error) {
        showStatus('error', 'Error', 'Failed to initiate payment. Please try again.');
        payButton.disabled = false;
        payButton.innerHTML = '<i data-feather="smartphone" class="w-5 h-5 mr-2 inline"></i> Pay KSh ' + orderAmount.toFixed(2) + ' Now';
        feather.replace();
    }
});

function showStatus(type, title, message) {
    const statusDiv = document.getElementById('paymentStatus');
    const iconDiv = document.getElementById('statusIcon');
    const titleDiv = document.getElementById('statusTitle');
    const messageDiv = document.getElementById('statusMessage');
    const actionsDiv = document.getElementById('statusActions');
    
    statusDiv.classList.remove('hidden');
    
    if (type === 'processing') {
        iconDiv.innerHTML = '<div class="animate-spin rounded-full h-16 w-16 border-b-2 border-green-600 mx-auto"></div>';
        titleDiv.className = 'text-xl font-semibold mb-2 text-gray-900';
        actionsDiv.innerHTML = '';
    } else if (type === 'success') {
        iconDiv.innerHTML = '<div class="bg-green-100 p-4 rounded-full inline-block"><i data-feather="check-circle" class="w-12 h-12 text-green-600"></i></div>';
        titleDiv.className = 'text-xl font-semibold mb-2 text-green-600';
        actionsDiv.innerHTML = '<a href="order_success.php?order_id=' + orderId + '" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">View Order</a>';
        feather.replace();
    } else {
        iconDiv.innerHTML = '<div class="bg-red-100 p-4 rounded-full inline-block"><i data-feather="x-circle" class="w-12 h-12 text-red-600"></i></div>';
        titleDiv.className = 'text-xl font-semibold mb-2 text-red-600';
        actionsDiv.innerHTML = '<button onclick="location.reload()" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">Try Again</button>';
        feather.replace();
    }
    
    titleDiv.textContent = title;
    messageDiv.textContent = message;
    
    // Scroll to status
    statusDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function startStatusCheck() {
    let attempts = 0;
    const maxAttempts = 60; // Check for 2 minutes (60 * 2 seconds)
    
    statusCheckInterval = setInterval(async () => {
        attempts++;
        
        if (attempts > maxAttempts) {
            clearInterval(statusCheckInterval);
            showStatus('error', 'Payment Timeout', 'Payment verification timed out. Please check your M-Pesa messages or try again.');
            return;
        }
        
        try {
            const response = await fetch('/E-Commerce/api/mpesa_status.php?checkout_request_id=' + checkoutRequestId);
            const result = await response.json();
            
            if (result.success && result.status === 'completed') {
                clearInterval(statusCheckInterval);
                showStatus('success', 'Payment Successful!', 'Your payment has been received. Receipt: ' + result.mpesa_receipt_number);
                setTimeout(() => {
                    window.location.href = 'order_success.php?order_id=' + orderId;
                }, 3000);
            } else if (result.success && result.status === 'failed') {
                clearInterval(statusCheckInterval);
                showStatus('error', 'Payment Failed', result.result_description || 'Payment was not completed. Please try again.');
            }
        } catch (error) {
            console.error('Status check error:', error);
        }
    }, 2000); // Check every 2 seconds
}

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
