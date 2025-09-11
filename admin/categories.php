<?php
include('auth.php');
include('../includes/db.php');

$success = $error = "";

// Handle category addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
        $success = "✅ Category added successfully!";
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Handle category delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $success = "🗑️ Category deleted successfully!";
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Fetch categories
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Manage Categories</title>
</head>

<body class="bg-gray-100 font-sans">
    <div class="flex flex-col md:flex-row">
        <?php include('sidebar.php'); ?>

        <!-- Main content -->
        <main class="flex-1 p-6 md:ml-64">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-700">Manage Categories</h1>
            </div>

            <!-- Success / Error messages -->
            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded-lg mb-4">
                <?= $success ?>
            </div>
            <?php elseif ($error): ?>
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded-lg mb-4"><?= $error ?></div>
            <?php endif; ?>

            <!-- Two column layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Add Category Form -->
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h2 class="text-lg font-semibold mb-4 text-gray-700">➕ Add New Category</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block mb-1 font-medium text-gray-600">Category Name</label>
                            <input type="text" name="name"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400"
                                placeholder="Enter category name" required>
                        </div>
                        <button type="submit" name="add_category"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-blue-700 transition w-full md:w-auto text-center">
                            Add Category
                        </button>
                    </form>
                </div>

                <!-- Categories Table -->
                <div class="bg-white p-6 rounded-xl shadow-md overflow-x-auto">
                    <h2 class="text-lg font-semibold mb-4 text-gray-700">📂 All Categories</h2>
                    <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden text-sm md:text-base">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700">
                                <th class="p-2 md:p-3 text-left">ID</th>
                                <th class="p-2 md:p-3 text-left">Name</th>
                                <th class="p-2 md:p-3 text-left">Slug</th>
                                <th class="p-2 md:p-3 text-left">Created At</th>
                                <th class="p-2 md:p-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-2 md:p-3"><?= $cat['id'] ?></td>
                                <td class="p-2 md:p-3 font-medium"><?= htmlspecialchars($cat['name']) ?></td>
                                <td class="p-2 md:p-3 text-gray-600"><?= htmlspecialchars($cat['slug']) ?></td>
                                <td class="p-2 md:p-3 text-sm text-gray-500"><?= $cat['created_at'] ?></td>
                                <td class="p-2 md:p-3 text-center">
                                    <a href="categories.php?delete=<?= $cat['id'] ?>"
                                        onclick="return confirm('Are you sure you want to delete this category?')"
                                        class="bg-red-500 text-white px-3 py-1 rounded-lg shadow hover:bg-red-600 transition">
                                        🗑️ Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-3 text-center text-gray-500">No categories found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>