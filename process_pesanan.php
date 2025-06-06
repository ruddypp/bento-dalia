<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if ID is provided
if (!isset($_POST['id_pesanan']) || empty($_POST['id_pesanan'])) {
    setAlert("error", "ID pesanan tidak ditemukan");
    header("Location: pesan_barang.php");
    exit();
}

$id_pesanan = (int)$_POST['id_pesanan'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Get pesanan details
    $query = "SELECT pb.*, s.nama_supplier 
              FROM pesanan_barang pb 
              LEFT JOIN supplier s ON pb.id_supplier = s.id_supplier 
              WHERE pb.id_pesanan = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pesanan = mysqli_fetch_assoc($result);
    
    if (!$pesanan) {
        throw new Exception("Pesanan tidak ditemukan");
    }
    
    // Check if status is pending (only pending orders can be processed)
    if ($pesanan['status'] != 'pending') {
        throw new Exception("Hanya pesanan dengan status pending yang dapat diproses");
    }
    
    if ($action == 'process') {
        // Process pesanan - create bahan_baku entries
        
        // Get pesanan items
        $items_query = "SELECT pd.*, b.nama_barang, b.satuan 
                       FROM pesanan_detail pd 
                       LEFT JOIN barang b ON pd.id_barang = b.id_barang 
                       WHERE pd.id_pesanan = ?";
        $items_stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($items_stmt, "i", $id_pesanan);
        mysqli_stmt_execute($items_stmt);
        $items_result = mysqli_stmt_get_result($items_stmt);
        
        $bahan_baku_ids = []; // Store created bahan_baku IDs for reference
        
        // Check if bahan_baku entries already exist for this pesanan
        $check_query = "SELECT COUNT(*) as count FROM bahan_baku WHERE id_pesanan = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "i", $id_pesanan);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $check_row = mysqli_fetch_assoc($check_result);
        $bahan_baku_exists = $check_row['count'] > 0;
        
        if ($bahan_baku_exists) {
            // Update existing bahan_baku entries
            $update_query = "UPDATE bahan_baku SET status = 'pending' WHERE id_pesanan = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "i", $id_pesanan);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Gagal memperbarui status bahan baku: " . mysqli_stmt_error($update_stmt));
            }
            
            mysqli_stmt_close($update_stmt);
            
            // Log activity
            $user_id = $_SESSION['user_id'];
            logActivity($user_id, "Memproses pesanan #$id_pesanan dari supplier: {$pesanan['nama_supplier']} (bahan baku sudah ada)");
        } else {
            // Insert each item into bahan_baku
            while ($item = mysqli_fetch_assoc($items_result)) {
                $bahan_query = "INSERT INTO bahan_baku 
                              (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input, id_pesanan, catatan_retur) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?)";
                $bahan_stmt = mysqli_prepare($conn, $bahan_query);
                $user_id = $_SESSION['user_id'];
                $total = $item['qty'] * $item['harga_satuan']; // Recalculate total to be safe
                $catatan = "Dari pesanan #{$id_pesanan}";
                
                mysqli_stmt_bind_param(
                    $bahan_stmt, 
                    "iiiddssis", 
                    $item['id_barang'], 
                    $item['qty'], 
                    $item['periode'], 
                    $item['harga_satuan'], 
                    $total, 
                    $item['lokasi'], 
                    $user_id,
                    $id_pesanan,
                    $catatan
                );
                
                if (!mysqli_stmt_execute($bahan_stmt)) {
                    throw new Exception("Gagal menambahkan bahan baku: " . mysqli_stmt_error($bahan_stmt));
                }
                
                $bahan_baku_id = mysqli_insert_id($conn);
                $bahan_baku_ids[] = $bahan_baku_id;
                mysqli_stmt_close($bahan_stmt);
                
                // Log activity for each item
                logActivity($user_id, "Menambahkan bahan baku: {$item['nama_barang']}, qty: {$item['qty']}, periode: {$item['periode']} dari pesanan #{$id_pesanan}");
            }
        }
        
        // Keep pesanan status as 'pending' until all items are processed
        // We're removing the 'diproses' state, so no update needed here
        
        // Log activity
        $user_id = $_SESSION['user_id'];
        logActivity($user_id, "Memproses pesanan #$id_pesanan dari supplier: {$pesanan['nama_supplier']}");
        
        setAlert("success", "Pesanan berhasil diproses! Bahan baku telah ditambahkan dengan status pending.");
    } 
    elseif ($action == 'cancel') {
        // Cancel pesanan
        
        // Update pesanan status to dibatalkan
        $update_query = "UPDATE pesanan_barang SET status = 'dibatalkan' WHERE id_pesanan = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $id_pesanan);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Gagal membatalkan pesanan: " . mysqli_stmt_error($update_stmt));
        }
        
        mysqli_stmt_close($update_stmt);
        
        // Check if any bahan_baku entries are linked to this pesanan and delete them
        $bahan_query = "SELECT id_bahan_baku FROM bahan_baku WHERE id_pesanan = ?";
        $bahan_stmt = mysqli_prepare($conn, $bahan_query);
        mysqli_stmt_bind_param($bahan_stmt, "i", $id_pesanan);
        mysqli_stmt_execute($bahan_stmt);
        $bahan_result = mysqli_stmt_get_result($bahan_stmt);
        
        while ($bahan = mysqli_fetch_assoc($bahan_result)) {
            $delete_query = "DELETE FROM bahan_baku WHERE id_bahan_baku = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $bahan['id_bahan_baku']);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
        }
        
        // Log activity
        $user_id = $_SESSION['user_id'];
        logActivity($user_id, "Membatalkan pesanan #$id_pesanan dari supplier: {$pesanan['nama_supplier']}");
        
        setAlert("success", "Pesanan berhasil dibatalkan!");
    }
    else {
        throw new Exception("Aksi tidak valid");
    }
    
    // Commit transaction
    mysqli_commit($conn);
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    setAlert("error", $e->getMessage());
}

// Redirect back to pesan_barang.php
header("Location: pesan_barang.php");
exit();
?>