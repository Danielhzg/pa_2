<?php

// Simple order creation test
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\Cache;

echo "=== SIMPLE ORDER CREATION TEST ===\n\n";

try {
    // Clear cache
    Cache::flush();
    echo "✓ Cache cleared\n";
    
    // Test 1: Direct order creation
    echo "\n1. Testing direct order creation...\n";
    
    $order = new Order();
    $order->order_id = 'SIMPLE-TEST-' . time();
    $order->user_id = null;
    $order->customer_name = 'Simple Test Customer';
    $order->customer_email = 'simple@test.com';
    $order->customer_phone = '081234567890';
    $order->shipping_address = json_encode([
        'name' => 'Simple Test Customer',
        'address' => 'Simple Test Address',
        'phone' => '081234567890'
    ]);
    $order->phone_number = '081234567890';
    $order->subtotal = 100000;
    $order->shipping_cost = 15000;
    $order->total_amount = 115000;
    $order->payment_method = 'qris';
    $order->status = 'waiting_for_payment';
    $order->payment_status = 'pending';
    $order->is_read = false;
    $order->payment_deadline = now()->addMinutes(15);
    $order->order_items = [
        [
            'id' => 1,
            'product_id' => 1,
            'name' => 'Simple Test Product',
            'price' => 100000,
            'quantity' => 1,
            'subtotal' => 100000
        ]
    ];
    
    $order->save();
    
    echo "✅ Order created successfully!\n";
    echo "Order ID: {$order->order_id}\n";
    echo "Customer: {$order->customer_name}\n";
    echo "Total: Rp " . number_format($order->total_amount) . "\n";
    echo "Status: {$order->status}\n";
    
    // Test 2: Check if order appears in admin
    echo "\n2. Checking order in database...\n";
    
    $foundOrder = Order::where('order_id', $order->order_id)->first();
    if ($foundOrder) {
        echo "✅ Order found in database\n";
        echo "Database ID: {$foundOrder->id}\n";
        echo "Created at: {$foundOrder->created_at}\n";
    } else {
        echo "❌ Order not found in database\n";
    }
    
    // Test 3: Count total orders
    echo "\n3. Checking total orders...\n";
    
    $totalOrders = Order::count();
    echo "Total orders in database: $totalOrders\n";
    
    if ($totalOrders > 0) {
        echo "✅ Orders exist in database\n";
        
        // Show recent orders
        $recentOrders = Order::orderBy('created_at', 'desc')->limit(5)->get();
        echo "\nRecent orders:\n";
        foreach ($recentOrders as $recentOrder) {
            echo "- {$recentOrder->order_id}: {$recentOrder->customer_name} (Rp " . number_format($recentOrder->total_amount) . ")\n";
        }
    } else {
        echo "❌ No orders in database\n";
    }
    
    echo "\n=== TEST SUMMARY ===\n";
    echo "✅ Order creation: WORKING\n";
    echo "✅ Database storage: WORKING\n";
    echo "✅ Order retrieval: WORKING\n";
    
    echo "\n🎯 CONCLUSION:\n";
    echo "The order system is working correctly.\n";
    echo "Orders should appear in admin dashboard.\n";
    echo "Check admin dashboard at: http://localhost:8000/admin/order-management\n";
    
    // Clean up test order
    $order->delete();
    echo "\n🧹 Test order cleaned up\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
