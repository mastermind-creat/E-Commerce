<?php
// public/index.php - Professional E-Commerce Homepage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

// Set page title
$pageTitle = 'Home';

// Get product images from filesystem for carousel and new arrivals
$productsDir = __DIR__ . '/assets/products';
$productImages = [];
if (is_dir($productsDir)) {
    $files = glob($productsDir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });
    $productImages = array_map('basename', $files);
}

// Get categories for navigation
try {
    $categories = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get featured products
try {
    $featuredProducts = $pdo->query("
        SELECT p.*, c.name as category_name, 
               COALESCE(
                   (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
                   (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.id LIMIT 1)
               ) AS image_url
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC 
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $featuredProducts = [];
}

// Get recent reviews
try {
    $reviews = $pdo->query("
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC 
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reviews = [];
}

include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-primary-50 to-pink-50 py-12 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Hero Content -->
                <div class="space-y-8">
                    <div class="space-y-4">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                            Discover Your
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-500 to-pink-600">
                                Perfect Style
                            </span>
                        </h1>
                        <p class="text-lg sm:text-xl text-gray-600 leading-relaxed">
                            Explore our curated collection of clothes, bags, and jewelry.
                            Quality pieces that reflect your unique personality at unbeatable prices.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="shop.php"
                            class="inline-flex items-center justify-center px-8 py-4 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-all duration-200 transform hover:scale-105 shadow-lg">
                            <i data-feather="shopping-bag" class="w-5 h-5 mr-2"></i>
                            Shop Now
                        </a>
                        <a href="#featured"
                            class="inline-flex items-center justify-center px-8 py-4 border-2 border-primary-500 text-primary-600 font-semibold rounded-lg hover:bg-primary-50 transition-all duration-200">
                            <i data-feather="eye" class="w-5 h-5 mr-2"></i>
                            View Collection
                        </a>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-8 pt-8 border-t border-gray-200">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary-600">500+</div>
                            <div class="text-sm text-gray-600">Products</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary-600">1000+</div>
                            <div class="text-sm text-gray-600">Happy Customers</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary-600">24/7</div>
                            <div class="text-sm text-gray-600">Support</div>
                        </div>
                    </div>
                </div>

                <!-- Hero Image Carousel -->
                <div class="relative">
                    <div class="relative h-96 lg:h-[500px] rounded-2xl overflow-hidden shadow-2xl">
                        <?php if (!empty($productImages)): ?>
                        <div id="heroCarousel" class="relative h-full">
                            <?php foreach (array_slice($productImages, 0, 5) as $i => $img): ?>
                            <div
                                class="hero-slide absolute inset-0 transition-opacity duration-1000 <?= $i === 0 ? 'opacity-100' : 'opacity-0' ?>">
                                <img src="assets/products/<?= htmlspecialchars($img) ?>" alt="Featured Product"
                                    class="w-full h-full object-cover"
                                    onerror="this.src='assets/images/placeholder.png'">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Carousel Controls -->
                        <button id="prevSlide"
                            class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all">
                            <i data-feather="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <button id="nextSlide"
                            class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all">
                            <i data-feather="chevron-right" class="w-5 h-5"></i>
                        </button>

                        <!-- Indicators -->
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2">
                            <?php foreach (array_slice($productImages, 0, 5) as $i => $img): ?>
                            <button
                                class="hero-indicator w-3 h-3 rounded-full bg-white/60 hover:bg-white transition-all <?= $i === 0 ? 'bg-white' : '' ?>"
                                data-slide="<?= $i ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                            <div class="text-center text-gray-500">
                                <i data-feather="image" class="w-16 h-16 mx-auto mb-4"></i>
                                <p>No images available</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Discover our carefully curated collections designed to match your style and needs
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <a href="shop.php?category=<?= urlencode($category['slug']) ?>"
                    class="group bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 text-center">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-primary-100 to-pink-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i data-feather="shopping-bag" class="w-8 h-8 text-primary-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                        <?= htmlspecialchars($category['name']) ?>
                    </h3>
                </a>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <i data-feather="package" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                    <p class="text-gray-500">No categories available yet</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section id="featured" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-12">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Featured Products</h2>
                    <p class="text-lg text-gray-600">Handpicked items just for you</p>
                </div>
                <a href="shop.php"
                    class="mt-4 sm:mt-0 inline-flex items-center px-6 py-3 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-colors">
                    View All Products
                    <i data-feather="arrow-right" class="w-4 h-4 ml-2"></i>
                </a>
            </div>

            <?php if (!empty($featuredProducts)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featuredProducts as $product): ?>
                <div
                    class="group bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden">
                    <a href="product.php?id=<?= $product['id'] ?>" class="block">
                        <div class="relative overflow-hidden">
                            <img src="<?= $product['image_url'] ? 'assets/products/' . htmlspecialchars($product['image_url']) : 'assets/images/placeholder.png' ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                                onerror="this.src='assets/images/placeholder.png'">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary-50">
                                    <i data-feather="heart" class="w-5 h-5 text-gray-600"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="text-sm text-primary-600 font-medium mb-1">
                                <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></div>
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                <?= htmlspecialchars($product['name']) ?></h3>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-gray-900">KSh
                                    <?= number_format($product['price'], 2) ?></span>
                                <div class="flex items-center text-yellow-400">
                                    <i data-feather="star" class="w-4 h-4 fill-current"></i>
                                    <i data-feather="star" class="w-4 h-4 fill-current"></i>
                                    <i data-feather="star" class="w-4 h-4 fill-current"></i>
                                    <i data-feather="star" class="w-4 h-4 fill-current"></i>
                                    <i data-feather="star" class="w-4 h-4 fill-current"></i>
                                    <span class="text-gray-500 text-sm ml-1">(4.8)</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <i data-feather="package" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                <p class="text-gray-500">No products available yet</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- New Arrivals Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">New Arrivals</h2>
                <p class="text-lg text-gray-600">Fresh styles just added to our collection</p>
            </div>

            <?php if (!empty($productImages)): ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach (array_slice($productImages, 0, 12) as $img): ?>
                <div
                    class="group aspect-square bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <img src="assets/products/<?= htmlspecialchars($img) ?>" alt="New Arrival"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        onerror="this.src='assets/images/placeholder.png'">
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <i data-feather="image" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                <p class="text-gray-500">No new arrivals available</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Testimonials Section -->
    <?php if (!empty($reviews)): ?>
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">What Our Customers Say</h2>
                <p class="text-lg text-gray-600">Real feedback from real customers</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($reviews as $review): ?>
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mr-4">
                            <i data-feather="user" class="w-6 h-6 text-primary-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($review['user_name']) ?></h4>
                            <div class="flex items-center text-yellow-400">
                                <?php for($i = 0; $i < 5; $i++): ?>
                                <i data-feather="star" class="w-4 h-4 fill-current"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"<?= htmlspecialchars($review['comment']) ?>"</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Newsletter Section -->
    <section class="py-16 bg-gradient-to-r from-primary-500 to-pink-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Stay in the Loop</h2>
            <p class="text-xl text-primary-100 mb-8">Get the latest updates on new arrivals, exclusive offers, and style
                tips delivered to your inbox.</p>

            <form action="newsletter.php" method="POST" class="max-w-md mx-auto">
                <div class="flex">
                    <input type="email" name="email" placeholder="Enter your email address" required
                        class="flex-1 px-6 py-4 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-white/50 text-gray-900 placeholder-gray-500">
                    <button type="submit"
                        class="px-8 py-4 bg-white text-primary-600 font-semibold rounded-r-lg hover:bg-gray-50 transition-colors">
                        Subscribe
                    </button>
                </div>
                <p class="text-primary-100 text-sm mt-4">We respect your privacy. Unsubscribe at any time.</p>
            </form>
        </div>
    </section>
</main>

<!-- JavaScript -->
<script>
// Hero Carousel
let currentSlide = 0;
const slides = document.querySelectorAll('.hero-slide');
const indicators = document.querySelectorAll('.hero-indicator');
const totalSlides = slides.length;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.style.opacity = i === index ? '1' : '0';
    });
    indicators.forEach((indicator, i) => {
        indicator.classList.toggle('bg-white', i === index);
        indicator.classList.toggle('bg-white/60', i !== index);
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    showSlide(currentSlide);
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    showSlide(currentSlide);
}

// Event listeners
document.getElementById('nextSlide')?.addEventListener('click', nextSlide);
document.getElementById('prevSlide')?.addEventListener('click', prevSlide);

indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
        currentSlide = index;
        showSlide(currentSlide);
    });
});

// Auto-advance carousel
if (totalSlides > 1) {
    setInterval(nextSlide, 5000);
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>