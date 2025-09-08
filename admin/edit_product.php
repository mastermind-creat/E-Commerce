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

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $_POST['name'];
    $description = $_POST['description'];
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];
    $category_id = $_POST['category_id'];
    

    // Update product
    $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=? WHERE id=?");
    $stmt->execute([$name, $description, $price, $stock, $category_id, $product_id]);


    // Handle new images upload
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

// Delete image action
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
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700">Category</label>
                    <select name="category_id" class="w-full border rounded p-2" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-700">Add New Images</label>
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
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Update
                    Product</button>
            </div>
        </form>
    </div>

</body>

</html>