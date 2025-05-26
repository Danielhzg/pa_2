<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Script untuk membersihkan kolom yang tidak digunakan dari tabel orders
 * setelah migrasi order_items ke kolom JSON selesai.
 * 
 * CATATAN: Backup database sebelum menjalankan skrip ini!
 */

// Konfigurasi database
$config = require __DIR__ . '/config/database.php';
$db = new Capsule;
$db->addConnection($config['connections'][$config['default']]);
$db->setAsGlobal();
$db->bootEloquent();

echo "Memulai pembersihan tabel orders...\n";

try {
    // Menampilkan daftar kolom yang ada di tabel orders
    $columns = Schema::getColumnListing('orders');
    echo "Kolom yang ada di tabel orders:\n";
    foreach ($columns as $i => $column) {
        echo ($i + 1) . ". {$column}\n";
    }
    
    // Daftar kolom yang perlu dipertahankan
    $essentialColumns = [
        'id', 'order_id', 'user_id', 'admin_id', 'shipping_address', 'phone_number',
        'subtotal', 'shipping_cost', 'total_amount', 'status', 'payment_status',
        'payment_method', 'midtrans_token', 'midtrans_redirect_url', 'payment_details',
        'qr_code_data', 'qr_code_url', 'notes', 'order_items', 'paid_at', 'shipped_at',
        'delivered_at', 'cancelled_at', 'created_at', 'updated_at', 'is_read', 'payment_deadline'
    ];
    
    // Menentukan kolom yang dapat dihapus
    $columnsToRemove = array_diff($columns, $essentialColumns);
    
    if (empty($columnsToRemove)) {
        echo "Tidak ditemukan kolom yang dapat dihapus.\n";
    } else {
        echo "\nKolom yang dapat dihapus:\n";
        foreach ($columnsToRemove as $i => $column) {
            echo ($i + 1) . ". {$column}\n";
        }
        
        // Konfirmasi penghapusan
        echo "\nApakah Anda ingin menghapus kolom-kolom ini? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        
        if (trim(strtolower($line)) === 'y') {
            // Hapus kolom yang tidak digunakan
            Schema::table('orders', function (Blueprint $table) use ($columnsToRemove) {
                foreach ($columnsToRemove as $column) {
                    $table->dropColumn($column);
                    echo "Kolom '{$column}' dihapus.\n";
                }
            });
            
            echo "Pembersihan tabel orders selesai!\n";
        } else {
            echo "Pembersihan dibatalkan.\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 