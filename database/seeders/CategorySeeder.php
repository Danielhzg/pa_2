<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Wisuda',
            'Makanan', 
            'Money',
            'Hampers'
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'username' => $category,  // Changed from 'name' to 'username'
                'slug' => Str::slug($category),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
