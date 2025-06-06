<?php
// File implementasi untuk memperbaiki bahan_baku.php
// Jalankan file ini untuk menerapkan perubahan langsung ke bahan_baku.php

// Pastikan kita memiliki akses ke file
if (!file_exists('bahan_baku.php')) {
    die("Error: File bahan_baku.php tidak ditemukan!");
}

// Baca isi file bahan_baku.php
$content = file_get_contents('bahan_baku.php');
if ($content === false) {
    die("Error: Gagal membaca file bahan_baku.php!");
}

// Backup file asli terlebih dahulu
$backup_file = 'bahan_baku.php.bak_' . date('Ymd_His');
if (!file_put_contents($backup_file, $content)) {
    die("Error: Gagal membuat backup file!");
}

// Perbaikan 1: Ubah query untuk menampilkan semua item termasuk yang approved
// Cari bagian query yang menampilkan bahan_baku
$old_query_pattern = '/\$query = "SELECT bb\.\*, b\.nama_barang, b\.satuan.*?ORDER BY bb\.tanggal_input DESC";/s';
$new_query = 'if ($filter_periode > 0) {
    $query .= " WHERE bb.periode = $filter_periode";
} else {
    $query .= " WHERE 1=1";
}

// Show all items including approved ones, only exclude retur items
$query .= " AND bb.status != \'retur\'";
$query .= " ORDER BY bb.tanggal_input DESC";';

