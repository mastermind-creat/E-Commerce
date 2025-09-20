<?php
// public/register.php - Modern Registration Page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

// Set page title
$pageTitle = 'Register';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $redirect = $_GET['redirect'] ?? 'index.php';
    header("Location: $redirect");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $agreeTerms = isset($_POST['agree_terms']);

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($address)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!$agreeTerms) {
        $error = 'Please agree to the terms and conditions.';
    } else {
        try {
            // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
        } else {
                // Create new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, password, default_address, role, created_at) 
                VALUES (?, ?, ?, ?, ?, 'customer', NOW())
            ");
                $stmt->execute([$name, $email, $phone, $hashedPassword, $address]);
                
                // Auto-login after registration
                $userId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';

                // Redirect
            $redirect = $_GET['redirect'] ?? 'index.php';
            header("Location: $redirect");
            exit;
        }
        } catch (Exception $e) {
            $error = 'An error occurred during registration. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main
    class="min-h-screen bg-gradient-to-br from-primary-50 to-pink-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div
                class="w-16 h-16 bg-gradient-to-br from-primary-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <i data-feather="user-plus" class="w-8 h-8 text-white"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
            <p class="mt-2 text-gray-600">Join us and start shopping today</p>
        </div>

        <!-- Registration Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="user" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($name ?? '') ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                            placeholder="Enter your full name">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="mail" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" required
                            value="<?= htmlspecialchars($email ?? '') ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                            placeholder="Enter your email address">
                    </div>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="phone" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($phone ?? '') ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                            placeholder="Enter your phone number">
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Delivery Address <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                            <i data-feather="map-pin" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <textarea id="address" name="address" required rows="3"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none"
                            placeholder="Enter your complete delivery address"><?= htmlspecialchars($address ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="lock" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                            placeholder="Create a password (min. 6 characters)">
                        <button type="button" id="togglePassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i data-feather="eye" class="w-5 h-5 text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Password must be at least 6 characters long</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password
                        <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="lock" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="password" id="confirm_password" name="confirm_password" required
                            class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                            placeholder="Confirm your password">
                        <button type="button" id="toggleConfirmPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i data-feather="eye" class="w-5 h-5 text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="flex items-start">
                    <input type="checkbox" id="agree_terms" name="agree_terms" required
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded mt-1">
                    <label for="agree_terms" class="ml-2 block text-sm text-gray-700">
                        I agree to the
                        <a href="terms.php" class="text-primary-600 hover:text-primary-500 font-medium">Terms and
                            Conditions</a>
                        and
                        <a href="privacy.php" class="text-primary-600 hover:text-primary-500 font-medium">Privacy
                            Policy</a>
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-primary-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                    <i data-feather="user-plus" class="w-5 h-5 mr-2 inline"></i>
                    Create Account
                </button>
            </form>

            <!-- Divider -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Or continue with</span>
                    </div>
                </div>
            </div>

            <!-- Social Registration (Placeholder) -->
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button
                    class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i data-feather="facebook" class="w-5 h-5"></i>
                    <span class="ml-2">Facebook</span>
                </button>
                <button
                    class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i data-feather="twitter" class="w-5 h-5"></i>
                    <span class="ml-2">Twitter</span>
                </button>
            </div>
        </div>

        <!-- Sign In Link -->
        <div class="text-center">
            <p class="text-gray-600">
                Already have an account?
                <a href="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>"
                    class="font-medium text-primary-600 hover:text-primary-500">
                    Sign in here
                </a>
            </p>
        </div>
    </div>
</main>

<!-- JavaScript -->
<script>
// Password toggle functions
function setupPasswordToggle(toggleId, inputId) {
    const toggle = document.getElementById(toggleId);
    const input = document.getElementById(inputId);
    const icon = toggle.querySelector('i');

    toggle.addEventListener('click', function() {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);

        if (type === 'password') {
            icon.setAttribute('data-feather', 'eye');
        } else {
            icon.setAttribute('data-feather', 'eye-off');
        }
        feather.replace();
    });
}

// Setup password toggles
setupPasswordToggle('togglePassword', 'password');
setupPasswordToggle('toggleConfirmPassword', 'confirm_password');

// Form validation
const form = document.querySelector('form');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');

// Real-time password confirmation validation
confirmPasswordInput.addEventListener('input', function() {
    if (this.value && this.value !== passwordInput.value) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('border-red-500');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-500');
    }
});

passwordInput.addEventListener('input', function() {
    if (confirmPasswordInput.value) {
        confirmPasswordInput.dispatchEvent(new Event('input'));
    }
});

form.addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    const address = document.getElementById('address').value.trim();
    const agreeTerms = document.getElementById('agree_terms').checked;

    if (!name || !email || !phone || !password || !address) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return;
    }

    if (!filter_var(email, FILTER_VALIDATE_EMAIL)) {
        e.preventDefault();
        alert('Please enter a valid email address.');
        return;
    }

    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long.');
        return;
    }

    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match.');
        return;
    }

    if (!agreeTerms) {
        e.preventDefault();
        alert('Please agree to the terms and conditions.');
        return;
    }
});

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>