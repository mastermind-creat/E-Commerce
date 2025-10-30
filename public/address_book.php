<?php
// public/address_book.php - Address Book Management
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=address_book.php');
    exit;
}

$pageTitle = 'Address Book';
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $addressId = $_POST['address_id'] ?? null;
        $addressType = $_POST['address_type'] ?? 'home';
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $addressLine1 = trim($_POST['address_line_1'] ?? '');
        $addressLine2 = trim($_POST['address_line_2'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? 'Kenya');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $deliveryInstructions = trim($_POST['delivery_instructions'] ?? '');
        
        // Validation
        if (empty($firstName) || empty($lastName) || empty($addressLine1) || empty($city) || empty($state) || empty($postalCode) || empty($phone)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // If setting as default, unset other defaults
                if ($isDefault) {
                    $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$userId]);
                }
                
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_addresses 
                        (user_id, address_type, is_default, first_name, last_name, company, address_line_1, address_line_2, city, state, postal_code, country, phone, email, delivery_instructions)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $userId, $addressType, $isDefault, $firstName, $lastName, $company, $addressLine1, $addressLine2, 
                        $city, $state, $postalCode, $country, $phone, $email, $deliveryInstructions
                    ]);
                    $success = 'Address added successfully!';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE user_addresses SET 
                        address_type = ?, is_default = ?, first_name = ?, last_name = ?, company = ?, 
                        address_line_1 = ?, address_line_2 = ?, city = ?, state = ?, postal_code = ?, 
                        country = ?, phone = ?, email = ?, delivery_instructions = ?
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([
                        $addressType, $isDefault, $firstName, $lastName, $company, $addressLine1, $addressLine2,
                        $city, $state, $postalCode, $country, $phone, $email, $deliveryInstructions, $addressId, $userId
                    ]);
                    $success = 'Address updated successfully!';
                }
                
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error saving address: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $addressId = $_POST['address_id'] ?? null;
        if ($addressId) {
            try {
                $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
                $stmt->execute([$addressId, $userId]);
                $success = 'Address deleted successfully!';
            } catch (Exception $e) {
                $error = 'Error deleting address: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'set_default') {
        $addressId = $_POST['address_id'] ?? null;
        if ($addressId) {
            try {
                $pdo->beginTransaction();
                
                // Unset all defaults
                $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                // Set new default
                $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$addressId, $userId]);
                
                $pdo->commit();
                $success = 'Default address updated successfully!';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error updating default address: ' . $e->getMessage();
            }
        }
    }
}

