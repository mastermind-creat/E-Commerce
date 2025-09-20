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

    try {
        // Insert product
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, sku, description, price, stock, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $sku, $description, $price, $stock, $status]);
        $product_id = $pdo->lastInsertId();

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
            $uploadDir = "../public/assets/products/";
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $fileName = uniqid() . "_" . basename($_FILES['images']['name'][$key]);
                $targetFile = $uploadDir . $fileName;

                $check = getimagesize($tmp_name);
                if ($check !== false) {
                    if (move_uploaded_file($tmp_name, $targetFile)) {
                        $stmtImg = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                        $stmtImg->execute([$product_id, $fileName]);
                    }
                }
            }
        }

        $success = "✅ Product added successfully!";
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
                        <input type="number" step="0.01" name="price"
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
                    <input type="file" name="images[]" multiple class="w-full border rounded-lg px-4 py-2"
                        onchange="previewImages(event)">
                    <small class="text-gray-500">You can select multiple images</small>
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
    </script>
</body>

</html>