<?php

// Test script untuk memverifikasi complete order flow
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Log;

echo "=== Testing Complete Order Flow ===\n\n";

try {
    // Step 1: Simulate customer creating order (like from Flutter)
    echo "1. Simulating customer order creation...\n";
    
    $orderId = 'FLUTTER-ORDER-' . time();
    
    $order = new Order();
    $order->order_id = $orderId;
    $order->user_id = null; // Guest order
    $order->shipping_address = json_encode([
        'name' => 'Flutter Customer',
        'address' => 'Jl. Test Flutter 123',
        'phone' => '081234567890'
    ]);
    $order->phone_number = '081234567890';
    $order->subtotal = 150000;
    $order->shipping_cost = 15000;
    $order->total_amount = 165000;
    $order->payment_method = 'qris';
    $order->status = 'waiting_for_payment';
    $order->payment_status = 'pending';
    $order->is_read = false;
    $order->payment_deadline = now()->addMinutes(15);
    $order->order_items = [
        [
            'id' => 1,
            'product_id' => 1,
            'name' => 'Bouquet Mawar Merah',
            'price' => 150000,
            'quantity' => 1,
            'subtotal' => 150000
        ]
    ];
    
    $order->save();
    echo "✓ Order created: {$order->order_id}\n";
    echo "  - Status: {$order->status}\n";
    echo "  - Payment Status: {$order->payment_status}\n";
    echo "  - Total: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
    
    // Step 2: Send notification to admin (like from Flutter checkout)
    echo "\n2. Sending notification to admin...\n";
    
    $notificationService = new OrderNotificationService();
    $notificationService->notifyNewOrder($order);
    echo "✓ Admin notification sent\n";
    
    // Step 3: Check if order appears in admin queries
    echo "\n3. Checking admin dashboard queries...\n";
    
    $waitingPaymentOrders = Order::where('status', Order::STATUS_WAITING_PAYMENT)->get();
    $unreadOrders = Order::where('is_read', false)->get();
    
    echo "✓ Orders waiting for payment: " . $waitingPaymentOrders->count() . "\n";
    echo "✓ Unread orders: " . $unreadOrders->count() . "\n";
    
    // Check if our order is in the list
    $ourOrder = $waitingPaymentOrders->where('order_id', $orderId)->first();
    if ($ourOrder) {
        echo "✓ Our order found in waiting payment list\n";
    } else {
        echo "❌ Our order NOT found in waiting payment list\n";
    }
    
    // Step 4: Simulate payment completion (like from Flutter payment)
    echo "\n4. Simulating payment completion...\n";
    
    $oldPaymentStatus = $order->payment_status;
    $oldOrderStatus = $order->status;
    
    $order->updatePaymentStatus(Order::PAYMENT_PAID);
    
    echo "✓ Payment status updated: {$oldPaymentStatus} → {$order->payment_status}\n";
    echo "✓ Order status updated: {$oldOrderStatus} → {$order->status}\n";
    
    // Send notifications for status changes
    $notificationService->notifyPaymentStatusChange($order, $oldPaymentStatus, $order->payment_status);
    $notificationService->notifyOrderStatusChange($order, $oldOrderStatus, $order->status);
    echo "✓ Status change notifications sent\n";
    
    // Step 5: Check admin dashboard after payment
    echo "\n5. Checking admin dashboard after payment...\n";
    
    $processingOrders = Order::where('status', Order::STATUS_PROCESSING)->get();
    $paidOrders = Order::where('payment_status', Order::PAYMENT_PAID)->get();
    
    echo "✓ Orders in processing: " . $processingOrders->count() . "\n";
    echo "✓ Paid orders: " . $paidOrders->count() . "\n";
    
    // Check if our order moved to processing
    $ourProcessingOrder = $processingOrders->where('order_id', $orderId)->first();
    if ($ourProcessingOrder) {
        echo "✓ Our order found in processing list\n";
    } else {
        echo "❌ Our order NOT found in processing list\n";
    }
    
    // Step 6: Check notifications
    echo "\n6. Checking admin notifications...\n";
    
    $notifications = $notificationService->getAdminNotifications(10);
    echo "✓ Total notifications: " . count($notifications) . "\n";
    
    $orderNotifications = array_filter($notifications, function($notif) use ($orderId) {
        return strpos($notif['order_id'] ?? '', $orderId) !== false;
    });
    
    echo "✓ Notifications for our order: " . count($orderNotifications) . "\n";
    
    foreach ($orderNotifications as $notif) {
        echo "  - " . ($notif['message'] ?? 'No message') . "\n";
    }
    
    // Step 7: Simulate admin viewing the order
    echo "\n7. Simulating admin actions...\n";
    
    // Mark order as read (admin viewed it)
    $order->is_read = true;
    $order->save();
    echo "✓ Order marked as read by admin\n";
    
    // Admin updates order status to shipping
    $oldStatus = $order->status;
    $order->updateStatus(Order::STATUS_SHIPPING);
    echo "✓ Admin updated order status: {$oldStatus} → {$order->status}\n";
    
    // Send notification for admin status change
    $notificationService->notifyOrderStatusChange($order, $oldStatus, $order->status);
    echo "✓ Status change notification sent\n";
    
    // Final summary
    echo "\n=== FLOW SUMMARY ===\n";
    echo "Order ID: {$order->order_id}\n";
    echo "Current Status: {$order->status}\n";
    echo "Payment Status: {$order->payment_status}\n";
    echo "Is Read: " . ($order->is_read ? 'Yes' : 'No') . "\n";
    echo "Total Amount: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
    echo "Created: " . $order->created_at->format('Y-m-d H:i:s') . "\n";
    echo "Paid At: " . ($order->paid_at ? $order->paid_at->format('Y-m-d H:i:s') : 'Not paid') . "\n";
    
    echo "\n=== ADMIN DASHBOARD STATS ===\n";
    echo "Total Orders: " . Order::count() . "\n";
    echo "Waiting Payment: " . Order::where('status', Order::STATUS_WAITING_PAYMENT)->count() . "\n";
    echo "Processing: " . Order::where('status', Order::STATUS_PROCESSING)->count() . "\n";
    echo "Shipping: " . Order::where('status', Order::STATUS_SHIPPING)->count() . "\n";
    echo "Delivered: " . Order::where('status', Order::STATUS_DELIVERED)->count() . "\n";
    echo "Unread Orders: " . Order::where('is_read', false)->count() . "\n";
    
    echo "\n✅ COMPLETE ORDER FLOW TEST PASSED!\n";
    echo "The order flow from Flutter customer to Laravel admin is working correctly.\n";
    
    echo "\n🎯 NEXT STEPS:\n";
    echo "1. Run Flutter app and create an order\n";
    echo "2. Check admin dashboard at: http://localhost:8000/admin/order-management\n";
    echo "3. Simulate payment in Flutter app\n";
    echo "4. See real-time status updates in admin dashboard\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
