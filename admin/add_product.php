<?php
include('auth.php');
include('../includes/db.php');

$success = $error = "";

// Fetch categories
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $_POST['name'];
    $sku         = $_POST['sku'] ?? '';
    $description = $_POST['description'];
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $status      = $_POST['status'];
    $variants    = $_POST['variants'] ?? [];
    
    // Discount fields
    $discount_percentage = $_POST['discount_percentage'] ?? 0;
    $discount_start_date = $_POST['discount_start_date'] ?: null;
    $discount_end_date = $_POST['discount_end_date'] ?: null;
    $is_discounted = ($discount_percentage > 0) ? 1 : 0;

    try {
        // Insert product
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, sku, description, price, stock, status, discount_percentage, discount_start_date, discount_end_date, is_discounted) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $sku, $description, $price, $stock, $status, $discount_percentage, $discount_start_date, $discount_end_date, $is_discounted]);
        $product_id = $pdo->lastInsertId();

        // If SKU not provided, auto-generate one based on product ID to ensure uniqueness
        if (empty(trim($sku))) {
            // Format: PROD-000001 (zero-padded to 6 digits)
            $generatedSku = sprintf('PROD-%06d', $product_id);
            $pdo->prepare("UPDATE products SET sku = ? WHERE id = ?")->execute([$generatedSku, $product_id]);
            $sku = $generatedSku; // keep $sku variable in sync for any further use
        }

        // Insert variants (optional) and sync total stock on product
        $totalVariantStock = 0;
        if (!empty($variants)) {
            // Align with schema: columns color, size, stock, extra_price
            $stmtVar = $pdo->prepare("INSERT INTO product_variants (product_id, color, size, stock, extra_price) VALUES (?, ?, ?, ?, ?)");
            foreach ($variants as $index => $variant) {
                $color = trim($variant['color'] ?? '');
                $size = trim($variant['size'] ?? '');
                $vStock = (int)($variant['stock'] ?? 0);
                $extra = (float)($variant['extra_price'] ?? 0);
                if ($color !== '' || $size !== '') {
                    $stmtVar->execute([$product_id, $color, $size, $vStock, $extra]);
                    $totalVariantStock += $vStock;
                }
            }
        }

        // If variants provided, update product stock to sum of variant stock
        if ($totalVariantStock > 0) {
            $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$totalVariantStock, $product_id]);
        }

        // Handle main product images
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = realpath(__DIR__ . '/../public/assets/products/') . '/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (!is_writable($uploadDir)) {
                $error = "Upload directory not writable: " . htmlspecialchars($uploadDir);
            } else {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff', 'image/svg+xml'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];
                
                $uploadedCount = 0;
                $totalFiles = count($_FILES['images']['name']);
                
                for ($key = 0; $key < $totalFiles; $key++) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $originalName = $_FILES['images']['name'][$key];
                        $tmpName = $_FILES['images']['tmp_name'][$key];
                        $fileType = $_FILES['images']['type'][$key];
                        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                        if (in_array($fileType, $allowedTypes) && in_array($fileExtension, $allowedExtensions)) {
                            $fileName = uniqid('prod_', true) . '.' . $fileExtension;
                            $targetFile = $uploadDir . $fileName;

                            if (move_uploaded_file($tmpName, $targetFile)) {
                                $stmtImg = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                                $stmtImg->execute([$product_id, $fileName]);
                                $uploadedCount++;
                            } else {
                                error_log("Failed to move uploaded file: $originalName → $targetFile");
                            }
                        } else {
                            error_log("Invalid file: $originalName ($fileType)");
                        }
                    } else {
                        error_log("Upload error code: " . $_FILES['images']['error'][$key]);
                    }
                }

                $success = "Product added successfully! Uploaded $uploadedCount of $totalFiles images.";
            }
        } else {
            $success = "Product added successfully! (No images uploaded)";
        }

    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <title>Add Product</title>
</head>

