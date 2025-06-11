<?php

// Test authenticated order creation from Flutter customer
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Http\Controllers\API\OrderController;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

echo "=== TESTING AUTHENTICATED ORDER CREATION ===\n\n";

try {
    // Step 1: Create authenticated customer
    echo "1. CREATING AUTHENTICATED CUSTOMER...\n";
    echo "====================================\n";
    
    $customer = User::firstOrCreate(
        ['email' => 'authenticated@customer.com'],
        [
            'name' => 'Authenticated Customer',
            'full_name' => 'Authenticated Customer Flutter',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]
    );
    
    echo "✓ Customer: {$customer->name} ({$customer->email})\n";
    echo "✓ Customer ID: {$customer->id}\n";
    
    // Create API token for authentication
    $token = $customer->createToken('flutter-app')->plainTextToken;
    echo "✓ API Token created: " . substr($token, 0, 20) . "...\n";
    
    // Clean existing orders
    Order::where('customer_email', 'authenticated@customer.com')->delete();
    
    // Step 2: Simulate Flutter order creation with authentication
    echo "\n2. SIMULATING FLUTTER ORDER CREATION...\n";
    echo "=======================================\n";
    
    // Create order data as it comes from Flutter checkout
    $orderData = [
        'id' => 'AUTH-ORDER-' . time(),
        'order_id' => 'AUTH-ORDER-' . time(),
        'user_id' => $customer->id, // Include authenticated user ID
        'items' => [
            [
                'id' => 1,
                'product_id' => 1,
                'name' => 'Premium Rose Bouquet',
                'price' => 350000,
                'quantity' => 2,
            ],
            [
                'id' => 2,
                'product_id' => 2,
                'name' => 'Greeting Card',
                'price' => 50000,
                'quantity' => 1,
            ]
        ],
        'deliveryAddress' => [
            'name' => $customer->name,
            'address' => 'Jl. Authenticated Customer No. 123, Jakarta Pusat',
            'phone' => '081234567890',
            'email' => $customer->email,
        ],
        'subtotal' => 750000, // (350000 * 2) + (50000 * 1)
        'shippingCost' => 25000,
        'total' => 775000,
        'paymentMethod' => 'qris',
        'status' => 'waiting_for_payment',
        'payment_status' => 'pending',
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'created_at' => now()->toIso8601String(),
    ];
    
    echo "Order details:\n";
    echo "- Customer: {$orderData['customer_name']} ({$orderData['customer_email']})\n";
    echo "- User ID: {$orderData['user_id']}\n";
    echo "- Items: " . count($orderData['items']) . " products\n";
    echo "- Subtotal: Rp " . number_format($orderData['subtotal']) . "\n";
    echo "- Shipping: Rp " . number_format($orderData['shippingCost']) . "\n";
    echo "- Total: Rp " . number_format($orderData['total']) . "\n";
    
    // Step 3: Create authenticated request
    echo "\n3. CREATING AUTHENTICATED REQUEST...\n";
    echo "===================================\n";
    
    $request = new Request();
    $request->replace($orderData);
    
    // Set authorization header
    $request->headers->set('Authorization', 'Bearer ' . $token);
    
    // Set user resolver to simulate authentication
    $request->setUserResolver(function () use ($customer) {
        return $customer;
    });
    
    echo "✓ Request created with authentication\n";
    echo "✓ Bearer token set\n";
    echo "✓ User resolver configured\n";
    
    // Step 4: Call order creation API
    echo "\n4. CALLING ORDER CREATION API...\n";
    echo "================================\n";
    
    $orderController = new OrderController();
    $response = $orderController->createOrder($request);
    
    $statusCode = $response->getStatusCode();
    $responseData = json_decode($response->getContent(), true);
    
    echo "API Response Status: $statusCode\n";
    echo "API Response Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
    
    if ($responseData['success']) {
        $createdOrderId = $responseData['data']['id'] ?? null;
        echo "✅ Order created successfully!\n";
        echo "✅ Order ID: {$createdOrderId}\n";
        
        // Get the created order from database
        $createdOrder = Order::where('order_id', $createdOrderId)->first();
        if ($createdOrder) {
            echo "✅ Order found in database\n";
            echo "✅ Database Order ID: {$createdOrder->id}\n";
            echo "✅ Order Number: {$createdOrder->order_id}\n";
            echo "✅ User ID: " . ($createdOrder->user_id ?? 'NULL') . "\n";
            echo "✅ Customer: {$createdOrder->customer_name} ({$createdOrder->customer_email})\n";
            echo "✅ Status: {$createdOrder->status}\n";
            echo "✅ Payment Status: {$createdOrder->payment_status}\n";
            echo "✅ Total: Rp " . number_format($createdOrder->total_amount) . "\n";
            echo "✅ Items: " . count($createdOrder->order_items) . " products\n";
        }
    } else {
        echo "❌ Order creation failed!\n";
        echo "Error: " . ($responseData['message'] ?? 'Unknown error') . "\n";
        if (isset($responseData['errors'])) {
            echo "Details: " . json_encode($responseData['errors']) . "\n";
        }
        exit(1);
    }
    
    // Step 5: Verify order appears in My Orders API
    echo "\n5. VERIFYING ORDER IN MY ORDERS API...\n";
    echo "======================================\n";
    
    $myOrdersRequest = new Request();
    $myOrdersRequest->headers->set('Authorization', 'Bearer ' . $token);
    $myOrdersRequest->setUserResolver(function () use ($customer) {
        return $customer;
    });
    
    $myOrdersResponse = $orderController->getUserOrders($myOrdersRequest);
    $myOrdersData = json_decode($myOrdersResponse->getContent(), true);
    
    if ($myOrdersData['success']) {
        echo "✅ My Orders API working\n";
        echo "✅ Total orders: " . count($myOrdersData['data']) . "\n";
        
        $foundOrder = null;
        foreach ($myOrdersData['data'] as $order) {
            if ($order['order_id'] === $createdOrderId) {
                $foundOrder = $order;
                break;
            }
        }
        
        if ($foundOrder) {
            echo "✅ Order found in My Orders API\n";
            echo "✅ Order ID: {$foundOrder['order_id']}\n";
            echo "✅ Status: {$foundOrder['status']}\n";
            echo "✅ Payment Status: {$foundOrder['paymentStatus']}\n";
            echo "✅ Customer: {$foundOrder['customer_name']} ({$foundOrder['customer_email']})\n";
            echo "✅ Total: Rp " . number_format($foundOrder['total']) . "\n";
            echo "✅ Items: " . count($foundOrder['items']) . " products\n";
        } else {
            echo "❌ Order NOT found in My Orders API\n";
        }
    } else {
        echo "❌ My Orders API failed: " . $myOrdersData['message'] . "\n";
    }
    
    // Step 6: Verify order appears in admin dashboard
    echo "\n6. VERIFYING ORDER IN ADMIN DASHBOARD...\n";
    echo "========================================\n";
    
    $adminOrders = Order::where('customer_email', 'authenticated@customer.com')
                        ->orderBy('created_at', 'desc')
                        ->get();
    
    echo "Admin dashboard orders: {$adminOrders->count()}\n";
    
    foreach ($adminOrders as $order) {
        echo "\nAdmin Order:\n";
        echo "- Order ID: {$order->order_id}\n";
        echo "- User ID: " . ($order->user_id ?? 'NULL (Guest)') . "\n";
        echo "- Status: {$order->status}\n";
        echo "- Status Label: {$order->getStatusLabelAttribute()}\n";
        echo "- Payment Status: {$order->payment_status}\n";
        echo "- Customer: {$order->customer_name} ({$order->customer_email})\n";
        echo "- Total: Rp " . number_format($order->total_amount) . "\n";
        echo "- Created: {$order->created_at}\n";
    }
    
    // Step 7: Test status update synchronization
    echo "\n7. TESTING STATUS UPDATE SYNCHRONIZATION...\n";
    echo "===========================================\n";
    
    if ($createdOrder) {
        echo "Simulating payment completion...\n";
        
        // Update payment status (simulates payment webhook)
        $createdOrder->payment_status = 'paid';
        $createdOrder->status = 'processing';
        $createdOrder->status_updated_at = now();
        $createdOrder->save();
        
        echo "✅ Payment status updated to: {$createdOrder->payment_status}\n";
        echo "✅ Order status updated to: {$createdOrder->status}\n";
        
        // Check if change reflects in My Orders API
        $updatedResponse = $orderController->getUserOrders($myOrdersRequest);
        $updatedData = json_decode($updatedResponse->getContent(), true);
        
        if ($updatedData['success']) {
            $updatedOrder = null;
            foreach ($updatedData['data'] as $order) {
                if ($order['order_id'] === $createdOrderId) {
                    $updatedOrder = $order;
                    break;
                }
            }
            
            if ($updatedOrder) {
                echo "✅ Status change reflected in My Orders API\n";
                echo "✅ New status: {$updatedOrder['status']}\n";
                echo "✅ New payment status: {$updatedOrder['paymentStatus']}\n";
                
                if ($updatedOrder['status'] === 'processing') {
                    echo "✅ Order moved from 'To Pay' to 'Processing' tab\n";
                }
            }
        }
    }
    
    // Step 8: Summary
    echo "\n=== AUTHENTICATED ORDER CREATION TEST SUMMARY ===\n";
    
    $allTestsPassed = true;
    $testResults = [
        'order_creation' => $responseData['success'] ?? false,
        'database_storage' => isset($createdOrder) && $createdOrder->exists,
        'user_association' => isset($createdOrder) && $createdOrder->user_id === $customer->id,
        'my_orders_api' => isset($foundOrder) && $foundOrder !== null,
        'admin_dashboard' => $adminOrders->count() > 0,
        'status_sync' => isset($updatedOrder) && $updatedOrder['status'] === 'processing',
    ];
    
    echo "\nTest Results:\n";
    echo "=============\n";
    foreach ($testResults as $test => $passed) {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo "- " . ucwords(str_replace('_', ' ', $test)) . ": {$status}\n";
        if (!$passed) $allTestsPassed = false;
    }
    
    if ($allTestsPassed) {
        echo "\n🎉 ALL TESTS PASSED! 🎉\n\n";
        
        echo "✅ AUTHENTICATED ORDER CREATION: SUCCESS\n";
        echo "✅ USER ASSOCIATION: CORRECT\n";
        echo "✅ MY ORDERS INTEGRATION: WORKING\n";
        echo "✅ ADMIN DASHBOARD SYNC: WORKING\n";
        echo "✅ STATUS SYNCHRONIZATION: WORKING\n";
        
        echo "\n🎯 PRODUCTION STATUS:\n";
        echo "✅ Authenticated customers can place orders\n";
        echo "✅ Orders appear in My Orders immediately\n";
        echo "✅ Orders appear in admin dashboard with correct customer info\n";
        echo "✅ Status changes sync between admin and Flutter\n";
        echo "✅ Customer data is accurate (not Guest User)\n";
        
    } else {
        echo "\n❌ SOME TESTS FAILED\n";
        echo "❌ System needs fixes before production\n";
    }
    
    // Clean up test data
    Order::where('customer_email', 'authenticated@customer.com')->delete();
    
    // Delete the token
    PersonalAccessToken::where('tokenable_id', $customer->id)->delete();
    
    echo "\n🧹 Test data cleaned up\n";
    
    echo "\n📱 FLUTTER APP TESTING:\n";
    echo "Login: authenticated@customer.com / password123\n";
    echo "Expected: Orders from authenticated users appear in My Orders\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
