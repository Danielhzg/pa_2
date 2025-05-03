<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpdateProductSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-product-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing products with slugs based on their names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::whereNull('slug')->orWhere('slug', '')->get();
        $this->info("Found {$products->count()} products without slugs");

        foreach ($products as $product) {
            $slug = Str::slug($product->name);
            
            // Make sure the slug is unique
            $count = 1;
            $originalSlug = $slug;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            
            $product->slug = $slug;
            $product->save();
            
            $this->info("Updated product '{$product->name}' with slug: {$slug}");
        }
        
        $this->info('All products have been updated with slugs');
    }
} 