-- Add images JSON column if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS images JSON AFTER price;

-- No need to drop the image column yet for safety
-- After confirming everything works, you can run:
-- ALTER TABLE products DROP COLUMN image; 