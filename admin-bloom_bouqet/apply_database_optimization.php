<?php

// Load the Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Order;
use App\Models\Carousel;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Script untuk mengoptimasi database Bloom Bouquet:
 * 1. Memigrasikan data order_items ke kolom JSON di tabel orders
 * 2. Menghapus kolom button_text dan button_url dari tabel carousels
 * 3. Menghapus tabel job_batches dan failed_jobs
 * 
 * PENTING: Backup database sebelum menjalankan script ini!
 */

// Konfigurasi database
$config = require __DIR__ . '/config/database.php';
$db = new Capsule;
$db->addConnection($config['connections'][$config['default']]);
$db->setAsGlobal();
$db->bootEloquent();

echo "======================================\n";
echo "  BLOOM BOUQUET DATABASE OPTIMIZATION\n";
echo "======================================\n\n";

try {
    // 1. Memigrasikan data order_items ke kolom JSON di orders
    echo "STEP 1: Migrasi order_items ke JSON\n";
    echo "-----------------------------------\n";

    // 1.1 Memastikan kolom order_items ada di tabel orders
    if (!Schema::hasColumn('orders', 'order_items')) {
        echo "Menambahkan kolom order_items ke tabel orders...\n";
        Schema::table('orders', function (Blueprint $table) {
            $table->json('order_items')->nullable()->comment('JSON encoded order items data');
        });
        echo "✓ Kolom order_items berhasil ditambahkan.\n";
    } else {
        echo "✓ Kolom order_items sudah ada di tabel orders.\n";
    }

    // 1.2 Memigrasikan data dari tabel order_items ke kolom order_items
    if (Schema::hasTable('order_items')) {
        echo "Memigrasikan data dari tabel order_items...\n";
        
        // Dapatkan semua order yang perlu dimigrasikan
        $orders = DB::table('orders')
            ->whereNull('order_items')
            ->orWhere('order_items', '[]')
            ->orWhere('order_items', '')
            ->get();
            
        echo "Ditemukan " . count($orders) . " pesanan untuk dimigrasi.\n";
        
        $migratedCount = 0;
        foreach ($orders as $order) {
            $orderItems = DB::table('order_items')
                ->where('order_id', $order->id)
                ->get();
                
            if (count($orderItems) > 0) {
                // Konversi ke array untuk JSON
                $itemsArray = json_decode(json_encode($orderItems), true);
                
                // Update order dengan data items
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['order_items' => json_encode($itemsArray)]);
                
                $migratedCount++;
                echo ".";
                if ($migratedCount % 50 == 0) {
                    echo " $migratedCount\n";
                }
            }
        }
        
        echo "\n✓ Berhasil memigrasikan data untuk $migratedCount pesanan.\n";
        
        // 1.3 Validasi migrasi data
        $ordersWithNullItems = DB::table('orders')
            ->whereNull('order_items')
            ->count();
            
        if ($ordersWithNullItems > 0) {
            echo "⚠️ Masih ada $ordersWithNullItems pesanan tanpa data order_items.\n";
        } else {
            echo "✓ Validasi selesai: Semua pesanan telah memiliki data order_items.\n";
            
            // 1.4 Tanyakan apakah ingin menghapus tabel order_items
            echo "\nApakah Anda ingin menghapus tabel order_items? [y/N]: ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                echo "Menghapus tabel order_items...\n";
                Schema::dropIfExists('order_items');
                echo "✓ Tabel order_items berhasil dihapus.\n";
            } else {
                echo "Tabel order_items dipertahankan.\n";
            }
        }
    } else {
        echo "✓ Tabel order_items tidak ditemukan, migrasi tidak diperlukan.\n";
    }
    
    // 2. Menghapus kolom button_text dan button_url dari tabel carousels
    echo "\nSTEP 2: Menghapus kolom tidak digunakan di tabel carousels\n";
    echo "------------------------------------------------------\n";
    
    if (Schema::hasTable('carousels')) {
        $columnsToRemove = [];
        
        if (Schema::hasColumn('carousels', 'button_text')) {
            $columnsToRemove[] = 'button_text';
        }
        
        if (Schema::hasColumn('carousels', 'button_url')) {
            $columnsToRemove[] = 'button_url';
        }
        
        if (count($columnsToRemove) > 0) {
            echo "Menghapus kolom: " . implode(', ', $columnsToRemove) . " dari tabel carousels...\n";
            
            // Cek apakah ada data di kolom tersebut
            $hasData = false;
            foreach ($columnsToRemove as $column) {
                $count = DB::table('carousels')
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->count();
                    
                if ($count > 0) {
                    $hasData = true;
                    echo "⚠️ Ditemukan $count baris dengan data di kolom $column.\n";
                }
            }
            
            if ($hasData) {
                echo "Apakah Anda ingin melanjutkan penghapusan kolom? [y/N]: ";
                $handle = fopen("php://stdin", "r");
                $line = fgets($handle);
                if (trim(strtolower($line)) !== 'y') {
                    echo "Pembatalan penghapusan kolom.\n";
                    $columnsToRemove = [];
                }
            }
            
            if (count($columnsToRemove) > 0) {
                Schema::table('carousels', function (Blueprint $table) use ($columnsToRemove) {
                    $table->dropColumn($columnsToRemove);
                });
                echo "✓ Kolom berhasil dihapus dari tabel carousels.\n";
            }
        } else {
            echo "✓ Kolom button_text dan button_url sudah tidak ada di tabel carousels.\n";
        }
    } else {
        echo "⚠️ Tabel carousels tidak ditemukan.\n";
    }
    
    // 3. Menghapus tabel job_batches dan failed_jobs
    echo "\nSTEP 3: Menghapus tabel yang tidak digunakan\n";
    echo "----------------------------------------\n";
    
    $tablesToDrop = ['job_batches', 'failed_jobs'];
    
    foreach ($tablesToDrop as $table) {
        if (Schema::hasTable($table)) {
            echo "Menghapus tabel $table...\n";
            Schema::dropIfExists($table);
            echo "✓ Tabel $table berhasil dihapus.\n";
        } else {
            echo "✓ Tabel $table sudah tidak ada.\n";
        }
    }
    
    echo "\n✅ Optimasi database selesai!\n";
    echo "Silakan baca dokumentasi di DATABASE_OPTIMIZATION.md untuk informasi lebih lanjut.\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Detail: " . $e->getTraceAsString() . "\n";
    exit(1);
} 