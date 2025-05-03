<?php

// Load Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

echo "Starting product data synchronization...\n";

// Get all products
$products = Product::all();
echo "Found " . count($products) . " products to update.\n";

$updates = 0;

// Update each product
foreach ($products as $product) {
    $changed = false;
    
    // Check if slug is missing, generate it from name
    if (empty($product->slug)) {
        $slug = Str::slug($product->name);
        
        // Make sure the slug is unique
        $count = 1;
        $originalSlug = $slug;
        while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }
        
        $product->slug = $slug;
        $changed = true;
        echo "Generated slug for product {$product->id}: {$slug}\n";
    }
    
    // If image exists but images array is empty/doesn't exist, populate images array
    if (!empty($product->image) && (empty($product->images) || !is_array($product->images))) {
        $product->images = [$product->image];
        $changed = true;
        echo "Converted image to images array for product {$product->id}\n";
    }
    
    // If images field is a string, convert to array
    if (!empty($product->images) && is_string($product->images)) {
        try {
            // Try to decode if it's already JSON
            $imagesArray = json_decode($product->images, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Not valid JSON, assume it's a single image path
                $imagesArray = [$product->images];
            }
            $product->images = $imagesArray;
            $changed = true;
            echo "Converted images string to JSON for product {$product->id}\n";
        } catch (Exception $e) {
            echo "Error converting images for product {$product->id}: " . $e->getMessage() . "\n";
        }
    }
    
    // Set default values for new fields
    if (!isset($product->is_active) || $product->is_active === null) {
        $product->is_active = true;
        $changed = true;
    }
    
    if (!isset($product->is_on_sale) || $product->is_on_sale === null) {
        $product->is_on_sale = false;
        $changed = true;
    }
    
    if (!isset($product->discount) || $product->discount === null) {
        $product->discount = 0;
        $changed = true;
    }
    
    if (!isset($product->rating) || $product->rating === null) {
        $product->rating = 0;
        $changed = true;
    }
    
    if (!isset($product->total_reviews) || $product->total_reviews === null) {
        $product->total_reviews = 0;
        $changed = true;
    }
    
    if ($changed) {
        try {
            $product->save();
            $updates++;
            echo "Updated product {$product->id}: {$product->name}\n";
        } catch (Exception $e) {
            echo "Error updating product {$product->id}: " . $e->getMessage() . "\n";
        }
    }
}

echo "Completed updating {$updates} products out of " . count($products) . " total products.\n";
echo "Product synchronization complete!\n"; 