<?php
// admin/returns_management.php - Admin Returns & Refunds Management
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Returns Management';
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $returnId = $_POST['return_id'] ?? null;
        $status = $_POST['status'] ?? '';
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        
        if ($returnId && $status) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE returns 
                    SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$status, $adminNotes, $returnId]);
                $success = 'Return status updated successfully!';
            } catch (Exception $e) {
                $error = 'Error updating return status: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'process_refund') {
        $returnId = $_POST['return_id'] ?? null;
        $amount = floatval($_POST['amount'] ?? 0);
        $method = $_POST['method'] ?? '';
        $transactionRef = trim($_POST['transaction_reference'] ?? '');
        
        if ($returnId && $amount > 0 && $method) {
            try {
                $pdo->beginTransaction();
                
                // Create refund record
                $refundNumber = 'REF' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("
                    INSERT INTO refunds (return_id, refund_number, amount, method, status, transaction_reference) 
                    VALUES (?, ?, ?, ?, 'processing', ?)
                ");
                $stmt->execute([$returnId, $refundNumber, $amount, $method, $transactionRef]);
                
                // Update return status
                $stmt = $pdo->prepare("
                    UPDATE returns 
                    SET status = 'processing', processed_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$returnId]);
                
                $pdo->commit();
                $success = 'Refund processed successfully! Refund number: ' . $refundNumber;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error processing refund: ' . $e->getMessage();
            }
        }
    }
}

// Get all returns with details
try {
    $stmt = $pdo->prepare("
        SELECT r.*, o.order_number, u.name as customer_name, u.email as customer_email,
               COUNT(ri.id) as item_count, SUM(ri.quantity) as total_quantity
        FROM returns r
        JOIN orders o ON r.order_id = o.id
        JOIN users u ON r.user_id = u.id
        LEFT JOIN return_items ri ON r.id = ri.return_id
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $returns = [];
}

// Get return statistics
try {
    $stats = [];
    
    // Total returns
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM returns");
    $stats['total'] = $stmt->fetchColumn();
    
    // Pending returns
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM returns WHERE status = 'pending'");
    $stats['pending'] = $stmt->fetchColumn();
    
    // Approved returns
    $stmt = $pdo->query("SELECT COUNT(*) as approved FROM returns WHERE status = 'approved'");
    $stats['approved'] = $stmt->fetchColumn();
    
    // Completed returns
    $stmt = $pdo->query("SELECT COUNT(*) as completed FROM returns WHERE status = 'completed'");
    $stats['completed'] = $stmt->fetchColumn();
    
    // Total refund amount
    $stmt = $pdo->query("SELECT SUM(amount) as total_refunds FROM refunds WHERE status = 'completed'");
    $stats['total_refunds'] = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0, 'total_refunds' => 0];
}

include __DIR__ . '/sidebar.php';
?>

