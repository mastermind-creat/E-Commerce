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
$userName  = $isLoggedIn ? $_SESSION['user_name'] : null;
$userRole  = $isLoggedIn ? ($_SESSION['user_role'] ?? null) : null;

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= get_setting('site_title', 'Springs Ministries Store') ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?= get_setting('site_description', 'Quality clothes, bags, jewelry and more. Fast delivery, great prices.') ?>">
    <meta name="keywords" content="<?= get_setting('site_keywords', 'ecommerce, online shopping, products') ?>">
    <meta name="robots" content="<?= get_setting('meta_robots', 'index, follow') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/<?= get_setting('site_favicon', 'assets/images/favicon.ico') ?>">
    
    <!-- Google Analytics -->
    <?php if ($gaId = get_setting('google_analytics_id')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($gaId) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
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
    /* Hero carousel overlay styling */
    .hero-slide img { filter: brightness(0.78); transform-origin: center; transition: transform 8s ease; }
    .hero-slide.active img { transform: scale(1.06); }
    .hero-slide .overlay-card { backdrop-filter: blur(4px); }
    .hero-slide .overlay-card h2 { text-shadow: 0 6px 18px rgba(0,0,0,0.45); }
    .hero-slide .overlay-card p { text-shadow: 0 4px 12px rgba(0,0,0,0.35); }
    @media (min-width: 1024px) {
        .hero-slide .overlay-card { max-width: 640px; }
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
    <header class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Top Row -->
            <div class="flex items-center justify-between h-16">
                <!-- Mobile Menu Button -->
                <button id="mobileMenuToggle"
                    class="lg:hidden p-2 rounded-md text-gray-600 hover:text-primary-600 hover:bg-gray-100">
                    <i data-feather="menu" class="w-6 h-6"></i>
                </button>

                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-primary-500 to-pink-600 rounded-lg flex items-center justify-center">
                            <i data-feather="shopping-bag" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="text-xl font-bold text-gray-900">Springs Store</h1>
                            <p class="text-xs text-gray-500">Ministries</p>
                        </div>
                    </a>
                </div>

                <!-- Search Bar (Desktop) -->
                <div class="hidden lg:flex flex-1 max-w-lg mx-8">
                    <form action="shop.php" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search products..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button type="submit"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-primary-600">
                                <i data-feather="search" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Search Button (Mobile) -->
                    <button id="searchToggle"
                        class="lg:hidden p-2 rounded-md text-gray-600 hover:text-primary-600 hover:bg-gray-100">
                        <i data-feather="search" class="w-5 h-5"></i>
                    </button>

                    <!-- Cart -->
                    <a href="<?= $isLoggedIn ? 'cart.php' : 'login.php?redirect=cart' ?>"
                        class="relative p-2 rounded-md text-gray-600 hover:text-primary-600 hover:bg-gray-100 transition-colors">
                        <i data-feather="shopping-cart" class="w-5 h-5"></i>
                        <?php if ($cartCount > 0): ?>
                        <span
                            class="absolute -top-1 -right-1 bg-primary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium cart-badge">
                            <?= $cartCount > 99 ? '99+' : $cartCount ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Menu -->
                    <div class="relative">
                        <?php if ($isLoggedIn): ?>
                        <button id="userMenuToggle"
                            class="flex items-center space-x-2 p-2 rounded-md text-gray-600 hover:text-primary-600 hover:bg-gray-100">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                <i data-feather="user" class="w-4 h-4 text-primary-600"></i>
                            </div>
                            <span class="hidden sm:block text-sm font-medium"><?= htmlspecialchars($userName) ?></span>
                            <i data-feather="chevron-down" class="w-4 h-4"></i>
                        </button>
                        <?php else: ?>
                        <a href="login.php"
                            class="flex items-center space-x-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                            <i data-feather="log-in" class="w-4 h-4"></i>
                            <span class="hidden sm:block">Login</span>
                        </a>
                        <?php endif; ?>

                        <!-- User Dropdown -->
                        <?php if ($isLoggedIn): ?>
                        <div id="userMenu"
                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50">
                            <?php if ($userRole === 'admin'): ?>
                            <a href="../admin/dashboard.php"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i data-feather="layout" class="w-4 h-4 mr-3"></i>
                                Dashboard
                            </a>
                            <?php else: ?>
                            <a href="orders.php"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i data-feather="package" class="w-4 h-4 mr-3"></i>
                                My Orders
                            </a>
                            <a href="profile.php"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i data-feather="user" class="w-4 h-4 mr-3"></i>
                                Profile
                            </a>
                            <?php endif; ?>
                            <hr class="my-1">
                            <a href="logout.php"
                                class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i data-feather="log-out" class="w-4 h-4 mr-3"></i>
                                Logout
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
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button type="submit" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                            <i data-feather="search" class="w-4 h-4"></i>
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

    // Initialize Feather icons
    feather.replace();
    </script>