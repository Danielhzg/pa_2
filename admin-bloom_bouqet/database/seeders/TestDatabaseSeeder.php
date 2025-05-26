<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin
        $adminId = DB::table('admins')->insertGetId([
            'username' => 'admin',
            'email' => 'admin@bloombouquet.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create user
        $userId = DB::table('users')->insertGetId([
            'username' => 'user1',
            'full_name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create category
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create product
        $productId = DB::table('products')->insertGetId([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'This is a test product',
            'price' => 100.00,
            'main_image' => 'products/default-product.jpg',
            'gallery_images' => json_encode([
                'products/gallery-1.jpg',
                'products/gallery-2.jpg',
                'products/gallery-3.jpg'
            ]),
            'stock' => 10,
            'category_id' => $categoryId,
            'admin_id' => $adminId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create order
        $orderId = DB::table('orders')->insertGetId([
            'order_id' => 'ORD-' . strtoupper(Str::random(8)),
            'user_id' => $userId,
            'admin_id' => $adminId,
            'shipping_address' => 'Test Address',
            'phone_number' => '123456789',
            'subtotal' => 100.00,
            'shipping_cost' => 10.00,
            'total_amount' => 110.00,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'order_items' => json_encode([
                [
                    'product_id' => $productId,
                    'quantity' => 1,
                    'price' => 100.00
                ]
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create cart
        DB::table('carts')->insert([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create favorite
        DB::table('favorites')->insert([
            'user_id' => $userId,
            'product_id' => $productId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create carousel
        DB::table('carousels')->insert([
            'title' => 'Test Carousel',
            'description' => 'This is a test carousel',
            'image_url' => 'test.jpg',
            'admin_id' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create report
        DB::table('reports')->insert([
            'report_number' => 'REP-' . strtoupper(Str::random(8)),
            'report_type' => 'sales',
            'title' => 'Test Report',
            'order_id' => $orderId,
            'admin_id' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create chat message
        DB::table('chat_messages')->insert([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'message' => 'This is a test message',
            'is_from_user' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
} 