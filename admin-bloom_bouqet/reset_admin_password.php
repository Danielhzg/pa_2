<?php

// Load the Laravel environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Define admin credentials
$email = 'admin@gmail.com';
$password = 'Adminbloom';

// Check if admin exists
$admin = DB::table('admins')->where('email', $email)->first();

if ($admin) {
    // Update admin password
    DB::table('admins')->where('email', $email)->update([
        'password' => Hash::make($password)
    ]);
    echo "Admin password reset successfully.\n";
} else {
    // Create new admin
    DB::table('admins')->insert([
        'username' => 'NewAdmin',
        'email' => $email,
        'password' => Hash::make($password),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "New admin created successfully.\n";
}

echo "Login credentials:\n";
echo "Email: $email\n";
echo "Password: $password\n"; 