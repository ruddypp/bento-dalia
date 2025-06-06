-- Update pesanan_barang status enum
ALTER TABLE pesanan_barang MODIFY COLUMN status ENUM('pending', 'diproses', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'pending';

-- Update bahan_baku status enum
ALTER TABLE bahan_baku MODIFY COLUMN status ENUM('pending', 'approved', 'retur') NOT NULL DEFAULT 'pending';

-- Update laporan_masuk status enum if needed
ALTER TABLE laporan_masuk MODIFY COLUMN status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending';

-- Add id_pesanan column to bahan_baku if not exists
SET @s = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'bahan_baku'
        AND COLUMN_NAME = 'id_pesanan'
    ),
    'SELECT "Column id_pesanan already exists in bahan_baku table";',
    'ALTER TABLE bahan_baku ADD COLUMN id_pesanan INT NULL, ADD FOREIGN KEY (id_pesanan) REFERENCES pesanan_barang(id_pesanan) ON DELETE SET NULL;'
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 