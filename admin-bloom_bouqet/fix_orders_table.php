<?php

/**
 * Script to fix orders table by making user_id nullable
 * This allows orders to be created without a user (guest orders)
 */

// Load Laravel environment
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

echo "Starting orders table fix script...\n";

try {
    // Disable foreign key checks to modify the table
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    // Modify the user_id column to be nullable
    echo "Modifying user_id column to be nullable...\n";
    DB::statement('ALTER TABLE orders MODIFY user_id BIGINT UNSIGNED NULL');
    
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
    // Update the foreign key constraint to use nullOnDelete
    echo "Updating foreign key constraint...\n";
    
    // Get the constraint name first (if it exists)
    $constraints = DB::select(
        "SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'orders' 
        AND COLUMN_NAME = 'user_id' 
        AND REFERENCED_TABLE_NAME = 'users'"
    );
    
    if (!empty($constraints)) {
        foreach ($constraints as $constraint) {
            $constraintName = $constraint->CONSTRAINT_NAME;
            echo "Dropping constraint: {$constraintName}\n";
            DB::statement("ALTER TABLE orders DROP FOREIGN KEY {$constraintName}");
        }
    }
    
    // Add the new foreign key constraint with nullOnDelete
    echo "Adding new foreign key constraint with nullOnDelete...\n";
    DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_user_id_foreign 
                   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
    
    echo "Successfully modified orders table!\n";
    
    // Check existing orders with payment_deadline but no status
    $ordersCount = DB::table('orders')
        ->whereNull('payment_deadline')
        ->where('status', 'waiting_for_payment')
        ->where('payment_status', 'pending')
        ->count();
    
    if ($ordersCount > 0) {
        echo "Found {$ordersCount} orders without payment_deadline. Adding default deadline (15 minutes from creation).\n";
        
        // Update orders without payment_deadline
        DB::statement("UPDATE orders 
                      SET payment_deadline = DATE_ADD(created_at, INTERVAL 15 MINUTE)
                      WHERE payment_deadline IS NULL 
                      AND status = 'waiting_for_payment'
                      AND payment_status = 'pending'");
        
        echo "Updated {$ordersCount} orders with default payment deadline.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    Log::error("Fix orders table script error: " . $e->getMessage());
} finally {
    // Always make sure foreign key checks are re-enabled
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
}

echo "Script completed.\n"; 