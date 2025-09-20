<?php
// admin/get_order_details.php - Order Details API
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once 'auth.php';

$order_id = (int) ($_GET['id'] ?? 0);

if (!$order_id) {
    echo '<div class="text-center text-red-600">Invalid order ID</div>';
    exit;
}

try {
    // Get order details
    $orderQuery = "
        SELECT o.*, u.name as user_name
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ";
    $orderStmt = $pdo->prepare($orderQuery);
    $orderStmt->execute([$order_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo '<div class="text-center text-red-600">Order not found</div>';
        exit;
    }

    // Get order items
    $itemsQuery = "
        SELECT oi.*, p.name as product_name, p.sku as product_sku, p.image as product_image, 
               pv.color as variant_color, pv.size as variant_size, pv.variant_name as legacy_variant_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.id
        WHERE oi.order_id = ?
    ";
    $itemsStmt = $pdo->prepare($itemsQuery);
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get order notes
    $notesQuery = "SELECT * FROM order_notes WHERE order_id = ? ORDER BY created_at DESC";
    $notesStmt = $pdo->prepare($notesQuery);
    $notesStmt->execute([$order_id]);
    $notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

    ?>
<div class="space-y-6">
    <script src="js/order-management.js"></script>

    <!-- Order Header -->
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h4 class="text-lg font-semibold text-gray-900">Order #<?= $order['id'] ?></h4>
                <p class="text-sm text-gray-600">Placed on
                    <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
            </div>
            <div class="mt-2 md:mt-0">
                <form id="updateOrderForm" class="space-y-4" data-order-id="<?= $order['id'] ?>" method="POST"
                    action="orders.php">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div class="flex items-center gap-x-4">
                        <select name="status"
                            class="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="pending" <?= $order['order_status'] == 'pending' ? 'selected' : '' ?>>Pending
                            </option>
                            <option value="confirmed" <?= $order['order_status'] == 'confirmed' ? 'selected' : '' ?>>
                                Confirmed</option>
                            <option value="shipped" <?= $order['order_status'] == 'shipped' ? 'selected' : '' ?>>Shipped
                            </option>
                            <option value="completed" <?= $order['order_status'] == 'completed' ? 'selected' : '' ?>>
                                Completed</option>
                            <option value="cancelled" <?= $order['order_status'] == 'cancelled' ? 'selected' : '' ?>>
                                Cancelled</option>
                        </select>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            Update Status
                        </button>
                        <button type="button" onclick="closeOrderModal()"
                            class="px-4 py-2 border rounded-md">Cancel</button>
                    </div>
                </form>
                <div id="statusMessage" class="mt-2 text-sm"></div>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h5 class="font-semibold text-gray-900 mb-3">Customer Information</h5>
            <div class="space-y-2 text-sm">
                <div><span class="font-medium">Name:</span> <?= htmlspecialchars($order['customer_name']) ?></div>
                <div><span class="font-medium">Email:</span> <?= htmlspecialchars($order['customer_email']) ?></div>
                <div><span class="font-medium">Phone:</span> <?= htmlspecialchars($order['customer_phone']) ?></div>
                <?php if ($order['user_name']): ?>
                <div><span class="font-medium">Account:</span> <?= htmlspecialchars($order['user_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h5 class="font-semibold text-gray-900 mb-3">Shipping Address</h5>
            <div class="text-sm text-gray-700">
                <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b">
            <h5 class="font-semibold text-gray-900">Order Items</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Variant</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <?php if ($item['product_image']): ?>
                                <img src="../public/assets/products/<?= htmlspecialchars($item['product_image']) ?>"
                                    alt="<?= htmlspecialchars($item['product_name']) ?>"
                                    class="w-12 h-12 object-cover rounded-lg mr-3">
                                <?php endif; ?>
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($item['product_name'] ?? '') ?></div>
                                    <div class="text-sm text-gray-500">SKU:
                                        <?= htmlspecialchars($item['product_sku'] ?? '') ?></div>
                                </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <?php 
                                    $variantDisplay = 'Default';
                                    if (!empty($item['variant_color']) && !empty($item['variant_size'])) {
                                        $variantDisplay = htmlspecialchars($item['variant_color']) . ' / ' . htmlspecialchars($item['variant_size']);
                                    } elseif (!empty($item['variant_color'])) {
                                        $variantDisplay = htmlspecialchars($item['variant_color']);
                                    } elseif (!empty($item['variant_size'])) {
                                        $variantDisplay = htmlspecialchars($item['variant_size']);
                                    } elseif (!empty($item['legacy_variant_name'])) {
                                        $variantDisplay = htmlspecialchars($item['legacy_variant_name']);
                                    }
                                ?>
                            <?= $variantDisplay ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= $item['quantity'] ?? 0 ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900">KSh
                            <?= number_format((float)($item['price'] ?? 0), 2) ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">KSh
                            <?= number_format((float)($item['price'] ?? 0) * (float)($item['quantity'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h5 class="font-semibold text-gray-900 mb-3">Order Summary</h5>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>KSh <?= number_format((float)($order['subtotal'] ?? 0), 2) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Shipping:</span>
                <span>KSh <?= number_format((float)($order['shipping_cost'] ?? 0), 2) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Tax:</span>
                <span>KSh <?= number_format((float)($order['tax_amount'] ?? 0), 2) ?></span>
            </div>
            <div class="flex justify-between font-semibold text-lg border-t pt-2">
                <span>Total:</span>
                <span>KSh <?= number_format((float)($order['total_amount'] ?? 0), 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Order Notes -->
    <?php if (!empty($notes)): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h5 class="font-semibold text-gray-900 mb-3">Order Notes</h5>
        <div class="space-y-3">
            <?php foreach ($notes as $note): ?>
            <div class="border-l-4 border-blue-200 pl-4">
                <div class="text-sm text-gray-600"><?= date('M j, Y \a\t g:i A', strtotime($note['created_at'])) ?>
                </div>
                <div class="text-sm text-gray-900"><?= nl2br(htmlspecialchars($note['note'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Order Actions -->
    <div class="flex justify-end space-x-3">
        <button onclick="updateOrderStatus(<?= $order['id'] ?>, '<?= $order['order_status'] ?>')"
            class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">
            <i data-feather="edit" class="w-4 h-4 mr-2 inline"></i>
            Confirm Update
        </button>
        <button onclick="closeOrderModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
            Close
        </button>
    </div>
</div>
<?php

} catch (Exception $e) {
    echo '<div class="text-center text-red-600">Error loading order details: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>