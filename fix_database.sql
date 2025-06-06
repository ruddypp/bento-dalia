-- Add id_pesanan column to bahan_baku if it doesn't exist
SET @s = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'bahan_baku'
        AND COLUMN_NAME = 'id_pesanan'
    ),
    'SELECT "Column id_pesanan already exists in bahan_baku table";',
    'ALTER TABLE bahan_baku ADD COLUMN id_pesanan INT NULL, ADD INDEX (id_pesanan);'
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make sure enum values for status fields are correct
ALTER TABLE pesanan_barang MODIFY COLUMN status ENUM('pending', 'diproses', 'selesai', 'dibatalkan', 'approved') NOT NULL DEFAULT 'pending';
ALTER TABLE bahan_baku MODIFY COLUMN status ENUM('pending', 'approved', 'retur') NOT NULL DEFAULT 'pending';

-- Check if any foreign key constraint exists and drop it if necessary
SET @fk_exists = (
    SELECT COUNT(1) FROM information_schema.table_constraints 
    WHERE constraint_schema = DATABASE() 
    AND constraint_name = 'fk_bahan_baku_pesanan' 
    AND table_name = 'bahan_baku'
);

SET @drop_fk_stmt = IF(@fk_exists > 0, 
    'ALTER TABLE bahan_baku DROP FOREIGN KEY fk_bahan_baku_pesanan', 
    'SELECT "No foreign key constraint exists"');

PREPARE stmt FROM @drop_fk_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 