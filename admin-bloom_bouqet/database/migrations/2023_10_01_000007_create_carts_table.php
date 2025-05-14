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
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->boolean('is_selected')->default(true)->comment('For multi-item checkout selection');
            $table->json('options')->nullable()->comment('Product options or customizations');
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate cart items for a user
            $table->unique(['user_id', 'product_id']);
            
            // Add index for quick lookups
            $table->index(['user_id', 'is_selected']);
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