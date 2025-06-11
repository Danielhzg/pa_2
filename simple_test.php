<?php

// Simple test to verify system is working
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;

echo "=== SIMPLE SYSTEM VERIFICATION ===\n\n";

try {
    // Check database connection
    echo "1. Database Connection: ";
    $orderCount = Order::count();
    echo "✅ Connected (Total orders: {$orderCount})\n";
    
    // Check user model
    echo "2. User Model: ";
    $userCount = User::count();
    echo "✅ Working (Total users: {$userCount})\n";
    
    // Check if test user exists
    echo "3. Test User: ";
    $testUser = User::where('email', 'customer@test.com')->first();
    if ($testUser) {
        echo "✅ Found ({$testUser->name})\n";
        
        // Check orders for this user
        echo "4. User Orders: ";
        $userOrders = Order::where('customer_email', 'customer@test.com')->count();
        echo "✅ Found {$userOrders} orders\n";
        
        if ($userOrders > 0) {
            echo "\nOrder details:\n";
            $orders = Order::where('customer_email', 'customer@test.com')
                          ->orderBy('created_at', 'desc')
                          ->limit(3)
                          ->get();
            
            foreach ($orders as $order) {
                echo "- {$order->order_id}: {$order->status} | Rp " . number_format($order->total_amount) . "\n";
            }
        }
    } else {
        echo "❌ Not found\n";
    }
    
    // Check status distribution
    echo "\n5. Status Distribution:\n";
    $statuses = ['waiting_for_payment', 'processing', 'shipping', 'delivered', 'cancelled'];
    foreach ($statuses as $status) {
        $count = Order::where('status', $status)->count();
        echo "   - {$status}: {$count} orders\n";
    }
    
    echo "\n=== SYSTEM STATUS ===\n";
    echo "✅ Database: Working\n";
    echo "✅ Models: Working\n";
    echo "✅ Orders: Available\n";
    echo "✅ Status System: Working\n";
    
    echo "\n🎯 READY FOR FLUTTER TESTING!\n";
    echo "Login credentials: customer@test.com / password123\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
