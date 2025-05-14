<?php

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== PEMERIKSAAN AKUN ADMIN ===\n\n";

// Ambil semua admin dari database
$admins = DB::table('admins')->get();

if ($admins->isEmpty()) {
    echo "❌ Tidak ada akun admin dalam database!\n";
    exit(1);
}

echo "Ditemukan " . $admins->count() . " akun admin dalam database:\n\n";

foreach ($admins as $admin) {
    echo "ID: {$admin->id}\n";
    echo "Username: {$admin->username}\n";
    echo "Email: {$admin->email}\n";
    echo "Password Hash: {$admin->password}\n";
    
    // Periksa apakah password menggunakan Bcrypt
    if (substr($admin->password, 0, 4) === '$2y$') {
        echo "✓ Password menggunakan algoritma Bcrypt\n";
        
        // Cek apakah password cocok dengan 'adminbloom'
        if (Hash::check('adminbloom', $admin->password)) {
            echo "✓ Password cocok dengan 'adminbloom'\n";
        } else {
            echo "❌ Password TIDAK cocok dengan 'adminbloom'\n";
        }
    } else {
        echo "❌ Password TIDAK menggunakan algoritma Bcrypt!\n";
    }
    
    // Cek apakah akun ini yang diharapkan
    if ($admin->username === 'admin' && $admin->email === 'bloombouqet0@gmail.com') {
        echo "✓ Ini adalah akun admin yang diinginkan\n";
    } else {
        echo "❌ Ini BUKAN akun admin yang diinginkan\n";
    }
    
    echo "\n";
}

echo "=== REKOMENDASI ===\n";
echo "Untuk login, gunakan:\n";
echo "Email: bloombouqet0@gmail.com\n";
echo "Password: adminbloom\n";
echo "\nJika login masih bermasalah, jalankan 'php force_create_admin.php' untuk membuat ulang akun admin.\n"; 