-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 30, 2025 at 10:06 PM
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
(6, 'Waiste beads', 'waiste-beads', '2025-09-15 20:01:17'),
(7, 'Shirts', 'shirts', '2025-09-20 08:30:37'),
(8, 'Jewelries', 'jewelries', '2025-10-11 23:19:10'),
(10, 'Sneakers', 'sneakers', '2025-10-12 11:26:24');

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
-- Table structure for table `loyalty_points`
--

CREATE TABLE `loyalty_points` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `points_redeemed` int(11) NOT NULL DEFAULT 0,
  `points_expired` int(11) NOT NULL DEFAULT 0,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_points`
--

INSERT INTO `loyalty_points` (`id`, `user_id`, `points`, `points_earned`, `points_redeemed`, `points_expired`, `last_activity`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 0, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(2, 2, 0, 0, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(3, 3, 0, 0, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(4, 4, 0, 0, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(5, 5, 0, 0, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(6, 6, 0, 0, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49', '2025-10-12 22:03:49');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('earned','redeemed','expired','adjusted') NOT NULL,
  `points` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference_type` enum('purchase','referral','review','signup','admin_adjustment','redemption') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_transactions`
--

INSERT INTO `loyalty_transactions` (`id`, `user_id`, `transaction_type`, `points`, `description`, `reference_type`, `reference_id`, `expires_at`, `created_at`) VALUES
(1, 2, 'earned', 250, 'Purchase bonus for order #18', 'purchase', 18, NULL, '2025-10-30 21:00:27'),
(2, 2, 'earned', 20, 'Purchase bonus for order #19', 'purchase', 19, NULL, '2025-10-30 21:03:34');

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_transactions`
--

