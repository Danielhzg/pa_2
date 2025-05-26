<?php

/**
 * Verify Migrations Script
 * 
 * This script checks that migrations can run successfully and database structure is correct.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Starting migration verification...\n";

try {
    // Step 1: Check if Laravel is running migrations
    echo "Checking migration status...\n";
    $migrations = DB::table('migrations')->get();
    echo "Found " . count($migrations) . " migrations in the database.\n\n";
    
    // Step 2: Check that key tables exist with correct structure
    echo "Verifying key tables exist:\n";
    
    $tables = [
        'users', 'admins', 'products', 'categories', 
        'orders', 'carts', 'favorites', 'carousels', 
        'reports', 'chat_messages'
    ];
    
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            echo "✓ {$table} table exists\n";
        } else {
            echo "✗ {$table} table missing\n";
        }
    }
    
    echo "\n";
    
    // Step 3: Verify foreign key relationships
    echo "Checking foreign key relationships:\n";
    
    $relationships = [
        ['carts', 'user_id', 'users', 'id'],
        ['carts', 'product_id', 'products', 'id'],
        ['carts', 'order_id', 'orders', 'id'],
        ['favorites', 'user_id', 'users', 'id'],
        ['favorites', 'product_id', 'products', 'id'],
        ['orders', 'user_id', 'users', 'id'],
        ['orders', 'admin_id', 'admins', 'id'],
        ['carousels', 'admin_id', 'admins', 'id'],
        ['reports', 'admin_id', 'admins', 'id'],
        ['reports', 'order_id', 'orders', 'id'],
        ['chat_messages', 'user_id', 'users', 'id'],
        ['chat_messages', 'admin_id', 'admins', 'id']
    ];
    
    // Get database name
    $dbName = DB::connection()->getDatabaseName();
    
    foreach ($relationships as $rel) {
        list($table, $column, $refTable, $refColumn) = $rel;
        
        $constraint = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = '{$dbName}'
                AND TABLE_NAME = '{$table}'
                AND COLUMN_NAME = '{$column}'
                AND REFERENCED_TABLE_NAME = '{$refTable}'
                AND REFERENCED_COLUMN_NAME = '{$refColumn}'
        ");
        
        if (!empty($constraint)) {
            echo "✓ {$table}.{$column} references {$refTable}.{$refColumn}\n";
        } else {
            echo "✗ Missing foreign key: {$table}.{$column} -> {$refTable}.{$refColumn}\n";
        }
    }
    
    echo "\n";
    
    // Step 4: Verify column types
    echo "Checking column types for foreign keys:\n";
    
    $columns = [
        ['carts', 'user_id', 'bigint'],
        ['carts', 'product_id', 'bigint'],
        ['carts', 'order_id', 'bigint'],
        ['favorites', 'user_id', 'bigint'],
        ['favorites', 'product_id', 'bigint'],
        ['orders', 'user_id', 'bigint'],
        ['orders', 'admin_id', 'bigint'],
        ['carousels', 'admin_id', 'bigint'],
        ['reports', 'admin_id', 'bigint'],
        ['reports', 'order_id', 'bigint'],
        ['chat_messages', 'user_id', 'bigint'],
        ['chat_messages', 'admin_id', 'bigint']
    ];
    
    foreach ($columns as $col) {
        list($table, $column, $expectedType) = $col;
        
        $columnInfo = DB::select("
            SELECT DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = '{$dbName}'
                AND TABLE_NAME = '{$table}'
                AND COLUMN_NAME = '{$column}'
        ");
        
        if (!empty($columnInfo) && strpos(strtolower($columnInfo[0]->DATA_TYPE), $expectedType) !== false) {
            echo "✓ {$table}.{$column} has correct type: {$columnInfo[0]->DATA_TYPE}\n";
        } else if (!empty($columnInfo)) {
            echo "✗ {$table}.{$column} has incorrect type: {$columnInfo[0]->DATA_TYPE} (expected {$expectedType})\n";
        } else {
            echo "✗ Column {$table}.{$column} not found\n";
        }
    }
    
    echo "\n";
    
    // Step 5: Check if orders.order_items exists and functions as JSON
    echo "Checking orders.order_items JSON functionality:\n";
    
    if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'order_items')) {
        // MySQL internally represents JSON as longtext with JSON checks
        // We'll validate this by trying to insert and query JSON data
        
        try {
            // Test JSON functionality by inserting a simple JSON array
            $testOrderId = DB::table('orders')->insertGetId([
                'order_id' => 'TEST-' . uniqid(),
                'phone_number' => '123456789',
                'shipping_address' => 'Test Address',
                'payment_method' => 'test',
                'subtotal' => 100,
                'shipping_cost' => 10,
                'total_amount' => 110,
                'order_items' => json_encode([
                    ['product_id' => 1, 'quantity' => 1, 'price' => 100]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Try to query JSON data
            $testData = DB::table('orders')
                ->select('order_items')
                ->where('id', $testOrderId)
                ->first();
                
            // Check if the returned data is valid JSON
            $decodedItems = json_decode($testData->order_items);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedItems)) {
                echo "✓ orders.order_items functions correctly as JSON\n";
                
                // Clean up test data
                DB::table('orders')->where('id', $testOrderId)->delete();
            } else {
                echo "✗ orders.order_items does not function correctly as JSON\n";
            }
        } catch (Exception $e) {
            echo "✗ orders.order_items test failed: {$e->getMessage()}\n";
        }
    } else {
        echo "✗ orders.order_items column not found\n";
    }
    
    echo "\nMigration verification completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 