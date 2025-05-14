<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('client_message_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('message');
            $table->boolean('is_from_user')->default(true);
            $table->json('product_images')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->boolean('is_read')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            
            // Add index for faster queries
            $table->index('user_id');
            $table->index('is_from_user');
            $table->index('timestamp');
            
            // Add foreign key if users table exists
            // (optional: bisa dihapus jika sudah yakin users selalu ada)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
}; 