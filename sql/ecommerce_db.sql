-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 20, 2025 at 09:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'Bags', 'bags', '2025-09-07 13:18:33'),
(2, 'Short Sleaved Shirts', 'short-sleaved-shirts', '2025-09-07 13:21:18'),
(3, 'Table Mats', 'table-mats', '2025-09-08 17:18:04'),
(4, 'Pillows', 'pillows', '2025-09-09 10:54:58'),
(5, 'Necklaces', 'necklaces', '2025-09-09 11:36:37'),
(6, 'Waiste beads', 'waiste-beads', '2025-09-15 20:01:17');

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL COMMENT 'Path to the slide image (e.g., /public/assets/image.jpg)',
  `title` text NOT NULL COMMENT 'Slide title (e.g., "Discover Handpicked Styles")',
  `description` text NOT NULL COMMENT 'Slide description',
  `button_text` varchar(100) NOT NULL DEFAULT 'Shop Now' COMMENT 'CTA button text',
  `button_link` varchar(255) NOT NULL DEFAULT '/shop.php' COMMENT 'CTA button URL',
  `order_num` int(11) NOT NULL DEFAULT 0 COMMENT 'Display order (lower first)',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Creation timestamp',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hero slider slides for landing page';

--
-- Dumping data for table `hero_slides`
--

INSERT INTO `hero_slides` (`id`, `image_path`, `title`, `description`, `button_text`, `button_link`, `order_num`, `active`, `created_at`, `updated_at`) VALUES
(1, '1757505015_533822337_10171980881495417_8880446056989685548_n.jpg', 'Discover Handpicked Styles', 'Clothes, bags, jewelry and more — quality finds at friendly prices.', 'Shop Now', 'http://localhost/E-Commerce/public/shop.php', 1, 1, '2025-09-09 21:20:27', '2025-09-10 11:50:15'),
(2, '1757505395_ornaments.jpg', 'New Jewelries', 'Hot', 'Shopr Now', 'http://localhost/E-Commerce/public/shop.php', 2, 1, '2025-09-10 11:56:35', '2025-09-10 11:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `notes` varchar(250) NOT NULL,
  `payment_method` enum('cash','mpesa','paypal') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','shipped','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `total_amount`, `status`, `notes`, `payment_method`, `payment_status`, `order_status`, `created_at`, `updated_at`) VALUES
