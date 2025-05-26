# Panduan Migrasi Tabel Orders dan Order Items

Dokumen ini menjelaskan langkah-langkah untuk menyatukan tabel `order_items` ke dalam tabel `orders` menggunakan kolom JSON, dan menghapus tabel `orders_backup` yang sudah tidak digunakan.

## Latar Belakang

Untuk meningkatkan performa dan menyederhanakan skema database, kita akan menggabungkan data `order_items` ke dalam kolom JSON di tabel `orders`. Ini memiliki beberapa keuntungan:

1. Mengurangi join query yang diperlukan untuk mendapatkan item pesanan
2. Menyederhanakan struktur database
3. Memudahkan backup dan pemulihan data
4. Mempertahankan fleksibilitas dengan tetap mendukung relasi lama untuk kompatibilitas

## Pra-syarat

- Backup database sebelum menjalankan migrasi!
- PHP 7.4+
- Laravel 8+

## Langkah-langkah Migrasi

### 1. Backup Database

```bash
php artisan db:backup
# Atau gunakan metode backup database lain yang Anda gunakan
```

### 2. Jalankan Skrip Migrasi

```bash
php merge_order_items.php
```

Skrip ini akan:
- Memastikan kolom `order_items` (JSON) sudah ada di tabel `orders`
- Memindahkan data dari tabel `order_items` ke kolom JSON di tabel `orders`
- Memvalidasi migrasi data
- Menghapus tabel `orders_backup` jika ada
- Memberikan opsi untuk menghapus tabel `order_items` setelah migrasi berhasil

### 3. Bersihkan Kolom yang Tidak Digunakan (Opsional)

```bash
php clean_orders_table.php
```

Skrip ini akan:
- Menampilkan daftar kolom di tabel `orders`
- Mengidentifikasi kolom yang tidak lagi digunakan
- Memberikan opsi untuk menghapus kolom-kolom tersebut

## Perubahan Kode

Perubahan utama pada kode aplikasi telah dilakukan pada model `Order`. Model ini sekarang mendukung kedua metode untuk mendapatkan item pesanan:

1. Melalui kolom JSON `order_items` (metode baru)
2. Melalui relasi `items()` (metode lama, untuk kompatibilitas)

### Contoh Penggunaan

```php
// Mendapatkan item dengan metode baru (JSON)
$order = Order::find(1);
$items = $order->order_items; // Mengembalikan array dari data JSON

// Mendapatkan item dengan metode lama (relasi)
$items = $order->items()->get(); // Mengembalikan koleksi OrderItem

// Mendapatkan koleksi model dari data JSON
$itemsCollection = $order->getItemsCollection();
```

## Setelah Migrasi

Setelah migrasi berhasil dilakukan, beberapa hal yang perlu diperhatikan:

1. Aplikasi akan tetap berfungsi dengan baik karena implementasi backward compatibility
2. Data yang baru akan disimpan di kolom JSON `order_items`
3. Aplikasi akan secara otomatis menggunakan data dari kolom JSON jika tersedia

## Rollback (Jika Diperlukan)

Jika terjadi masalah, gunakan backup database yang telah dibuat sebelumnya untuk mengembalikan data ke kondisi awal.

## Penutup

Migrasi ini telah dirancang untuk berjalan mulus tanpa mengganggu fungsionalitas aplikasi yang ada. Jika ada pertanyaan atau kendala selama proses migrasi, silakan hubungi tim pengembangan. 