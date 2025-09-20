<?php
// Fetch pending orders count
require_once '../includes/db.php';
$pendingStmt = $pdo->query("SELECT COUNT(*) AS pending_count FROM orders WHERE order_status = 'pending'");
$pendingCount = $pendingStmt->fetch(PDO::FETCH_ASSOC)['pending_count'] ?? 0;
?>

<!-- Mobile Toggle Button -->
<div class="md:hidden fixed top-4 left-4 z-50">
    <button id="sidebarToggle"
        class="p-2 rounded-md bg-blue-600 text-white focus:outline-none focus:ring-2 focus:ring-blue-400"
        aria-label="Open sidebar">
        <i data-feather="menu" class="w-5 h-5"></i>
    </button>
</div>

<!-- Mobile Overlay -->
<div id="sidebarOverlay"
    class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar"
    class="fixed top-0 left-0 h-screen w-64 bg-white/95 backdrop-blur shadow-xl flex flex-col transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40">
    <!-- Logo / Title -->
    <div class="p-6 border-b flex justify-between items-center">
        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Admin
            Panel</h2>
        <button id="closeSidebar"
            class="md:hidden text-gray-600 text-xl hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400"
            aria-label="Close sidebar">
            &times;
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <a href="dashboard.php" aria-label="Dashboard"
            class="group flex items-center px-4 py-2 rounded-lg transition-all duration-200 transform hover:translate-x-1 hover:shadow-sm
            <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border-l-4 border-blue-500' : 'hover:bg-gray-100 text-gray-700'; ?>">
            <i data-feather="bar-chart-2" class="w-5 h-5 text-gray-600"></i>
            <span class="ml-2">Dashboard</span>
        </a>
        <a href="products.php" aria-label="Products"
            class="group flex items-center px-4 py-2 rounded-lg transition-all duration-200 transform hover:translate-x-1 hover:shadow-sm
            <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border-l-4 border-blue-500' : 'hover:bg-gray-100 text-gray-700'; ?>">
            <i data-feather="shopping-bag" class="w-5 h-5 text-gray-600"></i>
            <span class="ml-2">Products</span>
        </a>
        <a href="categories.php" aria-label="Categories"
            class="group flex items-center px-4 py-2 rounded-lg transition-all duration-200 transform hover:translate-x-1 hover:shadow-sm
            <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border-l-4 border-blue-500' : 'hover:bg-gray-100 text-gray-700'; ?>">
            <i data-feather="layers" class="w-5 h-5 text-gray-600"></i>
            <span class="ml-2">Categories</span>
        </a>
        <a href="orders.php" aria-label="Orders"
            class="group flex items-center px-4 py-2 rounded-lg transition-all duration-200 transform hover:translate-x-1 hover:shadow-sm relative
            <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border-l-4 border-blue-500' : 'hover:bg-gray-100 text-gray-700'; ?>">
            <i data-feather="package" class="w-5 h-5 text-gray-600"></i>
            <span class="ml-2">Orders</span>
            <?php if ($pendingCount > 0): ?>
            <span
                class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full animate-pulse">
                <?= $pendingCount ?>
            </span>
            <?php endif; ?>
        </a>
        <a href="admin_hero.php" aria-label="Hero Manager"
            class="group flex items-center px-4 py-2 rounded-lg transition-all duration-200 transform hover:translate-x-1 hover:shadow-sm
            <?php echo basename($_SERVER['PHP_SELF']) == 'admin_hero.php' ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border-l-4 border-blue-500' : 'hover:bg-gray-100 text-gray-700'; ?>">
            <i data-feather="image" class="w-5 h-5 text-gray-600"></i>
            <span class="ml-2">Hero Manager</span>
        </a>
    </nav>

    <!-- Footer -->
    <div class="p-4 border-t">
        <a href="logout.php" aria-label="Logout"
            class="flex items-center px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all duration-200 transform hover:translate-x-1">
            <i data-feather="log-out" class="w-5 h-5 text-red-600"></i>
            <span class="ml-2">Logout</span>
        </a>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('closeSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    // Function to open sidebar
    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent body scroll on mobile
    }

    // Function to close sidebar
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = ''; // Re-enable body scroll
    }

    // Toggle button click (open)
    toggleBtn.addEventListener('click', openSidebar);

    // Close button click
    closeBtn.addEventListener('click', closeSidebar);

    // Overlay click (close)
    overlay.addEventListener('click', closeSidebar);

    // Close on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
});
</script>

<style>
/* Subtle card hover */
#sidebar a {
    transition: background-color .2s ease, transform .2s ease, box-shadow .2s ease;
}
</style>
<script>
// Ensure feather icons render inside the admin sidebar
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        try {
            feather.replace();
        } catch (e) {
            /* ignore */ }
    }
});
</script>