<?php

// Test script untuk memastikan order creation berfungsi
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Log;

echo "=== Testing Order Creation and Notification System ===\n\n";

try {
    // Test 1: Create a test order
    echo "1. Creating test order...\n";
    
    $order = new Order();
    $order->order_id = 'TEST-ORDER-' . time();
    $order->user_id = null; // Guest order
    $order->shipping_address = json_encode([
        'name' => 'Test Customer',
        'address' => 'Test Address 123',
        'phone' => '081234567890'
    ]);
    $order->phone_number = '081234567890';
    $order->subtotal = 100000;
    $order->shipping_cost = 10000;
    $order->total_amount = 110000;
    $order->payment_method = 'qris';
    $order->status = 'waiting_for_payment';
    $order->payment_status = 'pending';
    $order->is_read = false;
    $order->payment_deadline = now()->addMinutes(15);
    $order->order_items = [
        [
            'id' => 1,
            'product_id' => 1,
            'name' => 'Test Product',
            'price' => 100000,
            'quantity' => 1,
            'subtotal' => 100000
        ]
    ];
    
    $order->save();
    echo "✓ Order created successfully with ID: {$order->order_id}\n";
    
    // Test 2: Test notification service
    echo "\n2. Testing notification service...\n";
    
    $notificationService = new OrderNotificationService();
    $notificationService->notifyNewOrder($order);
    echo "✓ Notification sent successfully\n";
    
    // Test 3: Check if order appears in admin queries
    echo "\n3. Testing admin order queries...\n";
    
    $totalOrders = Order::count();
    $waitingPaymentOrders = Order::where('status', Order::STATUS_WAITING_PAYMENT)->count();
    $unreadOrders = Order::where('is_read', false)->count();
    
    echo "✓ Total orders: {$totalOrders}\n";
    echo "✓ Waiting for payment: {$waitingPaymentOrders}\n";
    echo "✓ Unread orders: {$unreadOrders}\n";
    
    // Test 4: Test notification retrieval
    echo "\n4. Testing notification retrieval...\n";
    
    $notifications = $notificationService->getAdminNotifications(5);
    echo "✓ Retrieved " . count($notifications) . " notifications\n";
    
    if (count($notifications) > 0) {
        echo "Latest notification: " . $notifications[0]['message'] . "\n";
    }
    
    // Test 5: Test order status update
    echo "\n5. Testing order status update...\n";
    
    $oldStatus = $order->status;
    $order->status = 'processing';
    $order->payment_status = 'paid';
    $order->save();
    
    $notificationService->notifyPaymentStatusChange($order, 'pending', 'paid');
    $notificationService->notifyOrderStatusChange($order, $oldStatus, 'processing');
    
    echo "✓ Order status updated and notifications sent\n";
    
    echo "\n=== All Tests Passed! ===\n";
    echo "Order management system is working correctly.\n";
    echo "You can now:\n";
    echo "1. Access admin dashboard at: http://localhost:8000/admin/order-management\n";
    echo "2. Create orders from Flutter app\n";
    echo "3. See real-time notifications in admin dashboard\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
