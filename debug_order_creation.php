<?php

// Script untuk debug order creation issues
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Notification;
use App\Http\Controllers\API\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "=== DEBUG ORDER CREATION ISSUES ===\n\n";

try {
    // Step 1: Clean system and check database
    echo "1. CHECKING SYSTEM STATUS...\n";
    echo "============================\n";
    
    // Clear cache first
    Cache::flush();
    echo "✓ Cache cleared\n";
    
    // Check database connection
    try {
        DB::connection()->getPdo();
        echo "✓ Database connection: OK\n";
    } catch (Exception $e) {
        echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
        return;
    }
    
    // Check orders table
    try {
        $orderCount = Order::count();
        echo "✓ Orders table accessible: $orderCount existing orders\n";
    } catch (Exception $e) {
        echo "❌ Orders table: ERROR - " . $e->getMessage() . "\n";
        return;
    }
    
    // Check notifications table
    try {
        $notificationCount = Notification::count();
        echo "✓ Notifications table accessible: $notificationCount existing notifications\n";
    } catch (Exception $e) {
        echo "❌ Notifications table: ERROR - " . $e->getMessage() . "\n";
        return;
    }
    
    // Step 2: Test simple order creation (direct model)
    echo "\n2. TESTING DIRECT MODEL CREATION...\n";
    echo "===================================\n";
    
    try {
        $testOrder = new Order();
        $testOrder->order_id = 'DEBUG-DIRECT-' . time();
        $testOrder->user_id = null;
        $testOrder->customer_name = 'Debug Test Customer';
        $testOrder->customer_email = 'debug@test.com';
        $testOrder->customer_phone = '081234567890';
        $testOrder->shipping_address = json_encode([
            'name' => 'Debug Test Customer',
            'address' => 'Debug Test Address',
            'phone' => '081234567890'
        ]);
        $testOrder->phone_number = '081234567890';
        $testOrder->subtotal = 100000;
        $testOrder->shipping_cost = 15000;
        $testOrder->total_amount = 115000;
        $testOrder->payment_method = 'qris';
        $testOrder->status = 'waiting_for_payment';
        $testOrder->payment_status = 'pending';
        $testOrder->is_read = false;
        $testOrder->payment_deadline = now()->addMinutes(15);
        $testOrder->order_items = [
            [
                'id' => 1,
                'product_id' => 1,
                'name' => 'Debug Test Product',
                'price' => 100000,
                'quantity' => 1,
                'subtotal' => 100000
            ]
        ];
        
        $testOrder->save();
        echo "✅ SUCCESS: Direct model creation works\n";
        echo "Order ID: {$testOrder->order_id}\n";
        echo "Database ID: {$testOrder->id}\n";
        
        // Clean up
        $testOrder->delete();
        
    } catch (Exception $e) {
        echo "❌ ERROR: Direct model creation failed\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
        return;
    }
    
    // Step 3: Test API controller with minimal data
    echo "\n3. TESTING API CONTROLLER (MINIMAL DATA)...\n";
    echo "===========================================\n";
    
    $minimalData = [
        'items' => [
            [
                'id' => 1,
                'name' => 'Minimal Test Product',
                'price' => 50000,
                'quantity' => 1
            ]
        ],
        'customer_name' => 'Minimal Test Customer',
        'customer_email' => 'minimal@test.com',
        'total' => 50000,
        'paymentMethod' => 'qris'
    ];
    
    echo "Testing with minimal data:\n";
    echo json_encode($minimalData, JSON_PRETTY_PRINT) . "\n\n";
    
    $minimalRequest = new Request();
    $minimalRequest->merge($minimalData);
    
    $beforeMinimalCount = Order::count();
    
    $orderController = new OrderController();
    $minimalResponse = $orderController->createOrder($minimalRequest);
    $minimalResponseData = json_decode($minimalResponse->getContent(), true);
    
    $afterMinimalCount = Order::count();
    
    echo "Orders before: $beforeMinimalCount\n";
    echo "Orders after: $afterMinimalCount\n";
    echo "Response status: " . $minimalResponse->getStatusCode() . "\n";
    echo "Response data:\n";
    echo json_encode($minimalResponseData, JSON_PRETTY_PRINT) . "\n";
    
    if ($minimalResponseData['success'] && $afterMinimalCount > $beforeMinimalCount) {
        echo "✅ SUCCESS: API controller works with minimal data\n";
    } else {
        echo "❌ ERROR: API controller failed with minimal data\n";
        if (isset($minimalResponseData['errors'])) {
            echo "Validation errors:\n";
            echo json_encode($minimalResponseData['errors'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    // Step 4: Test API controller with full Flutter data
    echo "\n4. TESTING API CONTROLLER (FULL FLUTTER DATA)...\n";
    echo "================================================\n";
    
    $now = now();
    $fullFlutterData = [
        'id' => 'ORDER-FLUTTER-DEBUG-' . $now->timestamp,
        'order_id' => 'ORDER-FLUTTER-DEBUG-' . $now->timestamp,
        'user_id' => null,
        'items' => [
            [
                'id' => 1,
                'name' => 'Flutter Debug Product 1',
                'price' => 200000,
                'quantity' => 1
            ],
            [
                'id' => 2,
                'name' => 'Flutter Debug Product 2',
                'price' => 150000,
                'quantity' => 2
            ]
        ],
        'deliveryAddress' => [
            'name' => 'Flutter Debug Customer',
            'address' => 'Jl. Flutter Debug 123, Jakarta',
            'phone' => '081234567890',
            'email' => 'flutter.debug@test.com'
        ],
        'subtotal' => 500000,
        'shippingCost' => 25000,
        'total' => 525000,
        'paymentMethod' => 'qris',
        'status' => 'waiting_for_payment',
        'payment_status' => 'pending',
        'customer_name' => 'Flutter Debug Customer',
        'customer_email' => 'flutter.debug@test.com',
        'created_at' => $now->toIso8601String(),
        'order_timestamp' => $now->toIso8601String(),
        'timezone' => 'Asia/Jakarta',
        'request_id' => 'debug_' . $now->timestamp . '_FLUTTER001'
    ];
    
    echo "Testing with full Flutter data:\n";
    echo "Order ID: " . $fullFlutterData['id'] . "\n";
    echo "Customer: " . $fullFlutterData['customer_name'] . "\n";
    echo "Total: Rp " . number_format($fullFlutterData['total']) . "\n";
    echo "Items: " . count($fullFlutterData['items']) . " products\n\n";
    
    $flutterRequest = new Request();
    $flutterRequest->merge($fullFlutterData);
    
    $beforeFlutterCount = Order::count();
    
    $flutterResponse = $orderController->createOrder($flutterRequest);
    $flutterResponseData = json_decode($flutterResponse->getContent(), true);
    
    $afterFlutterCount = Order::count();
    
    echo "Orders before: $beforeFlutterCount\n";
    echo "Orders after: $afterFlutterCount\n";
    echo "Response status: " . $flutterResponse->getStatusCode() . "\n";
    echo "Response data:\n";
    echo json_encode($flutterResponseData, JSON_PRETTY_PRINT) . "\n";
    
    if ($flutterResponseData['success'] && $afterFlutterCount > $beforeFlutterCount) {
        echo "✅ SUCCESS: API controller works with full Flutter data\n";
        
        // Check the created order
        $createdOrder = Order::where('order_id', $fullFlutterData['id'])->first();
        if ($createdOrder) {
            echo "✓ Order found in database:\n";
            echo "  - ID: {$createdOrder->id}\n";
            echo "  - Order ID: {$createdOrder->order_id}\n";
            echo "  - Customer: {$createdOrder->customer_name}\n";
            echo "  - Email: {$createdOrder->customer_email}\n";
            echo "  - Total: Rp " . number_format($createdOrder->total_amount) . "\n";
            echo "  - Status: {$createdOrder->status}\n";
            echo "  - Payment: {$createdOrder->payment_status}\n";
            echo "  - Created: {$createdOrder->created_at}\n";
        }
    } else {
        echo "❌ ERROR: API controller failed with full Flutter data\n";
        if (isset($flutterResponseData['errors'])) {
            echo "Validation errors:\n";
            echo json_encode($flutterResponseData['errors'], JSON_PRETTY_PRINT) . "\n";
        }
        if (isset($flutterResponseData['error_details'])) {
            echo "Error details: " . $flutterResponseData['error_details'] . "\n";
        }
    }
    
    // Step 5: Test duplicate prevention
    echo "\n5. TESTING DUPLICATE PREVENTION...\n";
    echo "==================================\n";
    
    if (isset($createdOrder) && $createdOrder) {
        $beforeDuplicateCount = Order::count();
        
        // Try to create the same order again
        $duplicateRequest = new Request();
        $duplicateRequest->merge($fullFlutterData); // Same data
        
        $duplicateResponse = $orderController->createOrder($duplicateRequest);
        $duplicateResponseData = json_decode($duplicateResponse->getContent(), true);
        
        $afterDuplicateCount = Order::count();
        
        echo "Orders before duplicate: $beforeDuplicateCount\n";
        echo "Orders after duplicate: $afterDuplicateCount\n";
        echo "Duplicate response:\n";
        echo json_encode($duplicateResponseData, JSON_PRETTY_PRINT) . "\n";
        
        if ($afterDuplicateCount === $beforeDuplicateCount) {
            echo "✅ SUCCESS: Duplicate prevention working\n";
        } else {
            echo "❌ ERROR: Duplicate order was created\n";
        }
    }
    
    // Step 6: Final system check
    echo "\n6. FINAL SYSTEM CHECK...\n";
    echo "========================\n";
    
    $finalStats = [
        'total_orders' => Order::count(),
        'waiting_payment' => Order::where('status', 'waiting_for_payment')->count(),
        'total_notifications' => Notification::count(),
        'cache_entries' => 'Cache cleared',
    ];
    
    foreach ($finalStats as $key => $value) {
        echo "✓ " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
    }
    
    // Check for any orders created during debug
    $debugOrders = Order::where('order_id', 'LIKE', '%DEBUG%')->get();
    if ($debugOrders->count() > 0) {
        echo "\nDebug orders created:\n";
        foreach ($debugOrders as $order) {
            echo "- {$order->order_id}: {$order->customer_name} (Rp " . number_format($order->total_amount) . ")\n";
        }
    }
    
    echo "\n=== DEBUG ORDER CREATION SUMMARY ===\n";
    echo "✅ Database connection: WORKING\n";
    echo "✅ Tables accessible: WORKING\n";
    echo "✅ Direct model creation: WORKING\n";
    
    if (isset($minimalResponseData) && $minimalResponseData['success']) {
        echo "✅ API with minimal data: WORKING\n";
    } else {
        echo "❌ API with minimal data: FAILED\n";
    }
    
    if (isset($flutterResponseData) && $flutterResponseData['success']) {
        echo "✅ API with Flutter data: WORKING\n";
    } else {
        echo "❌ API with Flutter data: FAILED\n";
    }
    
    echo "\n🎯 DIAGNOSIS:\n";
    if (isset($flutterResponseData) && $flutterResponseData['success']) {
        echo "✅ Order creation system is WORKING\n";
        echo "✅ Orders should appear in admin dashboard\n";
        echo "✅ Flutter integration should work\n";
    } else {
        echo "❌ Order creation system has ISSUES\n";
        echo "❌ Check validation rules and database constraints\n";
        echo "❌ Review error messages above\n";
    }
    
    // Cleanup debug orders
    Order::where('order_id', 'LIKE', '%DEBUG%')->delete();
    echo "\n🧹 Debug orders cleaned up\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