// Get user addresses
try {
    $stmt = $pdo->prepare("
        SELECT * FROM user_addresses 
        WHERE user_id = ? 
        ORDER BY is_default DESC, created_at DESC
    ");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $addresses = [];
}

// Get address for editing
$editAddress = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($addresses as $addr) {
        if ($addr['id'] == $editId) {
            $editAddress = $addr;
            break;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }
    
    .address-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .address-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .address-type-badge {
        position: relative;
        overflow: hidden;
    }
    
    .address-type-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }
    
    .address-type-badge:hover::before {
        left: 100%;
    }
</style>

<main class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-xl">
                        <i data-feather="map-pin" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Address Book</h1>
                        <p class="text-lg text-gray-600 mt-2">Manage your delivery addresses</p>
                    </div>
                </div>
                <button onclick="openAddModal()" 
                        class="bg-gradient-to-r from-primary-500 to-pink-600 text-white px-6 py-3 rounded-2xl font-semibold hover:from-primary-600 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105 flex items-center space-x-2">
                    <i data-feather="plus" class="w-5 h-5"></i>
                    <span>Add Address</span>
                </button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl flex items-center space-x-2">
            <i data-feather="check-circle" class="w-5 h-5"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-2xl flex items-center space-x-2">
            <i data-feather="alert-circle" class="w-5 h-5"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Addresses Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($addresses)): ?>
            <div class="col-span-full">
                <div class="bg-white/60 backdrop-blur-sm rounded-3xl shadow-xl border border-white/30 p-12 text-center">
                    <i data-feather="map-pin" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No addresses found</h3>
                    <p class="text-gray-600 mb-6">Add your first address to get started</p>
                    <button onclick="openAddModal()" 
                            class="bg-gradient-to-r from-primary-500 to-pink-600 text-white px-6 py-3 rounded-2xl font-semibold hover:from-primary-600 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                        Add Address
                    </button>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($addresses as $address): ?>
            <div class="address-card bg-white/80 backdrop-blur-md rounded-2xl shadow-lg border border-white/20 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="address-type-badge bg-gradient-to-r from-primary-500 to-pink-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                            <?= ucfirst($address['address_type']) ?>
                        </div>
                        <?php if ($address['is_default']): ?>
                        <div class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold flex items-center space-x-1">
                            <i data-feather="star" class="w-3 h-3"></i>
                            <span>Default</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="editAddress(<?= $address['id'] ?>)" 
                                class="text-gray-400 hover:text-primary-600 transition-colors duration-200 p-1">
                            <i data-feather="edit-2" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteAddress(<?= $address['id'] ?>)" 
                                class="text-gray-400 hover:text-red-600 transition-colors duration-200 p-1">
                            <i data-feather="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="space-y-2 mb-4">
                    <h3 class="font-semibold text-gray-900">
                        <?= htmlspecialchars($address['first_name'] . ' ' . $address['last_name']) ?>
                    </h3>
                    <?php if ($address['company']): ?>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($address['company']) ?></p>
                    <?php endif; ?>
                    <p class="text-gray-700">
                        <?= htmlspecialchars($address['address_line_1']) ?><br>
                        <?php if ($address['address_line_2']): ?>
                        <?= htmlspecialchars($address['address_line_2']) ?><br>
                        <?php endif; ?>
                        <?= htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']) ?><br>
                        <?= htmlspecialchars($address['country']) ?>
                    </p>
                    <p class="text-sm text-gray-600">
                        <i data-feather="phone" class="w-4 h-4 inline mr-1"></i>
                        <?= htmlspecialchars($address['phone']) ?>
                    </p>
                    <?php if ($address['email']): ?>
                    <p class="text-sm text-gray-600">
                        <i data-feather="mail" class="w-4 h-4 inline mr-1"></i>
                        <?= htmlspecialchars($address['email']) ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($address['delivery_instructions']): ?>
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600">
                            <i data-feather="info" class="w-4 h-4 inline mr-1"></i>
                            <strong>Delivery Instructions:</strong><br>
                            <?= htmlspecialchars($address['delivery_instructions']) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex space-x-2">
                    <?php if (!$address['is_default']): ?>
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="action" value="set_default">
                        <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                        <button type="submit" 
                                class="w-full bg-green-100 text-green-700 py-2 px-4 rounded-xl font-semibold hover:bg-green-200 transition-colors duration-200 text-sm">
                            Set as Default
                        </button>
                    </form>
                    <?php endif; ?>
                    <button onclick="editAddress(<?= $address['id'] ?>)" 
                            class="flex-1 bg-primary-100 text-primary-700 py-2 px-4 rounded-xl font-semibold hover:bg-primary-200 transition-colors duration-200 text-sm">
                        Edit
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Add/Edit Address Modal -->
<div id="addressModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-900">Add New Address</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-feather="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="addressForm" method="POST" class="p-6 space-y-6 overflow-y-auto max-h-[70vh]">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="address_id" id="addressId" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Address Type *</label>
                    <select name="address_type" id="addressType" required
                            class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                        <option value="home">Home</option>
                        <option value="work">Work</option>
                        <option value="billing">Billing</option>
                        <option value="shipping">Shipping</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_default" id="isDefault" 
                           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <label for="isDefault" class="ml-2 text-sm font-semibold text-gray-700">
                        Set as default address
                    </label>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">First Name *</label>
                    <input type="text" name="first_name" id="firstName" required
                           class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Last Name *</label>
                    <input type="text" name="last_name" id="lastName" required
                           class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Company</label>
                <input type="text" name="company" id="company"
                       class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Address Line 1 *</label>
                <input type="text" name="address_line_1" id="addressLine1" required
                       class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Address Line 2</label>
                <input type="text" name="address_line_2" id="addressLine2"
                       class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">City *</label>
                    <input type="text" name="city" id="city" required
                           class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">State/County *</label>
                    <input type="text" name="state" id="state" required
                           class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Postal Code *</label>
                    <input type="text" name="postal_code" id="postalCode" required
                           class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Country *</label>
                    <select name="country" id="country" required
                            class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                        <option value="Kenya">Kenya</option>
                        <option value="Uganda">Uganda</option>
                        <option value="Tanzania">Tanzania</option>
                        <option value="Rwanda">Rwanda</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Phone *</label>
                    <input type="tel" name="phone" id="phone" required
                           class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input type="email" name="email" id="email"
                       class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Delivery Instructions</label>
                <textarea name="delivery_instructions" id="deliveryInstructions" rows="3"
                          class="w-full px-4 py-3 bg-white/60 backdrop-blur-sm border border-white/30 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-300/50 focus:bg-white/80 transition-all duration-300 shadow-lg text-gray-900 resize-none"></textarea>
            </div>
            
            <div class="flex space-x-4 pt-6">
                <button type="button" onclick="closeModal()" 
                        class="flex-1 bg-gray-100 text-gray-700 py-3 px-6 rounded-2xl font-semibold hover:bg-gray-200 transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-primary-500 to-pink-600 text-white py-3 px-6 rounded-2xl font-semibold hover:from-primary-600 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl">
                    Save Address
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-feather="trash-2" class="w-8 h-8 text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Address</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this address? This action cannot be undone.</p>
            <div class="flex space-x-4">
                <button onclick="closeDeleteModal()" 
                        class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-xl font-semibold hover:bg-gray-200 transition-colors duration-200">
                    Cancel
                </button>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="address_id" id="deleteAddressId" value="">
                    <button type="submit" 
                            class="w-full bg-red-500 text-white py-2 px-4 rounded-xl font-semibold hover:bg-red-600 transition-colors duration-200">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Address data for editing
