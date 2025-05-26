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
        Schema::create('carts', function (Blueprint $table) {
            $table->increments('id'); // Changed from id() to increments() to use int
            $table->integer('user_id')->unsigned(); // Changed to unsigned integer
            $table->integer('product_id')->unsigned(); // Changed to unsigned integer
            $table->integer('order_id')->unsigned()->nullable(); // Changed to unsigned integer
            $table->integer('quantity')->unsigned()->default(1);
            $table->boolean('is_selected')->default(true)->comment('For multi-item checkout selection');
            $table->json('options')->nullable()->comment('Product options or customizations');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate cart items for a user
            $table->unique(['user_id', 'product_id']);
            
            // Indexes
            $table->index('user_id');
            $table->index('product_id');
            $table->index('order_id');
            $table->index(['user_id', 'is_selected']);
        });
        
        // Add foreign keys after table creation to prevent constraint issues
        Schema::table('carts', function (Blueprint $table) {
            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
                
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
                
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
}; 