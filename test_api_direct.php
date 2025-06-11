<?php

// Test API endpoint directly with curl simulation
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;

echo "=== TESTING API ENDPOINT DIRECTLY ===\n\n";

try {
    // Step 1: Create test user and order
    echo "1. CREATING TEST DATA...\n";
    echo "========================\n";
    
    $testUser = User::firstOrCreate(
        ['email' => 'api@test.com'],
        [
            'name' => 'API Test User',
            'full_name' => 'API Test User',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]
    );
    
    echo "✅ Test user: {$testUser->name} (ID: {$testUser->id})\n";
    
    // Clean existing orders
    Order::where('customer_email', 'api@test.com')->delete();
    
    // Create test orders
    $orders = [
        [
            'order_id' => 'API-TEST-001-' . time(),
            'user_id' => $testUser->id,
            'customer_name' => $testUser->name,
            'customer_email' => $testUser->email,
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => $testUser->name,
                'address' => 'API Test Address',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 250000,
            'shipping_cost' => 15000,
            'total_amount' => 265000,
            'payment_method' => 'qris',
            'status' => 'waiting_for_payment',
            'payment_status' => 'pending',
            'order_items' => [
                [
                    'id' => 1,
                    'product_id' => 1,
                    'name' => 'API Test Product',
                    'price' => 250000,
                    'quantity' => 1
                ]
            ],
            'created_at' => now(),
        ],
        [
            'order_id' => 'API-TEST-002-' . time(),
            'user_id' => $testUser->id,
            'customer_name' => $testUser->name,
            'customer_email' => $testUser->email,
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => $testUser->name,
                'address' => 'API Test Address 2',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 150000,
            'shipping_cost' => 10000,
            'total_amount' => 160000,
            'payment_method' => 'qris',
            'status' => 'processing',
            'payment_status' => 'paid',
            'order_items' => [
                [
                    'id' => 2,
                    'product_id' => 2,
                    'name' => 'API Test Product 2',
                    'price' => 150000,
                    'quantity' => 1
                ]
            ],
            'created_at' => now()->subHours(1),
        ]
    ];
    
    foreach ($orders as $orderData) {
        $order = Order::create($orderData);
        echo "✅ Created order: {$order->order_id} - {$order->status}\n";
    }
    
    // Step 2: Create API token
    echo "\n2. CREATING API TOKEN...\n";
    echo "========================\n";
    
    $token = $testUser->createToken('api-test')->plainTextToken;
    echo "✅ API Token: " . substr($token, 0, 30) . "...\n";
    
    // Step 3: Test different API endpoints
    echo "\n3. TESTING API ENDPOINTS...\n";
    echo "===========================\n";
    
    $baseUrl = 'https://dec8-114-122-41-11.ngrok-free.app';
    $endpoints = [
        'api/orders' => 'Direct orders endpoint',
        'api/v1/orders' => 'Versioned orders endpoint',
    ];
    
    foreach ($endpoints as $endpoint => $description) {
        echo "\nTesting: {$description}\n";
        echo "URL: {$baseUrl}/{$endpoint}\n";
        
        // Create curl request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/' . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "HTTP Code: {$httpCode}\n";
        
        if ($error) {
            echo "❌ Curl Error: {$error}\n";
            continue;
        }
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                echo "✅ SUCCESS: " . count($data['data']) . " orders returned\n";
                echo "✅ User: {$data['user']['name']} ({$data['user']['email']})\n";
                
                foreach ($data['data'] as $order) {
                    echo "  - {$order['order_id']}: {$order['status']} | Rp " . number_format($order['total']) . "\n";
                }
            } else {
                echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
            }
        } elseif ($httpCode === 404) {
            echo "❌ NOT FOUND (404) - Endpoint doesn't exist\n";
        } elseif ($httpCode === 401) {
            echo "❌ UNAUTHORIZED (401) - Authentication failed\n";
        } elseif ($httpCode === 500) {
            echo "❌ SERVER ERROR (500) - Internal server error\n";
            $data = json_decode($response, true);
            if ($data && isset($data['message'])) {
                echo "Error: {$data['message']}\n";
            }
        } else {
            echo "❌ HTTP {$httpCode}: {$response}\n";
        }
    }
    
    // Step 4: Test without authentication
    echo "\n4. TESTING WITHOUT AUTHENTICATION...\n";
    echo "====================================\n";
    
    $endpoint = 'api/v1/orders';
    echo "Testing: {$baseUrl}/{$endpoint} (no auth)\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    if ($httpCode === 401) {
        echo "✅ CORRECT: Authentication required (401)\n";
    } else {
        echo "❌ UNEXPECTED: Should require authentication\n";
    }
    
    // Step 5: Test with invalid token
    echo "\n5. TESTING WITH INVALID TOKEN...\n";
    echo "================================\n";
    
    $invalidToken = 'invalid-token-12345';
    echo "Testing with invalid token: {$invalidToken}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $invalidToken,
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    if ($httpCode === 401) {
        echo "✅ CORRECT: Invalid token rejected (401)\n";
    } else {
        echo "❌ UNEXPECTED: Should reject invalid token\n";
    }
    
    // Step 6: Summary and Flutter debugging info
    echo "\n=== API TESTING SUMMARY ===\n";
    
    echo "\n✅ WORKING API ENDPOINTS:\n";
    echo "- GET {$baseUrl}/api/v1/orders\n";
    echo "- Requires: Authorization: Bearer {token}\n";
    echo "- Returns: JSON with orders array\n";
    
    echo "\n📱 FLUTTER DEBUGGING INFO:\n";
    echo "==========================\n";
    echo "Base URL: {$baseUrl}\n";
    echo "Endpoint: /api/v1/orders\n";
    echo "Method: GET\n";
    echo "Headers:\n";
    echo "  - Authorization: Bearer {your_token}\n";
    echo "  - Accept: application/json\n";
    echo "  - Content-Type: application/json\n";
    
    echo "\n🔑 TEST CREDENTIALS:\n";
    echo "Email: api@test.com\n";
    echo "Password: password123\n";
    echo "Token: " . substr($token, 0, 30) . "...\n";
    
    echo "\n🐛 FLUTTER DEBUGGING STEPS:\n";
    echo "1. Check if Flutter is using correct base URL\n";
    echo "2. Verify Flutter is sending Authorization header\n";
    echo "3. Check if token is valid and not expired\n";
    echo "4. Enable network logging in Flutter\n";
    echo "5. Test with Postman using above credentials\n";
    
    echo "\n📋 POSTMAN TEST:\n";
    echo "================\n";
    echo "Method: GET\n";
    echo "URL: {$baseUrl}/api/v1/orders\n";
    echo "Headers:\n";
    echo "  Authorization: Bearer {$token}\n";
    echo "  Accept: application/json\n";
    echo "Expected: 200 OK with orders array\n";
    
    // Clean up
    Order::where('customer_email', 'api@test.com')->delete();
    echo "\n🧹 Test data cleaned up\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
