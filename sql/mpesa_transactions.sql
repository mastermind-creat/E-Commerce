-- M-Pesa Transactions Table
-- This table stores all M-Pesa payment transactions

CREATE TABLE IF NOT EXISTS `mpesa_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `checkout_request_id` (`checkout_request_id`),
  KEY `mpesa_receipt_number` (`mpesa_receipt_number`),
  KEY `status` (`status`),
  CONSTRAINT `mpesa_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for faster lookups
CREATE INDEX idx_checkout_request ON mpesa_transactions(checkout_request_id);
CREATE INDEX idx_order_status ON mpesa_transactions(order_id, status);
