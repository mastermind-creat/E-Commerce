<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once 'auth.php';

// Helper: build asset URL
function asset_url($filename) {
    return '/assets/' . ltrim($filename, '/');
}

// ===== HERO SLIDER MANAGEMENT =====
if (isset($_POST['section']) && $_POST['section'] == 'hero' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $button_text = $_POST['button_text'];
        $button_link = $_POST['button_link'];
        $order_num = $_POST['order_num'];
        $active = isset($_POST['active']) ? 1 : 0;

        // Image handling
        $image_filename = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = __DIR__ . '/../assets/';
            // Check if directory exists, create if not (with error handling)
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $message = 'Error: Unable to create assets directory. Check permissions.';
                    goto end_processing; // Skip further processing on failure
                }
            }
            // Ensure directory is writable
            if (!is_writable($upload_dir)) {
                $message = 'Error: Assets directory is not writable. Check permissions.';
                goto end_processing;
            }
            $image_filename = time() . '_' . basename($_FILES['image']['name']);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_filename)) {
                $message = 'Error: Failed to move uploaded file. Check directory permissions.';
                goto end_processing;
            }
        } elseif ($_POST['action'] == 'edit') {
            $image_filename = $_POST['existing_image'];
        }

        if ($_POST['action'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO hero_slides (image_path, title, description, button_text, button_link, order_num, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$image_filename, $title, $description, $button_text, $button_link, $order_num, $active]);
        } else {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE hero_slides SET image_path = ?, title = ?, description = ?, button_text = ?, button_link = ?, order_num = ?, active = ? WHERE id = ?");
            $stmt->execute([$image_filename, $title, $description, $button_text, $button_link, $order_num, $active, $id]);
        }
        $message = 'Hero slide updated successfully!';
    } elseif ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT image_path FROM hero_slides WHERE id = ?");
        $stmt->execute([$id]);
        $image_filename = $stmt->fetchColumn();
        if ($image_filename) {
            $file_path = __DIR__ . '/../assets/' . $image_filename;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $pdo->prepare("DELETE FROM hero_slides WHERE id = ?")->execute([$id]);
        $message = 'Hero slide deleted successfully!';
    }
    end_processing:
}
$hero_slides = $pdo->query("SELECT * FROM hero_slides ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// ===== PROMO TILES MANAGEMENT =====
if (isset($_POST['section']) && $_POST['section'] == 'promo' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $price_text = $_POST['price_text'];
        $link = $_POST['link'];
        $order_num = $_POST['order_num'];
        $active = isset($_POST['active']) ? 1 : 0;

        $image_filename = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = __DIR__ . '/../assets/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $message = 'Error: Unable to create assets directory. Check permissions.';
                    goto end_processing_promo;
                }
            }
            if (!is_writable($upload_dir)) {
                $message = 'Error: Assets directory is not writable. Check permissions.';
                goto end_processing_promo;
            }
            $image_filename = time() . '_' . basename($_FILES['image']['name']);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_filename)) {
                $message = 'Error: Failed to move uploaded file. Check directory permissions.';
                goto end_processing_promo;
            }
        } elseif ($_POST['action'] == 'edit') {
            $image_filename = $_POST['existing_image'];
        }

        if ($_POST['action'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO promo_tiles (image_path, title, description, price_text, link, order_num, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$image_filename, $title, $description, $price_text, $link, $order_num, $active]);
        } else {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE promo_tiles SET image_path = ?, title = ?, description = ?, price_text = ?, link = ?, order_num = ?, active = ? WHERE id = ?");
            $stmt->execute([$image_filename, $title, $description, $price_text, $link, $order_num, $active, $id]);
        }
        $message = 'Promo tile updated successfully!';
    } elseif ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT image_path FROM promo_tiles WHERE id = ?");
        $stmt->execute([$id]);
        $image_filename = $stmt->fetchColumn();
        if ($image_filename) {
            $file_path = __DIR__ . '/../assets/' . $image_filename;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $pdo->prepare("DELETE FROM promo_tiles WHERE id = ?")->execute([$id]);
        $message = 'Promo tile deleted successfully!';
    }
    end_processing_promo:
}
$promo_tiles = $pdo->query("SELECT * FROM promo_tiles ORDER BY order_num ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Landing Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow">
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <h1 class="text-3xl font-bold mb-6">Manage Landing Page Sections</h1>

            <?php if (isset($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <!-- HERO SLIDER -->
            <section class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-2xl font-semibold mb-4">Hero Slider Management</h2>
                <!-- Add/Edit Form -->
                <form method="POST" enctype="multipart/form-data" class="mb-6">
                    <input type="hidden" name="section" value="hero">
                    <input type="hidden" name="action" value="<?= isset($_GET['edit_hero']) ? 'edit' : 'add' ?>">
                    <?php if (isset($_GET['edit_hero'])):
                    $slide = $pdo->prepare("SELECT * FROM hero_slides WHERE id = ?");
                    $slide->execute([$_GET['edit_hero']]);
                    $current_slide = $slide->fetch(PDO::FETCH_ASSOC);
                endif; ?>
                    <input type="hidden" name="id" value="<?= $current_slide['id'] ?? '' ?>">
                    <input type="hidden" name="existing_image" value="<?= $current_slide['image_path'] ?? '' ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Image</label>
                            <input type="file" name="image" accept="image/*" class="w-full p-2 border rounded">
                            <?php if (!empty($current_slide['image_path'])): ?>
                            <p class="text-sm text-gray-500 mt-1">Current:
                                <?= htmlspecialchars($current_slide['image_path']) ?></p>
                            <img src="<?= asset_url($current_slide['image_path']) ?>"
                                class="w-32 h-32 object-cover mt-2 rounded">
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Order Number</label>
                            <input type="number" name="order_num" value="<?= $current_slide['order_num'] ?? '' ?>"
                                required class="w-full p-2 border rounded">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Title</label>
                        <textarea name="title" rows="2"
                            class="w-full p-2 border rounded"><?= $current_slide['title'] ?? '' ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full p-2 border rounded"><?= $current_slide['description'] ?? '' ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Button Text</label>
                            <input type="text" name="button_text" value="<?= $current_slide['button_text'] ?? '' ?>"
                                class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Button Link</label>
                            <input type="url" name="button_link" value="<?= $current_slide['button_link'] ?? '' ?>"
                                class="w-full p-2 border rounded">
                        </div>
                    </div>
                    <label class="flex items-center mb-4">
                        <input type="checkbox" name="active" <?= !empty($current_slide['active']) ? 'checked' : '' ?>
                            class="mr-2"> Active
                    </label>

                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Save Hero Slide</button>
                </form>

                <!-- List of Hero Slides -->
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Image</th>
                            <th class="px-4 py-2 text-left">Title</th>
                            <th class="px-4 py-2 text-left">Order</th>
                            <th class="px-4 py-2 text-left">Active</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hero_slides as $slide): ?>
                        <tr class="border-t">
                            <td class="px-4 py-2"><img src="<?= asset_url($slide['image_path']) ?>"
                                    class="w-16 h-16 object-cover rounded"></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($slide['title']) ?></td>
                            <td class="px-4 py-2"><?= $slide['order_num'] ?></td>
                            <td class="px-4 py-2"><?= $slide['active'] ? 'Yes' : 'No' ?></td>
                            <td class="px-4 py-2">
                                <a href="?edit_hero=<?= $slide['id'] ?>" class="text-blue-500 underline mr-2">Edit</a>
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Delete this slide?')">
                                    <input type="hidden" name="section" value="hero">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $slide['id'] ?>">
                                    <button type="submit" class="text-red-500 underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($hero_slides)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-center text-gray-500">No hero slides yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- PROMO TILES -->
            <section class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-semibold mb-4">Promo Tiles Management</h2>
                <form method="POST" enctype="multipart/form-data" class="mb-6">
                    <input type="hidden" name="section" value="promo">
                    <input type="hidden" name="action" value="<?= isset($_GET['edit_promo']) ? 'edit' : 'add' ?>">
                    <?php if (isset($_GET['edit_promo'])):
                    $tile = $pdo->prepare("SELECT * FROM promo_tiles WHERE id = ?");
                    $tile->execute([$_GET['edit_promo']]);
                    $current_tile = $tile->fetch(PDO::FETCH_ASSOC);
                endif; ?>
                    <input type="hidden" name="id" value="<?= $current_tile['id'] ?? '' ?>">
                    <input type="hidden" name="existing_image" value="<?= $current_tile['image_path'] ?? '' ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Image</label>
                            <input type="file" name="image" class="w-full p-2 border rounded">
                            <?php if (!empty($current_tile['image_path'])): ?>
                            <img src="<?= asset_url($current_tile['image_path']) ?>"
                                class="w-20 h-20 object-cover mt-2 rounded">
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Order (1–3)</label>
                            <input type="number" name="order_num" value="<?= $current_tile['order_num'] ?? '' ?>"
                                required class="w-full p-2 border rounded">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Title</label>
                        <input type="text" name="title" value="<?= $current_tile['title'] ?? '' ?>" required
                            class="w-full p-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea name="description" rows="2"
                            class="w-full p-2 border rounded"><?= $current_tile['description'] ?? '' ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Price Text</label>
                            <input type="text" name="price_text" value="<?= $current_tile['price_text'] ?? '' ?>"
                                class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Link</label>
                            <input type="url" name="link" value="<?= $current_tile['link'] ?? '' ?>"
                                class="w-full p-2 border rounded">
                        </div>
                    </div>
                    <label class="flex items-center mb-4">
                        <input type="checkbox" name="active" <?= !empty($current_tile['active']) ? 'checked' : '' ?>
                            class="mr-2"> Active
                    </label>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Save Promo Tile</button>
                </form>

                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">Image</th>
                            <th class="px-4 py-2">Title</th>
                            <th class="px-4 py-2">Order</th>
                            <th class="px-4 py-2">Active</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promo_tiles as $tile): ?>
                        <tr class="border-t">
                            <td class="px-4 py-2"><img src="<?= asset_url($tile['image_path']) ?>"
                                    class="w-20 h-20 object-cover rounded"></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($tile['title']) ?></td>
                            <td class="px-4 py-2"><?= $tile['order_num'] ?></td>
                            <td class="px-4 py-2"><?= $tile['active'] ? 'Yes' : 'No' ?></td>
                            <td class="px-4 py-2">
                                <a href="?edit_promo=<?= $tile['id'] ?>" class="text-blue-500 underline mr-2">Edit</a>
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Delete this tile?')">
                                    <input type="hidden" name="section" value="promo">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $tile['id'] ?>">
                                    <button type="submit" class="text-red-500 underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($promo_tiles)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-center text-gray-500">No promo tiles yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</body>

</html>