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
        Schema::create('carousels', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            
            // Foreign key constraints
            $table->foreign('admin_id')
                  ->references('id')
                  ->on('admins')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carousels');
    }
}; 