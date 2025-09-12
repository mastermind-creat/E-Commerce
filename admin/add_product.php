<?php
include('auth.php');
include('../includes/db.php');

$success = $error = "";

// Fetch categories
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $_POST['name'];
    $description = $_POST['description'];
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $status      = $_POST['status'];
    $variants    = $_POST['variants'] ?? [];

    try {
        // Insert product
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock, status) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $description, $price, $stock, $status]);
        $product_id = $pdo->lastInsertId();

        // Insert variants (only if provided)
        if (!empty($variants)) {
            $stmtVar = $pdo->prepare("INSERT INTO product_variants (product_id, variant_name, variant_stock, variant_image) 
                                      VALUES (?, ?, ?, ?)");
            foreach ($variants as $index => $variant) {
                if (!empty($variant['name']) && isset($variant['stock']) && $variant['stock'] !== '') {

                    // Handle optional variant image upload
                    $variantImagePath = null;
                    if (!empty($_FILES['variants']['name'][$index]['image'])) {
                        $uploadDir = "../public/assets/variants/";
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $fileName = uniqid() . "_" . basename($_FILES['variants']['name'][$index]['image']);
                        $targetFile = $uploadDir . $fileName;

                        if (move_uploaded_file($_FILES['variants']['tmp_name'][$index]['image'], $targetFile)) {
                            $variantImagePath = "assets/variants/" . $fileName;
                        }
                    }

                    $stmtVar->execute([$product_id, $variant['name'], $variant['stock'], $variantImagePath]);
                }
            }
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
    <title>Add Product</title>
</head>

<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="flex-1 p-4 sm:p-6 md:ml-64">
            <h1 class="text-2xl font-bold mb-6">Add New Product</h1>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-300 text-green-800 p-3 rounded mb-4"><?= $success ?></div>
            <?php elseif ($error): ?>
            <div class="bg-red-100 border border-red-300 text-red-800 p-3 rounded mb-4"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-md max-w-2xl">
                <div class="mb-4">
                    <label class="block mb-1 font-medium">Product Name</label>
                    <input type="text" name="name" class="w-full px-4 py-2 border rounded-lg" required>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Description</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-lg"
                        required></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-1 font-medium">Price (KSh)</label>
                        <input type="number" step="0.01" name="price" class="w-full px-4 py-2 border rounded-lg"
                            required>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Stock</label>
                        <input type="number" name="stock" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Category</label>
                    <select name="category_id" class="w-full px-4 py-2 border rounded-lg" required>
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
                        class="mt-2 bg-gray-200 px-3 py-1 rounded hover:bg-gray-300">+ Add Variant</button>
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
                    <input type="file" name="images[]" multiple class="w-full border rounded-lg px-4 py-2">
                    <small class="text-gray-500">You can select multiple images</small>
                </div>

                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
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
        div.classList.add('grid', 'grid-cols-3', 'gap-2', 'items-center');
        div.innerHTML = `
            <input type="text" name="variants[${index}][name]" placeholder="Variant name" class="border p-2 rounded">
            <input type="number" name="variants[${index}][stock]" placeholder="Stock" class="border p-2 rounded">
            <input type="file" name="variants[${index}][image]" class="border p-2 rounded">
        `;
        container.appendChild(div);
    }
    </script>
</body>

</html>