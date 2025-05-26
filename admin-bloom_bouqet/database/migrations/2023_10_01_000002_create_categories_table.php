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
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('admin_id')->unsigned()->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('is_active');
            $table->index('admin_id');
        });
        
        // Add foreign key constraints after table creation
        Schema::table('categories', function (Blueprint $table) {
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
        Schema::dropIfExists('categories');
    }
}; 