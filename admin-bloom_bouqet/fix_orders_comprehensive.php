<?php

/**
 * Script untuk memperbaiki tabel orders secara menyeluruh
 * Ini akan menangani berbagai masalah yang mungkin terjadi
 */

// Load Laravel environment
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

echo "======== PERBAIKAN TABEL ORDERS SECARA MENYELURUH ========\n";

try {
    // 1. Cek apakah tabel orders ada
    if (!Schema::hasTable('orders')) {
        echo "ERROR: Tabel orders tidak ditemukan!\n";
        exit;
    }
    
    echo "✓ Tabel orders ditemukan.\n";
    
    // 2. Backup tabel orders jika ada data
    $orderCount = DB::table('orders')->count();
    if ($orderCount > 0) {
        echo "Ada {$orderCount} order di database. Membuat backup...\n";
        
        // Buat tabel backup jika belum ada
        if (!Schema::hasTable('orders_backup')) {
            DB::statement('CREATE TABLE orders_backup LIKE orders');
            DB::statement('INSERT INTO orders_backup SELECT * FROM orders');
            echo "✓ Backup tabel orders berhasil dibuat.\n";
        } else {
            echo "Tabel orders_backup sudah ada. Backup baru tidak dibuat.\n";
        }
    } else {
        echo "Tidak ada data orders. Tidak perlu backup.\n";
    }
    
    // 3. Periksa struktur kolom status
    echo "\nMEMERIKSA STRUKTUR KOLOM:\n";
    $columns = DB::select("SHOW COLUMNS FROM orders WHERE Field IN ('status', 'payment_status')");
    
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} (Default: {$column->Default}, Null: {$column->Null})\n";
    }
    
    // 4. Ubah tipe data kolom status dan payment_status menjadi VARCHAR(50)
    echo "\nMEMPERBAIKI KOLOM STATUS:\n";
    
    // DISABLE FOREIGN KEY CHECKS terlebih dahulu
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    // Coba beberapa metode untuk fix kolom status
    try {
        echo "Mengubah kolom status menjadi VARCHAR(50)...\n";
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'waiting_for_payment'");
        echo "✓ Kolom status berhasil diubah.\n";
    } catch (\Exception $e) {
        echo "GAGAL: " . $e->getMessage() . "\n";
        try {
            echo "Mencoba metode alternatif dengan LONGTEXT...\n";
            DB::statement("ALTER TABLE orders MODIFY COLUMN status LONGTEXT");
            echo "✓ Kolom status berhasil diubah menjadi LONGTEXT.\n";
        } catch (\Exception $e2) {
            echo "GAGAL lagi: " . $e2->getMessage() . "\n";
        }
    }
    
    // Fix payment_status column
    try {
        echo "Mengubah kolom payment_status menjadi VARCHAR(50)...\n";
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        echo "✓ Kolom payment_status berhasil diubah.\n";
    } catch (\Exception $e) {
        echo "GAGAL: " . $e->getMessage() . "\n";
        try {
            echo "Mencoba metode alternatif dengan LONGTEXT...\n";
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status LONGTEXT");
            echo "✓ Kolom payment_status berhasil diubah menjadi LONGTEXT.\n";
        } catch (\Exception $e2) {
            echo "GAGAL lagi: " . $e2->getMessage() . "\n";
        }
    }
    
    // 5. Pastikan payment_deadline adalah TIMESTAMP
    try {
        echo "Memperbaiki kolom payment_deadline...\n";
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_deadline TIMESTAMP NULL");
        echo "✓ Kolom payment_deadline berhasil diperbaiki.\n";
    } catch (\Exception $e) {
        echo "GAGAL mengubah payment_deadline: " . $e->getMessage() . "\n";
    }
    
    // 6. Fix is_read column jika ada
    if (Schema::hasColumn('orders', 'is_read')) {
        try {
            echo "Memperbaiki kolom is_read...\n";
            DB::statement("ALTER TABLE orders MODIFY COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
            echo "✓ Kolom is_read berhasil diperbaiki.\n";
        } catch (\Exception $e) {
            echo "GAGAL mengubah is_read: " . $e->getMessage() . "\n";
        }
    }
    
    // 7. Pastikan FK untuk order_items juga fleksibel (jika ada)
    if (Schema::hasTable('order_items')) {
        try {
            echo "Memperbaiki relasi order_items...\n";
            // Hapus FK dulu
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'order_items'
                AND REFERENCED_TABLE_NAME = 'orders'
            ");
            
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE order_items DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                echo "  - Constraint {$constraint->CONSTRAINT_NAME} dihapus.\n";
            }
            
            // Tambahkan FK baru
            DB::statement("ALTER TABLE order_items ADD CONSTRAINT order_items_order_id_foreign 
                          FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE");
            echo "✓ Relasi order_items berhasil diperbaiki.\n";
        } catch (\Exception $e) {
            echo "GAGAL memperbaiki relasi order_items: " . $e->getMessage() . "\n";
        }
    }
    
    // 8. Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
    // 9. Bersihkan data yang ada
    try {
        echo "\nMEMBERSIHKAN DATA:\n";
        DB::statement("UPDATE orders SET status = 'waiting_for_payment' WHERE status IS NULL OR status = ''");
        DB::statement("UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
        echo "✓ Data berhasil dibersihkan.\n";
    } catch (\Exception $e) {
        echo "GAGAL membersihkan data: " . $e->getMessage() . "\n";
    }
    
    // 10. Clear cache dan reload config
    echo "\nMEMUAT ULANG KONFIGURASI:\n";
    try {
        Artisan::call('config:clear');
        echo "✓ Config cache dibersihkan.\n";
        
        Artisan::call('cache:clear');
        echo "✓ Application cache dibersihkan.\n";
        
        Artisan::call('view:clear');
        echo "✓ View cache dibersihkan.\n";
        
        Artisan::call('route:clear');
        echo "✓ Route cache dibersihkan.\n";
    } catch (\Exception $e) {
        echo "GAGAL memuat ulang konfigurasi: " . $e->getMessage() . "\n";
    }
    
    // 11. Verifikasi perubahan
    echo "\nVERIFIKASI PERUBAHAN:\n";
    $columns = DB::select("SHOW COLUMNS FROM orders WHERE Field IN ('status', 'payment_status')");
    
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} (Default: {$column->Default}, Null: {$column->Null})\n";
    }
    
    echo "\nPROSES SELESAI!\n";
    
} catch (\Exception $e) {
    echo "ERROR UTAMA: " . $e->getMessage() . "\n";
    Log::error("Fix orders table error: " . $e->getMessage());
}

echo "============================================================\n"; 