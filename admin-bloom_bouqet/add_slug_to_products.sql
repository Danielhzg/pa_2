-- Add the slug column to the products table if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS slug VARCHAR(255) AFTER name;

-- Index the column for faster lookups
CREATE UNIQUE INDEX IF NOT EXISTS products_slug_unique ON products (slug);

-- Run the following update command manually after executing this script:
-- This will update all products to have slugs based on their name
-- UPDATE products SET slug = LOWER(REPLACE(name, ' ', '-')) WHERE slug IS NULL;
-- 
-- Note: This basic update won't handle duplicate slugs.
-- For more complex slug generation with handling of duplicates,
-- use the Artisan command: php artisan products:add-slug 