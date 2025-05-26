<?php

/**
 * Create Default Admin User Script
 * 
 * This script creates a default admin user with the required fields.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "Creating default admin user...\n";

// Check if admin already exists
$existingAdmin = DB::table('admins')->where('email', 'bloombouqet0@gmail.com')->first();

if ($existingAdmin) {
    echo "Admin user already exists, updating role and is_active...\n";
    
    DB::table('admins')
        ->where('email', 'bloombouqet0@gmail.com')
        ->update([
            'role' => 'super-admin',
            'is_active' => true
        ]);
        
    echo "✓ Admin user updated successfully!\n";
} else {
    echo "Creating new admin user...\n";
    
    // Insert new admin
    DB::table('admins')->insert([
        'username' => 'admin',
        'email' => 'bloombouqet0@gmail.com',
        'password' => Hash::make('password123'),
        'role' => 'super-admin',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "✓ New admin user created successfully!\n";
}

// Verify admin user
$admin = DB::table('admins')->where('email', 'bloombouqet0@gmail.com')->first();

echo "\nAdmin user details (excluding password):\n";
unset($admin->password);
print_r($admin);

echo "\nAdmin creation completed!\n";
echo "You can now log in with:\n";
echo "Email: bloombouqet0@gmail.com\n";
echo "Password: password123\n"; 