(8, 2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', '0765454323', 'Arina', 400.00, 'pending', '', 'cash', 'paid', 'completed', '2025-09-19 12:48:40', '2025-09-20'),
(9, 2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', '0743394373', 'Kaloleni', 500.00, 'pending', '', 'cash', 'paid', 'completed', '2025-09-20 07:04:27', '2025-09-20'),
(10, 2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', '0743394373', 'Arina', 800.00, 'completed', '', 'cash', 'paid', 'completed', '2025-09-20 07:26:50', '2025-09-20');

-- --------------------------------------------------------

--
-- Table structure for table `order_history`
--

CREATE TABLE `order_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Completed','Cancelled') NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `moved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variant_id`, `quantity`, `price`, `subtotal`) VALUES
(8, 8, 6, NULL, 1, 400.00, 400.00),
(9, 9, 4, NULL, 1, 500.00, 500.00),
(10, 10, 2, NULL, 4, 200.00, 800.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_notes`
--

CREATE TABLE `order_notes` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, 'kennyleyy0@gmail.com', '4aca42ae8198991d496478d83a1a73683b6e698af965d2cede8d9501117e2b11', '2025-09-11 14:01:44', '2025-09-11 11:01:44'),
(2, 'kennyleyy0@gmail.com', '0487b1fe7fa3d621f42fbd8a8074b5737fb2d89439e1e78a867f3551ecf1c331', '2025-09-11 14:06:06', '2025-09-11 11:06:06'),
(3, 'kennyleyy0@gmail.com', '63b9a839aa0c7987588ece1ed3446d111f6ccb8baadaa2376b0a4ebe3f45f728', '2025-09-11 14:09:44', '2025-09-11 11:09:44');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `method` enum('mpesa','paypal') DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `color`, `name`, `description`, `price`, `stock`, `status`, `created_at`, `image`, `image_url`) VALUES
(1, 1, NULL, 'Hand bags', 'Medium', 400.00, 20, 'active', '2025-09-07 13:23:24', NULL, NULL),
(2, 3, NULL, 'Table Mats', 'Nice Woven Mats', 200.00, 25, 'active', '2025-09-08 17:42:26', NULL, NULL),
(3, 5, NULL, 'Necklace', 'Small size', 200.00, 12, 'active', '2025-09-09 11:38:12', NULL, NULL),
(4, 1, NULL, 'Ladies Bags', 'Small', 500.00, 10, 'active', '2025-09-09 11:40:28', NULL, NULL),
(5, 6, NULL, 'Waiste beads', 'small, Medium and Large', 200.00, 99, 'active', '2025-09-15 20:07:30', NULL, NULL),
(6, 3, NULL, 'Table Liner', 'Long authentic table liner', 400.00, 2, 'active', '2025-09-19 11:55:49', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `is_main`, `image_url`, `is_primary`) VALUES
(6, 3, NULL, 0, '68c011a47f564_ornaments.jpg', 0),
(7, 4, NULL, 0, '68c0122c21197_533822337_10171980881495417_8880446056989685548_n.jpg', 0),
(8, 5, NULL, 0, '68cd13f1d07b1_IMG_20250915_142135.jpg', 0),
(9, 4, NULL, 0, '68cd18318e097_IMG_20250915_120758.jpg', 0),
(10, 2, NULL, 0, '68cd1857e4305_IMG_20250917_121714.jpg', 0),
(11, 2, NULL, 0, '68cd1857e5e20_IMG_20250917_121751.jpg', 0),
(12, 2, NULL, 0, '68cd1857e7bab_IMG_20250917_122142.jpg', 0),
(13, 6, NULL, 0, '68cd44c577c1c_IMG_20250917_122053.jpg', 0),
(14, 1, NULL, 0, '68cd521cae7e8_IMG_20250915_120758.jpg', 0),
(15, 1, NULL, 0, '68cd521cb02b3_IMG_20250915_120959.jpg', 0),
(16, 1, NULL, 0, '68cd521cb1e7f_IMG_20250915_121231.jpg', 0),
(17, 1, NULL, 0, '68cd521cb3ae8_IMG_20250915_121305.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_name` varchar(100) NOT NULL,
  `variant_value` varchar(100) NOT NULL,
  `variant_stock` int(11) NOT NULL,
  `variant_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `variant_name`, `variant_value`, `variant_stock`, `variant_image`) VALUES
(1, 3, 'Color', 'Green', 4, 'assets/variants/68c46be178596_533732755_10171980894525417_197341463450478603_n.jpg'),
(2, 4, 'Size', 'Medium', 9, 'assets/variants/68c470973ea37_533732755_10171980894525417_197341463450478603_n.jpg'),
(3, 6, 'Color', 'Green', 1, 'assets/variants/68cd44ef6ee39_IMG_20250917_121559.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `promo_tiles`
--

CREATE TABLE `promo_tiles` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL COMMENT 'Path to the tile image',
  `title` varchar(200) NOT NULL COMMENT 'Tile title (e.g., "Summer Sale")',
  `description` text NOT NULL COMMENT 'Tile description',
  `price_text` varchar(100) DEFAULT NULL COMMENT 'Price display text (e.g., "KSh 999")',
  `link` varchar(255) NOT NULL COMMENT 'Tile link URL',
  `order_num` tinyint(3) NOT NULL DEFAULT 1 COMMENT 'Display order (1-3 for landing page)',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Creation timestamp',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp'
) ;

--
-- Dumping data for table `promo_tiles`
--

INSERT INTO `promo_tiles` (`id`, `image_path`, `title`, `description`, `price_text`, `link`, `order_num`, `active`, `created_at`, `updated_at`) VALUES
(1, '1757627733_534820221_10171980900605417_8676316353991169607_n.jpg', 'New Arivals', 'New stock available', '200', 'http://localhost/E-Commerce/public/shop.php', 1, 1, '2025-09-09 21:44:42', '2025-09-11 21:55:33');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 1, 5, 'The Delivery was first', '2025-09-09 09:22:42'),
(2, 2, 4, 4, 'Woow!... the bag is so nice. I highly recommend', '2025-09-11 22:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `default_address` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `default_address`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@gmail.com', '0700000000', NULL, '$2y$10$PECkZzpos3LSx/TcLVdSWe4IWur1l7V9Q4dkmDzfYSsYhr51kidIq', 'admin', '2025-09-07 12:59:21'),
(2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', NULL, NULL, '$2y$10$x21iPnDsai79n.eF5W7ZBePdxAz9oAkFOjWEbXfdQ2gaMzNeCOcua', 'customer', '2025-09-07 15:08:45'),
(3, 'Jane Atieno', 'jane@beauty.com', '0786543452', 'Kondele', '$2y$10$686x8gHGtTstpXv3uMRj/.7BGVJKLNZcnLVTgRkYE3c85QmMxYflm', 'customer', '2025-09-09 11:56:18'),
(4, 'mercy johnson', 'mercy@gmail.com', '0789876545', 'CBD', '$2y$10$0e76zM2C9mbyFHd1bM1Dj.SuRzXOHKyP.J/O1SPJSnnyt0MrbJlly', 'customer', '2025-09-09 22:01:23'),
(5, 'Martha Atieno', 'martha@gmail.com', '0717468794', 'Akala', '$2y$10$fUIuBdIJoGnLLmP4TFAK9.nzksmOM.FiiW7FE.K.YFV8GeiCrCpMq', 'customer', '2025-09-11 22:48:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_active` (`order_num`,`active`) COMMENT 'For efficient ordering of active slides',
  ADD KEY `idx_active` (`active`) COMMENT 'For filtering active slides';

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_history`
--
ALTER TABLE `order_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_notes`
--
ALTER TABLE `order_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `promo_tiles`
--
ALTER TABLE `promo_tiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_active` (`order_num`,`active`) COMMENT 'For efficient ordering of active tiles (limited to 3)',
  ADD KEY `idx_active` (`active`) COMMENT 'For filtering active tiles';

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_history`
--
ALTER TABLE `order_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_notes`
--
ALTER TABLE `order_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `promo_tiles`
--
ALTER TABLE `promo_tiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_history`
--
ALTER TABLE `order_history`
  ADD CONSTRAINT `order_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_notes`
--
ALTER TABLE `order_notes`
  ADD CONSTRAINT `order_notes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
