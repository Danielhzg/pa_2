# Instruksi Migrasi Database Manual

Dokumen ini memberikan panduan untuk melakukan migrasi database secara manual jika metode artisan (`php artisan migrate`) tidak berfungsi.

## Opsi 1: Menggunakan Laravel Migration

Jika Laravel dan artisan berfungsi dengan baik, jalankan perintah berikut dari direktori root aplikasi:

```bash
php artisan migrate
```

Laravel akan otomatis menjalankan migrasi yang belum dijalankan sebelumnya dalam urutan yang benar:

1. `2024_07_20_000001_merge_order_items_into_orders_table.php`
2. `2024_07_20_000002_optimize_database_tables_and_relations.php`
3. `2024_07_20_000003_drop_order_items_table.php`

## Opsi 2: Menggunakan SQL Script Manual

Jika Laravel migration tidak berfungsi, gunakan skrip SQL manual yang telah disediakan:

1. Buka tool database Anda (phpMyAdmin, MySQL Workbench, dll.)
2. Pilih database Bloom Bouquet
3. Import atau jalankan skrip SQL `manual_migration.sql`

Atau, jika menggunakan command line:

```bash
mysql -u username -p database_name < manual_migration.sql
```

Ganti `username` dan `database_name` dengan kredensial database Anda.

## Verifikasi Migrasi

Setelah migrasi selesai, verifikasi perubahan dengan menjalankan skrip `verify_migration.sql`:

```bash
mysql -u username -p database_name < verify_migration.sql
```

## Perubahan yang Diterapkan

Migrasi ini melakukan perubahan berikut:

1. Menambahkan kolom `order_items` (JSON) ke tabel `orders`
2. Memindahkan data dari tabel `order_items` ke kolom JSON di `orders`
3. Menambahkan kolom `order_id` ke tabel `reports` dan `carts`
4. Menambahkan kolom `admin_id` ke tabel `chat_messages`
5. Mengubah tipe data kolom `id` di tabel utama menjadi INT UNSIGNED untuk optimasi
6. Menyesuaikan tipe data kolom foreign key untuk konsistensi referensi
7. Menghapus tabel `order_items` (setelah data berhasil dimigrasikan)
8. Menghapus tabel `orders_backup` dan kolom-kolom yang tidak digunakan

## Jika Ada Masalah

Jika terjadi masalah selama migrasi:

1. Restore database dari backup terbaru jika tersedia
2. Jalankan hanya bagian tertentu dari skrip SQL manual yang diperlukan
3. Periksa log untuk informasi error yang lebih detail
4. Pastikan semua relasi tetap konsisten setelah perubahan

## Informasi Tambahan

Lihat file `DATABASE_MIGRATION_GUIDE.md` untuk deskripsi lebih lengkap tentang perubahan database ini.