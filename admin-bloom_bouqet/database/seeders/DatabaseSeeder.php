<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
    
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a customer user
        User::create([
            'username' => 'TestUser',    
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'full_name' => 'Test User',
            'role' => 'customer'
        ]);
        
        // Create a user with admin role
        User::create([
            'username' => 'AdminUser',    
            'email' => 'admin_user@example.com',
            'password' => bcrypt('password'),
            'full_name' => 'Admin User',
            'role' => 'admin'
        ]);
        
        // Call the AdminSeeder to create default admin user
        // Also call CarouselSeeder to create sample carousel data
        $this->call([
            AdminSeeder::class,
            CarouselSeeder::class,
        ]);
    }
}
