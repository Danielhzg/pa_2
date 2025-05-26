<?php

/**
 * Fix Migrations Script
 * 
 * This script helps fix migration issues by:
 * 1. Ensuring correct column types for foreign keys
 * 2. Fixing order of migrations
 * 3. Cleaning up any potential conflicts
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Starting migration fixes...\n";

try {
    // Step 1: Reset migration table if needed
    echo "Would you like to reset the migrations table? (This doesn't drop your tables) [y/N]: ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim(strtolower($line)) === 'y') {
        echo "Resetting migrations table...\n";
        Schema::dropIfExists('migrations');
        
        // Recreate migrations table
        Schema::create('migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
        
        echo "Migrations table reset successfully.\n";
    }
    
    // Step 2: Fix column types for tables that may already exist
    if (Schema::hasTable('favorites')) {
        echo "Checking favorites table column types...\n";
        $hasMismatch = false;
        
        // Check user_id type
        $userIdColumn = DB::select("
            SELECT DATA_TYPE 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'favorites' 
            AND COLUMN_NAME = 'user_id'
        ");
        
        if ($userIdColumn && strpos(strtolower($userIdColumn[0]->DATA_TYPE), 'bigint') === false) {
            $hasMismatch = true;
            echo "  - favorites.user_id has incorrect type: {$userIdColumn[0]->DATA_TYPE}\n";
        }
        
        // Check product_id type
        $productIdColumn = DB::select("
            SELECT DATA_TYPE 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'favorites' 
            AND COLUMN_NAME = 'product_id'
        ");
        
        if ($productIdColumn && strpos(strtolower($productIdColumn[0]->DATA_TYPE), 'bigint') === false) {
            $hasMismatch = true;
            echo "  - favorites.product_id has incorrect type: {$productIdColumn[0]->DATA_TYPE}\n";
        }
        
        if ($hasMismatch) {
            echo "Would you like to drop and recreate the favorites table? [y/N]: ";
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                echo "Dropping favorites table...\n";
                Schema::dropIfExists('favorites');
                echo "Favorites table dropped. It will be recreated during migration.\n";
            }
        } else {
            echo "  - Favorites table column types are correct.\n";
        }
    }
    
    // Similar checks for carts and carousels
    if (Schema::hasTable('carts')) {
        echo "Checking carts table column types...\n";
        $hasMismatch = false;
        
        $columnChecks = [
            'user_id' => 'bigint',
            'product_id' => 'bigint',
            'order_id' => 'bigint',
        ];
        
        foreach ($columnChecks as $column => $expectedType) {
            $columnInfo = DB::select("
                SELECT DATA_TYPE 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'carts' 
                AND COLUMN_NAME = '{$column}'
            ");
            
            if ($columnInfo && strpos(strtolower($columnInfo[0]->DATA_TYPE), $expectedType) === false) {
                $hasMismatch = true;
                echo "  - carts.{$column} has incorrect type: {$columnInfo[0]->DATA_TYPE}\n";
            }
        }
        
        if ($hasMismatch) {
            echo "Would you like to drop and recreate the carts table? [y/N]: ";
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                echo "Dropping carts table...\n";
                Schema::dropIfExists('carts');
                echo "Carts table dropped. It will be recreated during migration.\n";
            }
        } else {
            echo "  - Carts table column types are correct.\n";
        }
    }
    
    if (Schema::hasTable('carousels')) {
        echo "Checking carousels table column types...\n";
        $hasMismatch = false;
        
        $adminIdColumn = DB::select("
            SELECT DATA_TYPE 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'carousels' 
            AND COLUMN_NAME = 'admin_id'
        ");
        
        if ($adminIdColumn && strpos(strtolower($adminIdColumn[0]->DATA_TYPE), 'bigint') === false) {
            $hasMismatch = true;
            echo "  - carousels.admin_id has incorrect type: {$adminIdColumn[0]->DATA_TYPE}\n";
        }
        
        if ($hasMismatch) {
            echo "Would you like to drop and recreate the carousels table? [y/N]: ";
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                echo "Dropping carousels table...\n";
                Schema::dropIfExists('carousels');
                echo "Carousels table dropped. It will be recreated during migration.\n";
            }
        } else {
            echo "  - Carousels table column types are correct.\n";
        }
    }
    
    // After the carousels table check, add a check for the reports table
    if (Schema::hasTable('reports')) {
        echo "Checking reports table column types...\n";
        $hasMismatch = false;
        
        $columnChecks = [
            'admin_id' => 'bigint',
            'order_id' => 'bigint'
        ];
        
        foreach ($columnChecks as $column => $expectedType) {
            $columnInfo = DB::select("
                SELECT DATA_TYPE 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'reports' 
                AND COLUMN_NAME = '{$column}'
            ");
            
            if ($columnInfo && strpos(strtolower($columnInfo[0]->DATA_TYPE), $expectedType) === false) {
                $hasMismatch = true;
                echo "  - reports.{$column} has incorrect type: {$columnInfo[0]->DATA_TYPE}\n";
            }
        }
        
        if ($hasMismatch) {
            echo "Would you like to drop and recreate the reports table? [y/N]: ";
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                echo "Dropping reports table...\n";
                Schema::dropIfExists('reports');
                echo "Reports table dropped. It will be recreated during migration.\n";
            }
        } else {
            echo "  - Reports table column types are correct.\n";
        }
    }
    
    // After the reports table check, add a check for the chat_messages table
    if (Schema::hasTable('chat_messages')) {
        echo "Checking chat_messages table column types...\n";
        $hasMismatch = false;
        
        $columnChecks = [
            'user_id' => 'bigint',
            'admin_id' => 'bigint'
        ];
        
        foreach ($columnChecks as $column => $expectedType) {
            $columnInfo = DB::select("
                SELECT DATA_TYPE 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'chat_messages' 
                AND COLUMN_NAME = '{$column}'
            ");
            
            if ($columnInfo && strpos(strtolower($columnInfo[0]->DATA_TYPE), $expectedType) === false) {
                $hasMismatch = true;
                echo "  - chat_messages.{$column} has incorrect type: {$columnInfo[0]->DATA_TYPE}\n";
            }
        }
        
        if ($hasMismatch) {
            echo "Would you like to drop and recreate the chat_messages table? [y/N]: ";
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                echo "Dropping chat_messages table...\n";
                Schema::dropIfExists('chat_messages');
                echo "Chat_messages table dropped. It will be recreated during migration.\n";
            }
        } else {
            echo "  - Chat_messages table column types are correct.\n";
        }
    }
    
    // Step 3: Make sure all order_items data is migrated
    if (Schema::hasTable('orders') && Schema::hasTable('order_items')) {
        echo "Checking for unmigrated order_items data...\n";
        
        $orderCount = DB::table('orders')->count();
        $orderItemsCount = DB::table('order_items')->count();
        
        if ($orderItemsCount > 0) {
            echo "Found {$orderItemsCount} records in order_items table.\n";
            echo "Would you like to migrate this data to the orders.order_items JSON column? [y/N]: ";
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                echo "Migrating order items data...\n";
                
                $orders = DB::table('orders')->get();
                $migratedCount = 0;
                
                foreach ($orders as $order) {
                    $orderItems = DB::table('order_items')
                        ->where('order_id', $order->id)
                        ->get()
                        ->toArray();
                    
                    if (count($orderItems) > 0) {
                        $itemsArray = [];
                        foreach ($orderItems as $item) {
                            $itemArray = (array) $item;
                            if (isset($itemArray['options']) && is_string($itemArray['options'])) {
                                $itemArray['options'] = json_decode($itemArray['options'], true);
                            }
                            $itemsArray[] = $itemArray;
                        }
                        
                        DB::table('orders')
                            ->where('id', $order->id)
                            ->update(['order_items' => json_encode($itemsArray)]);
                            
                        $migratedCount++;
                    }
                }
                
                echo "Successfully migrated items for {$migratedCount} orders.\n";
                
                echo "Would you like to drop the order_items table now? [y/N]: ";
                $line = fgets($handle);
                if (trim(strtolower($line)) === 'y') {
                    Schema::dropIfExists('order_items');
                    echo "order_items table dropped.\n";
                }
            }
        } else {
            echo "No data found in order_items table.\n";
            
            if (Schema::hasTable('order_items')) {
                echo "Would you like to drop the empty order_items table? [y/N]: ";
                $line = fgets($handle);
                if (trim(strtolower($line)) === 'y') {
                    Schema::dropIfExists('order_items');
                    echo "order_items table dropped.\n";
                }
            }
        }
    }
    
    // Step 4: Suggest running migrations
    echo "\nAll preliminary fixes have been applied.\n";
    echo "Would you like to run migrations now? [y/N]: ";
    $line = fgets($handle);
    if (trim(strtolower($line)) === 'y') {
        echo "Running migrations...\n";
        
        echo "\n=== MIGRATION OUTPUT ===\n";
        passthru('php artisan migrate --force');
        echo "=== END MIGRATION OUTPUT ===\n\n";
        
        echo "Migrations completed.\n";
    } else {
        echo "Skipping migrations. You can run them manually with:\n";
        echo "php artisan migrate\n";
    }
    
    echo "\nMigration fixes completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 