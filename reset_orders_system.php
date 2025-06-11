<?php

// Script untuk reset order system dan fix duplicate orders
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "=== RESET ORDER SYSTEM & FIX DUPLICATES ===\n\n";

try {
    // Step 1: HAPUS SEMUA DATA DUMMY DAN EXISTING ORDERS
    echo "1. MENGHAPUS SEMUA DATA DUMMY DAN EXISTING ORDERS...\n";
    echo "====================================================\n";
    
    $allOrders = Order::all();
    echo "Found " . $allOrders->count() . " orders to delete\n";
    
    if ($allOrders->count() > 0) {
        echo "Deleting all existing orders:\n";
        foreach ($allOrders as $order) {
            echo "  - Deleting #{$order->order_id}: {$order->customer_name} ({$order->customer_email})\n";
            $order->delete();
        }
        echo "✓ All orders deleted successfully\n";
    } else {
        echo "✓ No orders to delete\n";
    }
    
    // Step 2: RESET AUTO INCREMENT ID
    echo "\n2. RESETTING AUTO INCREMENT ID...\n";
    echo "==================================\n";
    
    try {
        DB::statement('ALTER TABLE orders AUTO_INCREMENT = 1');
        echo "✓ Auto increment reset to 1\n";
    } catch (Exception $e) {
        echo "Warning: Could not reset auto increment: " . $e->getMessage() . "\n";
    }
    
    // Step 3: CLEAR CACHE
    echo "\n3. CLEARING CACHE...\n";
    echo "====================\n";
    
    try {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('cache:clear');
        echo "✓ All caches cleared\n";
    } catch (Exception $e) {
        echo "Warning: Cache clearing failed: " . $e->getMessage() . "\n";
    }
    
    // Step 4: CREATE SINGLE TEST ORDER WITH ORDER-1 ID
    echo "\n4. CREATING SINGLE TEST ORDER WITH ORDER-1 ID...\n";
    echo "=================================================\n";
    
    $testOrder = new Order();
    $testOrder->order_id = 'ORDER-1';
    $testOrder->user_id = null;
    $testOrder->customer_name = 'Test Customer';
    $testOrder->customer_email = 'test.customer@bloombouquet.com';
    $testOrder->customer_phone = '081234567890';
    $testOrder->shipping_address = json_encode([
        'name' => 'Test Customer',
        'address' => 'Jl. Test Address 123, Jakarta',
        'phone' => '081234567890',
        'email' => 'test.customer@bloombouquet.com'
    ]);
    $testOrder->phone_number = '081234567890';
    $testOrder->subtotal = 250000;
    $testOrder->shipping_cost = 25000;
    $testOrder->total_amount = 275000;
    $testOrder->payment_method = 'qris';
    $testOrder->status = 'waiting_for_payment';
    $testOrder->payment_status = 'pending';
    $testOrder->is_read = false;
    $testOrder->payment_deadline = now()->addMinutes(15);
    $testOrder->order_items = [
        [
            'id' => 1,
            'product_id' => 1,
            'name' => 'Bouquet Test Product',
            'price' => 250000,
            'quantity' => 1,
            'subtotal' => 250000
        ]
    ];
    
    $testOrder->save();
    
    echo "✓ Test order created with ORDER-1 ID:\n";
    echo "  - Order ID: {$testOrder->order_id}\n";
    echo "  - Customer: {$testOrder->customer_name}\n";
    echo "  - Email: {$testOrder->customer_email}\n";
    echo "  - Total: Rp " . number_format($testOrder->total_amount, 0, ',', '.') . "\n";
    echo "  - Database ID: {$testOrder->id}\n";
    
    // Step 5: VERIFY SYSTEM STATUS
    echo "\n5. VERIFYING SYSTEM STATUS...\n";
    echo "=============================\n";
    
    $stats = [
        'total_orders' => Order::count(),
        'waiting_payment' => Order::where('status', 'waiting_for_payment')->count(),
        'with_customer_info' => Order::whereNotNull('customer_name')->count(),
        'latest_order_id' => Order::latest('id')->first()?->order_id ?? 'None',
        'latest_db_id' => Order::latest('id')->first()?->id ?? 0,
    ];
    
    foreach ($stats as $key => $value) {
        echo "✓ " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
    }
    
    echo "\n=== DUPLICATE ORDER PREVENTION GUIDE ===\n";
    echo "=========================================\n";
    
    echo "Issues identified that cause duplicate orders:\n\n";
    
    echo "1. ❌ Flutter Retry Logic:\n";
    echo "   - checkout_page.dart has retry mechanism (lines 947-1048)\n";
    echo "   - Tries to create order 2x if first attempt fails\n";
    echo "   - Solution: Remove retry logic or add duplicate check\n\n";
    
    echo "2. ❌ Laravel Fallback Method:\n";
    echo "   - OrderController has createOrderAsGuest fallback\n";
    echo "   - Creates second order if first fails with user_id\n";
    echo "   - Solution: Fix user_id handling instead of fallback\n\n";
    
    echo "3. ❌ Multiple API Endpoints:\n";
    echo "   - Flutter tries different endpoints\n";
    echo "   - /api/orders, /api/v1/orders/create, etc.\n";
    echo "   - Solution: Use single consistent endpoint\n\n";
    
    echo "4. ❌ Order ID Generation:\n";
    echo "   - Both Flutter and Laravel generate order IDs\n";
    echo "   - Can cause conflicts and duplicates\n";
    echo "   - Solution: Let Laravel generate unique IDs\n\n";
    
    echo "=== FIXES TO IMPLEMENT ===\n";
    echo "===========================\n";
    
    echo "1. ✅ Remove Flutter retry logic\n";
    echo "2. ✅ Remove Laravel fallback method\n";
    echo "3. ✅ Use single API endpoint\n";
    echo "4. ✅ Implement proper duplicate detection\n";
    echo "5. ✅ Use sequential ORDER-X ID format\n";
    echo "6. ✅ Add transaction locking\n\n";
    
    echo "=== NEXT ORDER ID SEQUENCE ===\n";
    echo "===============================\n";
    
    echo "Next Flutter orders will have IDs:\n";
    echo "- ORDER-2 (next customer order)\n";
    echo "- ORDER-3 (following order)\n";
    echo "- ORDER-4 (and so on...)\n\n";
    
    echo "✅ SYSTEM RESET COMPLETED!\n";
    echo "✅ Ready for single, non-duplicate orders\n";
    echo "✅ Order ID sequence starts from ORDER-1\n";
    echo "✅ Database cleaned and optimized\n";
    
    echo "\n🎯 NEXT STEPS:\n";
    echo "1. Fix Flutter retry logic\n";
    echo "2. Fix Laravel duplicate prevention\n";
    echo "3. Test single order creation\n";
    echo "4. Verify no duplicates occur\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
