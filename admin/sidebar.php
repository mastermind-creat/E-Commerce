<?php
// Fetch pending orders count
require_once '../includes/db.php';
$pendingStmt = $pdo->query("SELECT COUNT(*) AS pending_count FROM orders WHERE status = 'Pending'");
$pendingCount = $pendingStmt->fetch(PDO::FETCH_ASSOC)['pending_count'] ?? 0;
?>

<!-- Mobile Toggle Button -->
<div class="md:hidden fixed top-4 left-4 z-50">
    <button id="sidebarToggle"
        class="p-2 rounded-md bg-blue-600 text-white focus:outline-none focus:ring-2 focus:ring-blue-400"
        aria-label="Open sidebar">
        ☰
    </button>
</div>

<!-- Mobile Overlay -->
<div id="sidebarOverlay"
    class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar"
    class="fixed top-0 left-0 h-screen w-64 bg-white shadow-lg flex flex-col transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40">
    <!-- Logo / Title -->
    <div class="p-6 border-b flex justify-between items-center">
        <h2 class="text-2xl font-bold text-blue-600">Admin Panel</h2>
        <button id="closeSidebar"
            class="md:hidden text-gray-600 text-xl hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400"
            aria-label="Close sidebar">
            &times;
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <a href="dashboard.php"
            class="flex items-center px-4 py-2 rounded-lg transition 
            <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-100 text-blue-700 font-medium' : 'hover:bg-gray-100 text-gray-700'; ?>">
            📊 <span class="ml-2">Dashboard</span>
        </a>
        <a href="products.php"
            class="flex items-center px-4 py-2 rounded-lg transition 
            <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'bg-blue-100 text-blue-700 font-medium' : 'hover:bg-gray-100 text-gray-700'; ?>">
            🛍️ <span class="ml-2">Products</span>
        </a>
        <a href="categories.php"
            class="flex items-center px-4 py-2 rounded-lg transition 
            <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-blue-100 text-blue-700 font-medium' : 'hover:bg-gray-100 text-gray-700'; ?>">
            🗂️ <span class="ml-2">Categories</span>
        </a>
        <a href="orders.php"
            class="flex items-center px-4 py-2 rounded-lg transition relative
            <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-blue-100 text-blue-700 font-medium' : 'hover:bg-gray-100 text-gray-700'; ?>">
            📦 <span class="ml-2">Orders</span>
            <?php if ($pendingCount > 0): ?>
            <span
                class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                <?= $pendingCount ?>
            </span>
            <?php endif; ?>
        </a>
        <a href="admin_hero.php"
            class="flex items-center px-4 py-2 rounded-lg transition 
            <?php echo basename($_SERVER['PHP_SELF']) == 'admin_hero.php' ? 'bg-blue-100 text-blue-700 font-medium' : 'hover:bg-gray-100 text-gray-700'; ?>">
            🖼️ <span class="ml-2">Hero Manager</span>
        </a>
    </nav>

    <!-- Footer -->
    <div class="p-4 border-t">
        <a href="logout.php"
            class="flex items-center px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">
            🚪 <span class="ml-2">Logout</span>
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