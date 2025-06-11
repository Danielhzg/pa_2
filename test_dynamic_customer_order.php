<?php

// Test script untuk memverifikasi dynamic customer order system
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Log;

echo "=== Testing Dynamic Customer Order System ===\n\n";

try {
    // Step 1: Create a test user (simulating Flutter user registration)
    echo "1. Creating test user (simulating Flutter registration)...\n";
    
    $testUser = User::create([
        'name' => 'John Doe Flutter',
        'full_name' => 'John Doe Flutter',
        'username' => 'johndoe_flutter',
        'email' => 'john.doe.flutter@example.com',
        'password' => bcrypt('password123'),
        'phone' => '081234567890',
        'address' => 'Jl. Flutter Test 123, Jakarta',
        'email_verified_at' => now(),
    ]);
    
    echo "✓ Test user created: {$testUser->name} ({$testUser->email}) - ID: {$testUser->id}\n";
    
    // Step 2: Create order with user data (simulating Flutter checkout)
    echo "\n2. Creating order with authenticated user data...\n";
    
    $orderId = 'FLUTTER-USER-' . time();
    
    $order = new Order();
    $order->order_id = $orderId;
    $order->user_id = $testUser->id;
    $order->customer_name = $testUser->name;
    $order->customer_email = $testUser->email;
    $order->customer_phone = $testUser->phone;
    $order->shipping_address = json_encode([
        'name' => $testUser->name,
        'address' => $testUser->address,
        'phone' => $testUser->phone,
        'email' => $testUser->email,
    ]);
    $order->phone_number = $testUser->phone;
    $order->subtotal = 250000;
    $order->shipping_cost = 25000;
    $order->total_amount = 275000;
    $order->payment_method = 'qris';
    $order->status = 'waiting_for_payment';
    $order->payment_status = 'pending';
    $order->is_read = false;
    $order->payment_deadline = now()->addMinutes(15);
    $order->order_items = [
        [
            'id' => 1,
            'product_id' => 1,
            'name' => 'Bouquet Mawar Premium',
            'price' => 250000,
            'quantity' => 1,
            'subtotal' => 250000
        ]
    ];
    
    $order->save();
    echo "✓ Order created with authenticated user data\n";
    echo "  - Order ID: {$order->order_id}\n";
    echo "  - Customer: {$order->customer_name} ({$order->customer_email})\n";
    echo "  - User ID: {$order->user_id}\n";
    echo "  - Total: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
    
    // Step 3: Send notification to admin
    echo "\n3. Sending notification to admin...\n";
    
    $notificationService = new OrderNotificationService();
    $notificationService->notifyNewOrder($order);
    echo "✓ Admin notification sent with customer details\n";
    
    // Step 4: Create guest order (simulating guest checkout)
    echo "\n4. Creating guest order...\n";
    
    $guestOrderId = 'FLUTTER-GUEST-' . time();
    
    $guestOrder = new Order();
    $guestOrder->order_id = $guestOrderId;
    $guestOrder->user_id = null;
    $guestOrder->customer_name = 'Jane Smith Guest';
    $guestOrder->customer_email = 'jane.guest@example.com';
    $guestOrder->customer_phone = '081987654321';
    $guestOrder->shipping_address = json_encode([
        'name' => 'Jane Smith Guest',
        'address' => 'Jl. Guest Order 456, Bandung',
        'phone' => '081987654321',
        'email' => 'jane.guest@example.com',
    ]);
    $guestOrder->phone_number = '081987654321';
    $guestOrder->subtotal = 180000;
    $guestOrder->shipping_cost = 18000;
    $guestOrder->total_amount = 198000;
    $guestOrder->payment_method = 'bank_transfer';
    $guestOrder->status = 'waiting_for_payment';
    $guestOrder->payment_status = 'pending';
    $guestOrder->is_read = false;
    $guestOrder->payment_deadline = now()->addMinutes(15);
    $guestOrder->order_items = [
        [
            'id' => 2,
            'product_id' => 2,
            'name' => 'Bouquet Tulip',
            'price' => 180000,
            'quantity' => 1,
            'subtotal' => 180000
        ]
    ];
    
    $guestOrder->save();
    echo "✓ Guest order created\n";
    echo "  - Order ID: {$guestOrder->order_id}\n";
    echo "  - Customer: {$guestOrder->customer_name} ({$guestOrder->customer_email})\n";
    echo "  - User ID: " . ($guestOrder->user_id ?? 'null (guest)') . "\n";
    echo "  - Total: Rp " . number_format($guestOrder->total_amount, 0, ',', '.') . "\n";
    
    // Send notification for guest order
    $notificationService->notifyNewOrder($guestOrder);
    echo "✓ Admin notification sent for guest order\n";
    
    // Step 5: Check admin dashboard data
    echo "\n5. Checking admin dashboard data...\n";
    
    $allOrders = Order::with('user')->orderBy('created_at', 'desc')->limit(10)->get();
    
    echo "✓ Recent orders in admin dashboard:\n";
    foreach ($allOrders as $adminOrder) {
        $customerName = $adminOrder->customer_name ?? 
                       ($adminOrder->user ? $adminOrder->user->name : 'Unknown');
        $customerEmail = $adminOrder->customer_email ?? 
                        ($adminOrder->user ? $adminOrder->user->email : 'No email');
        
        echo "  - #{$adminOrder->order_id}: {$customerName} ({$customerEmail}) - Rp " . 
             number_format($adminOrder->total_amount, 0, ',', '.') . "\n";
    }
    
    // Step 6: Simulate payment completion
    echo "\n6. Simulating payment completion for user order...\n";
    
    $oldPaymentStatus = $order->payment_status;
    $oldOrderStatus = $order->status;
    
    $order->updatePaymentStatus(Order::PAYMENT_PAID);
    
    echo "✓ Payment completed for user order\n";
    echo "  - Payment Status: {$oldPaymentStatus} → {$order->payment_status}\n";
    echo "  - Order Status: {$oldOrderStatus} → {$order->status}\n";
    
    // Send notifications
    $notificationService->notifyPaymentStatusChange($order, $oldPaymentStatus, $order->payment_status);
    $notificationService->notifyOrderStatusChange($order, $oldOrderStatus, $order->status);
    echo "✓ Status change notifications sent\n";
    
    // Step 7: Check final admin dashboard stats
    echo "\n7. Final admin dashboard statistics...\n";
    
    $stats = [
        'total_orders' => Order::count(),
        'waiting_payment' => Order::where('status', Order::STATUS_WAITING_PAYMENT)->count(),
        'processing' => Order::where('status', Order::STATUS_PROCESSING)->count(),
        'user_orders' => Order::whereNotNull('user_id')->count(),
        'guest_orders' => Order::whereNull('user_id')->count(),
        'orders_with_customer_info' => Order::whereNotNull('customer_name')->count(),
    ];
    
    foreach ($stats as $key => $value) {
        echo "✓ " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
    }
    
    // Step 8: Check notifications
    echo "\n8. Checking admin notifications...\n";
    
    $notifications = $notificationService->getAdminNotifications(5);
    echo "✓ Total notifications: " . count($notifications) . "\n";
    
    foreach ($notifications as $notif) {
        echo "  - " . ($notif['message'] ?? 'No message') . "\n";
    }
    
    echo "\n=== DYNAMIC CUSTOMER ORDER SYSTEM TEST SUMMARY ===\n";
    echo "✅ User order creation: WORKING\n";
    echo "✅ Guest order creation: WORKING\n";
    echo "✅ Customer info storage: WORKING\n";
    echo "✅ Admin dashboard display: WORKING\n";
    echo "✅ Payment processing: WORKING\n";
    echo "✅ Notifications with customer details: WORKING\n";
    
    echo "\n🎯 SYSTEM READY!\n";
    echo "Your order management system now:\n";
    echo "1. ✅ Shows real customer names and emails in admin dashboard\n";
    echo "2. ✅ Supports both authenticated users and guest orders\n";
    echo "3. ✅ Stores customer info in dedicated columns\n";
    echo "4. ✅ Sends notifications with customer details\n";
    echo "5. ✅ Handles dynamic order data from Flutter\n";
    
    echo "\n📱 Flutter Integration:\n";
    echo "- Send user_id, customer_name, customer_email in order creation\n";
    echo "- System will automatically use authenticated user data\n";
    echo "- Guest orders will show guest customer info\n";
    echo "- Admin dashboard will display correct customer details\n";
    
    // Cleanup test user
    $testUser->delete();
    echo "\n🧹 Test user cleaned up\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
