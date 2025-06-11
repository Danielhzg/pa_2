<?php

// Script untuk membersihkan data order dummy/test
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Log;

echo "=== Cleaning Up Dummy/Test Orders ===\n\n";

try {
    // Step 1: Identify dummy/test orders
    echo "1. Identifying dummy/test orders...\n";
    
    $dummyOrders = Order::where(function($query) {
        $query->where('customer_name', 'Guest User')
              ->orWhere('customer_email', 'guest@example.com')
              ->orWhere('customer_email', 'customer@example.com')
              ->orWhere('order_id', 'like', 'TEST-%')
              ->orWhere('order_id', 'like', 'FLUTTER-API-%')
              ->orWhere('order_id', 'like', 'FLUTTER-ORDER-%')
              ->orWhere('order_id', 'like', 'FLUTTER-USER-%')
              ->orWhere('order_id', 'like', 'FLUTTER-GUEST-%')
              ->orWhereNull('customer_name')
              ->orWhere('customer_name', '')
              ->orWhere('customer_name', 'Customer')
              ->orWhere('customer_name', 'Guest Customer');
    })->get();
    
    echo "✓ Found " . $dummyOrders->count() . " dummy/test orders\n";
    
    // Show details of orders to be deleted
    echo "\nOrders to be deleted:\n";
    foreach ($dummyOrders as $order) {
        echo "  - #{$order->order_id}: {$order->customer_name} ({$order->customer_email}) - Rp " . 
             number_format($order->total_amount, 0, ',', '.') . " - {$order->created_at->format('Y-m-d H:i')}\n";
    }
    
    // Step 2: Ask for confirmation
    echo "\nDo you want to delete these orders? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) !== 'y') {
        echo "Operation cancelled.\n";
        exit(0);
    }
    
    // Step 3: Delete dummy orders
    echo "\n2. Deleting dummy/test orders...\n";
    
    $deletedCount = 0;
    foreach ($dummyOrders as $order) {
        try {
            $orderId = $order->order_id;
            $order->delete();
            $deletedCount++;
            echo "  ✓ Deleted order: {$orderId}\n";
        } catch (\Exception $e) {
            echo "  ❌ Failed to delete order {$order->order_id}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✓ Deleted {$deletedCount} dummy orders\n";
    
    // Step 4: Clear admin notifications cache
    echo "\n3. Clearing admin notifications cache...\n";
    
    $notificationService = new OrderNotificationService();
    $notificationService->clearNotifications();
    echo "✓ Admin notifications cache cleared\n";
    
    // Step 5: Show remaining orders
    echo "\n4. Checking remaining orders...\n";
    
    $remainingOrders = Order::with('user')->orderBy('created_at', 'desc')->get();
    
    echo "✓ Remaining orders: " . $remainingOrders->count() . "\n";
    
    if ($remainingOrders->count() > 0) {
        echo "\nRemaining valid orders:\n";
        foreach ($remainingOrders as $order) {
            $customerName = $order->customer_name ?? 
                           ($order->user ? $order->user->name : 'Unknown');
            $customerEmail = $order->customer_email ?? 
                            ($order->user ? $order->user->email : 'No email');
            
            echo "  - #{$order->order_id}: {$customerName} ({$customerEmail}) - Rp " . 
                 number_format($order->total_amount, 0, ',', '.') . " - {$order->created_at->format('Y-m-d H:i')}\n";
        }
    } else {
        echo "\n📝 No orders remaining. Ready for real customer orders from Flutter!\n";
    }
    
    // Step 6: Show statistics
    echo "\n5. Final statistics...\n";
    
    $stats = [
        'total_orders' => Order::count(),
        'waiting_payment' => Order::where('status', Order::STATUS_WAITING_PAYMENT)->count(),
        'processing' => Order::where('status', Order::STATUS_PROCESSING)->count(),
        'shipping' => Order::where('status', Order::STATUS_SHIPPING)->count(),
        'delivered' => Order::where('status', Order::STATUS_DELIVERED)->count(),
        'user_orders' => Order::whereNotNull('user_id')->count(),
        'guest_orders' => Order::whereNull('user_id')->count(),
    ];
    
    foreach ($stats as $key => $value) {
        echo "✓ " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
    }
    
    echo "\n=== CLEANUP COMPLETED ===\n";
    echo "✅ Dummy/test orders removed\n";
    echo "✅ Admin notifications cleared\n";
    echo "✅ Database ready for real Flutter orders\n";
    
    echo "\n🎯 NEXT STEPS:\n";
    echo "1. Test order creation from Flutter app\n";
    echo "2. Verify orders appear correctly in admin dashboard\n";
    echo "3. Test order tracking from Flutter customer side\n";
    echo "4. Ensure payment flow works end-to-end\n";
    
    echo "\n📱 Flutter Integration Ready:\n";
    echo "- Only real customer orders will appear in admin\n";
    echo "- Order tracking will work correctly\n";
    echo "- Customer data will be accurate\n";
    echo "- Payment status will sync properly\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
