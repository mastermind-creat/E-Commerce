<?php
// admin/profile.php - Admin Profile Page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../includes/db.php";
require_once 'auth.php';

// Set page title
$pageTitle = 'Admin Profile';

$error = '';
$success = '';
$admin = null;

// Get admin data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    // Ensure all required fields have default values
    $admin = array_merge([
        'id' => null,
        'name' => '',
        'email' => '',
        'phone' => '',
        'default_address' => '',
        'password' => '',
        'role' => 'admin',
        'created_at' => null
    ], $admin ?: []);
    
    // Ensure all keys exist with proper defaults
    $admin['role'] = $admin['role'] ?? 'admin';
    $admin['created_at'] = $admin['created_at'] ?? null;
    
} catch (Exception $e) {
    $error = 'An error occurred while loading your profile.';
    $admin = [
        'id' => null,
        'name' => '',
        'email' => '',
        'phone' => '',
        'default_address' => '',
        'password' => '',
        'role' => 'admin',
        'created_at' => null
    ];
    
    // Ensure all keys exist with proper defaults
    $admin['role'] = $admin['role'] ?? 'admin';
    $admin['created_at'] = $admin['created_at'] ?? null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validation
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Check if email is already taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['admin_id']]);
                if ($stmt->fetch()) {
                    $error = 'This email address is already taken by another account.';
                } else {
                    // Update admin profile
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, phone = ?, default_address = ? 
                        WHERE id = ? AND role = 'admin'
                    ");
                    $stmt->execute([$name, $email, $phone, $address, $_SESSION['admin_id']]);
                    
                    // Refresh admin data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$_SESSION['admin_id']]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Ensure all required fields have default values
                    $admin = array_merge([
                        'id' => null,
                        'name' => '',
                        'email' => '',
                        'phone' => '',
                        'default_address' => '',
                        'password' => '',
                        'role' => 'admin',
                        'created_at' => null
                    ], $admin ?: []);
                    
                    $success = 'Your profile has been updated successfully!';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while updating your profile. Please try again.';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (!password_verify($currentPassword, $admin['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            try {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
                $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                
                $success = 'Your password has been changed successfully!';
            } catch (Exception $e) {
                $error = 'An error occurred while changing your password. Please try again.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Springs Store Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card:nth-child(2) {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card:nth-child(3) {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card:nth-child(4) {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stat-card:nth-child(5) {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .stat-card:nth-child(6) {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>
</head>

<body class="bg-gray-50">
    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen">
        <div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Admin Profile</h1>
                <p class="mt-2 text-gray-600">Manage your administrator account information</p>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($error): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
        <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
        <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Profile Information -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
                    <p class="text-sm text-gray-600">Update your administrator details</p>
                </div>
                
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" required
                                   value="<?= htmlspecialchars($admin['name'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" required
                                   value="<?= htmlspecialchars($admin['email'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($admin['phone'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="e.g., +254 712 345 678">
                        </div>
                        
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                Account Type
                            </label>
                            <input type="text" id="role" value="<?= ucfirst($admin['role']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" 
                                   readonly>
                        </div>
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            Default Address
                        </label>
                        <textarea id="address" name="address" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Enter your default address"><?= htmlspecialchars($admin['default_address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center px-6 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            <i data-feather="save" class="w-4 h-4 mr-2"></i>
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Account Summary -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Account Summary</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Member since</span>
                        <span class="text-sm font-medium text-gray-900">
                            <?= isset($admin['created_at']) && $admin['created_at'] ? date('M Y', strtotime($admin['created_at'])) : 'Unknown' ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Account type</span>
                        <span class="text-sm font-medium text-gray-900">
                            <?= ucfirst($admin['role'] ?? 'admin') ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Email verified</span>
                        <span class="text-sm font-medium text-green-600">
                            <i data-feather="check-circle" class="w-4 h-4"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
                </div>
                
                <form method="POST" class="p-6 space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Current Password
                        </label>
                        <div class="relative">
                            <input type="password" id="current_password" name="current_password" required
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" onclick="togglePassword('current_password')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i data-feather="eye" class="w-4 h-4 text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            New Password
                        </label>
                        <div class="relative">
                            <input type="password" id="new_password" name="new_password" required
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" onclick="togglePassword('new_password')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i data-feather="eye" class="w-4 h-4 text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm New Password
                        </label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" onclick="togglePassword('confirm_password')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i data-feather="eye" class="w-4 h-4 text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                        <i data-feather="key" class="w-4 h-4 mr-2"></i>
                        Change Password
                    </button>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="dashboard.php" 
                       class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        <i data-feather="layout" class="w-4 h-4 mr-3"></i>
                        Dashboard
                    </a>
                    <a href="products.php" 
                       class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        <i data-feather="package" class="w-4 h-4 mr-3"></i>
                        Manage Products
                    </a>
                    <a href="orders.php" 
                       class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        <i data-feather="shopping-cart" class="w-4 h-4 mr-3"></i>
                        View Orders
                    </a>
                    <a href="settings.php" 
                       class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        <i data-feather="settings" class="w-4 h-4 mr-3"></i>
                        Site Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Password toggle functionality
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const button = passwordField.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.setAttribute('data-feather', 'eye-off');
    } else {
        passwordField.type = 'password';
        icon.setAttribute('data-feather', 'eye');
    }
    
    // Re-render the feather icon
    feather.replace();
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Profile form validation
    const profileForm = document.querySelector('form[action="update_profile"]');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!name || !email) {
                e.preventDefault();
                alert('Name and email are required fields.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    }
    
    // Password form validation
    const passwordForm = document.querySelector('form[action="change_password"]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                e.preventDefault();
                alert('All password fields are required.');
                return;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('New password must be at least 6 characters long.');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match.');
                return;
            }
        });
    }
});

// Initialize Feather icons
feather.replace();
</script>
</body>

</html>
