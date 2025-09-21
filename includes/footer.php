<?php require_once __DIR__ . '/settings.php'; ?>
<!-- Footer -->
<footer class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <!-- System uses icon for logo -->
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-pink-600 rounded-lg flex items-center justify-center">
                        <i data-feather="shopping-bag" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">
                            <?= htmlspecialchars(get_setting('company_name', 'Springs Store')) ?></h3>
                        <p class="text-sm text-gray-400">
                            <?= htmlspecialchars(get_setting('site_title', 'Ministries')) ?></p>
                    </div>
                </div>
                <p class="text-gray-300 text-sm leading-relaxed">
                    <?= nl2br(htmlspecialchars(get_setting('footer_about', 'Your trusted destination for quality products. We bring you the latest trends at affordable prices with fast, reliable delivery.'))) ?>
                </p>
                <div class="flex space-x-4">
                    <?php if ($fbUrl = get_setting('facebook_url')): ?>
                    <a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" rel="noopener"
                        class="text-gray-400 hover:text-white transition-colors">
                        <i data-feather="facebook" class="w-5 h-5"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($igUrl = get_setting('instagram_url')): ?>
                    <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener"
                        class="text-gray-400 hover:text-white transition-colors">
                        <i data-feather="instagram" class="w-5 h-5"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($twUrl = get_setting('twitter_url')): ?>
                    <a href="<?= htmlspecialchars($twUrl) ?>" target="_blank" rel="noopener"
                        class="text-gray-400 hover:text-white transition-colors">
                        <i data-feather="twitter" class="w-5 h-5"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($liUrl = get_setting('linkedin_url')): ?>
                    <a href="<?= htmlspecialchars($liUrl) ?>" target="_blank" rel="noopener"
                        class="text-gray-400 hover:text-white transition-colors">
                        <i data-feather="linkedin" class="w-5 h-5"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="space-y-4">
                <h4 class="text-lg font-semibold">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="index.php" class="text-gray-300 hover:text-white transition-colors text-sm">Home</a>
                    </li>
                    <li><a href="shop.php" class="text-gray-300 hover:text-white transition-colors text-sm">Shop All</a>
                    </li>
                    <li><a href="shop.php?category=clothes"
                            class="text-gray-300 hover:text-white transition-colors text-sm">Clothes</a></li>
                    <li><a href="shop.php?category=bags"
                            class="text-gray-300 hover:text-white transition-colors text-sm">Bags</a></li>
                    <li><a href="shop.php?category=jewelry"
                            class="text-gray-300 hover:text-white transition-colors text-sm">Jewelry</a></li>
                    <li><a href="about.php" class="text-gray-300 hover:text-white transition-colors text-sm">About
                            Us</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div class="space-y-4">
                <h4 class="text-lg font-semibold">Customer Service</h4>
                <ul class="space-y-2">
                    <li><a href="contact.php" class="text-gray-300 hover:text-white transition-colors text-sm">Contact
                            Us</a></li>
                    <li><a href="shipping.php" class="text-gray-300 hover:text-white transition-colors text-sm">Shipping
                            Info</a></li>
                    <li><a href="returns.php" class="text-gray-300 hover:text-white transition-colors text-sm">Returns &
                            Exchanges</a></li>
                    <li><a href="size-guide.php" class="text-gray-300 hover:text-white transition-colors text-sm">Size
                            Guide</a></li>
                    <li><a href="faq.php" class="text-gray-300 hover:text-white transition-colors text-sm">FAQ</a></li>
                    <li><a href="privacy.php" class="text-gray-300 hover:text-white transition-colors text-sm">Privacy
                            Policy</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="space-y-4">
                <h4 class="text-lg font-semibold">Get in Touch</h4>
                <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <i data-feather="map-pin" class="w-5 h-5 text-primary-400 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-gray-300 text-sm">123 Main Street</p>
                            <p class="text-gray-300 text-sm">Nairobi, Kenya</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i data-feather="phone" class="w-5 h-5 text-primary-400 flex-shrink-0"></i>
                        <a href="tel:+254712345678"
                            class="text-gray-300 hover:text-white transition-colors text-sm">+254 712 345 678</a>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i data-feather="mail" class="w-5 h-5 text-primary-400 flex-shrink-0"></i>
                        <a href="mailto:info@springsstore.com"
                            class="text-gray-300 hover:text-white transition-colors text-sm">info@springsstore.com</a>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i data-feather="clock" class="w-5 h-5 text-primary-400 flex-shrink-0"></i>
                        <div>
                            <p class="text-gray-300 text-sm">Mon - Fri: 9:00 AM - 6:00 PM</p>
                            <p class="text-gray-300 text-sm">Sat: 10:00 AM - 4:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Newsletter Signup -->
        <div class="mt-12 pt-8 border-t border-gray-800">
            <div class="max-w-md mx-auto text-center">
                <h4 class="text-lg font-semibold mb-2">Stay Updated</h4>
                <p class="text-gray-300 text-sm mb-4">Subscribe to our newsletter for the latest updates and exclusive
                    offers.</p>
                <form action="newsletter.php" method="POST" class="flex">
                    <input type="email" name="email" placeholder="Enter your email" required
                        class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-white placeholder-gray-400">
                    <button type="submit"
                        class="px-6 py-2 bg-primary-500 text-white rounded-r-lg hover:bg-primary-600 transition-colors font-medium">
                        Subscribe
                    </button>
                </form>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="mt-8 pt-8 border-t border-gray-800">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="text-gray-400 text-sm">
                    &copy; <?= date('Y') ?> Springs Ministries Store. All rights reserved.
                </div>
                <div class="flex items-center space-x-6 text-sm">
                    <a href="privacy.php" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                    <a href="terms.php" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                    <a href="cookies.php" class="text-gray-400 hover:text-white transition-colors">Cookie Policy</a>
                </div>
                <div class="flex items-center space-x-2 text-sm text-gray-400">
                    <span>Powered by</span>
                    <a href="https://lates-portfolio-v1.vercel.app/" target="_blank" rel="noopener"
                        class="text-primary-400 font-semibold hover:text-pink-500 hover:underline">
                        MastermindCreat
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop"
    class="fixed bottom-6 right-6 bg-primary-500 text-white p-3 rounded-full shadow-lg hover:bg-primary-600 transition-all duration-300 opacity-0 invisible z-40">
    <i data-feather="arrow-up" class="w-5 h-5"></i>
</button>

<!-- JavaScript -->
<script>
// Back to top button
const backToTopButton = document.getElementById('backToTop');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTopButton.classList.remove('opacity-0', 'invisible');
    } else {
        backToTopButton.classList.add('opacity-0', 'invisible');
    }
});

backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Initialize Feather icons
feather.replace();
</script>

</body>

</html>