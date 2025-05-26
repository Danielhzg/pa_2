-- Skrip untuk memverifikasi perubahan database setelah migrasi
-- Jalankan dengan: mysql -u username -p database_name < verify_migration.sql

-- Bagian 1: Verifikasi Struktur Tabel Orders
-- ------------------------------------------
-- 1.1 Periksa kolom order_items di tabel orders
SELECT 'VERIFIKASI 1.1: Kolom order_items di tabel orders' AS 'PEMERIKSAAN';
SHOW COLUMNS FROM orders LIKE 'order_items';

-- 1.2 Periksa apakah tabel order_items masih ada (seharusnya tidak ada)
SELECT 'VERIFIKASI 1.2: Tabel order_items sudah dihapus?' AS 'PEMERIKSAAN';
SELECT 
    CASE 
        WHEN COUNT(*) = 0 THEN 'OK - Tabel order_items sudah dihapus'
        ELSE 'PERINGATAN - Tabel order_items masih ada'
    END AS result
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'order_items';

-- 1.3 Periksa apakah tabel orders_backup masih ada (seharusnya tidak ada)
SELECT 'VERIFIKASI 1.3: Tabel orders_backup sudah dihapus?' AS 'PEMERIKSAAN';
SELECT 
    CASE 
        WHEN COUNT(*) = 0 THEN 'OK - Tabel orders_backup sudah dihapus'
        ELSE 'PERINGATAN - Tabel orders_backup masih ada'
    END AS result
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'orders_backup';

-- Bagian 2: Verifikasi Kolom Relasi Baru
-- -------------------------------------
-- 2.1 Periksa kolom order_id di tabel reports
SELECT 'VERIFIKASI 2.1: Kolom order_id di tabel reports' AS 'PEMERIKSAAN';
SHOW COLUMNS FROM reports LIKE 'order_id';

-- 2.2 Periksa kolom admin_id di tabel chat_messages
SELECT 'VERIFIKASI 2.2: Kolom admin_id di tabel chat_messages' AS 'PEMERIKSAAN';
SHOW COLUMNS FROM chat_messages LIKE 'admin_id';

-- 2.3 Periksa kolom order_id di tabel carts
SELECT 'VERIFIKASI 2.3: Kolom order_id di tabel carts' AS 'PEMERIKSAAN';
SHOW COLUMNS FROM carts LIKE 'order_id';

-- Bagian 3: Verifikasi Tipe Data INT UNSIGNED
-- -----------------------------------------
-- 3.1 Periksa tipe data kolom id di tabel utama
SELECT 'VERIFIKASI 3.1: Tipe data kolom id di tabel utama' AS 'PEMERIKSAAN';

SELECT 
    table_name,
    column_name,
    column_type,
    CASE 
        WHEN LOWER(column_type) LIKE '%int%unsigned%' THEN 'OK - INT UNSIGNED'
        ELSE 'PERINGATAN - Bukan INT UNSIGNED'
    END AS status
FROM 
    information_schema.columns
WHERE 
    table_schema = DATABASE() 
    AND table_name IN ('users', 'admins', 'products', 'categories', 'orders', 
                       'reports', 'chat_messages', 'carts', 'carousels', 'favorites')
    AND column_name = 'id';

-- Bagian 4: Verifikasi Foreign Keys
-- -------------------------------
-- 4.1 Periksa relasi foreign key untuk reports.order_id
SELECT 'VERIFIKASI 4.1: Foreign key reports.order_id' AS 'PEMERIKSAAN';
SELECT 
    CONCAT(k.TABLE_NAME, '.', k.COLUMN_NAME, ' -> ', k.REFERENCED_TABLE_NAME, '.', k.REFERENCED_COLUMN_NAME) AS relation,
    'OK - Foreign key terdefinisi' AS status
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
WHERE
    k.REFERENCED_TABLE_SCHEMA = DATABASE() AND
    k.TABLE_NAME = 'reports' AND
    k.COLUMN_NAME = 'order_id';

-- 4.2 Periksa relasi foreign key untuk chat_messages.admin_id
SELECT 'VERIFIKASI 4.2: Foreign key chat_messages.admin_id' AS 'PEMERIKSAAN';
SELECT 
    CONCAT(k.TABLE_NAME, '.', k.COLUMN_NAME, ' -> ', k.REFERENCED_TABLE_NAME, '.', k.REFERENCED_COLUMN_NAME) AS relation,
    'OK - Foreign key terdefinisi' AS status
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
WHERE
    k.REFERENCED_TABLE_SCHEMA = DATABASE() AND
    k.TABLE_NAME = 'chat_messages' AND
    k.COLUMN_NAME = 'admin_id';

