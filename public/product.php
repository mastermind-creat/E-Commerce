<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';

$id = intval($_GET['id'] ?? 0);

// Fetch product
$productStmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND status = "active"');
$productStmt->execute([$id]);
$p = $productStmt->fetch();

if (!$p) {
    header('Location: index.php');
    exit;
}

// Fetch images, primary first
$imagesStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC');
$imagesStmt->execute([$id]);
$images = $imagesStmt->fetchAll();

// Fetch variants
$variantsStmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = ?');
$variantsStmt->execute([$id]);
$variants = $variantsStmt->fetchAll();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <title><?= htmlspecialchars($p['name']); ?> | Shop</title>
</head>

<body class="bg-gray-50 text-gray-900">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <!-- Images -->
            <div>
                <?php 
                $mainImage = $images[0]['image_url'] ?? 'assets/images/placeholder.png'; 
                ?>
                <<img id="mainImage"
                    src="assets/products/<?= htmlspecialchars($images[0]['image_url'] ?? 'placeholder.png') ?>"
                    alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-96 object-cover rounded-2xl shadow-lg">


                    <?php if (count($images) > 1): ?>
                    <div class="flex gap-3 mt-4 overflow-x-auto">
                        <?php foreach ($images as $img): ?>
                        <img src="assets/products/<?= htmlspecialchars($img['image_url']) ?>"
                            class="w-20 h-20 object-cover rounded-lg border cursor-pointer hover:ring-2 hover:ring-blue-500 transition"
                            onclick="document.getElementById('mainImage').src=this.src" alt="thumbnail">

                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
            </div>

            <!-- Product Details -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($p['name']); ?></h1>
                <p class="text-2xl text-blue-600 font-semibold mt-2 flex items-center gap-2">
                    <i class='bx bx-purchase-tag'></i>
                    KSh <?= number_format($p['price'],2); ?>
                </p>

                <div class="mt-2 flex items-center gap-2">
                    <?php if ((int)$p['stock'] > 0): ?>
                    <p id="stockText" class="text-sm text-green-600 flex items-center gap-1">
                        <i class='bx bx-check-circle'></i> In stock:
                        <span id="stockCount"><?= (int)$p['stock']; ?></span>
                    </p>
                    <?php else: ?>
                    <p id="stockText" class="text-sm text-red-600 flex items-center gap-1">
                        <i class='bx bx-x-circle'></i> Out of stock
                    </p>
                    <?php endif; ?>
                </div>

                <form id="addToCartForm" action="add_to_cart.php" method="post" class="mt-6 space-y-4">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id']; ?>">

                    <!-- Variants -->
                    <?php if (!empty($variants)): ?>
                    <div>
                        <label for="variantSelect" class="block font-semibold mb-1">Choose Variant</label>
                        <select id="variantSelect" name="variant_id"
                            class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($variants as $v):
                                    $label = trim(($v['color'] ?? '') . ' ' . ($v['size'] ?? ''));
                                    $stockAttr = (int)$v['stock'];
                                ?>
                            <option value="<?= (int)$v['id']; ?>" data-stock="<?= $stockAttr; ?>"
                                <?= $stockAttr<=0 ? 'disabled' : ''; ?>>
                                <?= htmlspecialchars($label ?: 'Option'); ?> — Stock: <?= $stockAttr; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Quantity -->
                    <div>
                        <label class="block font-semibold mb-1">Quantity</label>
                        <div class="flex items-center gap-2">
                            <button type="button" id="qtyDecrease"
                                class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">−</button>
                            <input id="qtyInput" name="quantity" type="number"
                                class="w-20 border rounded text-center p-1" value="1" min="1" step="1">
                            <button type="button" id="qtyIncrease"
                                class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">+</button>
                            <div id="qtyError" class="text-sm text-red-600 ml-3 hidden"></div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" id="addToCartBtn"
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg shadow flex items-center justify-center gap-2">
                            <i class='bx bx-cart-add text-xl'></i> Add to Cart
                        </button>
                    </div>
                </form>

                <!-- Description -->
                <div class="mt-8">
                    <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
                        <i class='bx bx-info-circle'></i> Product Description
                    </h2>
                    <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($p['description'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
    (() => {
        const qtyInput = document.getElementById('qtyInput');
        const decreaseBtn = document.getElementById('qtyDecrease');
        const increaseBtn = document.getElementById('qtyIncrease');
        const addBtn = document.getElementById('addToCartBtn');
        const qtyError = document.getElementById('qtyError');
        const stockCountEl = document.getElementById('stockCount');
        const variantSelect = document.getElementById('variantSelect');

        let maxStock = <?= (int)$p['stock']; ?>;
        const minQty = 1;

        function intVal(v, fallback = 0) {
            const n = parseInt(String(v).replace(/[^\d-]/g, ''), 10);
            return Number.isNaN(n) ? fallback : n;
        }

        function updateControls() {
            let qty = intVal(qtyInput.value, minQty);
            if (qty < minQty) qty = minQty;
            if (maxStock <= 0) {
                qtyInput.value = 0;
                qtyInput.disabled = true;
                decreaseBtn.disabled = true;
                increaseBtn.disabled = true;
                addBtn.disabled = true;
                addBtn.classList.add('opacity-60', 'cursor-not-allowed');
                return;
            }
            qtyInput.disabled = false;
            addBtn.disabled = false;
            addBtn.classList.remove('opacity-60', 'cursor-not-allowed');

            if (qty > maxStock) {
                qty = maxStock;
                qtyError.textContent = 'Max available is ' + maxStock;
                qtyError.classList.remove('hidden');
            } else {
                qtyError.textContent = '';
                qtyError.classList.add('hidden');
            }

            qtyInput.value = qty;
            decreaseBtn.disabled = qty <= minQty;
            increaseBtn.disabled = qty >= maxStock;

            if (stockCountEl) stockCountEl.textContent = maxStock;
        }

        decreaseBtn.addEventListener('click', () => {
            qtyInput.value = Math.max(minQty, intVal(qtyInput.value) - 1);
            updateControls();
        });
        increaseBtn.addEventListener('click', () => {
            qtyInput.value = Math.min(maxStock, intVal(qtyInput.value) + 1);
            updateControls();
        });
        qtyInput.addEventListener('input', () => {
            qtyInput.value = Math.max(minQty, intVal(qtyInput.value));
            updateControls();
        });

        if (variantSelect) {
            function refreshFromVariant() {
                const opt = variantSelect.options[variantSelect.selectedIndex];
                const vstock = opt ? intVal(opt.getAttribute('data-stock'), 0) : <?= (int)$p['stock']; ?>;
                maxStock = vstock;
                updateControls();
            }
            variantSelect.addEventListener('change', refreshFromVariant);
            refreshFromVariant();
        }

        document.getElementById('addToCartForm').addEventListener('submit', function(e) {
            const qty = intVal(qtyInput.value, 0);
            if (maxStock <= 0 || qty < 1 || qty > maxStock) {
                e.preventDefault();
                alert('Invalid quantity or product out of stock.');
                updateControls();
            }
        });

        updateControls();
    })();
    </script>
</body>

</html>