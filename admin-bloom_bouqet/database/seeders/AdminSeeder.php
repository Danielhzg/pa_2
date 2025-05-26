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
        $adminExists = Admin::where('username', 'admin')
            ->orWhereRaw('LOWER(email) = ?', ['bloombouqet0@gmail.com'])
            ->first();
            
        if (!$adminExists) {
            // Create default admin
            Admin::create([
                'username' => 'admin',
                'email' => 'bloombouqet0@gmail.com',
                'password' => Hash::make('adminbloom'),
                'role' => 'super-admin',
                'is_active' => true,
            ]);
            
            $this->command->info('Admin user created with username: admin, email: bloombouqet0@gmail.com, password: adminbloom');
        } else {
            // Update admin details
            $adminExists->username = 'admin';
            $adminExists->email = 'bloombouqet0@gmail.com';
            $adminExists->password = Hash::make('adminbloom');
            $adminExists->role = 'super-admin';
            $adminExists->is_active = true;
            $adminExists->save();
            
            $this->command->info('Admin user updated with username: admin, email: bloombouqet0@gmail.com, password: adminbloom');
        }
    }
} 