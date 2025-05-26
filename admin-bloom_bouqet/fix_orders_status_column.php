<?php

/**
 * Script untuk memperbaiki kolom status pada tabel orders
 * Mengubah tipe data agar bisa menerima nilai 'waiting_for_payment'
 */

// Load Laravel environment
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

echo "Mulai proses perbaikan kolom status pada tabel orders...\n";

try {
    // Cek terlebih dahulu tipe data kolom status
    $result = DB::select("SHOW COLUMNS FROM orders WHERE Field = 'status'");
    $statusType = '';
    
    if (!empty($result)) {
        $statusType = $result[0]->Type;
        echo "Tipe data kolom status saat ini: {$statusType}\n";
    } else {
        echo "Kolom status tidak ditemukan!\n";
        exit;
    }
    
    // Ubah kolom status menjadi VARCHAR(50)
    echo "Mengubah kolom status menjadi VARCHAR(50)...\n";
    DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'waiting_for_payment'");
    
    // Periksa apakah kolom payment_status adalah ENUM
    $result = DB::select("SHOW COLUMNS FROM orders WHERE Field = 'payment_status'");
    if (!empty($result)) {
        $paymentStatusType = $result[0]->Type;
        echo "Tipe data kolom payment_status saat ini: {$paymentStatusType}\n";
        
        // Ubah kolom payment_status menjadi VARCHAR(50)
        echo "Mengubah kolom payment_status menjadi VARCHAR(50)...\n";
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }
    
    // Update data yang ada
    echo "Memperbarui data yang mungkin NULL...\n";
    DB::statement("UPDATE orders SET status = 'waiting_for_payment' WHERE status IS NULL OR status = ''");
    DB::statement("UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
    
    echo "Berhasil memperbaiki struktur tabel orders!\n";
    
    // Check existing order status
    $orderCount = DB::table('orders')->count();
    echo "Jumlah total orders: {$orderCount}\n";
    
    $waitingOrdersCount = DB::table('orders')
        ->where('status', 'waiting_for_payment')
        ->count();
    echo "Jumlah orders dengan status 'waiting_for_payment': {$waitingOrdersCount}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    Log::error("Fix orders status column error: " . $e->getMessage());
}

echo "Script selesai.\n"; 