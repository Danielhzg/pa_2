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
            $table->string('order_id')->unique(); // External order ID for Midtrans
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('shipping_address');
            $table->string('phone_number');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('shipping_cost', 10, 2);
            $table->string('payment_method');
            $table->string('status')->default('pending'); // pending, processing, completed, cancelled
            $table->string('payment_status')->default('pending'); // pending, success, failed, expired
            $table->string('midtrans_token')->nullable();
            $table->string('midtrans_redirect_url')->nullable();
            $table->text('qr_code_data')->nullable(); // Data encoded in the QR code
            $table->string('qr_code_url')->nullable(); // URL to the QR code image if stored
            $table->timestamps();
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