const addresses = <?= json_encode($addresses) ?>;

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Address';
    document.getElementById('formAction').value = 'add';
    document.getElementById('addressId').value = '';
    document.getElementById('addressForm').reset();
    document.getElementById('addressModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function editAddress(addressId) {
    const address = addresses.find(addr => addr.id == addressId);
    if (!address) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Address';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('addressId').value = address.id;
    document.getElementById('addressType').value = address.address_type;
    document.getElementById('isDefault').checked = address.is_default == 1;
    document.getElementById('firstName').value = address.first_name;
    document.getElementById('lastName').value = address.last_name;
    document.getElementById('company').value = address.company || '';
    document.getElementById('addressLine1').value = address.address_line_1;
    document.getElementById('addressLine2').value = address.address_line_2 || '';
    document.getElementById('city').value = address.city;
    document.getElementById('state').value = address.state;
    document.getElementById('postalCode').value = address.postal_code;
    document.getElementById('country').value = address.country;
    document.getElementById('phone').value = address.phone;
    document.getElementById('email').value = address.email || '';
    document.getElementById('deliveryInstructions').value = address.delivery_instructions || '';
    
    document.getElementById('addressModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeModal() {
    document.getElementById('addressModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function deleteAddress(addressId) {
    document.getElementById('deleteAddressId').value = addressId;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Close modals on background click
document.getElementById('addressModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('addressModal')) {
        closeModal();
    }
});

document.getElementById('deleteModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('deleteModal')) {
        closeDeleteModal();
    }
});

// Initialize Feather icons
feather.replace();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
