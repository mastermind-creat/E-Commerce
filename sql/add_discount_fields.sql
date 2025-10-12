-- Add discount fields to products table
ALTER TABLE products 
ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Discount percentage (0-100)',
ADD COLUMN discount_start_date DATETIME NULL COMMENT 'Start date for discount',
ADD COLUMN discount_end_date DATETIME NULL COMMENT 'End date for discount',
ADD COLUMN is_discounted BOOLEAN DEFAULT FALSE COMMENT 'Whether product is currently discounted';

-- Add index for discount queries
CREATE INDEX idx_discount_active ON products (is_discounted, discount_end_date);
