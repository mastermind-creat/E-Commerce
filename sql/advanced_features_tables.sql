-- Advanced E-Commerce Features Database Tables
-- Address Book, Loyalty Points, Referral Program, Returns & Refunds

-- Address Book Table
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_type ENUM('home', 'work', 'billing', 'shipping', 'other') DEFAULT 'home',
    is_default BOOLEAN DEFAULT FALSE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(100) NULL,
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Kenya',
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NULL,
    delivery_instructions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_address_type (address_type),
    INDEX idx_is_default (is_default)
);

-- Loyalty Points System
CREATE TABLE IF NOT EXISTS loyalty_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL DEFAULT 0,
    points_earned INT NOT NULL DEFAULT 0,
    points_redeemed INT NOT NULL DEFAULT 0,
    points_expired INT NOT NULL DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_points (user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_points (points)
);

-- Loyalty Points Transactions
CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('earned', 'redeemed', 'expired', 'adjusted') NOT NULL,
    points INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_type ENUM('purchase', 'referral', 'review', 'signup', 'admin_adjustment', 'redemption') NOT NULL,
    reference_id INT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created_at (created_at)
);

-- Referral Program
CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    referral_code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
    points_awarded INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_referral (referrer_id, referred_id),
    INDEX idx_referrer_id (referrer_id),
    INDEX idx_referred_id (referred_id),
    INDEX idx_referral_code (referral_code),
    INDEX idx_status (status)
);

-- User Referral Codes
CREATE TABLE IF NOT EXISTS user_referral_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    referral_code VARCHAR(20) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    total_referrals INT DEFAULT 0,
    total_points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_code (user_id),
    INDEX idx_referral_code (referral_code),
    INDEX idx_is_active (is_active)
);

-- Returns and Refunds
CREATE TABLE IF NOT EXISTS returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    return_number VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending', 'approved', 'rejected', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    reason ENUM('defective', 'wrong_item', 'not_as_described', 'changed_mind', 'damaged_shipping', 'other') NOT NULL,
    description TEXT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_return_number (return_number)
);

-- Return Items
CREATE TABLE IF NOT EXISTS return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    order_item_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    item_condition ENUM('new', 'used', 'damaged', 'defective') NOT NULL,
    refund_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_return_id (return_id),
    INDEX idx_order_item_id (order_item_id),
    INDEX idx_product_id (product_id)
);

-- Refunds
CREATE TABLE IF NOT EXISTS refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    refund_number VARCHAR(50) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('original_payment', 'store_credit', 'bank_transfer', 'mobile_money') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    processed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    transaction_reference VARCHAR(100) NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
    INDEX idx_return_id (return_id),
    INDEX idx_refund_number (refund_number),
    INDEX idx_status (status)
);

-- Add loyalty points to users table if not exists
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS loyalty_points INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) UNIQUE NULL,
ADD COLUMN IF NOT EXISTS referred_by INT NULL,
ADD COLUMN IF NOT EXISTS total_referrals INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_points_earned INT DEFAULT 0;

-- Add foreign key for referred_by (only if it doesn't exist)
SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
                         WHERE TABLE_SCHEMA = 'ecommerce_db' 
                         AND TABLE_NAME = 'users' 
                         AND CONSTRAINT_NAME = 'fk_users_referred_by');
SET @sql = IF(@constraint_exists = 0, 
              'ALTER TABLE users ADD CONSTRAINT fk_users_referred_by FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL', 
              'SELECT "Foreign key already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes for new user columns (only if they don't exist)
SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = 'ecommerce_db' 
                    AND TABLE_NAME = 'users' 
                    AND INDEX_NAME = 'idx_loyalty_points');
SET @sql = IF(@index_exists = 0, 'ALTER TABLE users ADD INDEX idx_loyalty_points (loyalty_points)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = 'ecommerce_db' 
                    AND TABLE_NAME = 'users' 
                    AND INDEX_NAME = 'idx_referral_code');
SET @sql = IF(@index_exists = 0, 'ALTER TABLE users ADD INDEX idx_referral_code (referral_code)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = 'ecommerce_db' 
                    AND TABLE_NAME = 'users' 
                    AND INDEX_NAME = 'idx_referred_by');
SET @sql = IF(@index_exists = 0, 'ALTER TABLE users ADD INDEX idx_referred_by (referred_by)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default loyalty points for existing users
INSERT IGNORE INTO loyalty_points (user_id, points, points_earned, points_redeemed, points_expired)
SELECT id, 0, 0, 0, 0 FROM users WHERE id NOT IN (SELECT user_id FROM loyalty_points);

-- Create referral codes for existing users
INSERT IGNORE INTO user_referral_codes (user_id, referral_code, is_active, total_referrals, total_points_earned)
SELECT 
    id, 
    CONCAT('REF', LPAD(id, 6, '0')), 
    TRUE, 
    0, 
    0 
FROM users 
WHERE id NOT IN (SELECT user_id FROM user_referral_codes);
