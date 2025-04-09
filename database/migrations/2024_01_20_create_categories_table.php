<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the table already exists
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id('category_id'); // Ensure this matches your model
                $table->string('username'); // Changed from 'name' to 'username'
                $table->string('slug')->unique();
                $table->timestamps();
            });

            // Insert default categories
            DB::table('categories')->insert([
                ['username' => 'Wisuda', 'slug' => 'wisuda', 'created_at' => now(), 'updated_at' => now()],
                ['username' => 'Makanan', 'slug' => 'makanan', 'created_at' => now(), 'updated_at' => now()],
                ['username' => 'Money', 'slug' => 'money', 'created_at' => now(), 'updated_at' => now()],
                ['username' => 'Hampers', 'slug' => 'hampers', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
