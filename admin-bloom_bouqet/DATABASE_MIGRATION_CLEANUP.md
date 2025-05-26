# Pembersihan Migrasi Database Bloom Bouquet

## Perubahan yang Dilakukan

Sistem migrasi database telah dibersihkan dan dioptimalkan dengan perubahan berikut:

1. **Menghapus Migrasi Duplikat**
   - `2024_05_23_000001_add_is_on_sale_to_products_table.php` dihapus karena field `is_on_sale` sudah ada di migrasi utama pembuatan tabel products
   - `2024_05_23_000002_create_order_items_table.php` dihapus karena order_items sekarang disimpan sebagai JSON di tabel orders

2. **Perbaikan Migrasi**
   - `2023_10_01_000012_create_cache_table.php` diperbarui dengan pengecekan keberadaan tabel
   - `2023_10_01_000013_create_jobs_table.php` diperbarui dengan pengecekan keberadaan tabel dan menghapus pembuatan tabel job_batches dan failed_jobs
   - `2023_10_01_000006_create_orders_table.php` diperbarui dengan kolom order_items yang menyimpan data dalam format JSON

3. **Migrasi Pembersihan Tambahan**
   - Membuat migrasi baru `2023_10_01_999999_cleanup_database_schema.php` yang akan:
     - Menghapus tabel order_items jika masih ada (setelah memigrasikan data ke order_items JSON)
     - Menghapus tabel job_batches dan failed_jobs yang tidak digunakan
     - Menghapus kolom button_text dan button_url dari tabel carousels yang tidak digunakan

## Cara Menjalankan Migrasi

Sekarang migrasi dapat dijalankan dengan aman menggunakan perintah:

```bash
php artisan migrate:fresh
```

Perintah ini akan menjalankan semua migrasi dalam urutan yang benar, termasuk migrasi pembersihan di akhir untuk memastikan skema database final sudah optimal.

Jika hanya ingin menjalankan migrasi pembersihan tanpa membuat ulang database:

```bash
php artisan migrate
```

## Catatan Penting

1. **Backup Database**: Pastikan untuk selalu membuat backup database sebelum menjalankan migrasi ulang.
2. **Migrasi Tanpa Loss Data**: Migrasi pembersihan dirancang untuk memigrasikan data dari tabel order_items ke kolom JSON sebelum menghapus tabel tersebut.
3. **Pengujian**: Selalu uji aplikasi setelah menjalankan migrasi untuk memastikan semuanya berfungsi dengan baik. 