<?php

// Load the Laravel environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Define admin credentials
$username = 'admin';
$email = 'bloombouqet0@gmail.com';
$password = 'adminbloom';

// Generate a properly formatted Bcrypt hash using native PHP function
// This ensures we're using the correct algorithm and format
$bcryptHash = password_hash($password, PASSWORD_BCRYPT);
echo "Generated Bcrypt hash: " . $bcryptHash . "\n\n";

// Delete all existing admins to ensure clean state
DB::table('admins')->delete();
echo "Deleted all existing admin accounts.\n";

// Create new admin with correct credentials
        DB::table('admins')->insert([
    'username' => $username,
            'email' => $email,
    'password' => $bcryptHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

echo "Created new admin account with the following credentials:\n";
echo "Username: $username\n";
echo "Email: $email\n";
echo "Password: $password\n";

// Verify the admin exists with correct credentials
$adminCheck = DB::table('admins')
    ->where('email', $email)
    ->where('username', $username)
    ->first();

if ($adminCheck) {
    echo "\nVerification successful! Admin account created correctly.\n";
    echo "You can now login with these credentials.\n";
} else {
    echo "\nWARNING: Admin account verification failed. Please check your database settings.\n";
}

echo "\nPlease run the following SQL query to check the admin account:\n";
echo "SELECT username, email, password FROM admins WHERE email = 'bloombouqet0@gmail.com';\n";
echo "\nPlease try logging in with these credentials now.\n"; 