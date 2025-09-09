<!-- Mobile Toggle Button -->
<div class="md:hidden fixed top-4 left-4 z-50">
    <button id="sidebarToggle"
        class="p-2 rounded-md bg-blue-600 text-white focus:outline-none focus:ring-2 focus:ring-blue-400">
        ☰
    </button>
</div>

<!-- Sidebar -->
<aside id="sidebar"
    class="fixed top-0 left-0 h-screen w-64 bg-white shadow-lg flex flex-col transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-40">
    <!-- Logo / Title -->
    <div class="p-6 border-b flex justify-between items-center">
        <h2 class="text-2xl font-bold text-blue-600">Admin Panel</h2>
        <button id="closeSidebar" class="md:hidden text-gray-600 text-xl">&times;</button>
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
            class="flex items-center px-4 py-2 rounded-lg transition 
            <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-blue-100 text-blue-700 font-medium' : 'hover:bg-gray-100 text-gray-700'; ?>">
            📦 <span class="ml-2">Orders</span>
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

<!-- Overlay (for mobile) -->
<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-30 md:hidden"></div>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
const closeBtn = document.getElementById('closeSidebar');
const overlay = document.getElementById('overlay');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
});

closeBtn.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
});

overlay.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
});
</script>