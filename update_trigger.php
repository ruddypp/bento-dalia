<?php
// File untuk mengupdate trigger di database
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu");
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // 1. Drop existing trigger
    $drop_trigger = "DROP TRIGGER IF EXISTS `update_pesanan_after_bahan_baku_update`";
    if (!mysqli_query($conn, $drop_trigger)) {
        throw new Exception("Gagal menghapus trigger lama: " . mysqli_error($conn));
    }
    
    // 2. Create new trigger
    $create_trigger = "
    CREATE TRIGGER `update_pesanan_after_bahan_baku_update` AFTER UPDATE ON `bahan_baku` FOR EACH ROW 
    BEGIN
        -- If there's a pesanan associated with this bahan_baku
        IF NEW.id_pesanan IS NOT NULL THEN
            -- If status changed to approved
            IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
                -- Check if all items from this pesanan are approved
                DECLARE all_approved BOOLEAN;
                
                SELECT COUNT(*) = 0 INTO all_approved
                FROM bahan_baku
                WHERE id_pesanan = NEW.id_pesanan
                AND status != 'approved';
                
                IF all_approved THEN
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
    END
    ";
    
    // Set delimiter to execute the trigger creation
    mysqli_query($conn, "DELIMITER $$");
    if (!mysqli_query($conn, $create_trigger)) {
        throw new Exception("Gagal membuat trigger baru: " . mysqli_error($conn));
    }
    mysqli_query($conn, "DELIMITER ;");
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo "Trigger berhasil diperbarui!<br>";
    echo "Sekarang ketika bahan_baku diapprove, status pesanan akan otomatis diperbarui.<br>";
    echo "<a href='pesan_barang.php'>Kembali ke halaman Pesan Barang</a>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage();
}
?> 