<?php
// public/profile.php - Customer Profile Page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

// Set page title
$pageTitle = 'My Profile';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

// Check if user is admin (redirect to admin dashboard)
if ($_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';
$success = '';
$user = null;

// Get user data
try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, default_address, password, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Debug: Log the fetched data (remove this in production)
    error_log("Fetched user data: " . print_r($user, true));
    
    // Ensure all required fields have default values
    $user = array_merge([
        'id' => null,
        'name' => '',
        'email' => '',
        'phone' => '',
        'default_address' => '',
        'password' => '',
        'role' => 'customer',
        'created_at' => null
    ], $user ?: []);
    
    // Ensure all keys exist with proper defaults
    $user['role'] = $user['role'] ?? 'customer';
    $user['created_at'] = $user['created_at'] ?? null;
    
} catch (Exception $e) {
    $error = 'An error occurred while loading your profile.';
    $user = [
        'id' => null,
        'name' => '',
        'email' => '',
        'phone' => '',
        'default_address' => '',
        'password' => '',
        'role' => 'customer',
        'created_at' => null
    ];
    
    // Ensure all keys exist with proper defaults
    $user['role'] = $user['role'] ?? 'customer';
    $user['created_at'] = $user['created_at'] ?? null;
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
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $error = 'This email address is already taken by another account.';
                } else {
                    // Update user profile
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, phone = ?, default_address = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $phone, $address, $_SESSION['user_id']]);
                    
                    // Update session data
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
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
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            try {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                
                $success = 'Your password has been changed successfully!';
            } catch (Exception $e) {
                $error = 'An error occurred while changing your password. Please try again.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
                    <p class="mt-2 text-gray-600">Manage your account information and preferences</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="orders.php" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i data-feather="package" class="w-4 h-4 mr-2"></i>
                        My Orders
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

        <!-- Debug Information (Remove in production) -->
        <?php if (isset($_GET['debug'])): ?>
        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
            <h3 class="font-semibold mb-2">Debug Information:</h3>
            <pre class="text-xs"><?= htmlspecialchars(print_r($user, true)) ?></pre>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Information -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
                        <p class="text-sm text-gray-600">Update your personal details</p>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" required
                                       value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="email" name="email" required
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number
                                </label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                       placeholder="e.g., +254 712 345 678">
                            </div>
                            
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                    Account Type
                                </label>
                                <input type="text" id="role" value="<?= ucfirst($user['role'] ?? 'customer') ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" 
                                       readonly>
                            </div>
                        </div>
                        
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                Default Address
                            </label>
                            <textarea id="address" name="address" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                      placeholder="Enter your default shipping address"><?= htmlspecialchars($user['default_address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center px-6 py-2 bg-primary-500 text-white font-medium rounded-lg hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
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
                                <?= isset($user['created_at']) && $user['created_at'] ? date('M Y', strtotime($user['created_at'])) : 'Unknown' ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Account type</span>
                            <span class="text-sm font-medium text-gray-900">
                                <?= ucfirst($user['role'] ?? 'customer') ?>
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
                                       class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
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
                                       class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
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
                                       class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
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
                        <a href="orders.php" 
                           class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i data-feather="package" class="w-4 h-4 mr-3"></i>
                            View Order History
                        </a>
                        <a href="cart.php" 
                           class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i data-feather="shopping-cart" class="w-4 h-4 mr-3"></i>
                            View Cart
                        </a>
                        <a href="shop.php" 
                           class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i data-feather="grid" class="w-4 h-4 mr-3"></i>
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- JavaScript -->
<script>
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

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
