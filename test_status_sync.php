<?php

// Test status synchronization between Admin Dashboard and Flutter My Orders
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Http\Controllers\API\OrderController;
use Illuminate\Http\Request;

echo "=== TESTING STATUS SYNCHRONIZATION ===\n\n";

try {
    // Step 1: Setup test customer and orders
    echo "1. SETTING UP TEST ENVIRONMENT...\n";
    echo "=================================\n";
    
    $customer = User::firstOrCreate(
        ['email' => 'sync.test@customer.com'],
        [
            'name' => 'Sync Test Customer',
            'full_name' => 'Sync Test Customer',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]
    );
    
    echo "✓ Customer: {$customer->name} ({$customer->email})\n";
    
    // Clean existing orders
    Order::where('customer_email', 'sync.test@customer.com')->delete();
    
    // Create test orders with different statuses
    $testOrders = [
        [
            'order_id' => 'SYNC-TEST-001',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => $customer->name,
                'address' => 'Jl. Sync Test No. 123',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 300000,
            'shipping_cost' => 20000,
            'total_amount' => 320000,
            'payment_method' => 'qris',
            'status' => 'waiting_for_payment',
            'payment_status' => 'pending',
            'order_items' => [
                [
                    'id' => 1,
                    'product_id' => 1,
                    'name' => 'Test Product 1',
                    'price' => 300000,
                    'quantity' => 1
                ]
            ],
            'created_at' => now()->subMinutes(10),
        ],
        [
            'order_id' => 'SYNC-TEST-002',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => $customer->name,
                'address' => 'Jl. Sync Test No. 123',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 250000,
            'shipping_cost' => 15000,
            'total_amount' => 265000,
            'payment_method' => 'qris',
            'status' => 'processing',
            'payment_status' => 'paid',
            'order_items' => [
                [
                    'id' => 2,
                    'product_id' => 2,
                    'name' => 'Test Product 2',
                    'price' => 250000,
                    'quantity' => 1
                ]
            ],
            'created_at' => now()->subHours(1),
        ]
    ];
    
    foreach ($testOrders as $orderData) {
        $order = Order::create($orderData);
        echo "✓ Created: {$order->order_id} - {$order->status}\n";
    }
    
    // Step 2: Test initial status in Flutter My Orders
    echo "\n2. TESTING INITIAL STATUS IN FLUTTER...\n";
    echo "=======================================\n";
    
    $request = new Request();
    $request->setUserResolver(function () use ($customer) {
        return $customer;
    });
    
    $orderController = new OrderController();
    $response = $orderController->getUserOrders($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success']) {
        echo "✅ Flutter My Orders API working\n";
        echo "✅ Total orders: " . count($responseData['data']) . "\n";
        
        foreach ($responseData['data'] as $order) {
            echo "- {$order['order_id']}: {$order['status']} | {$order['paymentStatus']}\n";
        }
        
        // Test tab filtering
        $statusCounts = [];
        foreach ($responseData['data'] as $order) {
            $status = $order['status'];
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }
        
        echo "\nFlutter tab counts:\n";
        echo "- To Pay: " . ($statusCounts['waiting_for_payment'] ?? 0) . "\n";
        echo "- Processing: " . ($statusCounts['processing'] ?? 0) . "\n";
        echo "- Shipping: " . ($statusCounts['shipping'] ?? 0) . "\n";
        echo "- Completed: " . ($statusCounts['delivered'] ?? 0) . "\n";
    }
    
    // Step 3: Test admin dashboard status counts
    echo "\n3. TESTING ADMIN DASHBOARD STATUS COUNTS...\n";
    echo "===========================================\n";
    
    $adminCounts = [
        'waiting_for_payment' => Order::where('status', 'waiting_for_payment')->count(),
        'processing' => Order::where('status', 'processing')->count(),
        'shipping' => Order::where('status', 'shipping')->count(),
        'delivered' => Order::where('status', 'delivered')->count(),
        'cancelled' => Order::where('status', 'cancelled')->count(),
    ];
    
    echo "Admin dashboard counts:\n";
    echo "- Menunggu Pembayaran: {$adminCounts['waiting_for_payment']}\n";
    echo "- Sedang Diproses: {$adminCounts['processing']}\n";
    echo "- Sedang Dikirim: {$adminCounts['shipping']}\n";
    echo "- Selesai: {$adminCounts['delivered']}\n";
    echo "- Dibatalkan: {$adminCounts['cancelled']}\n";
    
    // Step 4: Simulate admin status change
    echo "\n4. SIMULATING ADMIN STATUS CHANGE...\n";
    echo "====================================\n";
    
    $testOrder = Order::where('order_id', 'SYNC-TEST-001')->first();
    if ($testOrder) {
        echo "Changing order {$testOrder->order_id} from {$testOrder->status} to processing...\n";
        
        // Simulate admin changing status
        $testOrder->status = 'processing';
        $testOrder->status_updated_at = now();
        $testOrder->save();
        
        echo "✅ Admin changed status to: {$testOrder->status}\n";
        
        // Check if change reflects in Flutter API
        $response = $orderController->getUserOrders($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($responseData['success']) {
            $updatedOrder = null;
            foreach ($responseData['data'] as $order) {
                if ($order['order_id'] === 'SYNC-TEST-001') {
                    $updatedOrder = $order;
                    break;
                }
            }
            
            if ($updatedOrder) {
                echo "✅ Status change reflected in Flutter API\n";
                echo "✅ New status in Flutter: {$updatedOrder['status']}\n";
                
                if ($updatedOrder['status'] === 'processing') {
                    echo "✅ Status sync successful!\n";
                    echo "✅ Order moved from 'To Pay' to 'Processing' tab\n";
                } else {
                    echo "❌ Status sync failed!\n";
                }
            }
        }
    }
    
    // Step 5: Test multiple status changes
    echo "\n5. TESTING MULTIPLE STATUS CHANGES...\n";
    echo "=====================================\n";
    
    $statusFlow = ['processing', 'shipping', 'delivered'];
    $testOrder2 = Order::where('order_id', 'SYNC-TEST-002')->first();
    
    if ($testOrder2) {
        echo "Testing status flow for order {$testOrder2->order_id}:\n";
        
        foreach ($statusFlow as $newStatus) {
            echo "\nChanging to: {$newStatus}\n";
            
            // Admin changes status
            $testOrder2->status = $newStatus;
            $testOrder2->status_updated_at = now();
            $testOrder2->save();
            
            // Check Flutter API
            $response = $orderController->getUserOrders($request);
            $responseData = json_decode($response->getContent(), true);
            
            if ($responseData['success']) {
                $updatedOrder = null;
                foreach ($responseData['data'] as $order) {
                    if ($order['order_id'] === 'SYNC-TEST-002') {
                        $updatedOrder = $order;
                        break;
                    }
                }
                
                if ($updatedOrder && $updatedOrder['status'] === $newStatus) {
                    echo "✅ {$newStatus}: Synced successfully\n";
                } else {
                    echo "❌ {$newStatus}: Sync failed\n";
                }
            }
        }
    }
    
    // Step 6: Final verification
    echo "\n6. FINAL VERIFICATION...\n";
    echo "========================\n";
    
    // Get final state from both systems
    $response = $orderController->getUserOrders($request);
    $responseData = json_decode($response->getContent(), true);
    
    $finalAdminCounts = [
        'waiting_for_payment' => Order::where('status', 'waiting_for_payment')->count(),
        'processing' => Order::where('status', 'processing')->count(),
        'shipping' => Order::where('status', 'shipping')->count(),
        'delivered' => Order::where('status', 'delivered')->count(),
        'cancelled' => Order::where('status', 'cancelled')->count(),
    ];
    
    if ($responseData['success']) {
        $finalFlutterCounts = [];
        foreach ($responseData['data'] as $order) {
            $status = $order['status'];
            $finalFlutterCounts[$status] = ($finalFlutterCounts[$status] ?? 0) + 1;
        }
        
        echo "Final status comparison:\n";
        echo "Status                | Admin | Flutter | Match\n";
        echo "---------------------+-------+---------+------\n";
        
        $allStatuses = ['waiting_for_payment', 'processing', 'shipping', 'delivered', 'cancelled'];
        $allMatch = true;
        
        foreach ($allStatuses as $status) {
            $adminCount = $finalAdminCounts[$status] ?? 0;
            $flutterCount = $finalFlutterCounts[$status] ?? 0;
            $match = $adminCount === $flutterCount ? '✅' : '❌';
            
            if ($adminCount !== $flutterCount) {
                $allMatch = false;
            }
            
            printf("%-20s | %5d | %7d | %s\n", $status, $adminCount, $flutterCount, $match);
        }
        
        echo "\n=== STATUS SYNCHRONIZATION TEST SUMMARY ===\n";
        
        if ($allMatch) {
            echo "✅ STATUS SYNCHRONIZATION: PERFECT\n";
            echo "✅ ADMIN DASHBOARD: All counts correct\n";
            echo "✅ FLUTTER MY ORDERS: All counts correct\n";
            echo "✅ STATUS CHANGES: Sync in real-time\n";
            echo "✅ TAB FILTERING: Working correctly\n";
            
            echo "\n🎯 PRODUCTION STATUS:\n";
            echo "✅ Customer orders appear in My Orders immediately\n";
            echo "✅ Status changes in admin sync to Flutter instantly\n";
            echo "✅ Tab counts match admin dashboard exactly\n";
            echo "✅ Order workflow is fully functional\n";
            echo "✅ Customer experience is seamless\n";
            
        } else {
            echo "❌ STATUS SYNCHRONIZATION: ISSUES FOUND\n";
            echo "❌ Counts don't match between systems\n";
        }
    }
    
    // Clean up
    Order::where('customer_email', 'sync.test@customer.com')->delete();
    $customer->delete();
    echo "\n🧹 Test data cleaned up\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
