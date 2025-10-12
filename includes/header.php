<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/settings.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cart count
$cartCount = array_sum(array_column($_SESSION['cart'], 'quantity')) ?? 0;

// Is user logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? ($_SESSION['user_role'] ?? null) : null;

// Get user name from database if logged in
$userName = null;
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $userName = $user['name'] ?? 'User';
    } catch (PDOException $e) {
        error_log("Error fetching user name: " . $e->getMessage());
        $userName = 'User';
    }
}

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= get_setting('site_title', 'Springs Ministries Store') ?>
    </title>

    <!-- Meta Tags -->
    <meta name="description"
        content="<?= get_setting('site_description', 'Quality clothes, bags, jewelry and more. Fast delivery, great prices.') ?>">
    <meta name="keywords" content="<?= get_setting('site_keywords', 'ecommerce, online shopping, products') ?>">
    <meta name="robots" content="<?= get_setting('meta_robots', 'index, follow') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/<?= get_setting('site_favicon', 'assets/images/favicon.ico') ?>">

    <!-- Google Analytics -->
    <?php if ($gaId = get_setting('google_analytics_id')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($gaId) ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', '<?= htmlspecialchars($gaId) ?>');
    </script>
    <?php endif; ?>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: {
                        50: '#fdf2f8',
                        100: '#fce7f3',
                        500: '#ec4899',
                        600: '#db2777',
                        700: '#be185d',
                    }
                }
            }
        }
    }
    </script>

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Custom Styles -->
    <style>
    .mobile-menu-enter {
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }

    .mobile-menu-enter.active {
        transform: translateX(0);
    }

    .cart-badge {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    .search-focus {
        transform: scale(1.02);
        box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
    }

    .suggestion-item.active {
        background-color: #f3f4f6;
    }

    /* Hero carousel overlay styling */
    .hero-slide img {
        filter: brightness(0.78);
        transform-origin: center;
        transition: transform 8s ease;
    }

    .hero-slide.active img {
        transform: scale(1.06);
    }

    .hero-slide .overlay-card {
        backdrop-filter: blur(4px);
    }

    .hero-slide .overlay-card h2 {
        text-shadow: 0 6px 18px rgba(0, 0, 0, 0.45);
    }

    .hero-slide .overlay-card p {
        text-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
    }

    @media (min-width: 1024px) {
        .hero-slide .overlay-card {
            max-width: 640px;
        }
    }
    </style>
</head>

<body class="bg-gray-50 text-gray-900 antialiased">
    <!-- Top Promo Bar -->
    <div class="bg-gradient-to-r from-primary-600 to-pink-600 text-white py-2 px-4 text-center text-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="hidden sm:flex items-center gap-2">
                <span class="animate-pulse">🔥</span>
                <span>Free shipping on orders over KSh 2,000!</span>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <a href="tel:+254712345678" class="hover:underline flex items-center gap-1">
                    <i data-feather="phone" class="w-3 h-3"></i>
                    +254 712 345 678
                </a>
                <a href="mailto:info@springsstore.com" class="hover:underline flex items-center gap-1">
                    <i data-feather="mail" class="w-3 h-3"></i>
                    info@springsstore.com
                </a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="bg-white/80 backdrop-blur-md shadow-lg sticky top-0 z-50 border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Top Row -->
            <div class="flex items-center justify-between h-16">
                <!-- Mobile Menu Button -->
                <button id="mobileMenuToggle"
                    class="lg:hidden p-3 rounded-xl text-gray-600 hover:text-primary-600 bg-white/50 backdrop-blur-sm hover:bg-white/80 border border-white/20 hover:border-primary-200 transition-all duration-300 shadow-lg hover:shadow-xl">
                    <i data-feather="menu" class="w-5 h-5"></i>
                </button>

                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="index.php" class="group flex items-center space-x-3 p-2 rounded-xl hover:bg-white/30 transition-all duration-300">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-primary-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:scale-105 transition-all duration-300">
                            <i data-feather="shopping-bag" class="w-6 h-6 text-white group-hover:scale-110 transition-transform duration-300"></i>
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="text-xl font-bold text-gray-900 group-hover:text-primary-700 transition-colors duration-300">Springs Store</h1>
                            <p class="text-xs text-gray-500 group-hover:text-primary-600 transition-colors duration-300">Ministries</p>
                        </div>
                    </a>
                </div>

                <!-- Search Bar (Desktop) -->
                <div class="hidden lg:flex flex-1 max-w-lg mx-8">
                    <form action="shop.php" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="search" id="searchInput" placeholder="Search products..."
                                class="w-full pl-12 pr-6 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg hover:shadow-xl text-gray-900 placeholder-gray-500"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                autocomplete="off">
                            <button type="submit"
                                class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary-600 transition-colors duration-300">
                                <i data-feather="search" class="w-5 h-5"></i>
                            </button>
                            
                            <!-- Search Suggestions Dropdown -->
                            <div id="searchSuggestions" class="hidden absolute top-full left-0 right-0 bg-white/95 backdrop-blur-md border border-white/30 rounded-2xl shadow-2xl mt-2 z-50 max-h-80 overflow-y-auto">
                                <div id="suggestionsList" class="py-3">
                                    <!-- Suggestions will be populated here -->
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-3">
                    <!-- Search Button (Mobile) -->
                    <button id="searchToggle"
                        class="lg:hidden p-3 rounded-xl text-gray-600 hover:text-primary-600 bg-white/50 backdrop-blur-sm hover:bg-white/80 border border-white/20 hover:border-primary-200 transition-all duration-300 shadow-lg hover:shadow-xl">
                        <i data-feather="search" class="w-5 h-5"></i>
                    </button>

                    <!-- Compare -->
                    <a href="compare.php"
                        class="group relative p-3 rounded-xl text-gray-600 hover:text-primary-600 bg-white/50 backdrop-blur-sm hover:bg-white/80 border border-white/20 hover:border-orange-200 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                        <i data-feather="git-compare" class="w-5 h-5 group-hover:scale-110 transition-transform duration-300"></i>
                        <?php 
                        $compareCount = count($_SESSION['compare'] ?? []);
                        if ($compareCount > 0): ?>
                        <span
                            class="absolute -top-1 -right-1 bg-gradient-to-r from-orange-500 to-orange-600 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold shadow-lg animate-pulse">
                            <?= $compareCount ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <!-- Cart -->
                    <a href="<?= $isLoggedIn ? 'cart.php' : 'login.php?redirect=cart' ?>"
                        class="group relative p-3 rounded-xl text-gray-600 hover:text-primary-600 bg-white/50 backdrop-blur-sm hover:bg-white/80 border border-white/20 hover:border-primary-200 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                        <i data-feather="shopping-cart" class="w-5 h-5 group-hover:scale-110 transition-transform duration-300"></i>
                        <?php if ($cartCount > 0): ?>
                        <span
                            class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-primary-600 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold shadow-lg cart-badge animate-bounce">
                            <?= $cartCount > 99 ? '99+' : $cartCount ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Menu -->
                    <div class="relative">
                        <?php if ($isLoggedIn): ?>
                        <button id="userMenuToggle"
                            class="group flex items-center space-x-3 p-3 rounded-xl text-gray-600 hover:text-primary-600 bg-white/50 backdrop-blur-sm hover:bg-white/80 border border-white/20 hover:border-primary-200 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300">
                                <i data-feather="user" class="w-5 h-5 text-white"></i>
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-sm font-semibold text-gray-900 group-hover:text-primary-700 transition-colors duration-300">
                                    <?= htmlspecialchars($userName ?? 'User') ?>
                                </div>
                                <div class="text-xs text-gray-500 group-hover:text-primary-600 transition-colors duration-300">
                                    My Account
                                </div>
                            </div>
                            <i data-feather="chevron-down" class="w-4 h-4 group-hover:rotate-180 transition-transform duration-300"></i>
                        </button>
                        <?php else: ?>
                        <a href="login.php"
                            class="group flex items-center space-x-3 px-6 py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                            <div class="w-6 h-6 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors duration-300 flex items-center justify-center">
                                <i data-feather="log-in" class="w-4 h-4 group-hover:scale-110 transition-transform duration-300"></i>
                            </div>
                            <span class="hidden sm:block font-semibold">Login</span>
                        </a>
                        <?php endif; ?>

                        <!-- User Dropdown -->
                        <?php if ($isLoggedIn): ?>
                        <div id="userMenu"
                            class="hidden absolute right-0 mt-3 w-56 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-white/30 py-2 z-50">
                            <?php if ($userRole === 'admin'): ?>
                            <a href="../admin/dashboard.php"
                                class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50/80 hover:text-primary-700 transition-all duration-300 mx-2 rounded-xl">
                                <div class="w-8 h-8 rounded-lg bg-primary-100 group-hover:bg-primary-200 flex items-center justify-center mr-3 transition-colors duration-300">
                                    <i data-feather="layout" class="w-4 h-4 text-primary-600 group-hover:scale-110 transition-transform duration-300"></i>
                                </div>
                                <span class="font-medium">Dashboard</span>
                            </a>
                            <?php else: ?>
                            <a href="orders.php"
                                class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50/80 hover:text-blue-700 transition-all duration-300 mx-2 rounded-xl">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 group-hover:bg-blue-200 flex items-center justify-center mr-3 transition-colors duration-300">
                                    <i data-feather="package" class="w-4 h-4 text-blue-600 group-hover:scale-110 transition-transform duration-300"></i>
                                </div>
                                <span class="font-medium">My Orders</span>
                            </a>
                            <a href="profile.php"
                                class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-green-50/80 hover:text-green-700 transition-all duration-300 mx-2 rounded-xl">
                                <div class="w-8 h-8 rounded-lg bg-green-100 group-hover:bg-green-200 flex items-center justify-center mr-3 transition-colors duration-300">
                                    <i data-feather="user" class="w-4 h-4 text-green-600 group-hover:scale-110 transition-transform duration-300"></i>
                                </div>
                                <span class="font-medium">Profile</span>
                            </a>
                            <a href="wishlist.php"
                                class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-red-50/80 hover:text-red-700 transition-all duration-300 mx-2 rounded-xl">
                                <div class="w-8 h-8 rounded-lg bg-red-100 group-hover:bg-red-200 flex items-center justify-center mr-3 transition-colors duration-300">
                                    <i data-feather="heart" class="w-4 h-4 text-red-600 group-hover:scale-110 transition-transform duration-300"></i>
                                </div>
                                <span class="font-medium">Wishlist</span>
                            </a>
                            <a href="reviews.php"
                                class="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-yellow-50/80 hover:text-yellow-700 transition-all duration-300 mx-2 rounded-xl">
                                <div class="w-8 h-8 rounded-lg bg-yellow-100 group-hover:bg-yellow-200 flex items-center justify-center mr-3 transition-colors duration-300">
                                    <i data-feather="star" class="w-4 h-4 text-yellow-600 group-hover:scale-110 transition-transform duration-300"></i>
                                </div>
                                <span class="font-medium">My Reviews</span>
                            </a>
                            <?php endif; ?>
                            <hr class="my-2 border-gray-200/50 mx-4">
                            <a href="logout.php"
                                class="group flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50/80 hover:text-red-700 transition-all duration-300 mx-2 rounded-xl">
                                <div class="w-8 h-8 rounded-lg bg-red-100 group-hover:bg-red-200 flex items-center justify-center mr-3 transition-colors duration-300">
                                    <i data-feather="log-out" class="w-4 h-4 text-red-600 group-hover:scale-110 transition-transform duration-300"></i>
                                </div>
                                <span class="font-medium">Logout</span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Mobile Search Bar -->
            <div id="mobileSearch" class="hidden lg:hidden pb-4">
                <form action="shop.php" method="GET">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search products..."
                            class="w-full pl-12 pr-6 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg hover:shadow-xl text-gray-900 placeholder-gray-500"
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button type="submit" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary-600 transition-colors duration-300">
                            <i data-feather="search" class="w-5 h-5"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="hidden lg:block bg-gray-50 border-t">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex space-x-8">
                    <a href="index.php"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors <?= $currentPage === 'index' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                        Home
                    </a>
                    <a href="shop.php"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors <?= $currentPage === 'shop' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                        Shop All
                    </a>
                    <a href="shop.php?category=clothes"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Clothes
                    </a>
                    <a href="shop.php?category=bags"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Bags
                    </a>
                    <a href="shop.php?category=jewelry"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Jewelry
                    </a>
                    <a href="about.php"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        About
                    </a>
                    <a href="contact.php"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Contact
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <!-- Mobile Menu -->
    <div id="mobileMenu"
        class="fixed inset-y-0 left-0 w-80 bg-white shadow-xl z-50 transform -translate-x-full transition-transform duration-300 ease-in-out lg:hidden">
        <div class="flex items-center justify-between p-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Menu</h2>
            <button id="mobileMenuClose" class="p-2 rounded-md text-gray-400 hover:text-gray-600">
                <i data-feather="x" class="w-6 h-6"></i>
            </button>
        </div>

        <nav class="mt-4">
            <a href="index.php"
                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 <?= $currentPage === 'index' ? 'bg-primary-50 text-primary-600 border-r-2 border-primary-500' : '' ?>">
                <i data-feather="home" class="w-5 h-5 mr-3"></i>
                Home
            </a>
            <a href="shop.php"
                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 <?= $currentPage === 'shop' ? 'bg-primary-50 text-primary-600 border-r-2 border-primary-500' : '' ?>">
                <i data-feather="grid" class="w-5 h-5 mr-3"></i>
                Shop All
            </a>
            <a href="shop.php?category=clothes" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i data-feather="shirt" class="w-5 h-5 mr-3"></i>
                Clothes
            </a>
            <a href="shop.php?category=bags" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i data-feather="briefcase" class="w-5 h-5 mr-3"></i>
                Bags
            </a>
            <a href="shop.php?category=jewelry" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i data-feather="award" class="w-5 h-5 mr-3"></i>
                Jewelry
            </a>
            <a href="about.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i data-feather="info" class="w-5 h-5 mr-3"></i>
                About
            </a>
            <a href="contact.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i data-feather="phone" class="w-5 h-5 mr-3"></i>
                Contact
            </a>
        </nav>

        <?php if (!$isLoggedIn): ?>
        <div class="mt-6 px-4">
            <a href="login.php"
                class="block w-full bg-primary-500 text-white text-center py-3 rounded-lg hover:bg-primary-600 transition-colors">
                Login / Register
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script>
    // Mobile menu toggle
    document.getElementById('mobileMenuToggle').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.remove('-translate-x-full');
        document.getElementById('mobileMenuOverlay').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    });

    document.getElementById('mobileMenuClose').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.add('-translate-x-full');
        document.getElementById('mobileMenuOverlay').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    });

    document.getElementById('mobileMenuOverlay').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.add('-translate-x-full');
        document.getElementById('mobileMenuOverlay').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    });

    // Mobile search toggle
    document.getElementById('searchToggle').addEventListener('click', () => {
        document.getElementById('mobileSearch').classList.toggle('hidden');
    });

    // User menu toggle
    document.getElementById('userMenuToggle')?.addEventListener('click', () => {
        document.getElementById('userMenu').classList.toggle('hidden');
    });

    // Close user menu when clicking outside
    document.addEventListener('click', (e) => {
        const userMenu = document.getElementById('userMenu');
        const userMenuToggle = document.getElementById('userMenuToggle');
        if (userMenu && userMenuToggle && !userMenuToggle.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.add('hidden');
        }
    });

    // Search suggestions
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    const suggestionsList = document.getElementById('suggestionsList');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchSuggestions.classList.add('hidden');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                searchSuggestions.classList.add('hidden');
            }
        });

        // Handle keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const suggestions = suggestionsList.querySelectorAll('.suggestion-item');
            const activeSuggestion = suggestionsList.querySelector('.suggestion-item.active');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (activeSuggestion) {
                    activeSuggestion.classList.remove('active');
                    const next = activeSuggestion.nextElementSibling;
                    if (next) {
                        next.classList.add('active');
                    }
                } else if (suggestions.length > 0) {
                    suggestions[0].classList.add('active');
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (activeSuggestion) {
                    activeSuggestion.classList.remove('active');
                    const prev = activeSuggestion.previousElementSibling;
                    if (prev) {
                        prev.classList.add('active');
                    }
                }
            } else if (e.key === 'Enter' && activeSuggestion) {
                e.preventDefault();
                const link = activeSuggestion.querySelector('a');
                if (link) {
                    window.location.href = link.href;
                }
            } else if (e.key === 'Escape') {
                searchSuggestions.classList.add('hidden');
            }
        });
    }

    async function fetchSuggestions(query) {
        try {
            const response = await fetch(`api/search_suggestions.php?q=${encodeURIComponent(query)}&limit=5`);
            const suggestions = await response.json();
            
            if (suggestions.length === 0) {
                searchSuggestions.classList.add('hidden');
                return;
            }
            
            suggestionsList.innerHTML = suggestions.map(suggestion => {
                if (suggestion.type === 'product') {
                    return `
                        <div class="suggestion-item flex items-center p-3 hover:bg-gray-50 cursor-pointer">
                            <img src="${suggestion.image}" alt="${suggestion.title}" 
                                 class="w-10 h-10 object-cover rounded-lg mr-3"
                                 onerror="this.src='assets/images/placeholder.png'">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate">${suggestion.title}</div>
                                <div class="text-sm text-gray-500">${suggestion.subtitle} • KSh ${suggestion.price}</div>
                            </div>
                            <a href="${suggestion.url}" class="hidden"></a>
                        </div>
                    `;
                } else {
                    return `
                        <div class="suggestion-item flex items-center p-3 hover:bg-gray-50 cursor-pointer">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                                <i data-feather="tag" class="w-5 h-5 text-primary-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate">${suggestion.title}</div>
                                <div class="text-sm text-gray-500">${suggestion.subtitle}</div>
                            </div>
                            <a href="${suggestion.url}" class="hidden"></a>
                        </div>
                    `;
                }
            }).join('');
            
            // Add click handlers
            suggestionsList.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    const link = this.querySelector('a');
                    if (link) {
                        window.location.href = link.href;
                    }
                });
            });
            
            searchSuggestions.classList.remove('hidden');
            feather.replace();
            
        } catch (error) {
            console.error('Error fetching suggestions:', error);
            searchSuggestions.classList.add('hidden');
        }
    }

    // Initialize Feather icons
    feather.replace();
    </script>