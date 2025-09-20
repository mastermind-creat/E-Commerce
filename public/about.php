<?php
// public/about.php - About Us Page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'About Us';

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">About Springs Store</h1>
            <p class="text-gray-600 mt-2">Our journey, values, and commitment to you.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- About Content -->
            <div class="space-y-6">
                <p class="text-lg text-gray-700 leading-relaxed">
                    Welcome to Springs Store, your ultimate destination for stylish clothes, exquisite bags, and
                    dazzling jewelry.
                    Born out of a passion for fashion and a commitment to quality, we strive to bring you a curated
                    collection that reflects the latest trends while maintaining timeless elegance.
                </p>
                <p class="text-lg text-gray-700 leading-relaxed">
                    Our mission is simple: to make high-quality fashion accessible to everyone. We believe that style
                    shouldn't come at a premium, and that's why we work tirelessly to source the best products from
                    around the globe, ensuring exceptional craftsmanship and fair prices.
                </p>
                <ul class="list-disc list-inside space-y-2 text-gray-600">
                    <li><strong>Quality Assurance:</strong> Every item is handpicked and rigorously inspected.</li>
                    <li><strong>Customer Satisfaction:</strong> Your happiness is our top priority.</li>
                    <li><strong>Ethical Sourcing:</strong> We partner with suppliers who share our values.</li>
                    <li><strong>Sustainable Practices:</strong> Committed to minimizing our environmental footprint.
                    </li>
                </ul>
            </div>

            <!-- Image Section (Optional: add a compelling image here) -->
            <div class="relative rounded-2xl overflow-hidden shadow-xl lg:h-96">
                <img src="assets/images/placeholder.png" alt="Our Team" class="w-full h-full object-cover"
                    loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                <div class="absolute bottom-6 left-6 text-white">
                    <h3 class="text-2xl font-bold">Our Story</h3>
                    <p class="text-sm">Crafting elegance, one piece at a time.</p>
                </div>
            </div>
        </div>

        <!-- Values Section -->
        <div class="mt-16 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-8">Our Values</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div
                    class="bg-white rounded-2xl shadow-lg p-6 group transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                    <div
                        class="w-16 h-16 mx-auto bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mb-4 text-3xl group-hover:scale-110 transition-transform">
                        💎
                    </div>
                    <h3 class="font-semibold text-gray-900 text-xl mb-2">Quality</h3>
                    <p class="text-gray-600">We never compromise on the quality of our products.</p>
                </div>
                <div
                    class="bg-white rounded-2xl shadow-lg p-6 group transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                    <div
                        class="w-16 h-16 mx-auto bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-4 text-3xl group-hover:scale-110 transition-transform">
                        💖
                    </div>
                    <h3 class="font-semibold text-gray-900 text-xl mb-2">Customer Love</h3>
                    <p class="text-gray-600">Your satisfaction is the heart of our business.</p>
                </div>
                <div
                    class="bg-white rounded-2xl shadow-lg p-6 group transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                    <div
                        class="w-16 h-16 mx-auto bg-pink-100 text-pink-600 rounded-full flex items-center justify-center mb-4 text-3xl group-hover:scale-110 transition-transform">
                        🌱
                    </div>
                    <h3 class="font-semibold text-gray-900 text-xl mb-2">Integrity</h3>
                    <p class="text-gray-600">Transparent and ethical practices in everything we do.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>