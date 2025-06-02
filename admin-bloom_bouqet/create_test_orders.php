<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;

echo "Checking Orders Table and creating test data if needed...\n";

// Check if orders table exists
if (!Schema::hasTable('orders')) {
    echo "ERROR: Orders table does not exist!\n";
    exit(1);
}

// Check order count
$orderCount = DB::table('orders')->count();
echo "Total orders in database: {$orderCount}\n";

// If we have orders already, exit
if ($orderCount > 0) {
    echo "Orders already exist. No need to create test data.\n";
    exit(0);
}

echo "Creating test orders...\n";

// Get or create a test user
$user = User::firstOrCreate(
    ['email' => 'customer@example.com'],
    [
        'name' => 'Test Customer',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]
);

echo "Using user ID: {$user->id}\n";

// Get some products or create dummy ones
$products = Product::limit(3)->get();
if ($products->isEmpty()) {
    echo "Creating dummy products...\n";
    for ($i = 1; $i <= 3; $i++) {
        $products[] = Product::create([
            'name' => "Test Product {$i}",
            'description' => "This is a test product {$i}",
            'price' => $i * 100000,
            'stock' => 10,
            'category_id' => 1,
        ]);
    }
}

// Create orders with different statuses
$statuses = [
    Order::STATUS_WAITING_FOR_PAYMENT,
    Order::STATUS_PROCESSING,
    Order::STATUS_SHIPPING,
    Order::STATUS_DELIVERED,
    Order::STATUS_CANCELLED
];

$paymentStatuses = ['pending', 'paid', 'failed', 'expired'];

foreach ($statuses as $index => $status) {
    $orderNumber = $index + 1;
    $paymentStatus = $status === Order::STATUS_WAITING_FOR_PAYMENT ? 'pending' : ($status === Order::STATUS_CANCELLED ? 'failed' : 'paid');
    
    // Create order
    $order = new Order();
    $order->user_id = $user->id;
    $order->status = $status;
    $order->payment_status = $paymentStatus;
    $order->payment_method = 'transfer';
    $order->shipping_address = json_encode([
        'name' => 'Test Customer',
        'phone' => '08123456789',
        'address' => 'Jl. Test No. 123',
        'city' => 'Jakarta',
        'postal_code' => '12345',
    ]);
    $order->phone_number = '08123456789';
    $order->subtotal = 300000;
    $order->shipping_cost = 15000;
    $order->total_amount = 315000;
    $order->is_read = false;
    
    // Set timestamps based on status
    $order->created_at = Carbon::now()->subDays(10 - $index);
    $order->updated_at = Carbon::now()->subDays(10 - $index);
    
    if ($status === Order::STATUS_PROCESSING) {
        $order->processing_started_at = Carbon::now()->subDays(8 - $index);
    } elseif ($status === Order::STATUS_SHIPPING) {
        $order->processing_started_at = Carbon::now()->subDays(8 - $index);
        $order->shipped_at = Carbon::now()->subDays(6 - $index);
    } elseif ($status === Order::STATUS_DELIVERED) {
        $order->processing_started_at = Carbon::now()->subDays(8 - $index);
        $order->shipped_at = Carbon::now()->subDays(6 - $index);
        $order->delivered_at = Carbon::now()->subDays(4 - $index);
    } elseif ($status === Order::STATUS_CANCELLED) {
        $order->cancelled_at = Carbon::now()->subDays(9 - $index);
    }
    
    // Save order
    $order->save();
    
    // Create order items
    $orderItems = [];
    foreach ($products as $product) {
        $orderItems[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ];
    }
    
    // Save order items
    $order->order_items = $orderItems;
    $order->save();
    
    echo "Created order #{$order->id} with status {$status}\n";
}

echo "\nDone creating test orders.\n"; 