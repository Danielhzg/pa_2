<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, make sure to drop any existing tables to avoid conflicts
        Schema::dropIfExists('favorite_products');
        Schema::dropIfExists('favorites');
        
        // Create the new favorites table
        Schema::create('favorites', function (Blueprint $table) {
            $table->increments('id'); // Changed from id() to increments() to use int
            $table->integer('user_id')->unsigned(); // Changed to unsigned integer
            $table->integer('product_id')->unsigned(); // Changed to unsigned integer
            $table->timestamps();
            
            // Unique constraint to prevent duplicate favorites
            $table->unique(['user_id', 'product_id']);
            
            // Indexes for better query performance
            $table->index('user_id');
            $table->index('product_id');
            $table->index('created_at');
        });
        
        // Add foreign keys after table creation to prevent constraint issues
        Schema::table('favorites', function (Blueprint $table) {
            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
                
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
}; 