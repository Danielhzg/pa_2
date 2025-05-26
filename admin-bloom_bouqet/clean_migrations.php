<?php

/**
 * Clean Migrations Script
 * 
 * This script removes unnecessary fix migrations from the migrations table,
 * allowing for a clean migration history.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

echo "Starting migration cleanup...\n";

// Step 1: Check current migration status
echo "Current migration status:\n";
$migrations = DB::table('migrations')->get();
foreach ($migrations as $migration) {
    echo "- {$migration->migration}\n";
}
echo "\n";

// Step 2: Identify migrations to remove
$migrationsToRemove = [
    'fix_orders_json_column',
    '2023_10_02_000001_fix_order_items_json_column'
];

echo "Migrations to remove:\n";
foreach ($migrationsToRemove as $migration) {
    echo "- {$migration}\n";
}
echo "\n";

// Step 3: Remove the migrations from the database
echo "Removing migrations from database...\n";
foreach ($migrationsToRemove as $migration) {
    $count = DB::table('migrations')->where('migration', $migration)->delete();
    echo "- {$migration}: " . ($count ? "Removed" : "Not found") . "\n";
}
echo "\n";

// Step 4: Delete the migration files
echo "Removing migration files...\n";
$filesToRemove = [
    __DIR__ . '/database/migrations/fix_orders_json_column.php',
    __DIR__ . '/database/migrations/2023_10_02_000001_fix_order_items_json_column.php'
];

foreach ($filesToRemove as $file) {
    if (File::exists($file)) {
        File::delete($file);
        echo "- " . basename($file) . ": Deleted\n";
    } else {
        echo "- " . basename($file) . ": Not found\n";
    }
}
echo "\n";

// Step 5: Verify the orders table has the correct structure
echo "Verifying orders table structure:\n";
if (Schema::hasTable('orders')) {
    $hasJsonColumn = false;
    
    // Get the column type using raw query since Laravel doesn't expose this information easily
    $dbName = DB::connection()->getDatabaseName();
    $columnInfo = DB::select("
        SELECT DATA_TYPE, COLUMN_TYPE, COLUMN_DEFAULT
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = '{$dbName}'
            AND TABLE_NAME = 'orders'
            AND COLUMN_NAME = 'order_items'
    ");
    
    if (!empty($columnInfo)) {
        echo "order_items column type: " . $columnInfo[0]->DATA_TYPE . "\n";
        
        // Check if MySQL treats it as JSON (even if stored as longtext)
        try {
            $testJson = '{"test": "value"}';
            DB::statement("SELECT JSON_VALID('{$testJson}') as valid");
            echo "JSON validation supported by database.\n";
            
            // Try a simple JSON operation on the column
            $result = DB::select("SELECT COUNT(*) as count FROM orders WHERE JSON_VALID(order_items)");
            echo "Orders with valid JSON in order_items: " . $result[0]->count . "\n";
            $hasJsonColumn = true;
        } catch (Exception $e) {
            echo "Error testing JSON functionality: " . $e->getMessage() . "\n";
        }
    } else {
        echo "order_items column not found in orders table.\n";
    }
    
    if ($hasJsonColumn) {
        echo "✓ orders.order_items appears to be functioning as JSON\n";
    } else {
        echo "✗ orders.order_items has issues\n";
    }
} else {
    echo "orders table not found.\n";
}

echo "\nMigration cleanup completed!\n";
echo "You can now run 'php artisan migrate:status' to verify the clean migration list.\n"; 