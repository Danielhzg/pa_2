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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique()->comment('External order ID for reference (e.g., INV-20241001-001)');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete()->comment('Admin who processed this order');
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
            $table->json('order_items')->nullable()->comment('JSON encoded order items data');
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}; 