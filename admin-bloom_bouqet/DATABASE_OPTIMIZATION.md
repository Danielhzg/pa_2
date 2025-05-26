# Optimasi Database Bloom Bouquet

## Ringkasan Perubahan

Dalam rangka optimalisasi database, kami telah melakukan beberapa perubahan untuk meningkatkan efisiensi dan performa sistem:

1. **Penggabungan Tabel Order_Items ke Tabel Orders**
   - Semua data dari tabel `order_items` sekarang disimpan sebagai JSON dalam kolom `order_items` pada tabel `orders`
   - Mengurangi join query dan meningkatkan performa query pesanan
   - Memudahkan pengelolaan data pesanan dalam satu tabel

2. **Penghapusan Kolom yang Tidak Digunakan pada Tabel Carousel**
   - Kolom `button_text` dan `button_url` dihapus dari tabel `carousels` karena tidak digunakan dalam aplikasi
   - Menyederhanakan struktur tabel dan mengurangi overhead penyimpanan

3. **Penghapusan Tabel yang Tidak Digunakan**
   - Tabel `job_batches` dan `failed_jobs` dihapus karena tidak digunakan dalam aplikasi
   - Konfigurasi queue.php diubah untuk menggunakan driver 'null' untuk failed jobs

## Detail Teknis

### 1. Penggabungan Tabel Order_Items ke Tabel Orders

Sebelumnya, data item pesanan disimpan dalam tabel terpisah `order_items` yang memerlukan join query saat mengambil data pesanan lengkap. Sekarang:

- Kolom `order_items` pada tabel `orders` menyimpan semua data item sebagai JSON
- Model `OrderItem` tetap dipertahankan sebagai model presentasi untuk backward compatibility
- Relasi `items()` di model `Order` tetap berfungsi melalui method `getItemsCollection()`

**Keuntungan:**
- Query lebih cepat karena tidak memerlukan join antar tabel
- Mengurangi kompleksitas database
- Data item tetap terstruktur dalam JSON

### 2. Penghapusan Kolom yang Tidak Digunakan pada Tabel Carousel

Kolom `button_text` dan `button_url` dihapus dari tabel `carousels` karena:
- Tidak digunakan dalam aplikasi mobile
- Tidak ditampilkan dalam UI admin
- Tidak memiliki fungsionalitas terkait

### 3. Penghapusan Tabel yang Tidak Digunakan

Tabel-tabel berikut dihapus karena tidak digunakan dalam aplikasi:
- `job_batches`: Tidak ada penggunaan batch jobs
- `failed_jobs`: Sistem tidak memerlukan pencatatan job yang gagal

Konfigurasi queue telah diubah untuk menggunakan driver 'null' untuk failed jobs.

## Implikasi Pengembangan

1. **Perubahan pada Kode Pengambilan Data Order:**
   - Gunakan `$order->order_items` untuk mengakses item pesanan
   - Untuk kompatibilitas, model `Order` memiliki method `getItemsCollection()` yang mengembalikan koleksi model `OrderItem`

2. **Penggunaan Model OrderItem:**
   - Model `OrderItem` masih dapat digunakan tetapi tidak terkait dengan tabel database
   - Model ini berfungsi sebagai presentasi data dari JSON

3. **Pembuatan Carousel:**
   - Form pembuatan carousel tidak perlu lagi field `button_text` dan `button_url`
   - UI yang ada perlu diperbarui jika masih menampilkan field tersebut

## Kesimpulan

Perubahan-perubahan ini telah diimplementasikan dengan tetap mempertahankan backward compatibility melalui model presentasi dan relasi virtual. Semua fungsi aplikasi tetap berjalan normal dengan performa yang lebih baik dan struktur database yang lebih bersih. 