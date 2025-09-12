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
$userName  = $isLoggedIn ? $_SESSION['user_name'] : null;
$userRole  = $isLoggedIn ? ($_SESSION['user_role'] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Springs Ministries Online Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gray-50 text-gray-900">
    <!-- Navbar -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <!-- Logo -->
            <a href="index.php" class="text-2xl font-extrabold text-pink-600 tracking-wide flex items-center gap-2">
                <i data-feather="shopping-bag" class="w-6 h-6"></i> Springs Ministries Online Shop
            </a>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex space-x-8 items-center font-medium">
                <a href="index.php" class="flex items-center gap-1 hover:text-pink-600 transition">
                    <i data-feather="home" class="w-4 h-4"></i> Home
                </a>
                <a href="shop.php" class="flex items-center gap-1 hover:text-pink-600 transition">
                    <i data-feather="grid" class="w-4 h-4"></i> Shop
                </a>

                <!-- Cart -->
                <a href="<?= $isLoggedIn ? 'cart.php' : 'login.php?redirect=cart' ?>"
                    class="relative flex items-center gap-1 hover:text-pink-600 transition">
                    <i data-feather="shopping-cart" class="w-4 h-4"></i> Cart
                    <?php if ($cartCount > 0): ?>
                    <span
                        class="absolute -top-2 -right-3 bg-pink-500 text-white text-xs px-2 py-0.5 rounded-full shadow-sm">
                        <?= $cartCount ?>
                    </span>
                    <?php endif; ?>
                </a>

                <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                <a href="../admin/dashboard.php"
                    class="flex items-center gap-1 border border-pink-500 text-pink-600 px-3 py-1.5 rounded-full hover:bg-pink-50 transition">
                    <i data-feather="layout" class="w-4 h-4"></i> Dashboard
                </a>
                <?php else: ?>
                <a href="orders.php" class="flex items-center gap-1 hover:text-pink-600 transition">
                    <i data-feather="package" class="w-4 h-4"></i> My Orders
                </a>
                <?php endif; ?>

                <span class="flex items-center gap-1 text-gray-700">
                    <i data-feather="user" class="w-4 h-4"></i> <?= htmlspecialchars($userName) ?>
                </span>
                <a href="logout.php" class="flex items-center gap-1 text-red-500 hover:text-red-600 text-sm transition">
                    <i data-feather="log-out" class="w-4 h-4"></i> Logout
                </a>
                <?php else: ?>
                <a href="login.php"
                    class="flex items-center gap-1 bg-pink-500 text-white px-4 py-1.5 rounded-full hover:bg-pink-600 transition">
                    <i data-feather="log-in" class="w-4 h-4"></i> Login
                </a>
                <a href="../admin/index.php"
                    class="flex items-center gap-1 border border-pink-500 text-pink-600 px-4 py-1.5 rounded-full hover:bg-pink-50 transition">
                    <i data-feather="shield" class="w-4 h-4"></i> Admin
                </a>
                <?php endif; ?>
            </nav>

            <!-- Mobile Menu Button -->
            <button id="menuToggle" class="md:hidden text-gray-700">
                <i data-feather="menu" class="w-6 h-6"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden flex-col bg-white shadow-md md:hidden divide-y divide-gray-100">
            <a href="index.php" class="px-6 py-3 flex items-center gap-2 hover:bg-pink-50">
                <i data-feather="home" class="w-4 h-4"></i> Home
            </a>
            <a href="shop.php" class="px-6 py-3 flex items-center gap-2 hover:bg-pink-50">
                <i data-feather="grid" class="w-4 h-4"></i> Shop
            </a>
            <a href="<?= $isLoggedIn ? 'cart.php' : 'login.php?redirect=cart' ?>"
                class="px-6 py-3 flex items-center gap-2 hover:bg-pink-50">
                <i data-feather="shopping-cart" class="w-4 h-4"></i> Cart
            </a>

            <?php if ($isLoggedIn): ?>
            <?php if ($userRole === 'admin'): ?>
            <a href="../admin/dashboard.php" class="px-6 py-3 flex items-center gap-2 hover:bg-pink-50">
                <i data-feather="layout" class="w-4 h-4"></i> Dashboard
            </a>
            <?php else: ?>
            <a href="orders.php" class="px-6 py-3 flex items-center gap-2 hover:bg-pink-50">
                <i data-feather="package" class="w-4 h-4"></i> My Orders
            </a>
            <?php endif; ?>
            <a href="logout.php" class="px-6 py-3 flex items-center gap-2 text-red-500 hover:bg-pink-50">
                <i data-feather="log-out" class="w-4 h-4"></i> Logout
            </a>
            <?php else: ?>
            <a href="login.php" class="px-6 py-3 flex items-center gap-2 hover:bg-pink-50">
                <i data-feather="log-in" class="w-4 h-4"></i> Login
            </a>
            <a href="../admin/index.php" class="px-6 py-3 flex items-center gap-2 hover:bg-pink-50">
                <i data-feather="shield" class="w-4 h-4"></i> Admin
            </a>
            <?php endif; ?>
        </div>
    </header>

    <script>
    document.getElementById('menuToggle').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });
    feather.replace();
    </script>