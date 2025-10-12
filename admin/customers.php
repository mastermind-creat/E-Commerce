<?php
include('auth.php');

// Check if database connection is available
$dbAvailable = false;
$pdo = null;

try {
    include('../includes/db.php');
    // Test the connection with a simple query
    $testQuery = $pdo->query("SELECT 1");
    $dbAvailable = true;
} catch (Exception $e) {
    $dbAvailable = false;
    $error = "❌ Database connection failed: " . $e->getMessage() . ". Please ensure MySQL drivers are installed.";
}

$success = $error = "";

// Pagination settings
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage);

// Mock data for demonstration when database is not available
$mockCustomers = [
    [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '0712345678',
        'default_address' => '123 Main Street, Nairobi',
        'role' => 'customer',
        'created_at' => '2025-01-01 10:00:00'
    ],
    [
        'id' => 2,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '0723456789',
        'default_address' => '456 Oak Avenue, Mombasa',
        'role' => 'customer',
        'created_at' => '2025-01-02 11:30:00'
    ],
    [
        'id' => 3,
        'name' => 'Mike Johnson',
        'email' => 'mike@example.com',
        'phone' => '0734567890',
        'default_address' => '789 Pine Road, Kisumu',
        'role' => 'customer',
        'created_at' => '2025-01-03 14:15:00'
    ]
];

