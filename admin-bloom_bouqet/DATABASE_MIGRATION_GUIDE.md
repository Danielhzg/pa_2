# Panduan Migrasi Database

Dokumen ini menjelaskan perubahan struktur database yang dilakukan untuk meningkatkan performa dan memudahkan pengelolaan data pada aplikasi Bloom Bouquet.

## Rangkaian Migrasi

Migrasi database dibuat lebih sederhana dan efisien dengan tiga file utama:

1. **`2024_07_20_000001_merge_order_items_into_orders_table.php`**
   - Menambahkan kolom `order_items` JSON ke tabel orders (jika belum ada)
   - Memindahkan data dari tabel `order_items` ke kolom JSON di tabel `orders`

2. **`2024_07_20_000002_optimize_database_tables_and_relations.php`**
   - Menambahkan kolom `order_id` ke tabel `reports` dan `carts`
   - Menambahkan kolom `admin_id` ke tabel `chat_messages`
   - Mengoptimalkan tipe data kolom ID dari BIGINT menjadi INT UNSIGNED
   - Menyesuaikan tipe data kolom foreign key dengan tabel referensinya
   - Menghapus tabel `orders_backup` yang tidak digunakan lagi

3. **`2024_07_20_000003_drop_order_items_table.php`**
   - Memverifikasi bahwa migrasi data dari `order_items` ke `orders` berhasil
   - Menghapus tabel `order_items` jika migrasi data berhasil

## Cara Menjalankan Migrasi

1. Pastikan Anda memiliki backup database terbaru
2. Jalankan perintah migrasi Laravel:
   ```bash
   php artisan migrate
   ```

## Struktur Baru Tabel Orders

Tabel `orders` sekarang memiliki kolom `order_items` bertipe JSON yang menyimpan semua informasi yang sebelumnya ada di tabel `order_items`.

Format data JSON pada kolom `order_items` adalah sebagai berikut:

```json
[
    {
        "id": 1,
        "order_id": 123,
        "product_id": 456,
        "name": "Nama Produk",
        "price": 150000,
        "quantity": 2,
        "options": {
            "warna": "Merah",
            "ukuran": "M"
        },
        "created_at": "2024-07-01 12:30:00",
        "updated_at": "2024-07-01 12:30:00"
    },
    ...
]
```

## Relasi Baru

1. **Tabel `reports` → `orders`**
   - Kolom `order_id` (INT UNSIGNED) di tabel `reports` mengacu pada `id` di tabel `orders`

2. **Tabel `chat_messages` → `admins`**
   - Kolom `admin_id` (INT UNSIGNED) di tabel `chat_messages` mengacu pada `id` di tabel `admins`

3. **Tabel `carts` → `orders`**
   - Kolom `order_id` (INT UNSIGNED) di tabel `carts` mengacu pada `id` di tabel `orders`

## Optimasi Tipe Data

Semua kolom ID utama (`id`) dan kolom foreign key terkait diubah dari BIGINT menjadi INT UNSIGNED untuk mengoptimalkan penyimpanan dan performa. Perubahan ini berlaku untuk tabel-tabel:
- `users`
- `admins`
- `products`
- `categories`
- `orders`
- `reports`
- `chat_messages`
- `carts`
- `carousels`
- `favorites`

## Kompatibilitas dengan Kode Lama

Model `Order` telah diperbarui untuk mendukung penanganan order items baik dari kolom JSON maupun relasi lama. Beberapa metode yang ada:

- `getOrderItemsAttribute`: Mengambil data order items dari kolom JSON
- `setOrderItemsAttribute`: Menyimpan data order items ke kolom JSON
- `getItemsCollection`: Mengkonversi data JSON menjadi koleksi model OrderItem

Fungsi `items()` tetap dipertahankan untuk backward compatibility tetapi akan mengembalikan collection kosong jika tabel `order_items` sudah dihapus.

## Verifikasi Migrasi

Setelah menjalankan migrasi, verifikasi perubahan dengan:

1. Memeriksa struktur database menggunakan phpMyAdmin, MySQL Workbench, atau tool serupa
2. Menjalankan script verifikasi di `verify_migration.sql` (tersedia di folder root)
3. Memastikan aplikasi masih berjalan dengan normal

## Migrasi Manual (Jika Laravel Artisan Tidak Berfungsi)

Jika terjadi masalah dengan Laravel Artisan, Anda dapat menggunakan skrip SQL manual yang disediakan:

```bash
mysql -u username -p database_name < manual_migration.sql
```

Lihat `MANUAL_MIGRATION_INSTRUCTIONS.md` untuk petunjuk lengkap. 