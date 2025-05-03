<?php

/**
 * Database Migration Test Script for Bloom Bouquet
 * 
 * This script tests the new consolidated migration structure by:
 * 1. Creating a temporary SQLite database
 * 2. Running the migrations against it
 * 3. Checking that all tables were created correctly
 * 
 * Usage: php database_migration_test.php
 */

echo "🧪 Bloom Bouquet Migration Test 🧪\n";
echo "================================\n\n";

// Create a temporary environment file for testing
$envContent = <<<EOT
APP_NAME=BloomBouquet
APP_ENV=testing
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

BROADCAST_DRIVER=log
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
EOT;

file_put_contents(__DIR__ . '/.env.testing', $envContent);

// Run the migration test
echo "Running migrations test with SQLite in-memory database...\n\n";
$output = [];
exec('php artisan migrate:fresh --env=testing --no-interaction', $output, $return_code);

foreach ($output as $line) {
    echo $line . "\n";
}

if ($return_code !== 0) {
    echo "\n❌ Migration test failed! Error code: $return_code\n";
    unlink(__DIR__ . '/.env.testing');
    exit(1);
}

// Verify tables were created
echo "\nVerifying table structure...\n";

// Run the verification
$tables = [];
exec('php artisan db:table --env=testing', $tables, $return_code);

if ($return_code !== 0) {
    echo "\n❌ Table verification failed! Error code: $return_code\n";
    unlink(__DIR__ . '/.env.testing');
    exit(1);
}

// Check for expected tables
$expectedTables = [
    'users', 'categories', 'products', 'admins', 'carousels',  // Core schema
    'orders', 'order_items', 'favorites', 'product_reviews', 'delivery_tracking', 'carts',  // E-commerce schema
    'chats', 'reports',  // Communication schema
    'jobs', 'migrations', 'failed_jobs', 'cache'  // Laravel system tables
];

$missingTables = [];
$foundTables = [];

foreach ($tables as $line) {
    $line = trim($line);
    if (in_array($line, $expectedTables)) {
        $foundTables[] = $line;
    }
}

foreach ($expectedTables as $table) {
    if (!in_array($table, $foundTables)) {
        $missingTables[] = $table;
    }
}

// Report results
if (count($missingTables) > 0) {
    echo "\n❌ Migration test failed! The following tables are missing:\n";
    foreach ($missingTables as $table) {
        echo "  - $table\n";
    }
} else {
    echo "\n✅ Migration test successful! All expected tables were created.\n";
}

// Clean up
unlink(__DIR__ . '/.env.testing');

echo "\nTest completed.\n";
exit(count($missingTables) > 0 ? 1 : 0); 