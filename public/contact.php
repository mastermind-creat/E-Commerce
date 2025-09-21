<?php
// public/contact.php - Contact Us Page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Contact Us';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // In a real application, you would send an email here
        // For now, we'll just simulate success
        $success = 'Thank you for your message! We will get back to you soon.';
        // You might log the message to a database or send an actual email
        // mail('info@springsstore.com', 'Contact Form: ' . $subject, $message, 'From: ' . $email);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Contact Us</h1>
            <p class="text-gray-600 mt-2">We'd love to hear from you!</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Contact Form -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Send us a Message</h2>

                <?php if ($success): ?>
                <div
                    class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                    <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                    <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" id="subject" name="subject"
                            value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea id="message" name="message" rows="5" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit"
                        class="w-full bg-primary-500 text-white py-3 px-6 rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                        Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Information -->
            <div class="bg-white rounded-2xl shadow-lg p-6 space-y-6">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Our Information</h3>
                    <div class="space-y-3 text-gray-700">
                        <p class="flex items-center"><i data-feather="map-pin"
                                class="w-5 h-5 mr-3 text-primary-600"></i>123 Springs Avenue, Nairobi, Kenya</p>
                        <p class="flex items-center"><i data-feather="phone"
                                class="w-5 h-5 mr-3 text-primary-600"></i>+254 712 345 678</p>
                        <p class="flex items-center"><i data-feather="mail"
                                class="w-5 h-5 mr-3 text-primary-600"></i>info@springsstore.com</p>
                        <p class="flex items-center"><i data-feather="clock"
                                class="w-5 h-5 mr-3 text-primary-600"></i>Mon - Fri: 9:00 AM - 5:00 PM</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-500 hover:text-primary-600 transition"><i data-feather="facebook"
                                class="w-6 h-6"></i></a>
                        <a href="#" class="text-gray-500 hover:text-primary-600 transition"><i data-feather="twitter"
                                class="w-6 h-6"></i></a>
                        <a href="#" class="text-gray-500 hover:text-primary-600 transition"><i data-feather="instagram"
                                class="w-6 h-6"></i></a>
                        <a href="#" class="text-gray-500 hover:text-primary-600 transition"><i data-feather="linkedin"
                                class="w-6 h-6"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>