<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="flex-1 p-4 sm:p-6 md:ml-64">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold">Add New Product</h1>
                <a href="products.php" class="text-sm text-gray-500 hover:text-gray-700 transition">Back to Products</a>
            </div>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-300 text-green-800 p-3 rounded mb-4"><?= $success ?></div>
            <?php elseif ($error): ?>
            <div class="bg-red-100 border border-red-300 text-red-800 p-3 rounded mb-4"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-2xl shadow-lg max-w-3xl">
                <div class="mb-4">
                    <label class="block mb-1 font-medium">Product Name</label>
                    <input type="text" name="name"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">SKU (Stock Keeping Unit)</label>
                    <input type="text" name="sku" placeholder="e.g., PROD-001"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <small class="text-gray-500">Optional: Unique identifier for inventory tracking</small>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Description</label>
                    <textarea name="description" rows="4"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        required></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-1 font-medium">Price (KSh)</label>
                        <input type="number" step="0.01" name="price" id="price"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Stock</label>
                        <input type="number" name="stock"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>
                </div>

                <!-- Discount Section (Optional) -->
                <div class="mb-6 p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl border border-orange-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i data-feather="percent" class="w-5 h-5 mr-2 text-orange-600"></i>
                        Discount Settings <span class="text-sm font-normal text-gray-600 ml-2">(Optional)</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block mb-1 font-medium text-gray-700">Discount Percentage</label>
                            <input type="number" step="0.01" min="0" max="100" name="discount_percentage"
                                id="discount_percentage"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                                placeholder="0.00" onchange="calculateDiscountedPrice()">
                            <small class="text-gray-500">Enter percentage (0-100) - Leave empty for no discount</small>
                        </div>
                        <div>
                            <label class="block mb-1 font-medium text-gray-700">Start Date</label>
                            <input type="datetime-local" name="discount_start_date" id="discount_start_date"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                            <small class="text-gray-500">Optional - Leave empty for immediate start</small>
                        </div>
                        <div>
                            <label class="block mb-1 font-medium text-gray-700">End Date</label>
                            <input type="datetime-local" name="discount_end_date" id="discount_end_date"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                            <small class="text-gray-500">Optional - Leave empty for no end date</small>
                        </div>
                    </div>

                    <!-- Price Preview -->
                    <div id="price-preview" class="mt-4 p-3 bg-white rounded-lg border border-orange-200 hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Original Price:</span>
                            <span id="original-price" class="text-lg font-semibold text-gray-800"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Discounted Price:</span>
                            <span id="discounted-price" class="text-xl font-bold text-green-600"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">You Save:</span>
                            <span id="savings" class="text-lg font-semibold text-red-600"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Category</label>
                    <select name="category_id"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- VARIANTS SECTION -->
                <div class="mb-4">
                    <label class="block mb-2 font-medium">Variants (Optional)</label>
                    <div id="variants-container" class="space-y-2"></div>
                    <button type="button" onclick="addVariant()"
                        class="mt-2 bg-gray-100 px-3 py-2 rounded hover:bg-gray-200 transition">+ Add Variant</button>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Status</label>
                    <select name="status" class="w-full px-4 py-2 border rounded-lg" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Upload Product Images</label>
                    <input type="file" name="images[]" multiple
                        accept="image/*,.jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff,.svg"
                        class="w-full border rounded-lg px-4 py-2" onchange="previewImages(event)">
                    <small class="text-gray-500">You can select multiple images (JPG, JPEG, PNG, GIF, WebP, BMP, TIFF,
                        SVG)</small>
                    <div id="image-previews" class="mt-3 grid grid-cols-3 gap-2"></div>
                </div>

                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transform transition active:scale-95">
                    Add Product
                </button>
            </form>
        </main>
    </div>

    <script>
    function addVariant() {
        const container = document.getElementById('variants-container');
        const index = container.children.length;
        const div = document.createElement('div');
        div.classList.add('grid', 'grid-cols-4', 'gap-2', 'items-center', 'p-2', 'rounded', 'bg-gray-50');
        div.innerHTML = `
            <input type="text" name="variants[${index}][color]" placeholder="Color" class="border p-2 rounded">
            <input type="text" name="variants[${index}][size]" placeholder="Size" class="border p-2 rounded">
            <input type="number" name="variants[${index}][stock]" placeholder="Stock" class="border p-2 rounded">
            <input type="number" step="0.01" name="variants[${index}][extra_price]" placeholder="Extra Price" class="border p-2 rounded">
        `;
        div.style.opacity = '0';
        div.style.transform = 'translateY(6px)';
        container.appendChild(div);
        requestAnimationFrame(() => {
            div.style.transition = 'all .2s ease';
            div.style.opacity = '1';
            div.style.transform = 'translateY(0)';
        });
    }

    function previewImages(event) {
        const container = document.getElementById('image-previews');
        container.innerHTML = '';
        const files = event.target.files;
        if (!files) return;
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full h-24 object-cover rounded border';
                container.appendChild(img);
            }
            reader.readAsDataURL(file);
        });
    }

    function calculateDiscountedPrice() {
        const price = parseFloat(document.getElementById('price').value) || 0;
        const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
        const pricePreview = document.getElementById('price-preview');

        if (price > 0 && discountPercentage > 0) {
            const discountAmount = (price * discountPercentage) / 100;
            const discountedPrice = price - discountAmount;

            document.getElementById('original-price').textContent = 'KSh ' + price.toFixed(2);
            document.getElementById('discounted-price').textContent = 'KSh ' + discountedPrice.toFixed(2);
            document.getElementById('savings').textContent = 'KSh ' + discountAmount.toFixed(2);

            pricePreview.classList.remove('hidden');
        } else {
            pricePreview.classList.add('hidden');
        }
    }

    // Initialize Feather icons
    feather.replace();
    </script>
</body>

</html>