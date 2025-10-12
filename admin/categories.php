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
    <title>Manage Categories - Springs Store Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .card-hover {
        transition: all 0.3s ease;
    }
    
    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="flex flex-col md:flex-row">
        <?php include('sidebar.php'); ?>

        <!-- Main content -->
        <main class="flex-1 p-6 md:ml-64">
            <!-- Header -->
            <div class="mb-8">
                <div class="gradient-bg rounded-2xl p-8 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="relative z-10">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 class="text-3xl sm:text-4xl font-bold mb-2">Manage Categories</h1>
                                <p class="text-blue-100 text-lg">Organize your products with categories</p>
                            </div>
                            <div class="mt-4 sm:mt-0 flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-blue-100 text-sm">Total Categories</p>
                                    <p class="text-2xl font-bold"><?= count($categories) ?></p>
                                </div>
                                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                                    <i data-feather="folder" class="w-8 h-8"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-12 -translate-x-12"></div>
                </div>
            </div>

            <!-- Success / Error messages -->
            <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
                <?= $success ?>
            </div>
            <?php elseif ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
                <?= $error ?>
            </div>
            <?php endif; ?>

            <!-- Two column layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Add Category Form -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 card-hover">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="flex items-center gap-3 text-lg font-semibold text-gray-900">
                            <i data-feather="plus-circle" class="w-5 h-5 text-blue-600"></i> 
                            Add New Category
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Create a new product category</p>
                    </div>
                    <form method="POST" class="p-6 space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                            <input type="text" name="name"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                placeholder="Enter category name" required>
                        </div>
                        <button type="submit" name="add_category"
                            class="w-full inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            <i data-feather="plus" class="w-4 h-4 mr-2"></i>
                            Add Category
                        </button>
                    </form>
                </div>

                <!-- Categories Table -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 card-hover mb-12">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="flex items-center gap-3 text-lg font-semibold text-gray-900">
                            <i data-feather="folder" class="w-5 h-5 text-gray-600"></i> 
                            All Categories
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Manage existing product categories</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $cat['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($cat['name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?= htmlspecialchars($cat['slug']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($cat['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a href="categories.php?delete=<?= $cat['id'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this category?')"
                                            class="inline-flex items-center gap-2 bg-red-500 text-white px-3 py-2 rounded-lg shadow hover:bg-red-600 transition-colors"
                                            aria-label="Delete category <?= htmlspecialchars($cat['name']) ?>">
                                            <i data-feather="trash-2" class="w-4 h-4"></i>
                                            <span class="hidden sm:inline">Delete</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i data-feather="folder-x" class="w-12 h-12 text-gray-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No categories found</h3>
                                            <p class="text-gray-500">Get started by creating your first category.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        try {
            feather.replace();
        } catch (e) {
            /* ignore */
        }
    }
});
</script>