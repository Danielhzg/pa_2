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
            $table->increments('id');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url');
            $table->boolean('is_active')->default(true);
            $table->integer('admin_id')->unsigned()->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            $table->index('admin_id');
        });

        // Add foreign key after table creation to prevent constraint issues
        Schema::table('carousels', function (Blueprint $table) {
            // Foreign keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
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