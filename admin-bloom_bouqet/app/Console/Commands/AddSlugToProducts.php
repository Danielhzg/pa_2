<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Product;
use Illuminate\Support\Str;

class AddSlugToProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:add-slug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add slug column to products table and populate it with values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Adding slug column to products table...');

        try {
            // Check if the column already exists
            if (!\Schema::hasColumn('products', 'slug')) {
                // Add the column
                \Schema::table('products', function ($table) {
                    $table->string('slug')->nullable()->after('name');
                });
                $this->info('Slug column added successfully.');
            } else {
                $this->info('Slug column already exists.');
            }

            // Update all products with slugs
            $this->info('Generating slugs for existing products...');
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

            $this->info("{$counter} product(s) updated with slugs.");

            // Make the slug column unique if it's not already
            if (!\Schema::hasIndex('products', 'products_slug_unique')) {
                \Schema::table('products', function ($table) {
                    $table->unique('slug');
                });
                $this->info('Added unique constraint to slug column.');
            }

            $this->info('Slug migration completed successfully.');
            
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 