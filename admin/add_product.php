<?php
include('auth.php');
include('../includes/db.php');

$success = $error = "";

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $_POST['name'];
    $description = $_POST['description'];
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $status      = $_POST['status'];

    try {
        // Insert product
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock, status) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $description, $price, $stock, $status]);

        $product_id = $pdo->lastInsertId();

        // Handle multiple images
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = "../public/assets/products/";

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $fileName = uniqid() . "_" . basename($_FILES['images']['name'][$key]);
                $targetFile = $uploadDir . $fileName;

                // Validate image
                $check = getimagesize($tmp_name);
                if ($check !== false) {
                    if (move_uploaded_file($tmp_name, $targetFile)) {
                        // Save into product_images table
                        $stmtImg = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                        $stmtImg->execute([$product_id, $fileName]);
                    }
                }
            }
        }

        $success = "Product added successfully!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
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

<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg p-6 hidden md:block">
            <h2 class="text-xl font-bold mb-6">Admin Panel</h2>
            <nav class="space-y-3">
                <a href="dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-200">Dashboard</a>
                <a href="products.php" class="block px-4 py-2 rounded-lg bg-blue-100 text-blue-700">Products</a>
                <a href="orders.php" class="block px-4 py-2 rounded-lg hover:bg-gray-200">Orders</a>
                <a href="logout.php" class="block px-4 py-2 rounded-lg hover:bg-red-100 text-red-600">Logout</a>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="flex-1 p-6">
            <h1 class="text-2xl font-bold mb-6">Add New Product</h1>

            <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= $success ?></div>
            <?php elseif ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-md max-w-lg">
                <div class="mb-4">
                    <label class="block mb-1 font-medium">Product Name</label>
                    <input type="text" name="name" class="w-full px-4 py-2 border rounded-lg" required>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Description</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-lg"
                        required></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
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

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Status</label>
                    <select name="status" class="w-full px-4 py-2 border rounded-lg" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Upload Images</label>
                    <input type="file" name="images[]" multiple class="w-full border rounded-lg px-4 py-2">
                    <small class="text-gray-500">You can select multiple images</small>
                </div>

                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Add
                    Product</button>
            </form>
        </main>
    </div>
</body>

</html>