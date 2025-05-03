# Database Migrations for Bloom Bouquet

This directory contains the database migrations for the Bloom Bouquet e-commerce application.

## Migration Structure (September 2024 Reorganization)

The migration system has been completely reorganized and consolidated to improve clarity and maintainability. Instead of many small migration files, we now have a few larger, well-organized migrations grouped by functionality.

### Core System Migrations

1. **Base Laravel Tables**: System tables for Laravel functionality
   - `0001_01_01_000001_create_cache_table.php`
   - `0001_01_01_000002_create_jobs_table.php`
   - `2025_03_15_062913_create_personal_access_tokens_table.php`

2. **Consolidated Core Schema**: Core system entities
   - `2024_09_01_000000_create_core_schema.php`
     - Users
     - Categories
     - Products
     - Admins
     - Carousels

### Business Logic Migrations

3. **E-commerce Schema**: All e-commerce related tables
   - `2024_09_01_000001_create_ecommerce_schema.php`
     - Orders
     - Order Items
     - Favorites
     - Product Reviews
     - Delivery Tracking
     - Carts

4. **Communication Schema**: Communication and reporting
   - `2024_09_01_000002_create_communication_schema.php`
     - Chats
     - Reports

## Migration Benefits

This new structure provides several benefits:

1. **Clarity**: Tables are grouped logically by domain
2. **Dependencies**: Tables that depend on each other are created in the correct order
3. **Consistency**: Table structure and relationships follow a consistent pattern
4. **Maintainability**: Easier to understand the overall database structure
5. **Rollbacks**: Each logical group can be rolled back together

## Running Migrations

```bash
# Check migration status
php artisan migrate:status

# Run all pending migrations
php artisan migrate

# Refresh all migrations (drops all tables and re-runs migrations)
php artisan migrate:refresh

# Rollback the last batch of migrations
php artisan migrate:rollback
```

## Migration Best Practices

When creating new migrations:

1. **Add to existing groups**: Instead of creating new files, consider adding to the appropriate schema file
2. **Maintain dependencies**: Make sure tables are created in the correct order
3. **Document changes**: Always document significant schema changes
4. **Include proper indexes**: Add indexes for fields used in WHERE clauses
5. **Use explicit foreign keys**: Always define relationships explicitly 