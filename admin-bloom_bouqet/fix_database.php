<?php

/**
 * Script untuk memperbaiki masalah database - terutama tabel orders dan order_items
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

echo "======== PERBAIKAN DATABASE ========\n";

try {
    // 1. Cek apakah tabel order_items ada
    if (!Schema::hasTable('order_items')) {
        echo "Tabel order_items tidak ditemukan. Membuat tabel baru...\n";
        
        // Jalankan migrasi untuk membuat tabel order_items
        $result = Artisan::call('migrate', [
            '--path' => 'database/migrations/2024_06_17_000001_create_order_items_table.php',
            '--force' => true
        ]);
        
        echo $result === 0 
            ? "✓ Tabel order_items berhasil dibuat.\n" 
            : "❌ Gagal membuat tabel order_items.\n";
    } else {
        echo "✓ Tabel order_items sudah ada.\n";
    }
    
    // 2. Memperbaiki kolom status pada tabel orders
    echo "\nMEMPERBAIKI KOLOM STATUS PADA TABEL ORDERS:\n";
    
    // Mematikan cek foreign key sementara
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    try {
        // Ambil informasi tentang kolom status
        $columns = DB::select("SHOW COLUMNS FROM orders WHERE Field IN ('status', 'payment_status')");
        
        foreach ($columns as $column) {
            echo "- {$column->Field}: {$column->Type} (Default: {$column->Default}, Null: {$column->Null})\n";
        }
        
        // Ubah kolom status menjadi VARCHAR(50)
        echo "Mengubah kolom status menjadi VARCHAR(50)...\n";
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'waiting_for_payment'");
        
        // Ubah kolom payment_status menjadi VARCHAR(50)
        echo "Mengubah kolom payment_status menjadi VARCHAR(50)...\n";
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        
        // Update nilai default
        echo "Memperbarui data NULL atau kosong...\n";
        DB::statement("UPDATE orders SET status = 'waiting_for_payment' WHERE status IS NULL OR status = ''");
        DB::statement("UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
        
        echo "✓ Kolom status berhasil diperbarui.\n";
    } catch (\Exception $e) {
        echo "❌ Gagal memperbarui kolom status: " . $e->getMessage() . "\n";
        
        // Coba dengan metode alternatif menggunakan LONGTEXT
        try {
            echo "Mencoba metode alternatif dengan LONGTEXT...\n";
            DB::statement("ALTER TABLE orders MODIFY COLUMN status LONGTEXT");
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status LONGTEXT");
            DB::statement("UPDATE orders SET status = 'waiting_for_payment' WHERE status IS NULL OR status = ''");
            DB::statement("UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
            echo "✓ Kolom status berhasil diperbarui dengan LONGTEXT.\n";
        } catch (\Exception $e2) {
            echo "❌ Gagal lagi: " . $e2->getMessage() . "\n";
        }
    }
    
    // 3. Pastikan user_id dapat menerima NULL
    echo "\nMEMPERBAIKI KOLOM USER_ID:\n";
    
    try {
        DB::statement("ALTER TABLE orders MODIFY COLUMN user_id BIGINT UNSIGNED NULL");
        
        // Remove and recreate foreign key constraint with ON DELETE SET NULL
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'orders' 
            AND COLUMN_NAME = 'user_id' 
            AND REFERENCED_TABLE_NAME = 'users'
        ");
        
        if (!empty($constraints)) {
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE orders DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }
        
        // Add foreign key with ON DELETE SET NULL
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_user_id_foreign 
                      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        
        echo "✓ Kolom user_id berhasil diperbarui untuk mendukung pesanan tanpa user.\n";
    } catch (\Exception $e) {
        echo "❌ Gagal memperbarui kolom user_id: " . $e->getMessage() . "\n";
    }
    
    // Hidupkan kembali cek foreign key
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
    // 4. Clear cache dan reload config
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
        echo "❌ Gagal memuat ulang konfigurasi: " . $e->getMessage() . "\n";
    }
    
    echo "\nPROSES SELESAI! Database telah diperbaiki.\n";
    
} catch (\Exception $e) {
    echo "ERROR UTAMA: " . $e->getMessage() . "\n";
    Log::error("Database fix error: " . $e->getMessage());
}

echo "============================================================\n"; 