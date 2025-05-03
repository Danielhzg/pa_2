<?php

// Load the Laravel environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check if the images column already exists
if (!Schema::hasColumn('products', 'images')) {
    echo "Adding 'images' column to products table...\n";
    
    // Add images column
    DB::statement('ALTER TABLE products ADD COLUMN images JSON AFTER price');
    echo "Column added successfully.\n";
    
    // Migrate data from image to images
    $products = DB::table('products')->whereNotNull('image')->get();
    echo "Found " . count($products) . " products with images to migrate.\n";
    
    foreach ($products as $product) {
        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'images' => json_encode([$product->image])
            ]);
    }
    
    echo "Data migrated successfully.\n";
    
    // Don't drop the image column yet, to be safe
    echo "Image column kept for safety. You can drop it manually once everything is working.\n";
    echo "Run this SQL command later: ALTER TABLE products DROP COLUMN image;\n";
} else {
    echo "The 'images' column already exists in the products table.\n";
}

echo "Done!\n"; 