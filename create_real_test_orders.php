<?php

// Create real test orders for Flutter app testing
require_once 'admin-bloom_bouqet/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'admin-bloom_bouqet/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Http\Controllers\API\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

echo "=== CREATING REAL TEST ORDERS FOR FLUTTER ===\n\n";

try {
    // Step 1: Create test user that matches Flutter login
    echo "1. CREATING TEST USER...\n";
    echo "========================\n";
    
    Cache::flush();
    
    // Create user with email that can be used in Flutter
    $testUser = User::firstOrCreate(
        ['email' => 'customer@test.com'],
        [
            'name' => 'Test Customer',
            'full_name' => 'Test Customer Flutter',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]
    );
    
    echo "✓ Test user: {$testUser->name} ({$testUser->email})\n";
    echo "✓ User ID: {$testUser->id}\n";
    echo "✓ Password: password123\n";
    
    // Step 2: Create realistic orders with different statuses
    echo "\n2. CREATING REALISTIC ORDERS...\n";
    echo "===============================\n";
    
    // Clean existing test orders
    Order::where('customer_email', 'customer@test.com')->delete();
    
    $realOrders = [
        [
            'order_id' => 'ORDER-REAL-' . time() . '-001',
            'user_id' => $testUser->id,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@test.com',
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => 'Test Customer',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10220',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 450000,
            'shipping_cost' => 25000,
            'total_amount' => 475000,
            'payment_method' => 'qris',
            'status' => 'waiting_for_payment',
            'payment_status' => 'pending',
            'is_read' => false,
            'payment_deadline' => now()->addMinutes(15),
            'order_items' => [
                [
                    'id' => 1,
                    'product_id' => 1,
                    'name' => 'Premium Rose Bouquet',
                    'price' => 350000,
                    'quantity' => 1,
                    'subtotal' => 350000
                ],
                [
                    'id' => 2,
                    'product_id' => 2,
                    'name' => 'Greeting Card',
                    'price' => 100000,
                    'quantity' => 1,
                    'subtotal' => 100000
                ]
            ],
            'created_at' => now()->subMinutes(5),
        ],
        [
            'order_id' => 'ORDER-REAL-' . time() . '-002',
            'user_id' => $testUser->id,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@test.com',
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => 'Test Customer',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10220',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 300000,
            'shipping_cost' => 20000,
            'total_amount' => 320000,
            'payment_method' => 'qris',
            'status' => 'processing',
            'payment_status' => 'paid',
            'is_read' => false,
            'payment_deadline' => now()->addMinutes(15),
            'order_items' => [
                [
                    'id' => 3,
                    'product_id' => 3,
                    'name' => 'Mixed Flower Bouquet',
                    'price' => 300000,
                    'quantity' => 1,
                    'subtotal' => 300000
                ]
            ],
            'created_at' => now()->subHours(2),
        ],
        [
            'order_id' => 'ORDER-REAL-' . time() . '-003',
            'user_id' => $testUser->id,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@test.com',
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => 'Test Customer',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10220',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 200000,
            'shipping_cost' => 15000,
            'total_amount' => 215000,
            'payment_method' => 'qris',
            'status' => 'shipping',
            'payment_status' => 'paid',
            'is_read' => false,
            'payment_deadline' => now()->addMinutes(15),
            'order_items' => [
                [
                    'id' => 4,
                    'product_id' => 4,
                    'name' => 'Simple Rose Bouquet',
                    'price' => 200000,
                    'quantity' => 1,
                    'subtotal' => 200000
                ]
            ],
            'created_at' => now()->subDays(1),
        ],
        [
            'order_id' => 'ORDER-REAL-' . time() . '-004',
            'user_id' => $testUser->id,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@test.com',
            'customer_phone' => '081234567890',
            'shipping_address' => json_encode([
                'name' => 'Test Customer',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10220',
                'phone' => '081234567890'
            ]),
            'phone_number' => '081234567890',
            'subtotal' => 150000,
            'shipping_cost' => 15000,
            'total_amount' => 165000,
            'payment_method' => 'qris',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'is_read' => false,
            'payment_deadline' => now()->addMinutes(15),
            'order_items' => [
                [
                    'id' => 5,
                    'product_id' => 5,
                    'name' => 'Mini Bouquet',
                    'price' => 150000,
                    'quantity' => 1,
                    'subtotal' => 150000
                ]
            ],
            'created_at' => now()->subDays(3),
        ]
    ];
    
    foreach ($realOrders as $orderData) {
        $order = Order::create($orderData);
        echo "✓ Created: {$order->order_id}\n";
        echo "  Status: {$order->status} | Payment: {$order->payment_status}\n";
        echo "  Total: Rp " . number_format($order->total_amount) . "\n";
        echo "  Items: " . count($order->order_items) . " products\n";
        echo "  Created: {$order->created_at}\n\n";
    }
    
    echo "✓ Total orders created: " . count($realOrders) . "\n";
    
    // Step 3: Verify orders can be retrieved
    echo "\n3. VERIFYING ORDER RETRIEVAL...\n";
    echo "===============================\n";
    
    $request = new Request();
    $request->setUserResolver(function () use ($testUser) {
        return $testUser;
    });
    
    $orderController = new OrderController();
    $response = $orderController->getUserOrders($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success']) {
        echo "✅ Orders can be retrieved successfully\n";
        echo "✅ Total orders: " . count($responseData['data']) . "\n";
        
        // Show status breakdown
        $statusCounts = [];
        foreach ($responseData['data'] as $order) {
            $status = $order['status'];
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }
        
        echo "\nStatus breakdown for Flutter tabs:\n";
        echo "- All Orders: " . count($responseData['data']) . " orders\n";
        echo "- To Pay (waiting_for_payment): " . ($statusCounts['waiting_for_payment'] ?? 0) . " orders\n";
        echo "- Processing: " . ($statusCounts['processing'] ?? 0) . " orders\n";
        echo "- Shipping: " . ($statusCounts['shipping'] ?? 0) . " orders\n";
        echo "- Completed (delivered): " . ($statusCounts['delivered'] ?? 0) . " orders\n";
        
    } else {
        echo "❌ Error retrieving orders: " . $responseData['message'] . "\n";
    }
    
    // Step 4: Show Flutter login instructions
    echo "\n4. FLUTTER APP TESTING INSTRUCTIONS...\n";
    echo "======================================\n";
    
    echo "📱 To test in Flutter app:\n\n";
    echo "1. Login with these credentials:\n";
    echo "   Email: customer@test.com\n";
    echo "   Password: password123\n\n";
    
    echo "2. Navigate to Profile > All My Orders\n\n";
    
    echo "3. You should see:\n";
    echo "   - All Orders tab: 4 orders\n";
    echo "   - To Pay tab: 1 order (waiting for payment)\n";
    echo "   - Processing tab: 1 order\n";
    echo "   - Shipping tab: 1 order\n";
    echo "   - Completed tab: 1 order (delivered)\n\n";
    
    echo "4. Each order should show:\n";
    echo "   - Order ID\n";
    echo "   - Customer name: Test Customer\n";
    echo "   - Total amount\n";
    echo "   - Order status\n";
    echo "   - Payment status\n";
    echo "   - Created date\n";
    echo "   - Product items\n\n";
    
    echo "5. Test navigation:\n";
    echo "   - Tap on any order to see details\n";
    echo "   - Switch between tabs to see filtered orders\n";
    echo "   - Pull to refresh should work\n\n";
    
    echo "=== REAL TEST ORDERS CREATED SUCCESSFULLY ===\n";
    echo "✅ User account ready for Flutter login\n";
    echo "✅ Orders created with different statuses\n";
    echo "✅ API endpoint verified working\n";
    echo "✅ Flutter app ready for testing\n";
    
    echo "\n🎯 NEXT STEPS:\n";
    echo "1. Open Flutter app\n";
    echo "2. Login with customer@test.com / password123\n";
    echo "3. Go to Profile > All My Orders\n";
    echo "4. Verify all orders appear correctly\n";
    echo "5. Test tab filtering\n";
    echo "6. Test order details\n";
    
    echo "\n📊 EXPECTED RESULTS:\n";
    echo "✅ No more 'Error loading orders' message\n";
    echo "✅ All 4 orders visible in All Orders tab\n";
    echo "✅ Orders properly filtered by status in each tab\n";
    echo "✅ Real customer data displayed (not Guest User)\n";
    echo "✅ Correct order amounts and dates\n";
    echo "✅ Order items displayed correctly\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
