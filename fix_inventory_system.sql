-- Fix for inventory system integration issues
-- This SQL file contains fixes for:
-- 1. Data disappearing when approved in bahan_baku
-- 2. Status in pesan_barang not automatically updating
-- 3. Items going to retur_barang.php instead of laporan_masuk.php

-- Drop existing trigger
DROP TRIGGER IF EXISTS `update_pesanan_after_bahan_baku_update`;

-- Create improved trigger that uses id_pesanan field instead of parsing from catatan_retur
DELIMITER $$
CREATE TRIGGER `update_pesanan_after_bahan_baku_update` AFTER UPDATE ON `bahan_baku` FOR EACH ROW 
BEGIN
    -- If there's a pesanan associated with this bahan_baku
    IF NEW.id_pesanan IS NOT NULL THEN
        -- If status changed to approved
        IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
            -- Check if all items from this pesanan are approved
            SELECT COUNT(*) = 0 INTO @all_approved
            FROM bahan_baku 
            WHERE id_pesanan = NEW.id_pesanan
            AND status != 'approved';
            
            IF @all_approved THEN
                -- Update pesanan status to selesai
                UPDATE pesanan_barang 
                SET status = 'approved' 
                WHERE id_pesanan = NEW.id_pesanan;
            ELSE
                -- Update pesanan status to diproses
                UPDATE pesanan_barang 
                SET status = 'processed' 
                WHERE id_pesanan = NEW.id_pesanan AND status = 'pending';
            END IF;
        END IF;
    END IF;
END$$
DELIMITER ;

-- Update the status values in pesanan_barang to match what's used in the code
ALTER TABLE `pesanan_barang` 
MODIFY COLUMN `status` enum('pending','processed','approved','canceled') NOT NULL DEFAULT 'pending'; 