<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cart count
$cartCount = array_sum(array_column($_SESSION['cart'], 'quantity')) ?? 0;

// Is user logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : null;
$userRole = $isLoggedIn ? ($_SESSION['user_role'] ?? null) : null; // 'admin' or 'user'
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beauty Online Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gray-50 text-gray-900">
    <!-- Navbar -->
    <header class="bg-white shadow sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <!-- Logo -->
            <a href="index.php" class="text-2xl font-bold text-blue-600 flex items-center gap-2">
                <i data-feather="shopping-bag"></i> Beauty Store
            </a>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex space-x-6 items-center">
                <a href="index.php" class="hover:text-yellow-500 flex items-center gap-1">
                    <i data-feather="home"></i> Home
                </a>
                <a href="shop.php" class="hover:text-yellow-500 flex items-center gap-1">
                    <i data-feather="grid"></i> Shop
                </a>

                <!-- Cart -->
                <a href="<?= $isLoggedIn ? 'cart.php' : 'login.php?redirect=cart' ?>"
                    class="relative hover:text-yellow-500 flex items-center gap-1">
                    <i data-feather="shopping-cart"></i> Cart
                    <?php if ($cartCount > 0): ?>
                    <span class="absolute -top-2 -right-3 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        <?= $cartCount ?>
                    </span>
                    <?php endif; ?>
                </a>

                <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                <a href="../admin/dashboard.php" class="hover:text-yellow-500 flex items-center gap-1">
                    <i data-feather="layout"></i> Dashboard
                </a>
                <?php else: ?>
                <!-- Added My Orders -->
                <a href="orders.php" class="hover:text-yellow-500 flex items-center gap-1">
                    <i data-feather="package"></i> My Orders
                </a>
                <?php endif; ?>

                <span class="text-gray-700 flex items-center gap-1">
                    <i data-feather="user"></i> <?= htmlspecialchars($userName) ?>
                </span>
                <a href="logout.php" class="text-red-500 hover:text-red-600 text-sm flex items-center gap-1">
                    <i data-feather="log-out"></i> Logout
                </a>
                <?php else: ?>
                <a href="login.php"
                    class="px-3 py-1 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 flex items-center gap-1">
                    <i data-feather="log-in"></i> Login
                </a>
                <a href="register.php"
                    class="px-3 py-1 border border-yellow-500 text-yellow-600 rounded-lg hover:bg-yellow-100 flex items-center gap-1">
                    <i data-feather="user-plus"></i> Sign Up
                </a>
                <?php endif; ?>
            </nav>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button id="menuToggle" class="text-gray-700 text-2xl">☰</button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden flex-col bg-white shadow-md md:hidden">
            <a href="index.php" class="px-4 py-2 hover:bg-gray-100 flex items-center gap-1">
                <i data-feather="home"></i> Home
            </a>
            <a href="shop.php" class="px-4 py-2 hover:bg-gray-100 flex items-center gap-1">
                <i data-feather="grid"></i> Shop
            </a>
            <a href="<?= $isLoggedIn ? 'cart.php' : 'login.php?redirect=cart' ?>"
                class="px-4 py-2 hover:bg-gray-100 flex items-center gap-1">
                <i data-feather="shopping-cart"></i> Cart
            </a>

            <?php if ($isLoggedIn): ?>
            <?php if ($userRole === 'admin'): ?>
            <a href="../admin/dashboard.php" class="px-4 py-2 hover:bg-gray-100 flex items-center gap-1">
                <i data-feather="layout"></i> Dashboard
            </a>
            <?php else: ?>
            <!-- Added My Orders -->
            <a href="orders.php" class="px-4 py-2 hover:bg-gray-100 flex items-center gap-1">
                <i data-feather="package"></i> My Orders
            </a>
            <?php endif; ?>
            <a href="logout.php" class="px-4 py-2 hover:bg-gray-100 text-red-500 flex items-center gap-1">
                <i data-feather="log-out"></i> Logout
            </a>
            <?php else: ?>
            <a href="login.php" class="px-4 py-2 hover:bg-gray-100 flex items-center gap-1">
                <i data-feather="log-in"></i> Login
            </a>
            <a href="register.php" class="px-4 py-2 hover:bg-gray-100 flex items-center gap-1">
                <i data-feather="user-plus"></i> Sign Up
            </a>
            <?php endif; ?>
        </div>
    </header>

    <script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    // Initialize Feather icons
    feather.replace();
    </script>