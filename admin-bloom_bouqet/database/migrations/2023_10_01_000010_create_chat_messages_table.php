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
            $table->increments('id'); // Changed from id() to increments() to use int
            $table->string('client_message_id')->nullable();
            $table->integer('user_id')->unsigned()->nullable(); // Changed to unsigned integer
            $table->integer('admin_id')->unsigned()->nullable(); // Changed to unsigned integer
            $table->text('message');
            $table->boolean('is_from_user')->default(true);
            $table->json('product_images')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->boolean('is_read')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('admin_id');
            $table->index('is_from_user');
            $table->index('timestamp');
            $table->index('is_read');
            $table->index('status');
        });
        
        // Add foreign keys after table creation to prevent constraint issues
        Schema::table('chat_messages', function (Blueprint $table) {
            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
                
            $table->foreign('admin_id')
                ->references('id')
                ->on('admins')
                ->cascadeOnDelete();
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