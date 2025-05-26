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
        // Drop the order_items table if it exists
        Schema::dropIfExists('order_items');
        
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id'); // Changed from id() to increments() to use int
            $table->string('order_id')->unique()->comment('External order ID for reference (e.g., INV-20241001-001)');
            $table->integer('user_id')->unsigned()->nullable()->comment('User who placed the order, can be null for guest orders'); // Changed to unsigned integer
            $table->integer('admin_id')->unsigned()->nullable()->comment('Admin who processed this order'); // Changed to unsigned integer
            $table->text('shipping_address');
            $table->string('phone_number');
            $table->decimal('subtotal', 10, 2)->comment('Sum of all items before shipping');
            $table->decimal('shipping_cost', 10, 2);
            $table->decimal('total_amount', 10, 2)->comment('Subtotal + shipping cost');
            $table->enum('status', [
                'waiting_for_payment',
                'processing',
                'shipping',
                'delivered',
                'cancelled'
            ])->default('waiting_for_payment');
            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'expired',
                'refunded'
            ])->default('pending');
            $table->string('payment_method');
            $table->string('midtrans_token')->nullable();
            $table->string('midtrans_redirect_url')->nullable();
            $table->text('payment_details')->nullable()->comment('JSON encoded payment details');
            $table->text('qr_code_data')->nullable()->comment('JSON encoded data for QR code');
            $table->string('qr_code_url')->nullable()->comment('URL to the QR code image if stored');
            $table->text('notes')->nullable();
            $table->json('order_items')->comment('JSON array of order items')->default('[]');
            $table->boolean('is_read')->default(false)->comment('Flag to indicate if the order has been read by admin');
            $table->timestamp('payment_deadline')->nullable()->comment('Deadline for payment to be received');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
            // Add indexes for common queries
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
            $table->index('user_id');
            $table->index('admin_id');
        });
        
        // Create the order_items table
        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id')->unsigned();
            $table->integer('product_id')->unsigned()->nullable();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();
            
            // Add indexes
            $table->index('order_id');
            $table->index('product_id');
        });
        
        // Add foreign key constraints after table creation
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
                
            $table->foreign('admin_id')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });
        
        // Add foreign key constraints for order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
                
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
}; 