<!-- Additional CDN Links for Styling -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"></script>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }
    
    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .stat-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .status-badge {
        position: relative;
        overflow: hidden;
    }
    
    .status-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }
    
    .status-badge:hover::before {
        left: 100%;
    }
    
    .table-row {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .table-row:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100">
    <!-- Enhanced Page Header -->
    <div class="bg-white/80 backdrop-blur-md border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-xl">
                        <i data-feather="rotate-ccw" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Returns & Refunds Management</h1>
                        <p class="text-lg text-gray-600 mt-2">Manage customer returns and process refunds efficiently</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-white/60 backdrop-blur-sm rounded-2xl px-6 py-3 border border-white/30">
                        <span class="text-2xl font-bold text-primary-600"><?= $stats['total'] ?></span>
                        <span class="text-gray-600 ml-2">Total Returns</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center">
        <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center">
        <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="stat-card bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i data-feather="package" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Returns</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['total'] ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">All Time</div>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                            <i data-feather="clock" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['pending'] ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Awaiting Review</div>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                            <i data-feather="check-circle" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Approved</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['approved'] ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Ready for Refund</div>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg">
                            <i data-feather="check" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Completed</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['completed'] ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Fully Processed</div>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                            <i data-feather="dollar-sign" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Refunds</p>
                            <p class="text-2xl font-bold text-gray-900">KSh <?= number_format($stats['total_refunds'], 2) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Amount Processed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Returns Table -->
        <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
            <div class="px-8 py-6 border-b border-white/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                            <i data-feather="list" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">All Returns</h3>
                            <p class="text-sm text-gray-600">Manage and process return requests</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button class="bg-white/60 backdrop-blur-sm border border-white/30 text-gray-700 px-4 py-2 rounded-xl font-semibold hover:bg-white/80 transition-all duration-300 shadow-lg hover:shadow-xl">
                            <i data-feather="filter" class="w-4 h-4 mr-2 inline"></i>
                            Filter
                        </button>
                        <button class="bg-white/60 backdrop-blur-sm border border-white/30 text-gray-700 px-4 py-2 rounded-xl font-semibold hover:bg-white/80 transition-all duration-300 shadow-lg hover:shadow-xl">
                            <i data-feather="download" class="w-4 h-4 mr-2 inline"></i>
                            Export
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="px-8 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <span>Return Details</span>
                                    <i data-feather="chevron-up" class="w-4 h-4 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-8 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <span>Customer</span>
                                    <i data-feather="chevron-up" class="w-4 h-4 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-8 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <span>Items</span>
                                    <i data-feather="chevron-up" class="w-4 h-4 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-8 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <span>Status</span>
                                    <i data-feather="chevron-up" class="w-4 h-4 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-8 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <span>Actions</span>
                                    <i data-feather="chevron-up" class="w-4 h-4 text-gray-400"></i>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($returns)): ?>
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center">
                                <div class="flex flex-col items-center space-y-4">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i data-feather="package" class="w-8 h-8 text-gray-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">No returns found</h3>
                                        <p class="text-gray-600">There are no return requests at the moment.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($returns as $return): ?>
                        <tr class="table-row hover:bg-gray-50/80">
                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center">
                                        <i data-feather="rotate-ccw" class="w-5 h-5 text-white"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($return['return_number']) ?></div>
                                        <div class="text-sm text-gray-500">Order #<?= htmlspecialchars($return['order_number']) ?></div>
                                        <div class="text-xs text-gray-400"><?= date('M j, Y g:i A', strtotime($return['created_at'])) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-semibold text-white"><?= strtoupper(substr($return['customer_name'], 0, 1)) ?></span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($return['customer_name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($return['customer_email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-gray-900"><?= $return['item_count'] ?></div>
                                    <div class="text-sm text-gray-500">item(s)</div>
                                    <div class="text-xs text-gray-400"><?= $return['total_quantity'] ?> total qty</div>
                                </div>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap">
                                <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                    <?= $return['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                        ($return['status'] === 'approved' ? 'bg-blue-100 text-blue-800' : 
                                        ($return['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                        ($return['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                        'bg-gray-100 text-gray-800'))) ?>">
                                    <div class="w-2 h-2 rounded-full mr-2 <?= $return['status'] === 'completed' ? 'bg-green-500' : 
                                        ($return['status'] === 'approved' ? 'bg-blue-500' : 
                                        ($return['status'] === 'rejected' ? 'bg-red-500' : 
                                        ($return['status'] === 'processing' ? 'bg-yellow-500' : 
                                        'bg-gray-500'))) ?>"></div>
                                    <?= ucfirst($return['status']) ?>
                                </span>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewReturn(<?= $return['id'] ?>)" 
                                            class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg font-semibold hover:bg-blue-200 transition-colors duration-200 text-xs">
                                        <i data-feather="eye" class="w-3 h-3 mr-1 inline"></i>
                                        View
                                    </button>
                                    <?php if ($return['status'] === 'pending'): ?>
                                    <button onclick="updateStatus(<?= $return['id'] ?>, 'approved')" 
                                            class="bg-green-100 text-green-700 px-3 py-1 rounded-lg font-semibold hover:bg-green-200 transition-colors duration-200 text-xs">
                                        <i data-feather="check" class="w-3 h-3 mr-1 inline"></i>
                                        Approve
                                    </button>
                                    <button onclick="updateStatus(<?= $return['id'] ?>, 'rejected')" 
                                            class="bg-red-100 text-red-700 px-3 py-1 rounded-lg font-semibold hover:bg-red-200 transition-colors duration-200 text-xs">
                                        <i data-feather="x" class="w-3 h-3 mr-1 inline"></i>
                                        Reject
                                    </button>
                                    <?php elseif ($return['status'] === 'approved'): ?>
                                    <button onclick="processRefund(<?= $return['id'] ?>)" 
                                            class="bg-purple-100 text-purple-700 px-3 py-1 rounded-lg font-semibold hover:bg-purple-200 transition-colors duration-200 text-xs">
                                        <i data-feather="credit-card" class="w-3 h-3 mr-1 inline"></i>
                                        Process Refund
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
    <div class="bg-white/95 backdrop-blur-md rounded-3xl max-w-md w-full p-8 shadow-2xl border border-white/20">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                <i data-feather="edit" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Update Return Status</h3>
                <p class="text-sm text-gray-600">Change the status of this return request</p>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="return_id" id="statusReturnId">
            <input type="hidden" name="status" id="statusValue">
            
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Admin Notes</label>
                <textarea name="admin_notes" rows="4" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 resize-none"
                          placeholder="Add notes about this return..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeStatusModal()" 
                        class="px-6 py-3 text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Refund Processing Modal -->