CREATE TABLE `mpesa_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `merchant_request_id` varchar(100) DEFAULT NULL,
  `checkout_request_id` varchar(100) DEFAULT NULL,
  `response_code` varchar(10) DEFAULT NULL,
  `response_description` text DEFAULT NULL,
  `result_code` varchar(10) DEFAULT NULL,
  `result_description` text DEFAULT NULL,
  `mpesa_receipt_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mpesa_transactions`
--

INSERT INTO `mpesa_transactions` (`id`, `order_id`, `phone_number`, `amount`, `merchant_request_id`, `checkout_request_id`, `response_code`, `response_description`, `result_code`, `result_description`, `mpesa_receipt_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 18, '254708374149', 2500.00, NULL, NULL, '400.002.02', 'Bad Request - Invalid CallBackURL', NULL, NULL, NULL, 'pending', '2025-10-30 21:01:59', '2025-10-30 21:01:59'),
(2, 19, '254743394373', 200.00, NULL, NULL, '400.002.02', 'Bad Request - Invalid CallBackURL', NULL, NULL, NULL, 'pending', '2025-10-30 21:03:43', '2025-10-30 21:03:43');

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
(10, 2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', '0743394373', 'Arina', 800.00, 'completed', '', 'cash', 'paid', 'completed', '2025-09-20 07:26:50', '2025-09-20'),
(11, 2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', '0788665438', 'Arina', 1500.00, 'completed', '', 'cash', 'paid', 'completed', '2025-09-21 13:54:20', '2025-09-21'),
(12, 6, 'Alice Atieno', 'alice@gmail.com', '0757744395', '117\r\n61', 1500.00, 'completed', '', 'cash', 'paid', 'completed', '2025-10-11 11:17:03', '2025-10-11'),
(13, 6, 'Alice Atieno', 'alice@gmail.com', '0757744395', '117\r\n61', 200.00, 'completed', '', 'cash', 'paid', 'completed', '2025-10-12 10:03:08', '2025-10-12'),
(18, 2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', '0708374149', 'seme', 2500.00, 'pending', '', 'mpesa', 'pending', 'pending', '2025-10-30 21:00:27', '2025-10-31'),
(19, 2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', '0743394373', 'kisumu', 200.00, 'pending', '', 'mpesa', 'pending', 'pending', '2025-10-30 21:03:34', '2025-10-31');

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
(10, 10, 2, NULL, 4, 200.00, 800.00),
(11, 11, 11, NULL, 1, 1500.00, 1500.00),
(12, 12, 11, NULL, 1, 1500.00, 1500.00),
(13, 13, 3, NULL, 1, 200.00, 200.00),
(18, 18, 23, NULL, 1, 2500.00, 2500.00),
(19, 19, 3, NULL, 1, 200.00, 200.00);

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
  `sku` varchar(50) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00 COMMENT 'Discount percentage (0-100)',
  `discount_start_date` datetime DEFAULT NULL COMMENT 'Start date for discount',
  `discount_end_date` datetime DEFAULT NULL COMMENT 'End date for discount',
  `is_discounted` tinyint(1) DEFAULT 0 COMMENT 'Whether product is currently discounted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `color`, `name`, `description`, `sku`, `price`, `stock`, `status`, `created_at`, `image`, `image_url`, `discount_percentage`, `discount_start_date`, `discount_end_date`, `is_discounted`) VALUES
(1, 1, NULL, 'Hand bags', 'Medium', '', 400.00, 20, 'active', '2025-09-07 13:23:24', NULL, NULL, 27.00, '2025-10-12 16:00:00', '2025-10-13 16:00:00', 1),
(2, 3, NULL, 'Table Mats', 'Nice Woven Mats', '', 200.00, 25, 'active', '2025-09-08 17:42:26', NULL, NULL, 0.00, NULL, NULL, 0),
(3, 5, NULL, 'Necklace', 'Small size', '', 200.00, 10, 'active', '2025-09-09 11:38:12', NULL, NULL, 0.00, NULL, NULL, 0),
(4, 1, NULL, 'Ladies Bags', 'Small', '', 500.00, 9, 'active', '2025-09-09 11:40:28', NULL, NULL, 0.00, NULL, NULL, 0),
(5, 6, NULL, 'Waiste beads', 'small, Medium and Large', '', 200.00, 99, 'active', '2025-09-15 20:07:30', NULL, NULL, 0.00, NULL, NULL, 0),
(6, 3, NULL, 'Table Liner', 'Long authentic table liner', '', 400.00, 2, 'active', '2025-09-19 11:55:49', NULL, NULL, 0.00, NULL, NULL, 0),
(9, 7, NULL, 'Men Shirt', 'Long Sleeved Shirt', 'S-001', 2500.00, 14, 'active', '2025-09-20 08:54:30', NULL, NULL, 7.00, '2025-10-12 07:00:00', '2025-10-13 07:00:00', 1),
(11, 7, NULL, 'Men Shirt', 'Short Sleeved Shirts', 'PROD-000011', 1500.00, 11, 'active', '2025-09-21 08:48:48', NULL, NULL, 10.00, '2025-10-12 06:00:00', '2025-10-13 06:00:00', 1),
(23, 10, NULL, 'Nike', 'Nike Air', 'N-009', 2500.00, 55, 'active', '2025-10-12 13:27:14', NULL, NULL, 5.00, '2025-10-12 06:00:00', '2025-10-13 17:00:00', 1);

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
(8, 5, NULL, 0, '68cd13f1d07b1_IMG_20250915_142135.jpg', 0),
(9, 4, NULL, 0, '68cd18318e097_IMG_20250915_120758.jpg', 0),
(10, 2, NULL, 0, '68cd1857e4305_IMG_20250917_121714.jpg', 0),
(11, 2, NULL, 0, '68cd1857e5e20_IMG_20250917_121751.jpg', 0),
(12, 2, NULL, 0, '68cd1857e7bab_IMG_20250917_122142.jpg', 0),
(13, 6, NULL, 0, '68cd44c577c1c_IMG_20250917_122053.jpg', 0),
(14, 1, NULL, 0, '68cd521cae7e8_IMG_20250915_120758.jpg', 0),
(15, 1, NULL, 0, '68cd521cb02b3_IMG_20250915_120959.jpg', 0),
(16, 1, NULL, 0, '68cd521cb1e7f_IMG_20250915_121231.jpg', 0),
(17, 1, NULL, 0, '68cd521cb3ae8_IMG_20250915_121305.jpg', 0),
(18, 9, NULL, 0, '68ce6bc6774ba_IMG_20250916_140315.png', 0),
(19, 11, NULL, 0, '68cfbbf0aa2eb_IMG_20250916_124104.png', 0),
(21, 23, NULL, 0, 'prod_68ebacb2cfe010.39651247.jpeg', 0);

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
(3, 6, 'Color', 'Green', 1, 'assets/variants/68cd44ef6ee39_IMG_20250917_121559.jpg'),
(4, 9, 'Color', 'Blue', 2, '<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/opt/lampp/htdocs/E-Commerce/admin/edit_product.php</b> on line <b>335</b><br />\r\n');

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
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `referred_id` int(11) NOT NULL,
  `referral_code` varchar(20) NOT NULL,
  `status` enum('pending','completed','expired') DEFAULT 'pending',
  `points_awarded` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `refund_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('original_payment','store_credit','bank_transfer','mobile_money') NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `return_number` varchar(50) NOT NULL,
  `status` enum('pending','approved','rejected','processing','completed','cancelled') DEFAULT 'pending',
  `reason` enum('defective','wrong_item','not_as_described','changed_mind','damaged_shipping','other') NOT NULL,
  `description` text NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_items`
--

CREATE TABLE `return_items` (
  `id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `item_condition` enum('new','used','damaged','defective') NOT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 2, 4, 4, 'Woow!... the bag is so nice. I highly recommend', '2025-09-11 22:22:31'),
(3, 2, 2, 4, 'The product is just wow.. I love it', '2025-09-20 09:01:33'),
(4, 6, 11, 5, 'The delivery was quick. The shirt is really amazing. I love it', '2025-10-11 11:46:13');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `key` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('text','textarea','email','number','color','image','url') NOT NULL DEFAULT 'text',
  `label` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`key`, `value`, `type`, `label`, `description`, `category`, `updated_at`) VALUES
('business_hours', 'Mon-Fri: 9AM-6PM', 'text', 'Business Hours', 'Your operating hours', 'contact', '2025-09-21 17:38:45'),
('company_address', 'GrooveHut Appartments, Opposite K-City. Kisumu', 'textarea', 'Company Address', 'Your physical business address', 'contact', '2025-09-21 19:37:17'),
('company_name', 'Springs Community Online Shop', 'text', 'Company Name', 'Your registered business name', 'contact', '2025-09-21 20:04:34'),
('contact_email', 'contact@example.com', 'email', 'Contact Email', 'Primary contact email address', 'contact', '2025-09-21 17:38:45'),
('contact_form_email', '', 'email', 'Contact Form Email', 'Email where contact form submissions are sent', 'contact', '2025-09-21 17:38:45'),
('contact_success_msg', 'Thank you for your message. We will get back to you soon!', 'textarea', 'Contact Success Message', 'Message shown after successful contact form submission', 'contact', '2025-09-21 17:38:45'),
('copyright_text', '© 2025 Your Company. All rights reserved.', 'text', 'Copyright Text', 'Copyright notice in footer', 'footer', '2025-09-21 17:38:45'),
('facebook_url', 'https://web.facebook.com/margaret.auma.7', 'url', 'Facebook URL', 'Your Facebook page URL', 'social', '2025-09-21 19:34:56'),
('footer_about', 'We are a passionate team dedicated to bringing you quality products at affordable prices. Our mission is to make online shopping easy, secure, and enjoyable — connecting you with the best fashion, accessories, and lifestyle items right from the comfort of your home.', 'textarea', 'Footer About Text', 'Short description shown in footer', 'footer', '2025-09-21 20:03:59'),
('google_analytics_id', '', 'text', 'Google Analytics ID', 'Your Google Analytics tracking ID', 'analytics', '2025-09-21 17:38:45'),
('instagram_url', '', 'url', 'Instagram URL', 'Your Instagram profile URL', 'social', '2025-09-21 17:38:45'),
('linkedin_url', '', 'url', 'LinkedIn URL', 'Your LinkedIn page URL', 'social', '2025-09-21 17:38:45'),
('meta_robots', 'index, follow, springs, necklaces, groovehut, K-City', 'text', 'Meta Robots', 'Default robots meta tag content', 'seo', '2025-09-21 19:41:54'),
('phone_number', '+1234567890', 'text', 'Phone Number', 'Primary contact phone number', 'contact', '2025-09-21 17:38:45'),
('site_description', 'Your one-stop shop for quality products', 'textarea', 'Site Description', 'A brief description of your website', 'general', '2025-09-21 17:38:45'),
('site_favicon', 'assets/images/favicon.ico', 'image', 'Site Favicon', 'Your website favicon', 'general', '2025-09-21 17:38:45'),
('site_keywords', 'ecommerce, online shopping, products', 'textarea', 'Site Keywords', 'SEO keywords (comma-separated)', 'general', '2025-09-21 17:38:45'),
('site_logo', 'assets/images/logo.png', 'image', 'Site Logo', 'Your website logo', 'general', '2025-09-21 17:38:45'),
('site_title', 'Springs Community Online Shop', 'text', 'Site Title', 'The name of your website', 'general', '2025-09-21 19:39:36'),
('twitter_url', '', 'url', 'Twitter URL', 'Your Twitter profile URL', 'social', '2025-09-21 17:38:45');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `loyalty_points` int(11) DEFAULT 0,
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `total_referrals` int(11) DEFAULT 0,
  `total_points_earned` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `default_address`, `password`, `role`, `created_at`, `loyalty_points`, `referral_code`, `referred_by`, `total_referrals`, `total_points_earned`) VALUES
(1, 'Admin User', 'admin@gmail.com', '0700000000', NULL, '$2y$10$PECkZzpos3LSx/TcLVdSWe4IWur1l7V9Q4dkmDzfYSsYhr51kidIq', 'admin', '2025-09-07 12:59:21', 0, NULL, NULL, 0, 0),
(2, 'Kennedy Otieno', 'kennyleyy0@gmail.com', NULL, NULL, '$2y$10$x21iPnDsai79n.eF5W7ZBePdxAz9oAkFOjWEbXfdQ2gaMzNeCOcua', 'customer', '2025-09-07 15:08:45', 0, NULL, NULL, 0, 0),
(3, 'Jane Atieno', 'jane@beauty.com', '0786543452', 'Kondele', '$2y$10$686x8gHGtTstpXv3uMRj/.7BGVJKLNZcnLVTgRkYE3c85QmMxYflm', 'customer', '2025-09-09 11:56:18', 0, NULL, NULL, 0, 0),
(4, 'mercy johnson', 'mercy@gmail.com', '0789876545', 'CBD', '$2y$10$0e76zM2C9mbyFHd1bM1Dj.SuRzXOHKyP.J/O1SPJSnnyt0MrbJlly', 'customer', '2025-09-09 22:01:23', 0, NULL, NULL, 0, 0),
(5, 'Martha Atieno', 'martha@gmail.com', '0717468794', 'Akala', '$2y$10$fUIuBdIJoGnLLmP4TFAK9.nzksmOM.FiiW7FE.K.YFV8GeiCrCpMq', 'customer', '2025-09-11 22:48:03', 0, NULL, NULL, 0, 0),
(6, 'Alice Atieno', 'alice@gmail.com', '0757744395', '117\r\n61', '$2y$10$qiWmvtqNKlzu3R/8/IO13uQNki1/5BD9jM66kdWy0sIUw5nLw0kha', 'customer', '2025-10-11 11:14:31', 0, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('home','work','billing','shipping','other') DEFAULT 'home',
  `is_default` tinyint(1) DEFAULT 0,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_line_1` varchar(255) NOT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Kenya',
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `delivery_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_referral_codes`
--

CREATE TABLE `user_referral_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `referral_code` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `total_referrals` int(11) DEFAULT 0,
  `total_points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_referral_codes`
--

INSERT INTO `user_referral_codes` (`id`, `user_id`, `referral_code`, `is_active`, `total_referrals`, `total_points_earned`, `created_at`, `updated_at`) VALUES
(1, 1, 'REF000001', 1, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(2, 2, 'REF000002', 1, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(3, 3, 'REF000003', 1, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(4, 4, 'REF000004', 1, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(5, 5, 'REF000005', 1, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49'),
(6, 6, 'REF000006', 1, 0, 0, '2025-10-12 22:03:49', '2025-10-12 22:03:49');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(1, 6, 11, '2025-10-12 07:01:08'),
(2, 6, 1, '2025-10-12 09:55:36');

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
-- Indexes for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_points` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_points` (`points`);

--
-- Indexes for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `checkout_request_id` (`checkout_request_id`),
  ADD KEY `mpesa_receipt_number` (`mpesa_receipt_number`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_checkout_request` (`checkout_request_id`),
  ADD KEY `idx_order_status` (`order_id`,`status`);

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
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_discount_active` (`is_discounted`,`discount_end_date`);

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
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD UNIQUE KEY `unique_referral` (`referrer_id`,`referred_id`),
  ADD KEY `idx_referrer_id` (`referrer_id`),
  ADD KEY `idx_referred_id` (`referred_id`),
  ADD KEY `idx_referral_code` (`referral_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `refund_number` (`refund_number`),
  ADD KEY `idx_return_id` (`return_id`),
  ADD KEY `idx_refund_number` (`refund_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_number` (`return_number`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_return_number` (`return_number`);

--
-- Indexes for table `return_items`
--
ALTER TABLE `return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_return_id` (`return_id`),
  ADD KEY `idx_order_item_id` (`order_item_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD KEY `idx_loyalty_points` (`loyalty_points`),
  ADD KEY `idx_referral_code` (`referral_code`),
  ADD KEY `idx_referred_by` (`referred_by`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_address_type` (`address_type`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- Indexes for table `user_referral_codes`
--
ALTER TABLE `user_referral_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD UNIQUE KEY `unique_user_code` (`user_id`),
  ADD KEY `idx_referral_code` (`referral_code`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist_item` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order_history`
--
ALTER TABLE `order_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `promo_tiles`
--
ALTER TABLE `promo_tiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_items`
--
ALTER TABLE `return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_referral_codes`
--
ALTER TABLE `user_referral_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD CONSTRAINT `loyalty_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD CONSTRAINT `loyalty_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  ADD CONSTRAINT `mpesa_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `return_items`
--
ALTER TABLE `return_items`
  ADD CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_referred_by` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_referral_codes`
--
ALTER TABLE `user_referral_codes`
  ADD CONSTRAINT `user_referral_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
