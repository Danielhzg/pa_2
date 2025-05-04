<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if admin already exists with either email or username
        $adminExists = Admin::where('username', 'Admin')
            ->orWhereRaw('LOWER(email) = ?', ['admin@gmail.com'])
            ->first();
            
        if (!$adminExists) {
            // Create default admin
            Admin::create([
                'username' => 'Admin',
                'email' => 'Admin@gmail.com',
                'password' => Hash::make('adminbloom'),
            ]);
            
            $this->command->info('Admin user created with username: Admin, email: Admin@gmail.com, password: adminbloom');
        } else {
            // Update admin details
            $adminExists->email = 'Admin@gmail.com';
            $adminExists->password = Hash::make('adminbloom');
            $adminExists->save();
            
            $this->command->info('Admin user updated with email: Admin@gmail.com, password: adminbloom');
        }
    }
} 