<div id="refundModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
    <div class="bg-white/95 backdrop-blur-md rounded-3xl max-w-md w-full p-8 shadow-2xl border border-white/20">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                <i data-feather="credit-card" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Process Refund</h3>
                <p class="text-sm text-gray-600">Process refund for this return request</p>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="process_refund">
            <input type="hidden" name="return_id" id="refundReturnId">
            
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Refund Amount (KSh)</label>
                <input type="number" name="amount" step="0.01" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                       placeholder="0.00">
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Refund Method</label>
                <select name="method" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                    <option value="">Select method</option>
                    <option value="original_payment">Original Payment Method</option>
                    <option value="store_credit">Store Credit</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="mobile_money">Mobile Money</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Transaction Reference</label>
                <input type="text" name="transaction_reference"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                       placeholder="Optional transaction reference">
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRefundModal()" 
                        class="px-6 py-3 text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Process Refund
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Enhanced JavaScript with animations and better UX
function updateStatus(returnId, status) {
    document.getElementById('statusReturnId').value = returnId;
    document.getElementById('statusValue').value = status;
    
    // Add animation to modal
    const modal = document.getElementById('statusModal');
    modal.classList.remove('hidden');
    modal.style.opacity = '0';
    modal.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.style.transform = 'scale(1)';
    }, 10);
}

function processRefund(returnId) {
    document.getElementById('refundReturnId').value = returnId;
    
    // Add animation to modal
    const modal = document.getElementById('refundModal');
    modal.classList.remove('hidden');
    modal.style.opacity = '0';
    modal.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.style.transform = 'scale(1)';
    }, 10);
}

function viewReturn(returnId) {
    // Create a more professional view return modal
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50';
    modal.innerHTML = `
        <div class="bg-white/95 backdrop-blur-md rounded-3xl max-w-2xl w-full p-8 shadow-2xl border border-white/20">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                        <i data-feather="eye" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Return Details</h3>
                        <p class="text-sm text-gray-600">View detailed information about this return</p>
                    </div>
                </div>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i data-feather="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="bg-gray-50 rounded-xl p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Return Information</h4>
                    <p class="text-sm text-gray-600">Return ID: ${returnId}</p>
                    <p class="text-sm text-gray-600">This feature will show detailed return information including items, reasons, and timeline.</p>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button onclick="this.closest('.fixed').remove()" 
                        class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Close
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add animation
    modal.style.opacity = '0';
    modal.style.transform = 'scale(0.9)';
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.style.transform = 'scale(1)';
    }, 10);
    
    // Re-initialize feather icons
    feather.replace();
}

function closeStatusModal() {
    const modal = document.getElementById('statusModal');
    modal.style.opacity = '0';
    modal.style.transform = 'scale(0.9)';
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function closeRefundModal() {
    const modal = document.getElementById('refundModal');
    modal.style.opacity = '0';
    modal.style.transform = 'scale(0.9)';
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Add smooth animations to stat cards
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('.table-row');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(8px)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});

// Initialize Feather icons
feather.replace();

// Add loading states for buttons
function addLoadingState(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i data-feather="loader" class="w-4 h-4 mr-2 animate-spin inline"></i>Processing...';
    button.disabled = true;
    feather.replace();
    
    // Remove loading state after 2 seconds (adjust as needed)
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
        feather.replace();
    }, 2000);
}

// Add click handlers for form submissions
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                addLoadingState(submitButton);
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
