-- users
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100),
email VARCHAR(100) UNIQUE,
phone VARCHAR(20),
password VARCHAR(255),
role ENUM('customer','admin') DEFAULT 'customer',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- categories
CREATE TABLE categories (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100),
slug VARCHAR(100) UNIQUE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- products
CREATE TABLE products (
id INT AUTO_INCREMENT PRIMARY KEY,
category_id INT,
name VARCHAR(150),
description TEXT,
price DECIMAL(10,2),
stock INT DEFAULT 0,
status ENUM('active','inactive') DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- product_images
CREATE TABLE product_images (
id INT AUTO_INCREMENT PRIMARY KEY,
product_id INT,
image_url VARCHAR(255),
is_primary BOOLEAN DEFAULT FALSE,
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);


-- product_variants (optional)
CREATE TABLE product_variants (
id INT AUTO_INCREMENT PRIMARY KEY,
product_id INT,
color VARCHAR(50),
size VARCHAR(50),
stock INT DEFAULT 0,
extra_price DECIMAL(10,2) DEFAULT 0,
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);


-- orders
CREATE TABLE orders (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NULL,
customer_name VARCHAR(150),
customer_email VARCHAR(150),
customer_phone VARCHAR(30),
shipping_address TEXT,
total_amount DECIMAL(10,2),
payment_method ENUM('mpesa','paypal','cod'),
payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
order_status ENUM('pending','confirmed','shipped','completed','cancelled') DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


-- order_items
CREATE TABLE order_items (
id INT AUTO_INCREMENT PRIMARY KEY,
order_id INT,
product_id INT,
variant_id INT NULL,
quantity INT,
price DECIMAL(10,2),
subtotal DECIMAL(10,2),
FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);


-- payments
CREATE TABLE payments (
id INT AUTO_INCREMENT PRIMARY KEY,
order_id INT,
transaction_id VARCHAR(100),
amount DECIMAL(10,2),
method ENUM('mpesa','paypal'),
status ENUM('pending','success','failed'),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);