<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once 'auth.php';

if (!isset($_GET['id'])) {
    die("Product ID is required.");
}

$product_id = (int) $_GET['id'];

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

// Fetch product images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch product variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Handle Delete Image Request ---
if (isset($_GET['delete_image'])) {
    $img_id = (int) $_GET['delete_image'];
    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id=? AND product_id=?");
    $stmt->execute([$img_id, $product_id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($img) {
        $filepath = "../public/assets/products/" . $img['image_url'];
        if (file_exists($filepath)) unlink($filepath);
        $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$img_id]);
    }

    header("Location: edit_product.php?id=" . $product_id);
    exit;
}

// --- Handle Delete Variant Request ---
if (isset($_GET['delete_variant'])) {
    $variant_id = (int) $_GET['delete_variant'];
    $stmt = $pdo->prepare("SELECT variant_image, variant_stock FROM product_variants WHERE id=? AND product_id=?");
    $stmt->execute([$variant_id, $product_id]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($variant) {
        // Decrease product stock by deleted variant stock
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id=?")
            ->execute([$variant['variant_stock'], $product_id]);

        if (!empty($variant['variant_image']) && file_exists("../public/" . $variant['variant_image'])) {
            unlink("../public/" . $variant['variant_image']);
        }
        $pdo->prepare("DELETE FROM product_variants WHERE id=?")->execute([$variant_id]);
    }

    header("Location: edit_product.php?id=" . $product_id);
    exit;
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $_POST['name'];
    $description = $_POST['description'];
    $price       = $_POST['price'];
    $stock       = (int) $_POST['stock'];
    $category_id = $_POST['category_id'];

    // First update product basic details
    $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=? WHERE id=?");
    $stmt->execute([$name, $description, $price, $stock, $category_id, $product_id]);

    // --- Update Existing Variants ---
    if (!empty($_POST['variant_id'])) {
        foreach ($_POST['variant_id'] as $i => $variantId) {
            $variantName  = $_POST['variant_name'][$i] ?? '';
            $variantValue = $_POST['variant_value'][$i] ?? '';
            $variantStock = (int) ($_POST['variant_stock'][$i] ?? 0);

            $variantImagePath = $_POST['existing_variant_image'][$i] ?? null;
            if (!empty($_FILES['variant_image']['name'][$i])) {
                $uploadDir = "../public/assets/variants/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = uniqid() . "_" . basename($_FILES['variant_image']['name'][$i]);
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['variant_image']['tmp_name'][$i], $targetFile)) {
                    if (!empty($variantImagePath) && file_exists("../public/" . $variantImagePath)) {
                        unlink("../public/" . $variantImagePath);
                    }
                    $variantImagePath = "assets/variants/" . $fileName;
                }
            }

            // Get current variant stock to adjust total
            $oldStockStmt = $pdo->prepare("SELECT variant_stock FROM product_variants WHERE id=?");
            $oldStockStmt->execute([$variantId]);
            $oldStock = (int)$oldStockStmt->fetchColumn();

            // Update product stock dynamically
            $stockDiff = $variantStock - $oldStock;
            if ($stockDiff !== 0) {
                $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?")
                    ->execute([$stockDiff, $product_id]);
            }

            $stmt = $pdo->prepare("UPDATE product_variants 
                                   SET variant_name=?, variant_value=?, variant_stock=?, variant_image=? 
                                   WHERE id=?");
            $stmt->execute([$variantName, $variantValue, $variantStock, $variantImagePath, $variantId]);
        }
    }

    // --- Insert New Variants ---
    if (!empty($_POST['new_variant_name'])) {
        $stmtVar = $pdo->prepare("INSERT INTO product_variants 
                                (product_id, variant_name, variant_value, variant_stock, variant_image) 
                                VALUES (?, ?, ?, ?, ?)");
        foreach ($_POST['new_variant_name'] as $i => $variantName) {
            if (!empty($variantName)) {
                $variantValue = $_POST['new_variant_value'][$i] ?? '';
                $variantStock = (int) ($_POST['new_variant_stock'][$i] ?? 0);
                $variantImagePath = null;

                if (!empty($_FILES['new_variant_image']['name'][$i])) {
                    $uploadDir = "../public/assets/variants/";
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $fileName = uniqid() . "_" . basename($_FILES['new_variant_image']['name'][$i]);
                    $targetFile = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['new_variant_image']['tmp_name'][$i], $targetFile)) {
                        $variantImagePath = "assets/variants/" . $fileName;
                    }
                }

                $stmtVar->execute([$product_id, $variantName, $variantValue, $variantStock, $variantImagePath]);

                // Increment product stock by new variant stock
                if ($variantStock > 0) {
                    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?")
                        ->execute([$variantStock, $product_id]);
                }
            }
        }
    }

    // Handle new product images
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = uniqid() . "_" . basename($_FILES['images']['name'][$key]);
                $targetPath = "../public/assets/products/" . $filename;
                move_uploaded_file($tmp_name, $targetPath);

                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                $stmt->execute([$product_id, $filename]);
            }
        }
    }

    header("Location: products.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="p-6 ml-64">
        <h1 class="text-2xl font-bold mb-6">Edit Product</h1>

        <form action="" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow space-y-4">
            <div>
                <label class="block text-gray-700">Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>"
                    class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block text-gray-700">Description</label>
                <textarea name="description" class="w-full border rounded p-2"
                    rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700">Price (Ksh)</label>
                    <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>"
                        class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-gray-700">Stock</label>
                    <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>"
                        class="w-full border rounded p-2" required>
                </div>
            </div>

            <div>
                <label class="block text-gray-700">Category</label>
                <select name="category_id" class="w-full border rounded p-2" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Variants Section -->
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Variants</label>
                <div class="space-y-3">
                    <?php if (!empty($variants)): ?>
                    <?php foreach ($variants as $i => $v): ?>
                    <div class="border p-3 rounded bg-gray-50">
                        <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
                        <input type="hidden" name="existing_variant_image[]"
                            value="<?= htmlspecialchars($v['variant_image']) ?>">

                        <div class="grid grid-cols-4 gap-2">
                            <input type="text" name="variant_name[]" value="<?= htmlspecialchars($v['variant_name']) ?>"
                                placeholder="Name" class="border rounded p-2">
                            <input type="text" name="variant_value[]"
                                value="<?= htmlspecialchars($v['variant_value']) ?>" placeholder="Value"
                                class="border rounded p-2">
                            <input type="number" name="variant_stock[]"
                                value="<?= htmlspecialchars($v['variant_stock']) ?>" placeholder="Stock"
                                class="border rounded p-2">
                            <input type="file" name="variant_image[]" class="border rounded p-2">
                        </div>

                        <?php if (!empty($v['variant_image'])): ?>
                        <div class="mt-2 flex items-center gap-2">
                            <img src="../public/<?= htmlspecialchars($v['variant_image']) ?>"
                                class="h-16 w-16 object-cover rounded border">
                            <a href="edit_product.php?id=<?= $product_id ?>&delete_variant=<?= $v['id'] ?>"
                                class="text-red-600 text-sm"
                                onclick="return confirm('Delete this variant?');">Delete</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="text-gray-500">No variants added yet.</p>
                    <?php endif; ?>
                </div>

                <hr class="my-3">

                <div id="new-variants" class="space-y-2"></div>
                <button type="button" onclick="addNewVariant()" class="mt-2 bg-green-500 text-white px-3 py-1 rounded">
                    + Add Variant
                </button>
            </div>

            <div>
                <label class="block text-gray-700">Add New Product Images</label>
                <input type="file" name="images[]" multiple class="w-full border rounded p-2">
            </div>

            <div>
                <label class="block text-gray-700">Existing Images</label>
                <div class="flex flex-wrap gap-4 mt-2">
                    <?php foreach ($images as $img): ?>
                    <div class="relative">
                        <img src="../public/assets/products/<?= htmlspecialchars($img['image_url']) ?>"
                            class="w-24 h-24 object-cover rounded border">
                        <a href="edit_product.php?id=<?= $product_id ?>&delete_image=<?= $img['id'] ?>"
                            onclick="return confirm('Delete this image?');"
                            class="absolute top-1 right-1 bg-red-600 text-white px-2 py-1 text-xs rounded">X</a>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($images)): ?>
                    <p class="text-gray-500">No images uploaded.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex justify-between">
                <a href="products.php" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Update Product
                </button>
            </div>
        </form>
    </div>

    <script>
    function addNewVariant() {
        const container = document.getElementById('new-variants');
        const index = container.children.length;
        const div = document.createElement('div');
        div.className = "grid grid-cols-4 gap-2 border p-2 rounded bg-gray-50";
        div.innerHTML = `
        <input type="text" name="new_variant_name[${index}]" placeholder="Name" class="border rounded p-2">
        <input type="text" name="new_variant_value[${index}]" placeholder="Value" class="border rounded p-2">
        <input type="number" name="new_variant_stock[${index}]" placeholder="Stock" class="border rounded p-2">
        <input type="file" name="new_variant_image[${index}]" class="border rounded p-2">
    `;
        container.appendChild(div);
    }
    </script>

</body>

</html>