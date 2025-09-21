<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pageTitle = "System Settings";
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = [];
        foreach ($_POST['settings'] ?? [] as $key => $value) {
            // Handle file uploads for image settings
            if (isset($_FILES['settings']['name'][$key]) && $_FILES['settings']['error'][$key] === UPLOAD_ERR_OK) {
                $file = $_FILES['settings']['tmp_name'][$key];
                $filename = $_FILES['settings']['name'][$key];
                
                // Generate safe filename
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $safeFilename = uniqid() . '_' . time() . '.' . $ext;
                
                // Determine upload directory based on setting type
                $uploadDir = __DIR__ . '/../public/assets/';
                if (strpos($key, 'logo') !== false) {
                    $uploadDir .= 'images/';
                }
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Move uploaded file
                if (move_uploaded_file($file, $uploadDir . $safeFilename)) {
                    $settings[$key] = 'assets/' . basename($uploadDir) . '/' . $safeFilename;
                }
            } else {
                $settings[$key] = trim($value);
            }
        }
        
        if (update_settings($settings)) {
            $message = 'Settings updated successfully!';
            $messageType = 'success';
            clear_settings_cache(); // Clear cache after update
        } else {
            $message = 'Error updating settings.';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current settings grouped by category
$categories = get_setting_categories();
$settings = [];
foreach ($categories as $category) {
    $settings[$category] = array_filter(get_all_settings($category));
}

include __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary-50: #fdf2f8;
        --primary-100: #fce7f3;
        --primary-200: #fbcfe8;
        --primary-300: #f9a8d4;
        --primary-400: #f472b6;
        --primary-500: #ec4899;
        --primary-600: #db2777;
        --primary-700: #be185d;
    }

    body {
        font-family: 'Inter', sans-serif;
    }

    .bg-gradient-primary {
        background: linear-gradient(to right, var(--primary-500), var(--primary-400));
    }

    .category-panel {
        transition: opacity 0.3s ease-in-out;
        opacity: 0;
        display: none;
    }

    .category-panel.active {
        opacity: 1;
        display: block;
    }

    .category-tab {
        transition: all 0.2s ease-in-out;
    }

    .category-tab:hover:not(.active) {
        background-color: var(--primary-50);
        color: var(--primary-500);
    }

    .form-input {
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        width: 100%;
        background-color: white;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-500);
        box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
    }

    .form-textarea {
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        width: 100%;
        background-color: white;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .form-textarea:focus {
        outline: none;
        border-color: var(--primary-500);
        box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
    }

    .file-input {
        padding: 0.5rem;
    }

    .file-input::file-selector-button {
        background-color: var(--primary-100);
        color: var(--primary-700);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        margin-right: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .file-input::file-selector-button:hover {
        background-color: var(--primary-200);
    }

    @media (max-width: 1024px) {
        .main-content {
            margin-left: 0;
            padding: 1rem;
        }

        .setting-grid {
            grid-template-columns: 1fr !important;
        }

        .col-span-4,
        .col-span-8 {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .tabs-container {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 0.5rem;
        }

        .category-tab {
            min-width: auto;
            padding: 0.75rem 1rem;
        }

        .header-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }

    @media (max-width: 640px) {
        .setting-item {
            margin-left: -1rem;
            margin-right: -1rem;
            padding: 1rem;
        }
    }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-4 md:p-8 main-content ml-0 lg:ml-64">
            <!-- Header -->
            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 md:mb-8 header-content">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">System Settings</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage your website's configuration and appearance</p>
                </div>
                <button type="submit" form="settingsForm"
                    class="w-full md:w-auto inline-flex items-center justify-center px-4 py-2 md:px-6 md:py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-primary hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-all">
                    <i data-feather="save" class="w-4 h-4 md:w-5 md:h-5 mr-2"></i>
                    Save Changes
                </button>
            </div>

            <?php if ($message): ?>
            <div
                class="mb-6 p-4 rounded-lg flex items-center <?= $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' ?>">
                <i data-feather="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>"
                    class="w-5 h-5 mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form id="settingsForm" method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Tabs for categories -->
                <div class="bg-white rounded-lg shadow-sm p-2 tabs-container">
                    <nav class="flex flex-nowrap gap-2" aria-label="Settings categories">
                        <?php foreach ($categories as $index => $category): ?>
                        <button type="button"
                            class="category-tab relative px-3 py-2 md:px-4 md:py-3 rounded-lg font-medium text-sm transition-all duration-200 <?= $index === 0 ? 'bg-gradient-primary text-white shadow-md active' : 'text-gray-600 hover:bg-pink-50' ?>"
                            data-category="<?= htmlspecialchars($category) ?>">
                            <?php
                        $icon = match($category) {
                            'general' => 'settings',
                            'contact' => 'mail',
                            'social' => 'share-2',
                            'footer' => 'layout',
                            'seo' => 'search',
                            'analytics' => 'bar-chart-2',
                            default => 'grid'
                        };
                    ?>
                            <i data-feather="<?= $icon ?>" class="w-4 h-4 inline-block mr-1"></i>
                            <span class="hidden sm:inline"><?= htmlspecialchars(ucfirst($category)) ?></span>
                        </button>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Settings panels container -->
                <div class="settings-container">
                    <!-- Settings panels -->
                    <?php foreach ($settings as $category => $categorySettings): ?>
                    <div class="category-panel <?= $category === $categories[0] ? 'active' : '' ?>"
                        data-category="<?= htmlspecialchars($category) ?>">
                        <div class="bg-white shadow-sm rounded-lg border border-gray-100">
                            <div class="p-4 md:p-6">
                                <div class="grid gap-4 md:gap-6">
                                    <?php foreach ($categorySettings as $setting): ?>
                                    <div
                                        class="setting-item group relative hover:bg-gray-50/80 -mx-4 md:-mx-6 p-4 md:p-6 transition-all duration-200">
                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6 setting-grid">
                                            <div class="md:col-span-4">
                                                <label for="<?= htmlspecialchars($setting['key']) ?>"
                                                    class="setting-label block text-sm font-medium text-gray-700">
                                                    <?= htmlspecialchars($setting['label']) ?>
                                                    <?php if ($setting['description']): ?>
                                                    <p class="setting-description mt-1 text-sm text-gray-500">
                                                        <?= htmlspecialchars($setting['description']) ?></p>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            <div class="md:col-span-8">
                                                <?php if ($setting['type'] === 'textarea'): ?>
                                                <textarea name="settings[<?= htmlspecialchars($setting['key']) ?>]"
                                                    id="<?= htmlspecialchars($setting['key']) ?>" rows="3"
                                                    class="form-textarea"><?= htmlspecialchars($setting['value']) ?></textarea>
                                                <?php elseif ($setting['type'] === 'image'): ?>
                                                <div class="flex flex-col gap-4">
                                                    <?php if ($setting['value']): ?>
                                                    <div class="relative group/preview inline-block">
                                                        <img src="/<?= htmlspecialchars($setting['value']) ?>"
                                                            alt="Current image"
                                                            class="h-24 md:h-32 w-auto rounded-lg border border-gray-200 object-contain p-2">
                                                        <div
                                                            class="absolute inset-0 flex items-center justify-center rounded-lg bg-black bg-opacity-50 opacity-0 transition-opacity group-hover/preview:opacity-100">
                                                            <span class="text-white text-sm font-medium">Current
                                                                Image</span>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="relative">
                                                        <input type="file"
                                                            name="settings[<?= htmlspecialchars($setting['key']) ?>]"
                                                            id="<?= htmlspecialchars($setting['key']) ?>"
                                                            accept="image/*"
                                                            class="file-input block w-full rounded-lg border border-gray-200 text-sm text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <input type="<?= htmlspecialchars($setting['type']) ?>"
                                                    name="settings[<?= htmlspecialchars($setting['key']) ?>]"
                                                    id="<?= htmlspecialchars($setting['key']) ?>"
                                                    value="<?= htmlspecialchars($setting['value']) ?>"
                                                    class="form-input">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit"
                        class="w-full md:w-auto inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-primary hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all">
                        Save Changes
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Feather icons
        feather.replace();

        // Tab switching logic
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const category = tab.dataset.category;

                // Update tab styles
                document.querySelectorAll('.category-tab').forEach(t => {
                    t.classList.remove('bg-gradient-primary', 'text-white', 'shadow-md',
                        'active');
                    t.classList.add('text-gray-600');
                });
                tab.classList.remove('text-gray-600');
                tab.classList.add('bg-gradient-primary', 'text-white', 'shadow-md', 'active');

                // Show/hide panels
                document.querySelectorAll('.category-panel').forEach(panel => {
                    if (panel.dataset.category === category) {
                        panel.classList.add('active');
                    } else {
                        panel.classList.remove('active');
                    }
                });
            });
        });
    });
    </script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>