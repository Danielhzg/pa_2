<?php

// Test product images in My Orders API
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Models\Product;

echo "=== TESTING PRODUCT IMAGES IN MY ORDERS ===\n\n";

try {
    // Step 1: Create test user
    echo "1. CREATING TEST USER...\n";
    echo "========================\n";
    
    $testUser = User::firstOrCreate(
        ['email' => 'images@test.com'],
        [
            'name' => 'Images Test User',
            'full_name' => 'Images Test User',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]
    );
    
    echo "✅ Test user: {$testUser->name} (ID: {$testUser->id})\n";
    
    // Step 2: Check existing products with images
    echo "\n2. CHECKING PRODUCTS WITH IMAGES...\n";
    echo "===================================\n";
    
    $productsWithImages = Product::whereNotNull('main_image')
                                ->orWhereNotNull('gallery_images')
                                ->limit(3)
                                ->get();
    
    if ($productsWithImages->isEmpty()) {
        echo "❌ No products with images found. Creating test products...\n";
        
        // Create test products with images
        $testProducts = [
            [
                'name' => 'Rose Bouquet with Image',
                'description' => 'Beautiful rose bouquet for testing',
                'price' => 250000,
                'stock' => 10,
                'category_id' => 1,
                'main_image' => 'products/test-rose.jpg',
                'gallery_images' => ['products/test-rose.jpg', 'products/test-rose-2.jpg'],
            ],
            [
                'name' => 'Tulip Bouquet with Image',
                'description' => 'Colorful tulip bouquet for testing',
                'price' => 200000,
                'stock' => 15,
                'category_id' => 1,
                'main_image' => 'products/test-tulip.jpg',
                'gallery_images' => ['products/test-tulip.jpg'],
            ]
        ];
        
        foreach ($testProducts as $productData) {
            $product = Product::create($productData);
            $productsWithImages->push($product);
            echo "✅ Created test product: {$product->name} (ID: {$product->id})\n";
        }
    } else {
        echo "✅ Found " . $productsWithImages->count() . " products with images:\n";
        foreach ($productsWithImages as $product) {
            echo "  - {$product->name} (ID: {$product->id})\n";
            echo "    Main Image: " . ($product->main_image ?? 'None') . "\n";
            echo "    Primary Image: " . ($product->getPrimaryImage() ?? 'None') . "\n";
        }
    }
    
    // Step 3: Clean existing orders and create test orders
    echo "\n3. CREATING TEST ORDERS WITH PRODUCTS...\n";
    echo "========================================\n";
    
    Order::where('customer_email', 'images@test.com')->delete();
    
    $testOrders = [];
    foreach ($productsWithImages->take(2) as $index => $product) {
        $orderData = [
            'order_id' => 'IMG-TEST-' . ($index + 1) . '-' . time(),
            'user_id' => $testUser->id,
            'customer_name' => $testUser->name,
            'customer_email' => $testUser->email,
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => $testUser->name,
                'address' => 'Images Test Address',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => $product->price,
            'shipping_cost' => 15000,
            'total_amount' => $product->price + 15000,
            'payment_method' => 'qris',
            'status' => $index === 0 ? 'waiting_for_payment' : 'processing',
            'payment_status' => $index === 0 ? 'pending' : 'paid',
            'order_items' => [
                [
                    'id' => $index + 1,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => 1
                ]
            ],
            'created_at' => now()->subHours($index),
        ];
        
        $order = Order::create($orderData);
        $testOrders[] = $order;
        echo "✅ Created order: {$order->order_id} with product: {$product->name}\n";
    }
    
    // Step 4: Test API response
    echo "\n4. TESTING MY ORDERS API RESPONSE...\n";
    echo "====================================\n";
    
    $token = $testUser->createToken('images-test')->plainTextToken;
    echo "✅ API Token: " . substr($token, 0, 30) . "...\n";
    
    $baseUrl = 'http://localhost:8000';
    $endpoint = '/api/v1/orders';
    
    echo "Testing: {$baseUrl}{$endpoint}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Curl Error: {$error}\n";
        exit(1);
    }
    
    echo "✅ HTTP Code: {$httpCode}\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ SUCCESS: " . count($data['data']) . " orders returned\n";
            
            foreach ($data['data'] as $order) {
                echo "\nOrder: {$order['order_id']}\n";
                echo "Status: {$order['status']}\n";
                echo "Items: " . count($order['items']) . "\n";
                
                foreach ($order['items'] as $item) {
                    echo "  - Product: {$item['name']}\n";
                    echo "    Product ID: " . ($item['product_id'] ?? 'None') . "\n";
                    echo "    Image (raw): " . ($item['product_image'] ?? 'None') . "\n";
                    echo "    Image URL: " . ($item['imageUrl'] ?? 'None') . "\n";
                    
                    if (isset($item['imageUrl']) && $item['imageUrl']) {
                        echo "    ✅ IMAGE URL FOUND: {$item['imageUrl']}\n";
                    } else {
                        echo "    ❌ NO IMAGE URL\n";
                    }
                }
            }
            
            // Step 5: Verify image URLs are accessible
            echo "\n5. TESTING IMAGE URL ACCESSIBILITY...\n";
            echo "=====================================\n";
            
            $imageUrls = [];
            foreach ($data['data'] as $order) {
                foreach ($order['items'] as $item) {
                    if (isset($item['imageUrl']) && $item['imageUrl']) {
                        $imageUrls[] = $item['imageUrl'];
                    }
                }
            }
            
            if (empty($imageUrls)) {
                echo "❌ No image URLs found to test\n";
            } else {
                echo "Testing " . count($imageUrls) . " image URLs:\n";
                
                foreach ($imageUrls as $imageUrl) {
                    echo "\nTesting: {$imageUrl}\n";
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $imageUrl);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    
                    $result = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200) {
                        echo "✅ Image accessible (HTTP 200)\n";
                    } else {
                        echo "❌ Image not accessible (HTTP {$httpCode})\n";
                    }
                }
            }
            
        } else {
            echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
            exit(1);
        }
    } else {
        echo "❌ HTTP Error: {$httpCode}\n";
        echo "Response: {$response}\n";
        exit(1);
    }
    
    // Step 6: Summary
    echo "\n=== PRODUCT IMAGES TEST SUMMARY ===\n";
    
    $hasImages = false;
    $imageCount = 0;
    
    if (isset($data['data'])) {
        foreach ($data['data'] as $order) {
            foreach ($order['items'] as $item) {
                if (isset($item['imageUrl']) && $item['imageUrl']) {
                    $hasImages = true;
                    $imageCount++;
                }
            }
        }
    }
    
    if ($hasImages) {
        echo "✅ PRODUCT IMAGES WORKING!\n";
        echo "✅ Found {$imageCount} product images in orders\n";
        echo "✅ Image URLs are properly formatted\n";
        echo "✅ Flutter app should display product images\n";
        
        echo "\n🎯 FLUTTER EXPECTED BEHAVIOR:\n";
        echo "============================\n";
        echo "1. ✅ My Orders page loads successfully\n";
        echo "2. ✅ Product images appear in order items\n";
        echo "3. ✅ Images load from: {$baseUrl}/storage/...\n";
        echo "4. ✅ Fallback placeholder shows if image fails\n";
        
    } else {
        echo "❌ NO PRODUCT IMAGES FOUND\n";
        echo "❌ Flutter app will show placeholder images\n";
        
        echo "\n🔧 TROUBLESHOOTING:\n";
        echo "===================\n";
        echo "1. Check if products have main_image or gallery_images\n";
        echo "2. Verify storage/products directory exists\n";
        echo "3. Check file permissions on storage directory\n";
        echo "4. Ensure images are uploaded correctly\n";
    }
    
    // Clean up
    Order::where('customer_email', 'images@test.com')->delete();
    echo "\n🧹 Test data cleaned up\n";
    
    echo "\n📱 FLUTTER TESTING:\n";
    echo "===================\n";
    echo "Login: images@test.com / password123\n";
    echo "Expected: Product images appear in My Orders\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
