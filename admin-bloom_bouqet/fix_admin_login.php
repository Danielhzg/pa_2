<?php

// Load the Laravel environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Define admin credentials
$email = 'Admin@gmail.com';
$password = 'adminbloom';

// Check if admin exists
$admin = DB::table('admins')->where('email', 'admin@gmail.com')->first();

if ($admin) {
    // Update existing admin
    DB::table('admins')->where('email', 'admin@gmail.com')->update([
        'email' => $email,
        'password' => Hash::make($password),
        'updated_at' => now()
    ]);
    echo "Admin updated successfully with new credentials.\n";
} else {
    // Try to find admin with the new email
    $adminWithNewEmail = DB::table('admins')->where('email', $email)->first();
    
    if ($adminWithNewEmail) {
        // Update just the password
        DB::table('admins')->where('email', $email)->update([
            'password' => Hash::make($password),
            'updated_at' => now()
        ]);
        echo "Admin password updated successfully.\n";
    } else {
        // Create new admin
        DB::table('admins')->insert([
            'username' => 'Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "New admin created successfully.\n";
    }
}

// Also update any admin account (as a fallback)
DB::table('admins')->update([
    'password' => Hash::make($password),
    'updated_at' => now()
]);

echo "Login credentials have been set to:\n";
echo "Email: $email\n";
echo "Password: $password\n";
echo "\nPlease try logging in with these credentials now.\n"; 