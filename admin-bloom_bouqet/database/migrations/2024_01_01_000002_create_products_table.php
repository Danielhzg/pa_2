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
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable();
            $table->integer('stock')->default(0); // Default stock 
            $table->unsignedBigInteger('category_id')->unsigned(); // Pastikan unsigned 
            $table->timestamps();
            // Foreign key constraint 
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');
        });

        // Tambahkan engine InnoDB (opsional, hanya jika diperlukan)
        Schema::getConnection()->statement('ALTER TABLE products ENGINE = InnoDB');
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};