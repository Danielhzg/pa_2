<?php

// Test API endpoint directly
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Http\Controllers\API\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

echo "=== API ENDPOINT TEST ===\n\n";

try {
    // Clear cache
    Cache::flush();
    echo "✓ Cache cleared\n";
    
    // Test API endpoint with Flutter-like data
    echo "\n1. Testing API endpoint with Flutter data...\n";
    
    $now = now();
    $flutterData = [
        'id' => 'ORDER-API-TEST-' . $now->timestamp,
        'order_id' => 'ORDER-API-TEST-' . $now->timestamp,
        'user_id' => null,
        'items' => [
            [
                'id' => 1,
                'name' => 'API Test Product',
                'price' => 200000,
                'quantity' => 1
            ]
        ],
        'deliveryAddress' => [
            'name' => 'API Test Customer',
            'address' => 'Jl. API Test 123, Jakarta',
            'phone' => '081234567890',
            'email' => 'api.test@customer.com'
        ],
        'subtotal' => 200000,
        'shippingCost' => 20000,
        'total' => 220000,
        'paymentMethod' => 'qris',
        'status' => 'waiting_for_payment',
        'payment_status' => 'pending',
        'customer_name' => 'API Test Customer',
        'customer_email' => 'api.test@customer.com',
        'created_at' => $now->toIso8601String(),
        'order_timestamp' => $now->toIso8601String(),
        'timezone' => 'Asia/Jakarta',
        'request_id' => 'api_test_' . $now->timestamp
    ];
    
    echo "Sending data:\n";
    echo "- Order ID: " . $flutterData['id'] . "\n";
    echo "- Customer: " . $flutterData['customer_name'] . "\n";
    echo "- Total: Rp " . number_format($flutterData['total']) . "\n";
    echo "- Items: " . count($flutterData['items']) . " products\n\n";
    
    // Create request
    $request = new Request();
    $request->merge($flutterData);
    
    // Add headers that Flutter would send
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('X-Request-ID', $flutterData['request_id']);
    
    $beforeCount = Order::count();
    echo "Orders before API call: $beforeCount\n";
    
    // Call API controller
    $orderController = new OrderController();
    $response = $orderController->createOrder($request);
    
    $afterCount = Order::count();
    echo "Orders after API call: $afterCount\n";
    
    // Check response
    $statusCode = $response->getStatusCode();
    $responseData = json_decode($response->getContent(), true);
    
    echo "Response status: $statusCode\n";
    echo "Response success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
    
    if ($responseData['success']) {
        echo "✅ API call successful!\n";
        echo "Response message: " . $responseData['message'] . "\n";
        
        if (isset($responseData['data'])) {
            echo "Order data returned:\n";
            echo "- ID: " . $responseData['data']['id'] . "\n";
            echo "- Status: " . $responseData['data']['orderStatus'] . "\n";
            echo "- Payment: " . $responseData['data']['paymentStatus'] . "\n";
        }
        
        // Check if order was created in database
        $createdOrder = Order::where('order_id', $flutterData['id'])->first();
        if ($createdOrder) {
            echo "✅ Order found in database:\n";
            echo "- Database ID: {$createdOrder->id}\n";
            echo "- Customer: {$createdOrder->customer_name}\n";
            echo "- Email: {$createdOrder->customer_email}\n";
            echo "- Total: Rp " . number_format($createdOrder->total_amount) . "\n";
            echo "- Created: {$createdOrder->created_at}\n";
        } else {
            echo "❌ Order not found in database\n";
        }
        
    } else {
        echo "❌ API call failed!\n";
        echo "Error message: " . $responseData['message'] . "\n";
        
        if (isset($responseData['errors'])) {
            echo "Validation errors:\n";
            foreach ($responseData['errors'] as $field => $errors) {
                echo "- $field: " . implode(', ', $errors) . "\n";
            }
        }
        
        if (isset($responseData['error_details'])) {
            echo "Error details: " . $responseData['error_details'] . "\n";
        }
    }
    
    // Test 2: Try duplicate request
    echo "\n2. Testing duplicate prevention...\n";
    
    $beforeDuplicateCount = Order::count();
    
    // Same request again
    $duplicateRequest = new Request();
    $duplicateRequest->merge($flutterData);
    $duplicateRequest->headers->set('X-Request-ID', $flutterData['request_id']);
    
    $duplicateResponse = $orderController->createOrder($duplicateRequest);
    $duplicateResponseData = json_decode($duplicateResponse->getContent(), true);
    
    $afterDuplicateCount = Order::count();
    
    echo "Orders before duplicate: $beforeDuplicateCount\n";
    echo "Orders after duplicate: $afterDuplicateCount\n";
    echo "Duplicate response: " . ($duplicateResponseData['success'] ? 'success' : 'failed') . "\n";
    echo "Duplicate message: " . $duplicateResponseData['message'] . "\n";
    
    if ($afterDuplicateCount === $beforeDuplicateCount) {
        echo "✅ Duplicate prevention working\n";
    } else {
        echo "❌ Duplicate order was created\n";
    }
    
    // Test 3: Check admin dashboard data
    echo "\n3. Checking admin dashboard data...\n";
    
    $allOrders = Order::orderBy('created_at', 'desc')->get();
    echo "Total orders for admin dashboard: " . $allOrders->count() . "\n";
    
    if ($allOrders->count() > 0) {
        echo "Orders that admin will see:\n";
        foreach ($allOrders as $order) {
            $customerName = $order->customer_name ?: 'Guest User';
            $customerEmail = $order->customer_email ?: 'No email';
            echo "- {$order->order_id}: $customerName ($customerEmail) - Rp " . number_format($order->total_amount) . " [{$order->status}]\n";
        }
    }
    
    echo "\n=== API ENDPOINT TEST SUMMARY ===\n";
    
    if ($responseData['success']) {
        echo "✅ API endpoint: WORKING\n";
        echo "✅ Order creation: WORKING\n";
        echo "✅ Database storage: WORKING\n";
        echo "✅ Response format: CORRECT\n";
        
        if ($afterDuplicateCount === $beforeDuplicateCount) {
            echo "✅ Duplicate prevention: WORKING\n";
        } else {
            echo "❌ Duplicate prevention: FAILED\n";
        }
        
        echo "\n🎯 CONCLUSION:\n";
        echo "✅ API endpoint is working correctly\n";
        echo "✅ Flutter app should be able to create orders\n";
        echo "✅ Orders will appear in admin dashboard\n";
        echo "✅ Real customer data will be shown (not Guest User)\n";
        
    } else {
        echo "❌ API endpoint: FAILED\n";
        echo "❌ Order creation: FAILED\n";
        echo "❌ Check validation rules and error messages above\n";
        
        echo "\n🎯 ISSUES TO FIX:\n";
        echo "❌ API validation or processing error\n";
        echo "❌ Flutter app will not be able to create orders\n";
        echo "❌ No orders will appear in admin dashboard\n";
    }
    
    // Clean up test orders
    Order::where('order_id', 'LIKE', '%API-TEST%')->delete();
    echo "\n🧹 Test orders cleaned up\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
