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
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('main_image')->nullable()->comment('Main product image displayed in listings');
            $table->json('gallery_images')->nullable()->comment('JSON array of additional product images');
            $table->integer('stock')->default(0);
            $table->integer('category_id')->unsigned();
            $table->integer('admin_id')->unsigned()->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_on_sale')->default(false);
            $table->integer('discount')->default(0);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('category_id');
            $table->index('admin_id');
            $table->index('is_active');
        });
        
        // Add foreign key constraints after table creation
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
                
            $table->foreign('admin_id')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');
        });

        // Set engine to InnoDB
        Schema::getConnection()->statement('ALTER TABLE products ENGINE = InnoDB');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};