-- SQL Script to update database structure for bahan baku and retur management system
-- Run this in phpMyAdmin to update your database
-- Database: inventori_db2

-- Start transaction for safety
START TRANSACTION;

-- Check if columns exist in bahan_baku table and drop them if they do (to avoid duplicates)
SET @colExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bahan_baku' 
                 AND COLUMN_NAME = 'jumlah_retur');
                 
SET @dropColSQL = IF(@colExists > 0, 'ALTER TABLE `bahan_baku` DROP COLUMN `jumlah_retur`', 'SELECT 1');
PREPARE stmt FROM @dropColSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @colExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bahan_baku' 
                 AND COLUMN_NAME = 'jumlah_masuk');
                 
SET @dropColSQL = IF(@colExists > 0, 'ALTER TABLE `bahan_baku` DROP COLUMN `jumlah_masuk`', 'SELECT 1');
PREPARE stmt FROM @dropColSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @colExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bahan_baku' 
                 AND COLUMN_NAME = 'catatan_retur');
                 
SET @dropColSQL = IF(@colExists > 0, 'ALTER TABLE `bahan_baku` DROP COLUMN `catatan_retur`', 'SELECT 1');
PREPARE stmt FROM @dropColSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1. Add new columns to bahan_baku table
ALTER TABLE `bahan_baku` 
ADD COLUMN `jumlah_retur` int(11) DEFAULT 0,
ADD COLUMN `jumlah_masuk` int(11) DEFAULT 0,
ADD COLUMN `catatan_retur` text DEFAULT NULL;

-- Make sure status column exists and has the correct enum values (pending, approved, retur)
SET @colExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bahan_baku' 
                 AND COLUMN_NAME = 'status');

SET @modifyColSQL = IF(@colExists > 0, 
                    'ALTER TABLE `bahan_baku` MODIFY COLUMN `status` enum("pending","approved","retur") NOT NULL DEFAULT "pending"', 
                    'ALTER TABLE `bahan_baku` ADD COLUMN `status` enum("pending","approved","retur") NOT NULL DEFAULT "pending"');
PREPARE stmt FROM @modifyColSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Check and drop columns in retur_barang if they exist
SET @colExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retur_barang' 
                 AND COLUMN_NAME = 'id_bahan_baku');
                 
SET @dropColSQL = IF(@colExists > 0, 'ALTER TABLE `retur_barang` DROP COLUMN `id_bahan_baku`', 'SELECT 1');
PREPARE stmt FROM @dropColSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @colExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retur_barang' 
                 AND COLUMN_NAME = 'jumlah_retur');
                 
SET @dropColSQL = IF(@colExists > 0, 'ALTER TABLE `retur_barang` DROP COLUMN `jumlah_retur`', 'SELECT 1');
PREPARE stmt FROM @dropColSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @colExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retur_barang' 
                 AND COLUMN_NAME = 'supplier');
                 
SET @dropColSQL = IF(@colExists > 0, 'ALTER TABLE `retur_barang` DROP COLUMN `supplier`', 'SELECT 1');
PREPARE stmt FROM @dropColSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop existing foreign key constraints if they exist
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'retur_barang' 
    AND REFERENCED_TABLE_NAME = 'penerimaan'
    LIMIT 1
);

SET @drop_fk_query = IF(@constraint_name IS NOT NULL, 
    CONCAT('ALTER TABLE `retur_barang` DROP FOREIGN KEY ', @constraint_name), 
    'SELECT 1');

PREPARE stmt FROM @drop_fk_query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop existing index if it exists
SET @indexExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retur_barang'
                   AND INDEX_NAME = 'idx_retur_bahan_baku');
                   
SET @dropIdxSQL = IF(@indexExists > 0, 'ALTER TABLE `retur_barang` DROP INDEX `idx_retur_bahan_baku`', 'SELECT 1');
PREPARE stmt FROM @dropIdxSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Now add the new columns
ALTER TABLE `retur_barang` 
ADD COLUMN `id_bahan_baku` int(11) DEFAULT NULL,
ADD COLUMN `jumlah_retur` int(11) DEFAULT NULL,
ADD COLUMN `supplier` varchar(100) DEFAULT NULL;

-- 3. Add index for better performance
ALTER TABLE `retur_barang`
ADD INDEX `idx_retur_bahan_baku` (`id_bahan_baku`);

-- 4. Add foreign key constraint if needed (optional - uncomment if you want to enforce referential integrity)
-- ALTER TABLE `retur_barang`
-- ADD CONSTRAINT `fk_retur_bahan_baku` FOREIGN KEY (`id_bahan_baku`) REFERENCES `bahan_baku` (`id_bahan_baku`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 5. Drop existing index if it exists
SET @indexExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bahan_baku'
                   AND INDEX_NAME = 'idx_bahan_baku_status');
                   
SET @dropIdxSQL = IF(@indexExists > 0, 'ALTER TABLE `bahan_baku` DROP INDEX `idx_bahan_baku_status`', 'SELECT 1');
PREPARE stmt FROM @dropIdxSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index to bahan_baku for status filtering
ALTER TABLE `bahan_baku`
ADD INDEX `idx_bahan_baku_status` (`status`);

-- 6. Update laporan_masuk table to ensure it has the required columns
-- Check if periode column exists, add it if it doesn't
SET @colExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'laporan_masuk' 
                 AND COLUMN_NAME = 'periode');
                 
SET @addColSQL = IF(@colExists = 0, 'ALTER TABLE `laporan_masuk` ADD COLUMN `periode` int(11) DEFAULT 1 COMMENT "1, 2, 3, or 4"', 'SELECT 1');
PREPARE stmt FROM @addColSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. Update the status column in laporan_masuk if needed
ALTER TABLE `laporan_masuk` 
MODIFY COLUMN `status` varchar(50) DEFAULT 'pending';

-- 8. Create a view to show the full report with all required information
-- This view will show total added, total returned, and total successfully added to stock
CREATE OR REPLACE VIEW `v_bahan_baku_report` AS
SELECT 
    bb.id_bahan_baku,
    b.nama_barang,
    bb.qty AS jumlah_total,
    COALESCE(bb.jumlah_retur, 0) AS jumlah_retur,
    COALESCE(bb.jumlah_masuk, 0) AS jumlah_masuk,
    bb.status,
    bb.tanggal_input,
    bb.catatan_retur,
    u.nama_lengkap AS nama_pengguna
FROM 
    bahan_baku bb
JOIN 
    barang b ON bb.id_barang = b.id_barang
LEFT JOIN 
    users u ON bb.id_user = u.id_user;

-- Commit the transaction if everything went well
COMMIT;

-- Done! Your database is now updated with the new structure for the bahan baku and retur management system.
-- This structure supports the workflow:
-- 1. Add bahan_baku with status=pending
-- 2. Admin verifies:
--    a. If approved: items go to stock and are recorded in laporan_masuk
--    b. If retur: admin clicks retur button, fills form with jumlah_retur, supplier, catatan
--       Only non-returned items go to stock and laporan_masuk
-- 3. Reports show total added, returned, and successfully added to stock 