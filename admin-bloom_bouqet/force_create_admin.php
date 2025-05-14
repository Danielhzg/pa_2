<?php

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

echo "=== MEMBUAT ULANG AKUN ADMIN ===\n\n";

// Pastikan tabel admins ada
if (!Schema::hasTable('admins')) {
    echo "ERROR: Tabel 'admins' tidak ditemukan dalam database.\n";
    echo "Pastikan migrasi database sudah dijalankan.\n";
    exit(1);
}

// Informasi admin baru
$username = 'admin';
$email = 'bloombouqet0@gmail.com';
$password = 'adminbloom';

// Cek apakah admin sudah ada
$existingAdmin = DB::table('admins')->first();

if ($existingAdmin) {
    echo "Admin dengan ID {$existingAdmin->id} ditemukan, perbarui kredensialnya...\n";
    
    // Update admin yang ada
    DB::table('admins')->where('id', $existingAdmin->id)->update([
        'username' => $username,
        'email' => $email, 
        'password' => Hash::make($password),
        'updated_at' => now()
    ]);
    echo "✓ Admin berhasil diperbarui.\n";
} else {
    echo "Tidak ada admin ditemukan, membuat admin baru...\n";
    
    // Masukkan admin ke database
    DB::table('admins')->insert([
        'username' => $username,
        'email' => $email,
        'password' => Hash::make($password),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "✓ Admin baru berhasil dibuat.\n";
}

// Membuat akun admin baru menggunakan cara native Laravel
echo "\nKredensial admin sekarang:\n";
echo "Username: $username\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

// Verifikasi apakah admin berhasil dibuat/diupdate
$admin = DB::table('admins')->where('email', $email)->first();

if ($admin) {
    echo "✓ Akun admin berhasil dikonfigurasi!\n";
    
    // Tampilkan hash password untuk verifikasi
    echo "Hash password dalam database: {$admin->password}\n\n";
    
    // Gunakan Hash::check untuk memverifikasi password
    if (Hash::check($password, $admin->password)) {
        echo "✓ Verifikasi password berhasil - password di-hash dengan benar menggunakan Bcrypt.\n";
    } else {
        echo "❌ Verifikasi password gagal - ada masalah dengan hash password.\n";
    }
} else {
    echo "❌ ERROR: Gagal mengkonfigurasi akun admin. Periksa koneksi database dan coba lagi.\n";
}

echo "\n=== PROSES SELESAI ===\n";
echo "Silakan coba login dengan kredensial di atas.\n"; 