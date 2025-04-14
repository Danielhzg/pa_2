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
            $table->string('title')->nullable(); // Judul carousel
            $table->text('description')->nullable(); // Deskripsi carousel
            $table->string('image'); // Path gambar carousel
            $table->boolean('active')->default(true); // Status aktif
            $table->integer('order')->default(0); // Urutan carousel
            $table->timestamps(); // Timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carousels'); // Hapus tabel jika rollback
    }
};
