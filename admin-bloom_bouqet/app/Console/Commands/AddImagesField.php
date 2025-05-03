<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddImagesField extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:add-images-field';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add images JSON field to products table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if the images column already exists
        if (!Schema::hasColumn('products', 'images')) {
            $this->info("Adding 'images' column to products table...");
            
            // Add images column
            DB::statement('ALTER TABLE products ADD COLUMN images JSON AFTER price');
            $this->info("Column added successfully.");
            
            // Migrate data from image to images
            $products = DB::table('products')->whereNotNull('image')->get();
            $this->info("Found " . count($products) . " products with images to migrate.");
            
            $bar = $this->output->createProgressBar(count($products));
            $bar->start();
            
            foreach ($products as $product) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'images' => json_encode([$product->image])
                    ]);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("Data migrated successfully.");
            
            // Don't drop the image column yet, to be safe
            $this->warn("Image column kept for safety. You can drop it manually once everything is working.");
            $this->warn("Run this SQL command later: ALTER TABLE products DROP COLUMN image;");
        } else {
            $this->info("The 'images' column already exists in the products table.");
        }

        $this->info("Done!");
        
        return Command::SUCCESS;
    }
} 