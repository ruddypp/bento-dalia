-- Trigger to update pesanan_barang status when bahan_baku status changes
DELIMITER //

-- Create a function to check if all bahan_baku items from a pesanan are approved
CREATE FUNCTION IF NOT EXISTS all_bahan_baku_approved(pesanan_id INT) RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE all_approved BOOLEAN;
    
    SELECT COUNT(*) = 0 INTO all_approved
    FROM bahan_baku bb
    WHERE bb.catatan_retur LIKE CONCAT('% Dari pesanan #', pesanan_id, '%')
    AND bb.status != 'approved';
    
    RETURN all_approved;
END //

-- Create trigger to update pesanan_barang when bahan_baku is updated
CREATE TRIGGER IF NOT EXISTS update_pesanan_after_bahan_baku_update
AFTER UPDATE ON bahan_baku
FOR EACH ROW
BEGIN
    DECLARE pesanan_id INT;
    
    -- Extract pesanan ID from catatan_retur field
    IF NEW.catatan_retur REGEXP 'Dari pesanan #[0-9]+' THEN
        SET pesanan_id = SUBSTRING_INDEX(SUBSTRING_INDEX(NEW.catatan_retur, 'Dari pesanan #', -1), ' ', 1);
        
        -- If status changed to approved
        IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
            -- Check if all items from this pesanan are approved
            IF all_bahan_baku_approved(pesanan_id) THEN
                -- Update pesanan status to selesai
                UPDATE pesanan_barang 
                SET status = 'selesai' 
                WHERE id_pesanan = pesanan_id;
            ELSE
                -- Update pesanan status to diproses
                UPDATE pesanan_barang 
                SET status = 'diproses' 
                WHERE id_pesanan = pesanan_id AND status = 'pending';
            END IF;
        END IF;
    END IF;
END //

DELIMITER ; 