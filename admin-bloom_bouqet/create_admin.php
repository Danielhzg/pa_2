<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

// Create a new admin with a different username
$admin = new Admin;
$admin->username = 'SuperAdmin';
$admin->email = 'admin@gmail.com';
$admin->password = Hash::make('Adminbloom');
$admin->save();

echo "New admin created successfully!\n";
echo "Username: SuperAdmin\n";
echo "Email: admin@gmail.com\n";
echo "Password: Adminbloom\n"; 