if ($dbAvailable) {
    // Handle customer addition
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $role = 'customer'; // Default role for customers

        // Validation
        if (empty($name) || empty($email)) {
            $error = "❌ Name and email are required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "❌ Please enter a valid email address.";
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "❌ Email address already exists.";
                } else {
                    // Generate a temporary password (customer will need to reset)
                    $tempPassword = bin2hex(random_bytes(8));
                    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, default_address, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $address, $hashedPassword, $role]);
                    $success = "✅ Customer added successfully! Temporary password: " . $tempPassword;
                }
            } catch (Exception $e) {
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }

    // Handle customer update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
        $id = $_POST['customer_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        // Validation
        if (empty($name) || empty($email)) {
            $error = "❌ Name and email are required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "❌ Please enter a valid email address.";
        } else {
            try {
                // Check if email already exists for another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    $error = "❌ Email address already exists for another customer.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, default_address = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $address, $id]);
                    $success = "✅ Customer updated successfully!";
                }
            } catch (Exception $e) {
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }

    // Handle customer delete
    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
            $stmt->execute([$id]);
            $success = "🗑️ Customer deleted successfully!";
        } catch (Exception $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    }

    // Get total count of customers
    try {
        $totalCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
        $totalCustomers = $totalCountStmt->fetchColumn();
    } catch (Exception $e) {
        $totalCustomers = 0;
        $error = "❌ Error counting customers: " . $e->getMessage();
    }

    // Calculate pagination
    $totalPages = ceil($totalCustomers / $itemsPerPage);
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Fetch customers with pagination
    $customers = [];
    try {
        // First try with simple query to see if data exists
        $testStmt = $pdo->query("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC");
        $allCustomers = $testStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($allCustomers) > 0) {
            // Use array_slice for pagination to avoid LIMIT/OFFSET issues
            $customers = array_slice($allCustomers, $offset, $itemsPerPage);
        }
    } catch (Exception $e) {
        $error = "❌ Error fetching customers: " . $e->getMessage();
        $customers = [];
    }

    // Get customer for editing
    $editCustomer = null;
    if (isset($_GET['edit'])) {
        $editId = $_GET['edit'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
            $stmt->execute([$editId]);
            $editCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = "❌ Error loading customer data.";
        }
    }
} else {
    // Use mock data when database is not available
    $totalCustomers = count($mockCustomers);
    $totalPages = ceil($totalCustomers / $itemsPerPage);
    $offset = ($currentPage - 1) * $itemsPerPage;
    $customers = array_slice($mockCustomers, $offset, $itemsPerPage);
    
    // Handle mock edit
    $editCustomer = null;
    if (isset($_GET['edit'])) {
        $editId = $_GET['edit'];
        foreach ($mockCustomers as $customer) {
            if ($customer['id'] == $editId) {
                $editCustomer = $customer;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Springs Store Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .card-hover {
        transition: all 0.3s ease;
    }
    
    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="flex flex-col md:flex-row">
        <?php include('sidebar.php'); ?>

        <!-- Main content -->
        <main class="flex-1 p-6 md:ml-64 mb-8 sm:mb-0">
            <!-- Header -->
            <div class="mb-8">
                <div class="gradient-bg rounded-2xl p-8 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="relative z-10">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 class="text-3xl sm:text-4xl font-bold mb-2">Manage Customers</h1>
                                <p class="text-blue-100 text-lg">View and manage customer accounts</p>
                            </div>
                            <div class="mt-4 sm:mt-0 flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-blue-100 text-sm">Total Customers</p>
                                    <p class="text-2xl font-bold"><?= $totalCustomers ?></p>
                                    <?php if ($totalPages > 1): ?>
                                    <p class="text-blue-100 text-xs mt-1">Page <?= $currentPage ?> of <?= $totalPages ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                                    <i data-feather="users" class="w-8 h-8"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-12 -translate-x-12"></div>
                </div>
            </div>

            <!-- Database Status -->
            <?php if (!$dbAvailable): ?>
            <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg flex items-center">
                <i data-feather="alert-triangle" class="w-5 h-5 mr-2"></i>
                <div>
                    <strong>Database Connection Issue:</strong> MySQL drivers are not installed. 
                    The page is showing mock data for demonstration. 
                    To fix this, install the PDO MySQL extension: <code>sudo apt-get install php-mysql</code>
                </div>
            </div>
            <?php else: ?>
            <!-- Debug Information -->
            <?php if (isset($_GET['debug'])): ?>
            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg">
                <h3 class="font-semibold mb-2">Debug Information:</h3>
                <p><strong>Total Customers:</strong> <?= $totalCustomers ?></p>
                <p><strong>Customers Fetched:</strong> <?= count($customers) ?></p>
                <p><strong>Current Page:</strong> <?= $currentPage ?></p>
                <p><strong>Items Per Page:</strong> <?= $itemsPerPage ?></p>
                <p><strong>Offset:</strong> <?= $offset ?></p>
                <p><strong>Total Pages:</strong> <?= $totalPages ?></p>
                <?php if (count($customers) > 0): ?>
                <p><strong>First Customer:</strong> <?= htmlspecialchars($customers[0]['name'] ?? 'N/A') ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Success / Error messages -->
            <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
                <?= $success ?>
            </div>
            <?php elseif ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
                <?= $error ?>
            </div>
            <?php endif; ?>

            <!-- Two column layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Add/Edit Customer Form -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 card-hover">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="flex items-center gap-3 text-lg font-semibold text-gray-900">
                            <i data-feather="<?= $editCustomer ? 'edit' : 'user-plus' ?>" class="w-5 h-5 text-blue-600"></i> 
                            <?= $editCustomer ? 'Edit Customer' : 'Add New Customer' ?>
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            <?= $editCustomer ? 'Update customer information' : 'Create a new customer account' ?>
                        </p>
                    </div>
                    <form method="POST" class="p-6 space-y-6">
                        <?php if ($editCustomer): ?>
                        <input type="hidden" name="customer_id" value="<?= $editCustomer['id'] ?>">
                        <?php endif; ?>
                        <input type="hidden" name="<?= $editCustomer ? 'update_customer' : 'add_customer' ?>" value="1">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" name="name" required
                                    value="<?= htmlspecialchars($editCustomer['name'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                    placeholder="Enter full name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" name="email" required
                                    value="<?= htmlspecialchars($editCustomer['email'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                    placeholder="Enter email address">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" name="phone"
                                    value="<?= htmlspecialchars($editCustomer['phone'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                    placeholder="Enter phone number">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                                <input type="text" value="Customer" readonly
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Address</label>
                            <textarea name="address" rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                placeholder="Enter default shipping address"><?= htmlspecialchars($editCustomer['default_address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="flex gap-3">
                            <button type="submit"
                                class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                                <i data-feather="<?= $editCustomer ? 'save' : 'user-plus' ?>" class="w-4 h-4 mr-2"></i>
                                <?= $editCustomer ? 'Update Customer' : 'Add Customer' ?>
                            </button>
                            <?php if ($editCustomer): ?>
                            <a href="customers.php"
                                class="inline-flex items-center justify-center px-6 py-3 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                <i data-feather="x" class="w-4 h-4 mr-2"></i>
                                Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Customers Table -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 card-hover mb-12">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="flex items-center gap-3 text-lg font-semibold text-gray-900">
                            <i data-feather="users" class="w-5 h-5 text-gray-600"></i> 
                            All Customers
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Manage existing customer accounts</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($customers) > 0): ?>
                                <?php foreach ($customers as $customer): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $customer['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($customer['email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($customer['phone'] ?: 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($customer['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="?edit=<?= $customer['id'] ?>"
                                                class="inline-flex items-center gap-1 bg-blue-500 text-white px-3 py-2 rounded-lg shadow hover:bg-blue-600 transition-colors"
                                                aria-label="Edit customer <?= htmlspecialchars($customer['name']) ?>">
                                                <i data-feather="edit" class="w-4 h-4"></i>
                                                <span class="hidden sm:inline">Edit</span>
                                            </a>
                                            <a href="?delete=<?= $customer['id'] ?>"
                                                onclick="return confirm('Are you sure you want to delete this customer?')"
                                                class="inline-flex items-center gap-1 bg-red-500 text-white px-3 py-2 rounded-lg shadow hover:bg-red-600 transition-colors"
                                                aria-label="Delete customer <?= htmlspecialchars($customer['name']) ?>">
                                                <i data-feather="trash-2" class="w-4 h-4"></i>
                                                <span class="hidden sm:inline">Delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i data-feather="user-x" class="w-12 h-12 text-gray-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                                            <p class="text-gray-500">Get started by adding your first customer.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 mb-4 sm:mb-0">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <!-- Mobile pagination -->
                                <?php if ($currentPage > 1): ?>
                                <a href="?page=<?= $currentPage - 1 ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?= $currentPage + 1 ?>" 
                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing 
                                        <span class="font-medium"><?= $offset + 1 ?></span>
                                        to 
                                        <span class="font-medium"><?= min($offset + $itemsPerPage, $totalCustomers) ?></span>
                                        of 
                                        <span class="font-medium"><?= $totalCustomers ?></span>
                                        results
                                    </p>
                                </div>
                                
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <!-- Previous button -->
                                        <?php if ($currentPage > 1): ?>
                                        <a href="?page=<?= $currentPage - 1 ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i data-feather="chevron-left" class="w-4 h-4"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                            <i data-feather="chevron-left" class="w-4 h-4"></i>
                                        </span>
                                        <?php endif; ?>
                                        
                                        <!-- Page numbers -->
                                        <?php
                                        $startPage = max(1, $currentPage - 2);
                                        $endPage = min($totalPages, $currentPage + 2);
                                        
                                        // Show first page if not in range
                                        if ($startPage > 1): ?>
                                        <a href="?page=1" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            1
                                        </a>
                                        <?php if ($startPage > 2): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                            ...
                                        </span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Page range -->
                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <?php if ($i == $currentPage): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">
                                            <?= $i ?>
                                        </span>
                                        <?php else: ?>
                                        <a href="?page=<?= $i ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <?= $i ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <!-- Show last page if not in range -->
                                        <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                            ...
                                        </span>
                                        <?php endif; ?>
                                        <a href="?page=<?= $totalPages ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <?= $totalPages ?>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <!-- Next button -->
                                        <?php if ($currentPage < $totalPages): ?>
                                        <a href="?page=<?= $currentPage + 1 ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i data-feather="chevron-right" class="w-4 h-4"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                            <i data-feather="chevron-right" class="w-4 h-4"></i>
                                        </span>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        try {
            feather.replace();
        } catch (e) {
            /* ignore */
        }
    }
});
</script>
