<?php

/**
 * This script adds a slug column to the products table and updates existing products with slugs.
 * It can be run directly from the command line using: php add_slug_to_products.php
 */

// Load the application
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Product;
use Illuminate\Support\Str;

// Output timestamp for logging
echo "Starting add_slug_to_products.php at " . date('Y-m-d H:i:s') . PHP_EOL;

try {
    // Step 1: Check if the column exists, add it if it doesn't
    if (!Schema::hasColumn('products', 'slug')) {
        echo "Adding slug column to products table..." . PHP_EOL;
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });
        echo "Slug column added." . PHP_EOL;
    } else {
        echo "Slug column already exists." . PHP_EOL;
    }

    // Step 2: Update existing products with slugs
    echo "Updating products with slugs..." . PHP_EOL;
    $products = Product::all();
    $counter = 0;

    foreach ($products as $product) {
        if (empty($product->slug)) {
            $slug = Str::slug($product->name);
            $originalSlug = $slug;
            $count = 1;
            
            // Make sure the slug is unique
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            
            $product->slug = $slug;
            $product->save();
            $counter++;
        }
    }

    echo "{$counter} product(s) updated with slugs." . PHP_EOL;

    // Step 3: Add a unique index if it doesn't exist
    if (!Schema::hasIndex('products', 'products_slug_unique')) {
        echo "Adding unique index to slug column..." . PHP_EOL;
        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug');
        });
        echo "Unique index added." . PHP_EOL;
    } else {
        echo "Unique index already exists." . PHP_EOL;
    }

    echo "Operation completed successfully at " . date('Y-m-d H:i:s') . PHP_EOL;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0); 