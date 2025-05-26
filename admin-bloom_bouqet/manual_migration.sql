-- Script SQL untuk menerapkan migrasi database secara manual
-- Jalankan dengan: mysql -u username -p database_name < manual_migration.sql

-- ============================================================================
-- BAGIAN 1: MERGE ORDER ITEMS INTO ORDERS TABLE
-- ============================================================================

-- PERHATIAN: Backup database Anda sebelum menjalankan script ini!

-- 1.1 Pastikan tabel orders memiliki kolom order_items
SET @columnExists = 0;
SELECT COUNT(*) INTO @columnExists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'order_items';

SET @sql = IF(@columnExists = 0, 
    'ALTER TABLE orders ADD COLUMN order_items JSON NULL AFTER notes COMMENT "JSON data of order items"',
    'SELECT "Kolom order_items sudah ada di tabel orders" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.2 Migrasi data dari order_items ke kolom JSON di orders
-- Migrasi data dari order_items ke kolom JSON di orders untuk setiap order
SET @orderItems = (SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'order_items');

-- Hanya jalankan jika tabel order_items ada
SET @migrationSQL = IF(@orderItems > 0, 
    'CREATE TEMPORARY TABLE order_items_json AS
    SELECT 
        order_id,
        JSON_ARRAYAGG(
            JSON_OBJECT(
                "id", id,
                "order_id", order_id,
                "product_id", product_id,
                "name", IFNULL(name, ""),
                "description", IFNULL(description, ""),
                "price", price,
                "quantity", quantity,
                "image_url", IFNULL(image_url, ""),
                "options", IFNULL(options, JSON_OBJECT()),
                "created_at", IFNULL(created_at, NOW()),
                "updated_at", IFNULL(updated_at, NOW())
            )
        ) AS items_json
    FROM order_items
    GROUP BY order_id;
    
    UPDATE orders o
    JOIN order_items_json oi ON o.id = oi.order_id
    SET o.order_items = oi.items_json
    WHERE o.order_items IS NULL OR JSON_LENGTH(o.order_items) = 0;
    
    DROP TEMPORARY TABLE order_items_json;',
    'SELECT "Tabel order_items tidak ditemukan, melewati migrasi data" AS message');

PREPARE stmt FROM @migrationSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- BAGIAN 2: OPTIMIZE DATABASE RELATIONS AND DATA TYPES
-- ============================================================================

-- 2.1 Tambahkan kolom order_id di tabel reports jika belum ada
SET @reportsOrderIdExists = 0;
SELECT COUNT(*) INTO @reportsOrderIdExists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'reports' AND column_name = 'order_id';

SET @sql = IF(@reportsOrderIdExists = 0, 
    'ALTER TABLE reports ADD COLUMN order_id INT UNSIGNED NULL AFTER id;
     ALTER TABLE reports ADD CONSTRAINT fk_reports_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;',
    'SELECT "Kolom order_id sudah ada di tabel reports" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.2 Tambahkan kolom admin_id di tabel chat_messages jika belum ada
SET @chatMessagesAdminIdExists = 0;
SELECT COUNT(*) INTO @chatMessagesAdminIdExists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'chat_messages' AND column_name = 'admin_id';

SET @sql = IF(@chatMessagesAdminIdExists = 0, 
    'ALTER TABLE chat_messages ADD COLUMN admin_id INT UNSIGNED NULL AFTER user_id;
     ALTER TABLE chat_messages ADD CONSTRAINT fk_chat_messages_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL;',
    'SELECT "Kolom admin_id sudah ada di tabel chat_messages" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.3 Tambahkan kolom order_id di tabel carts jika belum ada
SET @cartsOrderIdExists = 0;
SELECT COUNT(*) INTO @cartsOrderIdExists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'carts' AND column_name = 'order_id';

SET @sql = IF(@cartsOrderIdExists = 0, 
    'ALTER TABLE carts ADD COLUMN order_id INT UNSIGNED NULL AFTER id;
     ALTER TABLE carts ADD CONSTRAINT fk_carts_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;',
    'SELECT "Kolom order_id sudah ada di tabel carts" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.4 Ubah tipe data id dari BIGINT ke INT UNSIGNED di tabel-tabel utama
SET @tablesList = 'users,admins,products,categories,orders,reports,chat_messages,carts,carousels,favorites';

-- Pisahkan string menjadi tabel individual
SET @pos = 1;
SET @len = CHAR_LENGTH(@tablesList);
SET @tableName = '';

-- Loop melalui setiap tabel
label1: WHILE @pos <= @len DO
    SET @nextComma = LOCATE(',', @tablesList, @pos);
    IF @nextComma = 0 THEN
        SET @tableName = SUBSTRING(@tablesList, @pos);
        SET @pos = @len + 1;
    ELSE
        SET @tableName = SUBSTRING(@tablesList, @pos, @nextComma - @pos);
        SET @pos = @nextComma + 1;
    END IF;
    
    -- Periksa apakah tabel ada dan perlu diubah
    SET @tableExists = 0;
    SET @sql = CONCAT('SELECT COUNT(*) INTO @tableExists FROM information_schema.tables 
                      WHERE table_schema = DATABASE() AND table_name = "', @tableName, '"');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    IF @tableExists > 0 THEN
        -- Periksa tipe data kolom id
        SET @columnType = '';
        SET @sql = CONCAT('SELECT column_type INTO @columnType FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "', @tableName, '" AND column_name = "id"');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Ubah tipe data jika perlu
        IF @columnType != 'int(10) unsigned' THEN
            SET @sql = CONCAT('ALTER TABLE ', @tableName, ' MODIFY id INT UNSIGNED AUTO_INCREMENT');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            SELECT CONCAT('Tipe data id untuk tabel ', @tableName, ' diubah menjadi INT UNSIGNED') AS message;
        ELSE
            SELECT CONCAT('Tipe data id untuk tabel ', @tableName, ' sudah INT UNSIGNED') AS message;
        END IF;
    ELSE
        SELECT CONCAT('Tabel ', @tableName, ' tidak ditemukan, melewati') AS message;
    END IF;
END WHILE label1;

-- 2.5 Mengoptimalkan tipe data foreign key agar konsisten dengan kolom referensi
-- Dapatkan daftar foreign key
CREATE TEMPORARY TABLE IF NOT EXISTS foreign_keys AS
SELECT 
    TABLE_NAME as table_name,
    COLUMN_NAME as column_name,
    REFERENCED_TABLE_NAME as referenced_table,
    REFERENCED_COLUMN_NAME as referenced_column,
    CONSTRAINT_NAME as constraint_name
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = DATABASE() AND 
    REFERENCED_COLUMN_NAME = 'id';

-- Untuk setiap foreign key
SELECT table_name, column_name, referenced_table, referenced_column, constraint_name 
FROM foreign_keys INTO @tbl, @col, @ref_tbl, @ref_col, @constraint;

SET @done = 0;
WHILE NOT @done DO
    -- Dapatkan tipe data kolom referensi
    SET @ref_type = '';
    SET @sql = CONCAT('SELECT column_type INTO @ref_type FROM information_schema.columns 
                      WHERE table_schema = DATABASE() AND table_name = "', @ref_tbl, '" AND column_name = "', @ref_col, '"');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    -- Dapatkan tipe data kolom foreign key
    SET @fk_type = '';
    SET @sql = CONCAT('SELECT column_type INTO @fk_type FROM information_schema.columns 
                      WHERE table_schema = DATABASE() AND table_name = "', @tbl, '" AND column_name = "', @col, '"');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    -- Jika tipe data berbeda, update foreign key
    IF @ref_type != '' AND @fk_type != '' AND @ref_type != @fk_type THEN
        -- Hapus constraint
        SET @sql = CONCAT('ALTER TABLE `', @tbl, '` DROP FOREIGN KEY `', @constraint, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Ubah tipe data
        SET @sql = CONCAT('ALTER TABLE `', @tbl, '` MODIFY `', @col, '` ', @ref_type);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Buat ulang constraint
        SET @sql = CONCAT('ALTER TABLE `', @tbl, '` ADD CONSTRAINT `', @constraint, '` FOREIGN KEY (`', @col, '`) REFERENCES `', @ref_tbl, '`(`', @ref_col, '`)');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        SELECT CONCAT('Updated ', @tbl, '.', @col, ' to match ', @ref_tbl, '.', @ref_col, ' type: ', @ref_type) AS message;
    END IF;
    
    -- Ambil foreign key berikutnya
    DELETE FROM foreign_keys WHERE table_name = @tbl AND column_name = @col LIMIT 1;
    
    -- Cek apakah masih ada foreign key lain
    SET @remaining = 0;
    SELECT COUNT(*) INTO @remaining FROM foreign_keys;
    IF @remaining = 0 THEN
        SET @done = 1;
    ELSE
        SELECT table_name, column_name, referenced_table, referenced_column, constraint_name 
        FROM foreign_keys LIMIT 1 INTO @tbl, @col, @ref_tbl, @ref_col, @constraint;
    END IF;
END WHILE;

DROP TEMPORARY TABLE IF EXISTS foreign_keys;

-- ============================================================================
-- BAGIAN 3: DROP ORDER_ITEMS TABLE AND CLEANUP
-- ============================================================================

-- 3.1 Hapus tabel orders_backup jika ada
DROP TABLE IF EXISTS orders_backup;
SELECT "Tabel orders_backup dihapus jika ada" AS message;

-- 3.2 Hapus tabel order_items jika migrasi data berhasil
SET @ordersWithItems = 0;
SELECT COUNT(*) INTO @ordersWithItems FROM orders
WHERE order_items IS NOT NULL AND JSON_LENGTH(order_items) > 0;

SET @originalItems = 0;
SET @sql = CONCAT('SELECT IFNULL((SELECT COUNT(*) FROM order_items), 0) INTO @originalItems');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek apakah migrasi data berhasil - hanya lanjutkan jika:
-- 1. Tidak ada data di tabel order_items, atau
-- 2. Ada data order_items JSON di tabel orders dan jumlahnya mencukupi
SET @canDrop = IF(@originalItems = 0 OR (@ordersWithItems > 0 AND (@originalItems / 10) * 0.8 <= @ordersWithItems), 1, 0);

SET @dropItemsSQL = IF(@canDrop = 1, 
    'DROP TABLE IF EXISTS order_items;
     SELECT "Tabel order_items berhasil dihapus" AS message;',
    'SELECT "Tabel order_items tidak dihapus karena migrasi data belum lengkap" AS message;');

PREPARE stmt FROM @dropItemsSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifikasi migrasi selesai
SELECT "Migrasi database selesai!" AS message;
SELECT "Periksa laporan di atas untuk memastikan semua langkah berhasil dijalankan." AS message; 