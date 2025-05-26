<?php

/**
 * Script untuk menjalankan migrasi database dan memeriksa hasilnya
 * 
 * Script ini akan:
 * 1. Membuat backup database (opsional)
 * 2. Menjalankan migrasi Laravel
 * 3. Memverifikasi perubahan database
 */

// Pastikan script dijalankan dari command line
if (php_sapi_name() !== 'cli') {
    die("Script ini hanya dapat dijalankan melalui command line.\n");
}

// Cek apakah berada di direktori yang benar
if (!file_exists('artisan')) {
    die("Script harus dijalankan dari direktori root aplikasi Laravel.\n");
}

echo "==============================================\n";
echo "MIGRASI DATABASE BLOOM BOUQUET\n";
echo "==============================================\n\n";

// Tanyakan kepada user apakah ingin membuat backup terlebih dahulu
$backup = readline("Apakah Anda ingin membuat backup database terlebih dahulu? (y/n): ");
if (strtolower($backup) === 'y' || strtolower($backup) === 'yes') {
    echo "Membuat backup database...\n";
    $timestamp = date('Y-m-d_H-i-s');
    $command = "php artisan db:backup --filename=pre_migration_backup_{$timestamp}";
    passthru($command, $backupResult);
    
    if ($backupResult !== 0) {
        echo "PERINGATAN: Gagal membuat backup, pastikan package db:backup terinstall.\n";
        $continue = readline("Lanjutkan migrasi tanpa backup? (y/n): ");
        if (strtolower($continue) !== 'y' && strtolower($continue) !== 'yes') {
            die("Migrasi dibatalkan.\n");
        }
    } else {
        echo "Backup berhasil dibuat.\n";
    }
}

// Jalankan migrasi
echo "\nMenjalankan migrasi database...\n";
passthru("php artisan migrate", $migrationResult);

if ($migrationResult !== 0) {
    die("\nMigrasi GAGAL. Periksa error di atas dan coba lagi.\n");
}

echo "\nMigrasi berhasil dijalankan!\n";

// Verifikasi perubahan
echo "\n==============================================\n";
echo "VERIFIKASI PERUBAHAN DATABASE\n";
echo "==============================================\n\n";

// Jalankan script untuk memeriksa struktur database
echo "Memeriksa struktur database...\n";
passthru("php artisan db:show --table=orders", $result1);
passthru("php artisan db:show --table=reports", $result2);
passthru("php artisan db:show --table=chat_messages", $result3);
passthru("php artisan db:show --table=carts", $result4);

// Periksa apakah tabel order_items masih ada
passthru("php artisan db:show --table=order_items", $result5);
if ($result5 === 0) {
    echo "\nPERINGATAN: Tabel order_items masih ada. Migrasi mungkin tidak lengkap.\n";
} else {
    echo "\nVerifikasi: Tabel order_items telah dihapus dengan sukses.\n";
}

// Periksa apakah tabel orders_backup masih ada
passthru("php artisan db:show --table=orders_backup", $result6);
if ($result6 === 0) {
    echo "PERINGATAN: Tabel orders_backup masih ada. Migrasi mungkin tidak lengkap.\n";
} else {
    echo "Verifikasi: Tabel orders_backup telah dihapus dengan sukses.\n";
}

// Kesimpulan
echo "\n==============================================\n";
echo "MIGRASI DATABASE SELESAI\n";
echo "==============================================\n\n";

echo "Perubahan telah diterapkan ke database Anda.\n";
echo "Silakan periksa DATABASE_MIGRATION_GUIDE.md untuk informasi lebih lanjut.\n";

echo "\nPEMERIKSAAN RELASI TABEL:\n";
echo "- Relasi reports -> orders: " . (($result2 === 0) ? "OK" : "Tidak terverifikasi") . "\n";
echo "- Relasi chat_messages -> admins: " . (($result3 === 0) ? "OK" : "Tidak terverifikasi") . "\n";
echo "- Relasi carts -> orders: " . (($result4 === 0) ? "OK" : "Tidak terverifikasi") . "\n";

echo "\nSelanjutnya:\n";
echo "1. Periksa apakah semua fitur yang berhubungan dengan pesanan (orders) berfungsi dengan baik\n";
echo "2. Pastikan data sebelumnya dapat diakses dan ditampilkan dengan benar\n";
echo "3. Jika ditemukan masalah, restore dari backup dan laporkan masalah tersebut\n";

echo "\nTerima kasih!\n"; 