-- 4.3 Periksa relasi foreign key untuk carts.order_id
SELECT 'VERIFIKASI 4.3: Foreign key carts.order_id' AS 'PEMERIKSAAN';
SELECT 
    CONCAT(k.TABLE_NAME, '.', k.COLUMN_NAME, ' -> ', k.REFERENCED_TABLE_NAME, '.', k.REFERENCED_COLUMN_NAME) AS relation,
    'OK - Foreign key terdefinisi' AS status
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
WHERE
    k.REFERENCED_TABLE_SCHEMA = DATABASE() AND
    k.TABLE_NAME = 'carts' AND
    k.COLUMN_NAME = 'order_id';

-- Bagian 5: Verifikasi Konsistensi Tipe Data Foreign Keys
-- -----------------------------------------------------
-- 5.1 Periksa konsistensi tipe data foreign keys dengan kolom referensinya
SELECT 'VERIFIKASI 5.1: Konsistensi tipe data foreign keys' AS 'PEMERIKSAAN';
SELECT 
    CONCAT(k.TABLE_NAME, '.', k.COLUMN_NAME, ' -> ', k.REFERENCED_TABLE_NAME, '.', k.REFERENCED_COLUMN_NAME) AS relation,
    c1.COLUMN_TYPE AS foreign_key_type,
    c2.COLUMN_TYPE AS referenced_column_type,
    CASE 
        WHEN c1.COLUMN_TYPE = c2.COLUMN_TYPE THEN 'OK - Tipe data sama'
        ELSE 'PERINGATAN - Tipe data berbeda'
    END AS status
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
    JOIN INFORMATION_SCHEMA.COLUMNS c1 
        ON c1.TABLE_SCHEMA = k.TABLE_SCHEMA 
        AND c1.TABLE_NAME = k.TABLE_NAME 
        AND c1.COLUMN_NAME = k.COLUMN_NAME
    JOIN INFORMATION_SCHEMA.COLUMNS c2 
        ON c2.TABLE_SCHEMA = k.REFERENCED_TABLE_SCHEMA 
        AND c2.TABLE_NAME = k.REFERENCED_TABLE_NAME 
        AND c2.COLUMN_NAME = k.REFERENCED_COLUMN_NAME
WHERE
    k.REFERENCED_TABLE_SCHEMA = DATABASE() AND
    k.REFERENCED_COLUMN_NAME = 'id';

-- Bagian 6: Verifikasi Data Order Items di JSON
-- -------------------------------------------
-- 6.1 Sampel data dari kolom order_items di tabel orders
SELECT 'VERIFIKASI 6.1: Sampel data order_items di tabel orders' AS 'PEMERIKSAAN';
SELECT 
    id, 
    JSON_LENGTH(order_items) AS item_count,
    LEFT(order_items, 200) AS sample_data
FROM 
    orders
WHERE 
    order_items IS NOT NULL AND 
    JSON_LENGTH(order_items) > 0
LIMIT 3;

-- Ringkasan Hasil Migrasi
-- ----------------------
SELECT 'RINGKASAN MIGRASI DATABASE' AS 'KESIMPULAN';
SELECT 
    (SELECT COUNT(*) FROM orders WHERE order_items IS NOT NULL AND JSON_LENGTH(order_items) > 0) AS orders_with_json_items,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'order_items') AS order_items_table_exists,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'orders_backup') AS orders_backup_table_exists,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'reports' AND column_name = 'order_id') AS reports_has_order_id,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'chat_messages' AND column_name = 'admin_id') AS chat_messages_has_admin_id,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'carts' AND column_name = 'order_id') AS carts_has_order_id; 

-- Konfirmasi keberhasilan migrasi
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'order_items') = 0
             AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'order_items') = 1
             AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'reports' AND column_name = 'order_id') = 1
             AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'chat_messages' AND column_name = 'admin_id') = 1
             AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'carts' AND column_name = 'order_id') = 1
        THEN 'SUKSES - Semua perubahan migrasi berhasil diterapkan'
        ELSE 'PERINGATAN - Beberapa perubahan migrasi belum diterapkan, periksa detail di atas'
    END AS migration_status; 