<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable();
            $table->text('images')->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_on_sale')->default(false);
            $table->integer('discount')->default(0);
            $table->float('rating')->default(0);
            $table->integer('total_reviews')->default(0);
            $table->text('reviews')->nullable();
            $table->timestamp('featured_until')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
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

    public function down()
    {
        Schema::dropIfExists('products');
    }
};