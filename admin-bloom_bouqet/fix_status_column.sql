-- Fix status column in orders table
ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'waiting_for_payment';

-- Fix payment_status column in orders table
ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending';

-- Update any NULL values
UPDATE orders SET status = 'waiting_for_payment' WHERE status IS NULL;
UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL;

-- Verify changes
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM 
    INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = DATABASE() AND 
    TABLE_NAME = 'orders' AND 
    (COLUMN_NAME = 'status' OR COLUMN_NAME = 'payment_status'); 