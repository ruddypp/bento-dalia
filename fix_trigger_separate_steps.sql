-- LANGKAH 1: Drop existing trigger
DROP TRIGGER IF EXISTS `update_pesanan_after_bahan_baku_update`;

-- LANGKAH 2: Drop existing function
DROP FUNCTION IF EXISTS `all_bahan_baku_approved`;

-- LANGKAH 3: Create function first
-- Jalankan query ini terlebih dahulu
CREATE FUNCTION `all_bahan_baku_approved` (`pesanan_id` INT) RETURNS TINYINT(1) DETERMINISTIC 
BEGIN
    DECLARE all_approved BOOLEAN;
    
    SELECT COUNT(*) = 0 INTO all_approved
    FROM bahan_baku bb
    WHERE bb.id_pesanan = pesanan_id
    AND bb.status != 'approved';
    
    RETURN all_approved;
END;

-- LANGKAH 4: Create trigger
-- Jalankan query ini setelah function berhasil dibuat
CREATE TRIGGER `update_pesanan_after_bahan_baku_update` AFTER UPDATE ON `bahan_baku` FOR EACH ROW 
BEGIN
    DECLARE all_approved BOOLEAN;
    DECLARE last_id INT;
    DECLARE laporan_id INT;
    
    -- If there's a pesanan associated with this bahan_baku
    IF NEW.id_pesanan IS NOT NULL THEN
        -- If status changed to approved
        IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
            -- Check if all items from this pesanan are approved
            SET all_approved = all_bahan_baku_approved(NEW.id_pesanan);
            
            IF all_approved THEN
                -- Update pesanan status to approved (selesai)
                UPDATE pesanan_barang 
                SET status = 'approved' 
                WHERE id_pesanan = NEW.id_pesanan;
            ELSE
                -- Update pesanan status to processed (diproses)
                UPDATE pesanan_barang 
                SET status = 'processed' 
                WHERE id_pesanan = NEW.id_pesanan AND status = 'pending';
            END IF;
            
            -- Update jumlah_masuk when approved
            UPDATE bahan_baku
            SET jumlah_masuk = qty
            WHERE id_bahan_baku = NEW.id_bahan_baku;
            
            -- Create entry in barang_masuk for reporting
            INSERT INTO barang_masuk (
                id_barang, 
                tanggal_masuk, 
                id_user, 
                qty_masuk, 
                lokasi, 
                harga_satuan, 
                periode
            ) VALUES (
                NEW.id_barang,
                NOW(),
                NEW.id_user,
                NEW.qty,
                NEW.lokasi,
                NEW.harga_satuan,
                NEW.periode
            );
            
            -- Get the ID of the newly inserted barang_masuk
            SET last_id = LAST_INSERT_ID();
            
            -- Find or create laporan_masuk for today with the same period
            SELECT id_laporan_masuk INTO laporan_id
            FROM laporan_masuk
            WHERE DATE(tanggal_laporan) = CURDATE() AND periode = NEW.periode
            LIMIT 1;
            
            IF laporan_id IS NULL THEN
                -- Create new laporan_masuk
                INSERT INTO laporan_masuk (
                    tanggal_laporan, 
                    created_by, 
                    created_at, 
                    status, 
                    periode
                ) VALUES (
                    CURDATE(),
                    NEW.id_user,
                    NOW(),
                    'approved',
                    NEW.periode
                );
                
                SET laporan_id = LAST_INSERT_ID();
            END IF;
            
            -- Link barang_masuk to laporan_masuk
            INSERT INTO laporan_masuk_detail (
                id_laporan, 
                id_masuk
            ) VALUES (
                laporan_id,
                last_id
            );
        END IF;
    END IF;
END; 