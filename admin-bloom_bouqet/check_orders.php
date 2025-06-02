<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;

echo "Checking Orders Table...\n";

// Check if orders table exists
if (!Schema::hasTable('orders')) {
    echo "ERROR: Orders table does not exist!\n";
    exit(1);
}

// Check table structure
echo "Orders Table Structure:\n";
$columns = Schema::getColumnListing('orders');
print_r($columns);

// Check for required columns
$requiredColumns = ['id', 'user_id', 'status', 'payment_status', 'total_amount', 'created_at', 'updated_at'];
$missingColumns = array_diff($requiredColumns, $columns);

if (!empty($missingColumns)) {
    echo "ERROR: Missing required columns: " . implode(', ', $missingColumns) . "\n";
    exit(1);
}

// Check order count
$orderCount = DB::table('orders')->count();
echo "Total orders in database: {$orderCount}\n";

if ($orderCount > 0) {
    // Get first 5 orders
    echo "First 5 orders:\n";
    $orders = DB::table('orders')->limit(5)->get();
    foreach ($orders as $order) {
        echo "ID: {$order->id}, Status: {$order->status}, Payment Status: {$order->payment_status}, Total: {$order->total_amount}\n";
    }
    
    // Try to load orders using the model
    echo "\nTesting Order model:\n";
    try {
        $modelOrders = Order::with('user')->limit(5)->get();
        foreach ($modelOrders as $order) {
            echo "ID: {$order->id}, User: " . ($order->user ? $order->user->name : 'No User') . "\n";
            
            // Test getFormattedItems method
            try {
                $items = $order->getFormattedItems();
                echo "  Items count: " . count($items) . "\n";
            } catch (\Exception $e) {
                echo "  ERROR getting formatted items: " . $e->getMessage() . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "ERROR loading orders with model: " . $e->getMessage() . "\n";
    }
} else {
    echo "No orders found in database.\n";
}

echo "\nDone checking orders.\n"; 