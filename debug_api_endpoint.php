<?php

// Debug API endpoint untuk My Orders
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Route;

echo "=== DEBUGGING API ENDPOINT ===\n\n";

try {
    // Step 1: Check if routes are registered
    echo "1. CHECKING API ROUTES...\n";
    echo "=========================\n";
    
    $routes = Route::getRoutes();
    $apiRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'api/orders') !== false) {
            $methods = implode('|', $route->methods());
            $apiRoutes[] = "{$methods} /{$uri}";
        }
    }
    
    if (empty($apiRoutes)) {
        echo "❌ No API routes found for orders\n";
    } else {
        echo "✅ Found API routes:\n";
        foreach ($apiRoutes as $route) {
            echo "  - {$route}\n";
        }
    }
    
    // Step 2: Check database connection and data
    echo "\n2. CHECKING DATABASE...\n";
    echo "=======================\n";
    
    $totalOrders = Order::count();
    echo "✅ Database connected\n";
    echo "✅ Total orders in database: {$totalOrders}\n";
    
    if ($totalOrders > 0) {
        $recentOrders = Order::orderBy('created_at', 'desc')->limit(3)->get();
        echo "\nRecent orders:\n";
        foreach ($recentOrders as $order) {
            echo "- {$order->order_id}: {$order->customer_name} ({$order->customer_email}) - {$order->status}\n";
        }
    }
    
    // Step 3: Check users
    echo "\n3. CHECKING USERS...\n";
    echo "====================\n";
    
    $totalUsers = User::count();
    echo "✅ Total users: {$totalUsers}\n";
    
    $testUsers = User::whereIn('email', [
        'customer@test.com',
        'authenticated@customer.com', 
        'final@customer.com'
    ])->get();
    
    echo "Test users found:\n";
    foreach ($testUsers as $user) {
        $userOrders = Order::where('customer_email', $user->email)->count();
        echo "- {$user->name} ({$user->email}): {$userOrders} orders\n";
    }
    
    // Step 4: Test API endpoint directly
    echo "\n4. TESTING API ENDPOINT DIRECTLY...\n";
    echo "===================================\n";
    
    // Create a test user if not exists
    $testUser = User::firstOrCreate(
        ['email' => 'debug@test.com'],
        [
            'name' => 'Debug Test User',
            'full_name' => 'Debug Test User',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]
    );
    
    echo "✅ Test user: {$testUser->name} (ID: {$testUser->id})\n";
    
    // Create test order if none exists
    $testOrder = Order::where('customer_email', 'debug@test.com')->first();
    if (!$testOrder) {
        $testOrder = Order::create([
            'order_id' => 'DEBUG-' . time(),
            'user_id' => $testUser->id,
            'customer_name' => $testUser->name,
            'customer_email' => $testUser->email,
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => $testUser->name,
                'address' => 'Debug Test Address',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 100000,
            'shipping_cost' => 10000,
            'total_amount' => 110000,
            'payment_method' => 'qris',
            'status' => 'waiting_for_payment',
            'payment_status' => 'pending',
            'order_items' => [
                [
                    'id' => 1,
                    'product_id' => 1,
                    'name' => 'Debug Test Product',
                    'price' => 100000,
                    'quantity' => 1
                ]
            ],
            'created_at' => now(),
        ]);
        echo "✅ Created test order: {$testOrder->order_id}\n";
    } else {
        echo "✅ Test order exists: {$testOrder->order_id}\n";
    }
    
    // Step 5: Test controller directly
    echo "\n5. TESTING CONTROLLER DIRECTLY...\n";
    echo "=================================\n";
    
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(function () use ($testUser) {
        return $testUser;
    });

    $orderController = new \App\Http\Controllers\API\OrderController();
    
    try {
        $response = $orderController->getUserOrders($request);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        $data = json_decode($content, true);
        
        echo "✅ Controller response status: {$statusCode}\n";
        echo "✅ Response success: " . ($data['success'] ? 'true' : 'false') . "\n";
        
        if ($data['success']) {
            echo "✅ Orders returned: " . count($data['data']) . "\n";
            echo "✅ User info: {$data['user']['name']} ({$data['user']['email']})\n";
            
            if (!empty($data['data'])) {
                echo "\nOrder details:\n";
                foreach ($data['data'] as $order) {
                    echo "- {$order['order_id']}: {$order['status']} | Rp " . number_format($order['total']) . "\n";
                }
            }
        } else {
            echo "❌ Controller error: " . $data['message'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Controller exception: " . $e->getMessage() . "\n";
    }
    
    // Step 6: Check API routes file
    echo "\n6. CHECKING API ROUTES FILE...\n";
    echo "==============================\n";
    
    $apiRoutesFile = 'admin-bloom_bouqet/routes/api.php';
    if (file_exists($apiRoutesFile)) {
        echo "✅ API routes file exists\n";
        
        $routesContent = file_get_contents($apiRoutesFile);
        if (strpos($routesContent, '/orders') !== false) {
            echo "✅ Orders routes found in api.php\n";
        } else {
            echo "❌ Orders routes NOT found in api.php\n";
        }
        
        if (strpos($routesContent, 'getUserOrders') !== false) {
            echo "✅ getUserOrders method found in routes\n";
        } else {
            echo "❌ getUserOrders method NOT found in routes\n";
        }
    } else {
        echo "❌ API routes file not found\n";
    }
    
    // Step 7: Test with curl simulation
    echo "\n7. TESTING WITH CURL SIMULATION...\n";
    echo "==================================\n";
    
    // Create token for testing
    $token = $testUser->createToken('debug-test')->plainTextToken;
    echo "✅ Created API token: " . substr($token, 0, 20) . "...\n";
    
    // Simulate HTTP request
    $baseUrl = 'https://dec8-114-122-41-11.ngrok-free.app';
    $apiUrl = $baseUrl . '/api/orders';

    echo "✅ Testing URL: {$apiUrl}\n";

    // Check if server is running
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);

    try {
        $testResponse = @file_get_contents($baseUrl, false, $context);
        if ($testResponse !== false) {
            echo "✅ ngrok server is running\n";
        } else {
            echo "❌ ngrok server is NOT running\n";
            echo "❌ Please check ngrok tunnel: https://dec8-114-122-41-11.ngrok-free.app\n";
        }
    } catch (Exception $e) {
        echo "❌ Cannot connect to ngrok server\n";
        echo "❌ Please check ngrok tunnel: https://dec8-114-122-41-11.ngrok-free.app\n";
    }
    
    // Step 8: Summary and recommendations
    echo "\n=== DEBUG SUMMARY ===\n";
    
    $issues = [];
    $solutions = [];
    
    if (empty($apiRoutes)) {
        $issues[] = "API routes not registered";
        $solutions[] = "Check routes/api.php file";
    }
    
    if ($totalOrders == 0) {
        $issues[] = "No orders in database";
        $solutions[] = "Create test orders";
    }
    
    if (!isset($data) || !$data['success']) {
        $issues[] = "Controller not working";
        $solutions[] = "Check OrderController implementation";
    }
    
    if (empty($issues)) {
        echo "✅ NO ISSUES FOUND - API should be working\n";
        echo "\n🎯 FLUTTER DEBUGGING STEPS:\n";
        echo "1. Check Flutter API base URL\n";
        echo "2. Verify authentication token\n";
        echo "3. Check network connectivity\n";
        echo "4. Enable debug logging in Flutter\n";
        echo "5. Test with Postman/curl\n";
        
        echo "\n📱 FLUTTER API DETAILS:\n";
        echo "Base URL: https://dec8-114-122-41-11.ngrok-free.app\n";
        echo "Endpoint: /api/orders\n";
        echo "Method: GET\n";
        echo "Headers: Authorization: Bearer {token}\n";
        echo "Test User: debug@test.com / password123\n";
        
    } else {
        echo "❌ ISSUES FOUND:\n";
        foreach ($issues as $i => $issue) {
            echo "- {$issue}\n";
            echo "  Solution: {$solutions[$i]}\n";
        }
    }
    
    // Clean up
    Order::where('customer_email', 'debug@test.com')->delete();
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
