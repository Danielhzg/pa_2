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
use App\Models\OrderItem;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Script untuk menggabungkan tabel order_items ke dalam tabel orders dan menghapus tabel orders_backup.
 * Script ini akan:
 * 1. Memastikan kolom order_items JSON ada di tabel orders
 * 2. Memindahkan semua data dari tabel order_items ke kolom order_items di orders
 * 3. Menghapus order_items_table setelah migrasi selesai
 * 4. Menghapus orders_backup jika ada
 * 
 * CATATAN: Backup database sebelum menjalankan skrip ini!
 */

// Konfigurasi database
$config = require __DIR__ . '/config/database.php';
$db = new Capsule;
$db->addConnection($config['connections'][$config['default']]);
$db->setAsGlobal();
$db->bootEloquent();

echo "Memulai migrasi data dari order_items ke kolom JSON di tabel orders...\n";

try {
    // 1. Memastikan kolom order_items JSON ada di tabel orders
    if (!Schema::hasColumn('orders', 'order_items')) {
        echo "Menambahkan kolom order_items ke tabel orders...\n";
        Schema::table('orders', function (Blueprint $table) {
            $table->json('order_items')->nullable()->after('notes');
        });
    } else {
        echo "Kolom order_items sudah ada di tabel orders.\n";
    }

    // 2. Cek apakah semua order sudah memiliki data di kolom order_items
    $ordersTomigrate = Capsule::table('orders')
        ->whereNull('order_items')
        ->orWhere('order_items', '[]')
        ->orWhere('order_items', '')
        ->get();

    echo "Memigrasikan " . count($ordersTomigrate) . " pesanan yang belum memiliki data order_items...\n";

    foreach ($ordersTomigrate as $order) {
        $orderItems = Capsule::table('order_items')
            ->where('order_id', $order->id)
            ->get()
            ->toArray();

        if (count($orderItems) > 0) {
            // Ubah objek stdClass menjadi array assosiatif untuk JSON yang lebih bersih
            $itemsArray = [];
            foreach ($orderItems as $item) {
                $itemArray = (array) $item;
                // Konversi options jika dalam bentuk string JSON
                if (isset($itemArray['options']) && is_string($itemArray['options'])) {
                    $itemArray['options'] = json_decode($itemArray['options'], true);
                }
                $itemsArray[] = $itemArray;
            }

            // Update order dengan data items
            Capsule::table('orders')
                ->where('id', $order->id)
                ->update(['order_items' => json_encode($itemsArray)]);
            
            echo "Berhasil memigrasikan " . count($itemsArray) . " item untuk pesanan #" . $order->id . "\n";
        } else {
            // Jika tidak ada item, set sebagai array kosong
            Capsule::table('orders')
                ->where('id', $order->id)
                ->update(['order_items' => json_encode([])]);
            
            echo "Tidak ada item untuk pesanan #" . $order->id . ", diatur sebagai array kosong.\n";
        }
    }

    // 3. Validasi migrasi
    echo "Memvalidasi migrasi data...\n";
    $orderWithNullItems = Capsule::table('orders')
        ->whereNull('order_items')
        ->count();

    if ($orderWithNullItems > 0) {
        echo "PERINGATAN: Masih ada " . $orderWithNullItems . " pesanan tanpa data order_items!\n";
    } else {
        echo "Validasi selesai: Semua pesanan telah memiliki data order_items.\n";
    }

    // 4. Hapus tabel orders_backup jika ada
    if (Schema::hasTable('orders_backup')) {
        echo "Menghapus tabel orders_backup...\n";
        Schema::dropIfExists('orders_backup');
        echo "Tabel orders_backup berhasil dihapus.\n";
    } else {
        echo "Tabel orders_backup tidak ditemukan. Tidak ada yang perlu dihapus.\n";
    }

    // 5. Hapus tabel order_items jika semua data telah berhasil dimigrasikan
    if ($orderWithNullItems == 0) {
        echo "\nSemua data telah berhasil dimigrasikan.\n";
        echo "Apakah Anda ingin menghapus tabel order_items? [y/N]: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) === 'y') {
            echo "Menghapus tabel order_items...\n";
            Schema::dropIfExists('order_items');
            echo "Tabel order_items berhasil dihapus.\n";
            
            // Hapus bidang yang tidak digunakan dari tabel orders (opsional)
            // Ini harus dilakukan dengan hati-hati karena mungkin ada kode yang masih menggunakan kolom ini
            echo "Apakah Anda ingin menghapus kolom-kolom yang tidak digunakan dari tabel orders? [y/N]: ";
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                // Tambahkan logika untuk menghapus kolom yang tidak digunakan
                // Contoh: Schema::table('orders', function (Blueprint $table) { $table->dropColumn('unused_column'); });
                echo "Tidak ada kolom yang dihapus. Silakan tinjau secara manual field-field yang tidak digunakan.\n";
            }
        } else {
            echo "Tabel order_items dipertahankan.\n";
        }
    } else {
        echo "\nMasih ada pesanan yang belum dimigrasikan. Tabel order_items tidak akan dihapus.\n";
    }

    echo "\nPembersihan database selesai!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Done!\n"; 