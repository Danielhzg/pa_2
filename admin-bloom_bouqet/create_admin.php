<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// First check if there are any existing admins
$existingAdmins = DB::table('admins')->count();
if ($existingAdmins > 0) {
    echo "Deleting existing admin accounts...\n";
    DB::table('admins')->delete();
}

// Create a new admin with the required credentials
$admin = new Admin;
$admin->username = 'admin';
$admin->email = 'bloombouqet0@gmail.com';

// Use native PHP password_hash for consistent Bcrypt hashing
$bcryptHash = password_hash('adminbloom', PASSWORD_BCRYPT);
echo "Generated Bcrypt hash: " . $bcryptHash . "\n\n";

$admin->password = $bcryptHash;
$admin->save();

echo "New admin created successfully!\n";
echo "Username: admin\n";
echo "Email: bloombouqet0@gmail.com\n";
echo "Password: adminbloom\n";
echo "\nYou can now login with these credentials.\n"; 