-- SQL script to synchronize existing products table with the updated schema
-- Note: Run this script if you have issues with the database structure

-- Check if products table exists, if not, exit
SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'products';

-- Add missing columns if they don't exist
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS `image` VARCHAR(255) NULL AFTER `price`,
    ADD COLUMN IF NOT EXISTS `images` TEXT NULL,
    ADD COLUMN IF NOT EXISTS `admin_id` BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `is_on_sale` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `discount` INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `rating` FLOAT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `total_reviews` INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `reviews` TEXT NULL,
    ADD COLUMN IF NOT EXISTS `featured_until` TIMESTAMP NULL;

-- Add foreign key for admin_id if it doesn't exist
-- (First check if admin_id column exists and constraint doesn't exist)
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'products'
    AND CONSTRAINT_NAME = 'products_admin_id_foreign'
);

SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'admin_id'
);

-- Only add constraint if column exists and constraint doesn't
SET @sql = IF(@column_exists > 0 AND @constraint_exists = 0,
    'ALTER TABLE products ADD CONSTRAINT products_admin_id_foreign FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make sure the database engine is InnoDB
ALTER TABLE products ENGINE = InnoDB; 