// Perbaikan 2: Ubah fungsi edit_bahan_baku untuk memisahkan update data dan status
$edit_bahan_baku_pattern = '/function edit_bahan_baku\(\$conn, \$id, \$qty, \$periode, \$harga_satuan, \$lokasi, \$status\).*?exit\(\);/s';
$edit_bahan_baku_replacement = 'function edit_bahan_baku($conn, $id, $qty, $periode, $harga_satuan, $lokasi, $status) {
    // Calculate total
    $total = $qty * $harga_satuan;
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get bahan baku details first
        $query = "SELECT bb.*, b.nama_barang 
                  FROM bahan_baku bb 
                  JOIN barang b ON bb.id_barang = b.id_barang 
                  WHERE bb.id_bahan_baku = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $bahan_baku = mysqli_fetch_assoc($result);
        
        if (!$bahan_baku) {
            throw new Exception("Bahan baku tidak ditemukan");
        }
        
        // Update bahan baku - don\'t change the status to \'approved\' yet
        $update_query = "UPDATE bahan_baku SET qty = ?, periode = ?, harga_satuan = ?, total = ?, lokasi = ? WHERE id_bahan_baku = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "iiddsi", $qty, $periode, $harga_satuan, $total, $lokasi, $id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Gagal mengupdate bahan baku: " . mysqli_stmt_error($update_stmt));
        }
        mysqli_stmt_close($update_stmt);
        
        // If status is being changed to approved, handle that separately
        if ($status == \'approved\' && $bahan_baku[\'status\'] != \'approved\') {
            // Update status to approved
            $update_status_query = "UPDATE bahan_baku SET status = \'approved\' WHERE id_bahan_baku = ?";
            $update_status_stmt = mysqli_prepare($conn, $update_status_query);
            mysqli_stmt_bind_param($update_status_stmt, "i", $id);
            
            if (!mysqli_stmt_execute($update_status_stmt)) {
                throw new Exception("Gagal mengupdate status bahan baku: " . mysqli_stmt_error($update_status_stmt));
            }
            mysqli_stmt_close($update_status_stmt);
            
            // Update stock in barang table
            $update_stock_query = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
            $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
            mysqli_stmt_bind_param($update_stock_stmt, "ii", $qty, $bahan_baku[\'id_barang\']);
            
            if (!mysqli_stmt_execute($update_stock_stmt)) {
                throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stock_stmt));
            }
            mysqli_stmt_close($update_stock_stmt);
            
            // Create entry in barang_masuk
            $masuk_query = "INSERT INTO barang_masuk (id_barang, qty_masuk, tanggal_masuk, id_user, lokasi, harga_satuan, periode) 
                            VALUES (?, ?, NOW(), ?, ?, ?, ?)";
            $masuk_stmt = mysqli_prepare($conn, $masuk_query);
            mysqli_stmt_bind_param($masuk_stmt, "iisddi", 
                                  $bahan_baku[\'id_barang\'], 
                                  $qty, 
                                  $_SESSION[\'user_id\'],
                                  $lokasi, 
                                  $harga_satuan,
                                  $periode);
            
            if (!mysqli_stmt_execute($masuk_stmt)) {
                throw new Exception("Gagal menambahkan data barang masuk: " . mysqli_stmt_error($masuk_stmt));
            }
            
            $id_masuk = mysqli_insert_id($conn);
            mysqli_stmt_close($masuk_stmt);
            
            // Check if there\'s already a laporan_masuk for today
            $today = date(\'Y-m-d\');
            $check_laporan_query = "SELECT id_laporan_masuk FROM laporan_masuk WHERE DATE(tanggal_laporan) = ? AND periode = ?";
            $check_laporan_stmt = mysqli_prepare($conn, $check_laporan_query);
            mysqli_stmt_bind_param($check_laporan_stmt, "si", $today, $periode);
            mysqli_stmt_execute($check_laporan_stmt);
            $check_result = mysqli_stmt_get_result($check_laporan_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                // Use existing laporan for today
                $laporan_row = mysqli_fetch_assoc($check_result);
                $id_laporan = $laporan_row[\'id_laporan_masuk\'];
            } else {
                // Create new laporan masuk for today
                $laporan_query = "INSERT INTO laporan_masuk (tanggal_laporan, created_by, created_at, status, periode) 
                                VALUES (CURDATE(), ?, NOW(), \'approved\', ?)";
                $laporan_stmt = mysqli_prepare($conn, $laporan_query);
                mysqli_stmt_bind_param($laporan_stmt, "ii", $_SESSION[\'user_id\'], $periode);
                
                if (!mysqli_stmt_execute($laporan_stmt)) {
                    throw new Exception("Gagal membuat laporan masuk: " . mysqli_stmt_error($laporan_stmt));
                }
                
                $id_laporan = mysqli_insert_id($conn);
                mysqli_stmt_close($laporan_stmt);
            }
            mysqli_stmt_close($check_laporan_stmt);
            
            // Link laporan with barang_masuk
            $detail_query = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk) VALUES (?, ?)";
            $detail_stmt = mysqli_prepare($conn, $detail_query);
            mysqli_stmt_bind_param($detail_stmt, "ii", $id_laporan, $id_masuk);
            
            if (!mysqli_stmt_execute($detail_stmt)) {
                throw new Exception("Gagal membuat detail laporan: " . mysqli_stmt_error($detail_stmt));
            }
            mysqli_stmt_close($detail_stmt);
            
            // Log activity
            logActivity($_SESSION[\'user_id\'], "Menyetujui bahan baku: " . $bahan_baku[\'nama_barang\'] . ", qty: " . $qty);
            
            setAlert("success", "Bahan baku berhasil diapprove dan stok telah diperbarui!");
        } else if ($status != $bahan_baku[\'status\']) {
            // Just update the status if it\'s different but not going to approved
            $update_status_query = "UPDATE bahan_baku SET status = ? WHERE id_bahan_baku = ?";
            $update_status_stmt = mysqli_prepare($conn, $update_status_query);
            mysqli_stmt_bind_param($update_status_stmt, "si", $status, $id);
            
            if (!mysqli_stmt_execute($update_status_stmt)) {
                throw new Exception("Gagal mengupdate status bahan baku: " . mysqli_stmt_error($update_status_stmt));
            }
            mysqli_stmt_close($update_status_stmt);
            
            // Log activity
            logActivity($_SESSION[\'user_id\'], "Mengubah data bahan baku: " . $bahan_baku[\'nama_barang\'] . ", status: " . $status);
            
            setAlert("success", "Data bahan baku berhasil diperbarui!");
        } else {
            // Log activity for regular update
            logActivity($_SESSION[\'user_id\'], "Mengubah data bahan baku: " . $bahan_baku[\'nama_barang\']);
            
            setAlert("success", "Data bahan baku berhasil diperbarui!");
        }
        
        // Commit transaction
        mysqli_commit($conn);
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        setAlert("error", $e->getMessage());
    }
    
    // Redirect to laporan_masuk.php instead of bahan_baku.php when approved
    if ($status == \'approved\' && isset($_POST[\'edit_bahan_baku\'])) {
        header("Location: laporan_masuk.php");
        exit();
    } else {
        // Redirect to refresh page
        header("Location: bahan_baku.php");
        exit();
    }
}';

// Perbaikan 3: Ubah redirect setelah approval ke laporan_masuk.php
$redirect_pattern = '/header\("Location: retur_barang\.php"\);/';
$redirect_replacement = 'header("Location: laporan_masuk.php");';

// Terapkan perubahan
$content = preg_replace($old_query_pattern, $new_query, $content);
$content = preg_replace($edit_bahan_baku_pattern, $edit_bahan_baku_replacement, $content);
$content = preg_replace($redirect_pattern, $redirect_replacement, $content);

// Tulis kembali file yang sudah diperbaiki
if (!file_put_contents('bahan_baku.php', $content)) {
    die("Error: Gagal menulis ke file bahan_baku.php!");
}

echo "Perbaikan berhasil diterapkan ke file bahan_baku.php!<br>";
echo "File backup tersimpan sebagai: $backup_file<br>";
echo "<a href='bahan_baku.php'>Kembali ke halaman Bahan Baku</a>";
?> 