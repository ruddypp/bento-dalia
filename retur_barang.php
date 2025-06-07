<?php
$pageTitle = "Retur Barang";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php'; // Tambahkan ini untuk memeriksa hak akses

// Create retur_barang table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS retur_barang (
    id_retur INT PRIMARY KEY AUTO_INCREMENT,
    id_barang INT NOT NULL,
    qty_retur INT NOT NULL,
    tanggal_retur DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    alasan_retur TEXT,
    id_user INT,
    supplier VARCHAR(100),
    harga_satuan DECIMAL(20,2) NOT NULL,
    total DECIMAL(20,2) NOT NULL,
    periode INT NOT NULL,
    id_pesanan INT,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
)";

if (!mysqli_query($conn, $create_table_query)) {
    setAlert("error", "Gagal membuat tabel retur_barang: " . mysqli_error($conn));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_retur'])) {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Ambil data retur
            $id_bahan_baku = isset($_POST['id_bahan_baku']) ? (int)$_POST['id_bahan_baku'] : null;
            $jumlah_retur = isset($_POST['jumlah_retur']) ? (int)$_POST['jumlah_retur'] : 0;
            $catatan_retur = sanitize($_POST['catatan_retur']);
            $id_user = $_SESSION['user_id'];
            
            // Validasi data
            if (!$id_bahan_baku) {
                throw new Exception("ID bahan baku tidak valid");
            }
            
            // Get bahan baku details
            $query = "SELECT bb.*, b.nama_barang, b.satuan FROM bahan_baku bb 
                     JOIN barang b ON bb.id_barang = b.id_barang 
                     WHERE bb.id_bahan_baku = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_bahan_baku);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $bahan_baku = mysqli_fetch_assoc($result);
            
            if (!$bahan_baku) {
                throw new Exception("Data bahan baku tidak ditemukan");
            }
            
            // Validate jumlah_retur
            if ($jumlah_retur <= 0 || $jumlah_retur > $bahan_baku['qty']) {
                throw new Exception("Jumlah retur tidak valid. Maksimal: " . $bahan_baku['qty']);
            }
            
            // Calculate jumlah_masuk (remaining items that go to stock)
            $jumlah_masuk = $bahan_baku['qty'] - $jumlah_retur;
            
            // Calculate new total price based on items not returned
            $harga_satuan = $bahan_baku['harga_satuan'];
            $total_masuk = $jumlah_masuk * $harga_satuan;
            $total_retur = $jumlah_retur * $harga_satuan;
            
            // Update bahan_baku table
            $update_query = "UPDATE bahan_baku SET 
                            status = 'retur', 
                            jumlah_retur = ?, 
                            jumlah_masuk = ?, 
                            total = ?,
                            catatan_retur = ? 
                            WHERE id_bahan_baku = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "iidsi", $jumlah_retur, $jumlah_masuk, $total_retur, $catatan_retur, $id_bahan_baku);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Gagal mengupdate bahan baku: " . mysqli_stmt_error($update_stmt));
            }
            mysqli_stmt_close($update_stmt);
            
            // Create entry in retur_barang table
            $retur_query = "INSERT INTO retur_barang (id_barang, qty_retur, tanggal_retur, alasan_retur, id_user, supplier, harga_satuan, total, periode, id_pesanan) 
                           VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
            $retur_stmt = mysqli_prepare($conn, $retur_query);
            
            // Check if the prepare statement was successful
            if (!$retur_stmt) {
                // Table might not exist, try to create it
                $create_table_query = "CREATE TABLE IF NOT EXISTS retur_barang (
                    id_retur INT PRIMARY KEY AUTO_INCREMENT,
                    id_barang INT NOT NULL,
                    qty_retur INT NOT NULL,
                    tanggal_retur DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    alasan_retur TEXT,
                    id_user INT,
                    supplier VARCHAR(100),
                    harga_satuan DECIMAL(20,2) NOT NULL,
                    total DECIMAL(20,2) NOT NULL,
                    periode INT NOT NULL,
                    id_pesanan INT,
                    FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
                    FOREIGN KEY (id_user) REFERENCES users(id_user)
                )";
                
                if (!mysqli_query($conn, $create_table_query)) {
                    throw new Exception("Gagal membuat tabel retur_barang: " . mysqli_error($conn));
                }
                
                // Try preparing the statement again
                $retur_stmt = mysqli_prepare($conn, $retur_query);
                if (!$retur_stmt) {
                    throw new Exception("Gagal menyiapkan query retur: " . mysqli_error($conn));
                }
            }
            
            // Pastikan supplier tidak NULL untuk binding parameter
            $supplier_value = '';
            
            mysqli_stmt_bind_param(
                $retur_stmt, 
                "iisisidii", 
                $bahan_baku['id_barang'], 
                $jumlah_retur, 
                $catatan_retur, 
                $id_user, 
                $supplier_value, 
                $harga_satuan, 
                $total_retur, 
                $bahan_baku['periode'],
                $bahan_baku['id_pesanan']
            );
            
            if (!mysqli_stmt_execute($retur_stmt)) {
                throw new Exception("Gagal menyimpan data retur: " . mysqli_stmt_error($retur_stmt));
            }
            mysqli_stmt_close($retur_stmt);
            
            // If jumlah_masuk > 0, update stock in barang table
            if ($jumlah_masuk > 0) {
                $update_stock_query = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
                $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
                mysqli_stmt_bind_param($update_stock_stmt, "ii", $jumlah_masuk, $bahan_baku['id_barang']);
                
                if (!mysqli_stmt_execute($update_stock_stmt)) {
                    throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stock_stmt));
                }
                mysqli_stmt_close($update_stock_stmt);
                
                // Create entry in barang_masuk for the accepted portion
                $masuk_query = "INSERT INTO barang_masuk (id_barang, qty_masuk, tanggal_masuk, id_user, lokasi, harga_satuan, periode, id_supplier) 
                               VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
                $masuk_stmt = mysqli_prepare($conn, $masuk_query);
                
                // Get supplier ID from bahan_baku or use default
                $supplier_id = 1; // Default supplier ID
                if (!empty($bahan_baku['id_supplier'])) {
                    $supplier_id = $bahan_baku['id_supplier'];
                } else if (!empty($bahan_baku['id_pesanan'])) {
                    // Try to get supplier from associated pesanan
                    $get_pesanan_supplier = "SELECT id_supplier FROM pesanan_barang WHERE id_pesanan = ?";
                    $pesanan_supplier_stmt = mysqli_prepare($conn, $get_pesanan_supplier);
                    if ($pesanan_supplier_stmt) {
                        mysqli_stmt_bind_param($pesanan_supplier_stmt, "i", $bahan_baku['id_pesanan']);
                        mysqli_stmt_execute($pesanan_supplier_stmt);
                        $supplier_result = mysqli_stmt_get_result($pesanan_supplier_stmt);
                        if ($supplier_row = mysqli_fetch_assoc($supplier_result)) {
                            $supplier_id = $supplier_row['id_supplier'];
                        }
                        mysqli_stmt_close($pesanan_supplier_stmt);
                    }
                }
                
                // Verify that the supplier ID exists in the supplier table
                $check_supplier_query = "SELECT id_supplier FROM supplier WHERE id_supplier = ?";
                $check_supplier_stmt = mysqli_prepare($conn, $check_supplier_query);
                mysqli_stmt_bind_param($check_supplier_stmt, "i", $supplier_id);
                mysqli_stmt_execute($check_supplier_stmt);
                $check_supplier_result = mysqli_stmt_get_result($check_supplier_stmt);
                
                if (mysqli_num_rows($check_supplier_result) == 0) {
                    // Supplier not found, use the first available supplier
                    $get_any_supplier_query = "SELECT id_supplier FROM supplier ORDER BY id_supplier LIMIT 1";
                    $get_any_supplier_result = mysqli_query($conn, $get_any_supplier_query);
                    
                    if ($get_any_supplier_result && mysqli_num_rows($get_any_supplier_result) > 0) {
                        $any_supplier = mysqli_fetch_assoc($get_any_supplier_result);
                        $supplier_id = $any_supplier['id_supplier'];
                    } else {
                        // If no suppliers exist, create a default one
                        $create_supplier_query = "INSERT INTO supplier (nama_supplier, alamat, telepon) VALUES ('Default Supplier', 'Alamat Default', '000000')";
                        if (mysqli_query($conn, $create_supplier_query)) {
                            $supplier_id = mysqli_insert_id($conn);
                        } else {
                            throw new Exception("Tidak ada supplier yang tersedia dan gagal membuat supplier default");
                        }
                    }
                }
                mysqli_stmt_close($check_supplier_stmt);
                
                mysqli_stmt_bind_param($masuk_stmt, "iisidii", 
                                     $bahan_baku['id_barang'], 
                                     $jumlah_masuk,
                                     $id_user,
                                     $bahan_baku['lokasi'],
                                     $harga_satuan,
                                     $bahan_baku['periode'],
                                     $supplier_id);
                
                if (!mysqli_stmt_execute($masuk_stmt)) {
                    throw new Exception("Gagal menambahkan data barang masuk: " . mysqli_stmt_error($masuk_stmt));
                }
                
                $id_masuk = mysqli_insert_id($conn);
                mysqli_stmt_close($masuk_stmt);
                
                // Check if there's already a laporan_masuk for today
                $today = date('Y-m-d');
                $check_laporan_query = "SELECT id_laporan_masuk FROM laporan_masuk WHERE DATE(tanggal_laporan) = ? AND periode = ?";
                $check_laporan_stmt = mysqli_prepare($conn, $check_laporan_query);
                mysqli_stmt_bind_param($check_laporan_stmt, "si", $today, $bahan_baku['periode']);
                mysqli_stmt_execute($check_laporan_stmt);
                $check_result = mysqli_stmt_get_result($check_laporan_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    // Use existing laporan for today
                    $laporan_row = mysqli_fetch_assoc($check_result);
                    $id_laporan = $laporan_row['id_laporan_masuk'];
                } else {
                    // Create new laporan masuk for today
                    $laporan_query = "INSERT INTO laporan_masuk (tanggal_laporan, created_by, created_at, status, periode) 
                                     VALUES (CURDATE(), ?, NOW(), 'approved', ?)";
                    $laporan_stmt = mysqli_prepare($conn, $laporan_query);
                    mysqli_stmt_bind_param($laporan_stmt, "ii", $id_user, $bahan_baku['periode']);
                    
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
            }
            
            // If this bahan_baku is linked to a pesanan, check if all items are processed
            if (!empty($bahan_baku['id_pesanan'])) {
                // Check if all items in the pesanan are now approved or retur
                $check_pesanan_query = "SELECT COUNT(*) as total_items, 
                                       SUM(CASE WHEN status IN ('approved', 'retur') THEN 1 ELSE 0 END) as processed_items 
                                       FROM bahan_baku 
                                       WHERE id_pesanan = ?";
                $check_pesanan_stmt = mysqli_prepare($conn, $check_pesanan_query);
                mysqli_stmt_bind_param($check_pesanan_stmt, "i", $bahan_baku['id_pesanan']);
                mysqli_stmt_execute($check_pesanan_stmt);
                $pesanan_result = mysqli_stmt_get_result($check_pesanan_stmt);
                $pesanan_status = mysqli_fetch_assoc($pesanan_result);
                mysqli_stmt_close($check_pesanan_stmt);
                
                // If all items are processed, update the pesanan status to 'selesai'
                if ($pesanan_status['total_items'] > 0 && $pesanan_status['total_items'] == $pesanan_status['processed_items']) {
                    $update_pesanan_query = "UPDATE pesanan_barang SET status = 'selesai' WHERE id_pesanan = ?";
                    $update_pesanan_stmt = mysqli_prepare($conn, $update_pesanan_query);
                    mysqli_stmt_bind_param($update_pesanan_stmt, "i", $bahan_baku['id_pesanan']);
                    
                    if (!mysqli_stmt_execute($update_pesanan_stmt)) {
                        throw new Exception("Gagal mengupdate status pesanan: " . mysqli_stmt_error($update_pesanan_stmt));
                    }
                    mysqli_stmt_close($update_pesanan_stmt);
                    
                    // Log activity for pesanan status update
                    logActivity($id_user, "Menyelesaikan pesanan #{$bahan_baku['id_pesanan']} dengan beberapa barang retur");
                } else {
                    // Keep status as 'pending' until all items are processed
                    // No need to update status here as we're removing the 'diproses' state
                    
                    // Log activity for pesanan status update
                    logActivity($id_user, "Memproses item pesanan #{$bahan_baku['id_pesanan']}, menunggu semua item selesai diproses");
                }
            }
            
            // Log activity
            logActivity($id_user, "Melakukan retur barang: {$bahan_baku['nama_barang']}, jumlah: {$jumlah_retur} dari total {$bahan_baku['qty']}");
            
            // Commit transaction
            mysqli_commit($conn);
            
            setAlert("success", "Retur berhasil diproses! " . ($jumlah_masuk > 0 ? "Sebanyak $jumlah_masuk item telah ditambahkan ke stok." : "Tidak ada item yang ditambahkan ke stok."));
            
            // Redirect agar tidak terjadi double submit jika user refresh
            header("Location: retur_barang.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
    }
    
    // Handle direct retur form submission
    if (isset($_POST['add_direct_retur'])) {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Ambil data retur langsung
            $id_barang = isset($_POST['id_barang']) ? (int)$_POST['id_barang'] : null;
            $id_supplier = isset($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
            $qty_retur = isset($_POST['qty_retur']) ? (int)$_POST['qty_retur'] : 0;
            $harga_satuan = isset($_POST['harga_satuan']) ? (float)$_POST['harga_satuan'] : 0;
            $periode = isset($_POST['periode']) ? (int)$_POST['periode'] : date('Y');
            $alasan_retur = sanitize($_POST['alasan_retur']);
            $supplier = sanitize($_POST['supplier']);
            $id_user = $_SESSION['user_id'];
            
            // Validasi data
            if (!$id_barang || !$id_supplier) {
                throw new Exception("ID barang atau supplier tidak valid");
            }
            
            // Get barang details
            $query = "SELECT b.*, s.nama_supplier FROM barang b 
                     LEFT JOIN supplier s ON b.id_supplier = s.id_supplier 
                     WHERE b.id_barang = ? AND b.id_supplier = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $id_barang, $id_supplier);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $barang = mysqli_fetch_assoc($result);
            
            if (!$barang) {
                throw new Exception("Data barang tidak ditemukan atau tidak terkait dengan supplier yang dipilih");
            }
            
            // Validate qty_retur
            if ($qty_retur <= 0) {
                throw new Exception("Jumlah retur tidak valid");
            }
            
            // Kurangi stok barang
            $update_stock_query = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
            $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
            mysqli_stmt_bind_param($update_stock_stmt, "ii", $qty_retur, $id_barang);
            
            if (!mysqli_stmt_execute($update_stock_stmt)) {
                throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stock_stmt));
            }
            mysqli_stmt_close($update_stock_stmt);
            
            // Calculate total price
            $total = $qty_retur * $harga_satuan;
            
            // Create entry in retur_barang table
            $retur_query = "INSERT INTO retur_barang (id_barang, qty_retur, tanggal_retur, alasan_retur, id_user, supplier, harga_satuan, total, periode, id_pesanan) 
                           VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, NULL)";
            $retur_stmt = mysqli_prepare($conn, $retur_query);
            
            if (!$retur_stmt) {
                throw new Exception("Gagal menyiapkan query retur: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param(
                $retur_stmt, 
                "iisisidi", 
                $id_barang, 
                $qty_retur, 
                $alasan_retur, 
                $id_user, 
                $supplier, 
                $harga_satuan, 
                $total, 
                $periode
            );
            
            if (!mysqli_stmt_execute($retur_stmt)) {
                throw new Exception("Gagal menyimpan data retur: " . mysqli_stmt_error($retur_stmt));
            }
            mysqli_stmt_close($retur_stmt);
            
            // Log activity
            logActivity($id_user, "Melakukan retur langsung barang: {$barang['nama_barang']}, jumlah: {$qty_retur} {$barang['satuan']}");
            
            // Commit transaction
            mysqli_commit($conn);
            
            setAlert("success", "Retur langsung berhasil diproses! Stok barang telah dikurangi sebanyak $qty_retur {$barang['satuan']}.");
            
            // Redirect agar tidak terjadi double submit jika user refresh
            header("Location: retur_barang.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
    }
    
    if (isset($_POST['delete_retur'])) {
        $id_bahan_baku = (int)$_POST['id_bahan_baku'];
        $id_retur = isset($_POST['id_retur']) ? (int)$_POST['id_retur'] : 0;
        
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Get bahan baku details
            $query = "SELECT bb.*, b.nama_barang FROM bahan_baku bb 
                     JOIN barang b ON bb.id_barang = b.id_barang 
                     WHERE bb.id_bahan_baku = ? AND bb.status = 'retur'";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_bahan_baku);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $bahan_baku = mysqli_fetch_assoc($result);
            
            if (!$bahan_baku) {
                throw new Exception("Data retur bahan baku tidak ditemukan");
            }
            
            // If jumlah_masuk > 0, we need to reduce stock
            if ($bahan_baku['jumlah_masuk'] > 0) {
                $update_stock_query = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
                $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
                mysqli_stmt_bind_param($update_stock_stmt, "ii", $bahan_baku['jumlah_masuk'], $bahan_baku['id_barang']);
                mysqli_stmt_execute($update_stock_stmt);
            }
            
            // Reset bahan_baku status and retur fields
            $update_query = "UPDATE bahan_baku SET 
                            status = 'pending', 
                            jumlah_retur = 0, 
                            jumlah_masuk = 0, 
                            catatan_retur = NULL 
                            WHERE id_bahan_baku = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "i", $id_bahan_baku);
            mysqli_stmt_execute($update_stmt);
            
            // Delete from retur_barang table - only delete the specific retur entry if id_retur is provided
            if ($id_retur > 0) {
                $delete_retur_query = "DELETE FROM retur_barang WHERE id_retur = ?";
                $delete_retur_stmt = mysqli_prepare($conn, $delete_retur_query);
                mysqli_stmt_bind_param($delete_retur_stmt, "i", $id_retur);
            } else {
                // Fallback to the old method if id_retur is not provided
            $delete_retur_query = "DELETE FROM retur_barang WHERE id_barang = ? AND DATE(tanggal_retur) = DATE(?)";
            $delete_retur_stmt = mysqli_prepare($conn, $delete_retur_query);
            mysqli_stmt_bind_param($delete_retur_stmt, "is", $bahan_baku['id_barang'], $bahan_baku['tanggal_input']);
            }
            
            if (!mysqli_stmt_execute($delete_retur_stmt)) {
                throw new Exception("Gagal menghapus data retur: " . mysqli_stmt_error($delete_retur_stmt));
            }
            mysqli_stmt_close($delete_retur_stmt);
            
            // If this bahan_baku is linked to a pesanan, keep it as 'pending' since we're removing 'diproses' status
            if (!empty($bahan_baku['id_pesanan'])) {
                // No need to update status here as we're removing the 'diproses' state
                // The status will remain 'pending' until all items are processed
                
                // Log activity
                logActivity($_SESSION['user_id'], "Memproses item pesanan #{$bahan_baku['id_pesanan']}, menunggu semua item selesai diproses");
            }
            
            // Log aktivitas
            logActivity($_SESSION['user_id'], "Menghapus data retur bahan baku ID: {$id_bahan_baku}");
            setAlert("success", "Data retur bahan baku berhasil dihapus!");
            
            // Commit transaction
            mysqli_commit($conn);
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
        
        // Redirect
        header("Location: retur_barang.php");
        exit();
    }
    
    // Handle deletion of direct retur records (not linked to bahan_baku)
    if (isset($_POST['delete_direct_retur'])) {
        $id_retur = (int)$_POST['id_retur'];
        
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Get retur details
            $query = "SELECT rb.*, b.nama_barang FROM retur_barang rb 
                     JOIN barang b ON rb.id_barang = b.id_barang 
                     WHERE rb.id_retur = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_retur);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $retur = mysqli_fetch_assoc($result);
            
            if (!$retur) {
                throw new Exception("Data retur tidak ditemukan");
            }
            
            // Delete from retur_barang table
            $delete_retur_query = "DELETE FROM retur_barang WHERE id_retur = ?";
            $delete_retur_stmt = mysqli_prepare($conn, $delete_retur_query);
            mysqli_stmt_bind_param($delete_retur_stmt, "i", $id_retur);
            
            if (!mysqli_stmt_execute($delete_retur_stmt)) {
                throw new Exception("Gagal menghapus data retur: " . mysqli_stmt_error($delete_retur_stmt));
            }
            mysqli_stmt_close($delete_retur_stmt);
            
            // If this retur is linked to a pesanan, check if all items are now processed
            if (!empty($retur['id_pesanan'])) {
                // Check if all returns for this pesanan are now processed
                $check_pesanan_query = "SELECT COUNT(*) as remaining_returns FROM retur_barang WHERE id_pesanan = ?";
                $check_pesanan_stmt = mysqli_prepare($conn, $check_pesanan_query);
                mysqli_stmt_bind_param($check_pesanan_stmt, "i", $retur['id_pesanan']);
                mysqli_stmt_execute($check_pesanan_stmt);
                $pesanan_result = mysqli_stmt_get_result($check_pesanan_stmt);
                $pesanan_status = mysqli_fetch_assoc($pesanan_result);
                mysqli_stmt_close($check_pesanan_stmt);
                
                // No need to update status here as we're removing the 'diproses' state
                // The status will remain 'pending' until all items are processed
                
                // Log activity
                logActivity($_SESSION['user_id'], "Memproses item pesanan #{$retur['id_pesanan']}, menunggu semua item selesai diproses");
            }
            
            // Log aktivitas
            logActivity($_SESSION['user_id'], "Menghapus data retur langsung untuk barang: {$retur['nama_barang']}");
            setAlert("success", "Data retur berhasil dihapus!");
            
            // Commit transaction
            mysqli_commit($conn);
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
        
        // Redirect
        header("Location: retur_barang.php");
        exit();
    }
}

// Initialize variables that might be undefined
$week_filter = isset($_GET['week']) ? $_GET['week'] : '';
$week_condition = '';
$start_date = '';
$end_date = '';
$weekNumber = '';
$year = '';

// Parse week filter if it exists
if (!empty($week_filter) && preg_match('/^(\d{4})-W(\d{2})$/', $week_filter, $matches)) {
    $year = $matches[1];
    $weekNumber = $matches[2];
    
    // Calculate start and end dates for the selected week
    $dto = new DateTime();
    $dto->setISODate($year, $weekNumber);
    $start_date = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $end_date = $dto->format('Y-m-d');
    
    // Add week condition to the query
    $week_condition = " AND DATE(bb.tanggal_input) BETWEEN '$start_date' AND '$end_date'";
    $week_condition_direct = " AND DATE(rb.tanggal_retur) BETWEEN '$start_date' AND '$end_date'";
} else {
    $week_condition_direct = '';
}

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($records_per_page, [10, 25, 50, 100])) {
    $records_per_page = 10; // Default to 10 if invalid value
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Ensure page is at least 1
$offset = ($page - 1) * $records_per_page;

// Build the queries
$query = "SELECT bb.*, b.nama_barang, b.satuan, u.nama_lengkap as nama_pengguna, 
          pb.status as pesanan_status,
          rb.id_retur, rb.alasan_retur as rb_alasan_retur, rb.qty_retur as qty_retur_actual
          FROM bahan_baku bb 
          JOIN barang b ON bb.id_barang = b.id_barang 
          LEFT JOIN users u ON bb.id_user = u.id_user
          LEFT JOIN pesanan_barang pb ON bb.id_pesanan = pb.id_pesanan
          LEFT JOIN (
              SELECT id_retur, id_barang, alasan_retur, qty_retur
              FROM retur_barang 
              GROUP BY alasan_retur, id_barang
          ) rb ON rb.id_barang = bb.id_barang 
              AND rb.alasan_retur = bb.catatan_retur
          WHERE bb.status = 'retur' AND (pb.status != 'dibatalkan' OR pb.status IS NULL)
          $week_condition
          ORDER BY bb.tanggal_input DESC";

// Count total records for pagination from both queries
$count_query1 = "SELECT COUNT(*) as total FROM bahan_baku bb 
               JOIN barang b ON bb.id_barang = b.id_barang 
               LEFT JOIN users u ON bb.id_user = u.id_user
               LEFT JOIN pesanan_barang pb ON bb.id_pesanan = pb.id_pesanan
               LEFT JOIN (
                   SELECT id_retur, id_barang, alasan_retur, qty_retur as qty_retur_actual 
                   FROM retur_barang 
                   GROUP BY alasan_retur, id_barang
               ) rb ON rb.id_barang = bb.id_barang 
                   AND rb.alasan_retur = bb.catatan_retur
               WHERE bb.status = 'retur' AND (pb.status != 'dibatalkan' OR pb.status IS NULL)
               $week_condition";
$count_result1 = mysqli_query($conn, $count_query1);
$count_row1 = mysqli_fetch_assoc($count_result1);
$total_records1 = $count_row1['total'];

$count_query2 = "SELECT COUNT(*) as total
               FROM retur_barang rb
               JOIN barang b ON rb.id_barang = b.id_barang
               LEFT JOIN users u ON rb.id_user = u.id_user
               LEFT JOIN pesanan_barang pb ON rb.id_pesanan = pb.id_pesanan
               LEFT JOIN bahan_baku bb ON rb.id_barang = bb.id_barang 
                                     AND bb.status = 'retur' 
                                     AND rb.alasan_retur = bb.catatan_retur
               WHERE bb.id_bahan_baku IS NULL AND (pb.status != 'dibatalkan' OR pb.status IS NULL)
               $week_condition_direct";
$count_result2 = mysqli_query($conn, $count_query2);
$count_row2 = mysqli_fetch_assoc($count_result2);
$total_records2 = $count_row2['total'];

$total_records = $total_records1 + $total_records2;
$total_pages = ceil($total_records / $records_per_page);

// Execute the queries
$retur_list = mysqli_query($conn, $query);

// Juga ambil data langsung dari tabel retur_barang yang tidak terhubung dengan bahan_baku
$query2 = "SELECT NULL as id_bahan_baku, rb.id_barang, rb.qty_retur as qty, rb.qty_retur as jumlah_retur, 
           0 as jumlah_masuk, rb.tanggal_retur as tanggal_input, 
           'retur' as status, rb.alasan_retur as catatan_retur, rb.id_pesanan,
           b.nama_barang, b.satuan,
           u.nama_lengkap as nama_pengguna,
           pb.status as pesanan_status,
           rb.id_retur, rb.tanggal_retur, rb.alasan_retur as rb_alasan_retur, rb.supplier, rb.qty_retur as qty_retur_actual
           FROM retur_barang rb
           JOIN barang b ON rb.id_barang = b.id_barang
           LEFT JOIN users u ON rb.id_user = u.id_user
           LEFT JOIN pesanan_barang pb ON rb.id_pesanan = pb.id_pesanan
           LEFT JOIN bahan_baku bb ON rb.id_barang = bb.id_barang 
                                  AND bb.status = 'retur' 
                                  AND rb.alasan_retur = bb.catatan_retur
           WHERE bb.id_bahan_baku IS NULL AND (pb.status != 'dibatalkan' OR pb.status IS NULL)
           $week_condition_direct
           GROUP BY rb.id_retur
           ORDER BY rb.tanggal_retur DESC";
           
// Apply pagination to the queries
// We need to handle pagination for combined results from both queries
// First, get all results from both queries
$retur_list = mysqli_query($conn, $query);
$retur_list2 = mysqli_query($conn, $query2);

// Store all results in an array
$all_retur_items = [];
while ($retur = mysqli_fetch_assoc($retur_list)) {
    $retur['source'] = 'bahan_baku';
    $all_retur_items[] = $retur;
}

while ($retur = mysqli_fetch_assoc($retur_list2)) {
    $retur['source'] = 'retur_barang';
    $all_retur_items[] = $retur;
}

// Sort by date (newest first)
usort($all_retur_items, function($a, $b) {
    return strtotime($b['tanggal_input']) - strtotime($a['tanggal_input']);
});

// Apply pagination
$paginated_items = array_slice($all_retur_items, $offset, $records_per_page);

// Initialize $records_per_page_options for dropdown
$records_per_page_options = [10, 25, 50, 100];

// Get all pending bahan_baku for potential returns
$query = "SELECT bb.*, b.nama_barang, b.satuan 
          FROM bahan_baku bb 
          JOIN barang b ON bb.id_barang = b.id_barang 
          LEFT JOIN pesanan_barang pb ON bb.id_pesanan = pb.id_pesanan
          WHERE bb.status = 'pending' AND (pb.status != 'dibatalkan' OR pb.status IS NULL)
          $week_condition
          ORDER BY bb.tanggal_input DESC";
$bahan_baku_list = mysqli_query($conn, $query);

// Calculate week condition for direct retur query
$week_condition_direct = '';
if (!empty($week_filter) && preg_match('/^(\d{4})-W(\d{2})$/', $week_filter, $matches)) {
    $week_condition_direct = " AND DATE(rb.tanggal_retur) BETWEEN '$start_date' AND '$end_date'";
}

// Initialize variables that might be undefined
$week_condition = '';
$start_date = '';
$end_date = '';
$weekNumber = '';
$year = '';

// Parse week filter if it exists
if (!empty($_GET['week']) && preg_match('/^(\d{4})-W(\d{2})$/', $_GET['week'], $matches)) {
    $week_filter = $_GET['week'];
    $year = $matches[1];
    $weekNumber = $matches[2];
    
    // Calculate start and end dates for the selected week
    $dto = new DateTime();
    $dto->setISODate($year, $weekNumber);
    $start_date = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $end_date = $dto->format('Y-m-d');
    
    // Add week condition to the query
    $week_condition = " AND DATE(bb.tanggal_input) BETWEEN '$start_date' AND '$end_date'";
}

// Pagination settings
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="text-blue-600 mr-3">
                <i class="fas fa-info-circle text-xl"></i>
            </div>
            <div>
                <h4 class="text-blue-800 font-medium mb-1">Informasi Retur</h4>
                <p class="text-sm text-blue-700">
                    Ada dua jenis retur yang ditampilkan di halaman ini:
                </p>
                <ul class="list-disc list-inside text-sm text-blue-700 ml-4 mt-1">
                    <li>Retur dari penerimaan barang (supplier)</li>
                    <li>Retur bahan baku (ditandai dengan latar belakang kuning)</li>
                </ul>
                <p class="text-sm text-blue-700 mt-1">
                    Untuk membuat retur bahan baku, silakan klik tombol "Retur Bahan Baku" di atas atau kunjungi halaman Bahan Baku.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Weekly Filter Form -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6">
        <form method="GET" action="" class="flex flex-wrap items-center">
            <div class="w-full md:w-auto mb-2 md:mb-0 md:mr-4">
                <label for="week" class="block text-sm font-medium text-gray-700 mb-1">Filter Berdasarkan Minggu:</label>
                <input type="week" id="week" name="week" value="<?= isset($_GET['week']) ? htmlspecialchars($_GET['week']) : '' ?>" 
                       class="shadow-sm border border-gray-300 rounded py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="w-full md:w-auto flex items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <?php if (isset($_GET['week']) && !empty($_GET['week'])): ?>
                <a href="retur_barang.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-md transition-all">
                    <i class="fas fa-times mr-2"></i> Reset
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="flex justify-between items-center mb-4">
        <div>
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-undo text-blue-500 mr-2"></i> Daftar Retur Barang
        </h2>
            <?php if (!empty($week_filter) && isset($start_date) && isset($end_date)): ?>
            <p class="text-sm text-gray-600 mt-1">
                Menampilkan data periode: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?> (Minggu <?= $weekNumber ?>, <?= $year ?>)
            </p>
            <?php endif; ?>
        </div>
        
        <div class="flex space-x-2">
            <a href="bahan_baku.php" class="bg-green-500 hover:bg-green-600 text-white text-sm px-4 py-2 rounded-md transition-all">
                <i class="fas fa-cube mr-2"></i> Retur Bahan Baku
            </a>
            <div class="relative">
                <button id="exportDropdown" class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-4 py-2 rounded-md transition-all flex items-center" onclick="toggleDropdown('exportOptions')">
                    <i class="fas fa-file-export mr-2"></i> Export
                    <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="exportOptions" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden z-10">
                    <ul class="py-1">
                        <li><a class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap border border-gray-200" href="export_retur_barang.php?format=pdf<?= !empty($_GET['week']) ? '&week='.$_GET['week'] : '' ?>">
                            <i class="far fa-file-pdf text-red-500 mr-2"></i> Export PDF
                        </a></li>
                        <li><a class="rounded-b bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap border border-l border-r border-b border-gray-200" href="export_retur_barang.php?format=excel<?= !empty($_GET['week']) ? '&week='.$_GET['week'] : '' ?>">
                            <i class="far fa-file-excel text-green-500 mr-2"></i> Export Excel
                        </a></li>
                    </ul>
                </div>
            </div>
        <div class="relative">
            <button id="addReturDropdown" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all flex items-center" onclick="toggleDropdown('returOptions')">
                <i class="fas fa-plus-circle mr-2"></i> Tambah Retur
                <i class="fas fa-chevron-down ml-2"></i>
            </button>
            <div id="returOptions" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden z-10">
                <ul class="py-1">
                    <li><a class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap border border-gray-200 cursor-pointer" href="bahan_baku.php">
                            <i class="fas fa-cube text-blue-500 mr-2"></i> Retur Bahan Baku
                        </a></li>
                    <li><a class="rounded-b bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap border border-l border-r border-b border-gray-200 cursor-pointer" id="showDirectReturModal">
                            <i class="fas fa-undo text-red-500 mr-2"></i> Retur Langsung
                        </a></li>
                </ul>
            </div>
        </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg overflow-hidden data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Nama Barang</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Jumlah Total</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Jumlah Retur</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Jumlah Masuk</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Catatan Retur</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No Pesanan</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php 
                $no = 1 + $offset;
                
                if (!empty($paginated_items)):
                    foreach ($paginated_items as $retur):
                        // Determine if this is from a pesanan
                        $pesanan_info = !empty($retur['id_pesanan']) ? "#" . $retur['id_pesanan'] . " (" . ucfirst($retur['pesanan_status'] ?? 'pending') . ")" : "-";
                        
                        // If there's no jumlah_retur from bahan_baku but there is qty_retur_actual from retur_barang, use that
                        $retur_qty = $retur['qty_retur_actual'] ?? $retur['jumlah_retur'];
                        if (empty($retur_qty) || $retur_qty <= 0) {
                            $retur_qty = $retur['qty']; // Use the full quantity if no specific retur amount
                        }
                        
                        // Calculate total as sum of returned and accepted
                        $total_qty = $retur['qty'];
                        if (!empty($retur['jumlah_masuk']) && !empty($retur['jumlah_retur'])) {
                            $total_qty = $retur['jumlah_masuk'] + $retur['jumlah_retur'];
                        }

                        // Ensure jumlah_masuk is properly displayed
                        $jumlah_masuk = $retur['jumlah_masuk'] ?? 0;
                        
                        // Determine row style based on source
                        $rowClass = '';
                        if ($retur['source'] == 'bahan_baku') {
                            $rowClass = !empty($retur['id_pesanan']) ? 'bg-yellow-50' : '';
                        } else {
                            $rowClass = 'bg-blue-50';
                        }
                ?>
                <tr class="hover:bg-gray-50 <?= $rowClass ?>">
                    <td class="py-2 px-3 text-sm"><?= $no++ ?></td>
                    <td class="py-2 px-3 text-sm">
                        <?= date('d/m/Y', strtotime($retur['source'] == 'bahan_baku' ? $retur['tanggal_input'] : $retur['tanggal_retur'])) ?>
                    </td>
                    <td class="py-2 px-3 text-sm"><?= $retur['nama_barang'] ?? '-' ?></td>
                    <td class="py-2 px-3 text-sm">
                        <?= $total_qty ?> <?= $retur['satuan'] ?>
                    </td>
                    <td class="py-2 px-3 text-sm font-medium text-red-600">
                        <?= $retur_qty ?> <?= $retur['satuan'] ?>
                    </td>
                    <td class="py-2 px-3 text-sm font-medium text-green-600">
                        <?= $jumlah_masuk ?> <?= $retur['satuan'] ?>
                    </td>
                    <td class="py-2 px-3 text-sm text-gray-600">
                        <?= $retur['catatan_retur'] ?? ($retur['rb_alasan_retur'] ?? '-') ?>
                    </td>
                    <td class="py-2 px-3 text-sm">
                        <?= $pesanan_info ?>
                    </td>
                    <td class="py-2 px-3 text-sm">
                        <?php if ($retur['source'] == 'bahan_baku'): ?>
                        <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                onclick="viewBahanBakuRetur(<?= $retur['id_bahan_baku'] ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if (isset($DELETE_ALLOWED) && $DELETE_ALLOWED): ?>
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deleteRetur(<?= $retur['id_bahan_baku'] ?>, <?= $retur['id_retur'] ?? 'null' ?>, '<?= date('d/m/Y', strtotime($retur['tanggal_input'])) ?>', '<?= $retur['nama_barang'] ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <?php else: ?>
                        <?php if (!empty($retur['id_retur']) && isset($DELETE_ALLOWED) && $DELETE_ALLOWED): ?>
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deleteDirectRetur(<?= $retur['id_retur'] ?>, '<?= date('d/m/Y', strtotime($retur['tanggal_retur'])) ?>', '<?= $retur['nama_barang'] ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="9" class="py-3 px-3 text-center text-gray-500">Tidak ada data retur</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination Controls -->
        <?php if($total_pages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&per_page=<?= $records_per_page ?><?= isset($_GET['week']) ? '&week='.$_GET['week'] : '' ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Previous</span>
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php else: ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                    <span class="sr-only">Previous</span>
                    <i class="fas fa-chevron-left"></i>
                </span>
                <?php endif; ?>
                
                <?php
                // Calculate range of page numbers to display
                $range = 2; // Display 2 pages before and after current page
                $start_page = max(1, $page - $range);
                $end_page = min($total_pages, $page + $range);
                
                // Always show first page
                if($start_page > 1) {
                    echo '<a href="?page=1&per_page='.$records_per_page.(isset($_GET['week']) ? '&week='.$_GET['week'] : '').'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                    if($start_page > 2) {
                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                    }
                }
                
                // Display page links
                for($i = $start_page; $i <= $end_page; $i++) {
                    if($i == $page) {
                        echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">'.$i.'</span>';
                    } else {
                        echo '<a href="?page='.$i.'&per_page='.$records_per_page.(isset($_GET['week']) ? '&week='.$_GET['week'] : '').'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$i.'</a>';
                    }
                }
                
                // Always show last page
                if($end_page < $total_pages) {
                    if($end_page < $total_pages - 1) {
                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                    }
                    echo '<a href="?page='.$total_pages.'&per_page='.$records_per_page.(isset($_GET['week']) ? '&week='.$_GET['week'] : '').'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$total_pages.'</a>';
                }
                ?>
                
                <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&per_page=<?= $records_per_page ?><?= isset($_GET['week']) ? '&week='.$_GET['week'] : '' ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                    <span class="sr-only">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </span>
                <?php endif; ?>
            </nav>
        </div>
        
        <div class="mt-2 text-sm text-gray-500 text-center">
            Menampilkan <?= min(($page-1)*$records_per_page+1, $total_records) ?> - <?= min($page*$records_per_page, $total_records) ?> dari <?= $total_records ?> data
        </div>
        <?php endif; ?>
        
        <!-- Records Per Page Selector -->
        <div class="mt-4 flex justify-end">
            <div class="flex items-center">
                <span class="text-sm text-gray-700 mr-2">Tampilkan:</span>
                <select id="per_page_selector" class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="changePerPage(this.value)">
                    <option value="10" <?= $records_per_page == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $records_per_page == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $records_per_page == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $records_per_page == 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span class="text-sm text-gray-700 ml-2">data per halaman</span>
            </div>
        </div>
    </div>
</div>

<!-- Add Retur Modal -->
<div id="addReturModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Retur Barang</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addReturModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="returForm" method="POST" action="" class="mt-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_bahan_baku">
                        Pilih Bahan Baku
                    </label>
                    <select id="id_bahan_baku" name="id_bahan_baku" required 
                            class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent select-bahan-baku">
                        <option value="">Pilih Bahan Baku</option>
                        <?php 
                        // Reset pointer to beginning of result set
                        mysqli_data_seek($bahan_baku_list, 0);
                        
                        while ($bahan_baku = mysqli_fetch_assoc($bahan_baku_list)): ?>
                            <option value="<?= $bahan_baku['id_bahan_baku'] ?>">
                                ID:<?= $bahan_baku['id_bahan_baku'] ?> - <?= $bahan_baku['nama_barang'] ?> (<?= $bahan_baku['qty'] ?> <?= $bahan_baku['satuan'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="jumlah_retur">
                            Jumlah Retur
                        </label>
                        <input type="number" id="jumlah_retur" name="jumlah_retur" min="1" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Jumlah yang akan diretur</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="satuan">
                            Satuan
                        </label>
                        <input type="text" id="satuan" name="satuan" 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100" readonly>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="catatan_retur">
                        Alasan Retur
                    </label>
                    <textarea id="catatan_retur" name="catatan_retur" required 
                              class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              rows="3" placeholder="Masukkan alasan retur bahan baku"></textarea>
                </div>
                
                <div id="bahan_baku_detail" class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg hidden">
                    <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <span>Detail Bahan Baku</span>
                    </h4>
                    <div id="bahan_baku_detail_content" class="text-sm">
                        <!-- Content will be loaded dynamically -->
                        <div class="animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('addReturModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_retur" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Direct Retur Modal -->
<div id="addDirectReturModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Retur Langsung</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addDirectReturModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="directReturForm" method="POST" action="" class="mt-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_supplier">
                        Pilih Supplier
                    </label>
                    <select id="id_supplier" name="id_supplier" required 
                            class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent select-supplier">
                        <option value="">Pilih Supplier</option>
                        <?php 
                        // Get all suppliers
                        $supplier_query = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
                        $supplier_result = mysqli_query($conn, $supplier_query);
                        
                        if (!$supplier_result) {
                            echo '<option value="">Error: ' . mysqli_error($conn) . '</option>';
                        } else if (mysqli_num_rows($supplier_result) === 0) {
                            echo '<option value="">Tidak ada supplier ditemukan</option>';
                        } else {
                            while ($supplier = mysqli_fetch_assoc($supplier_result)): ?>
                                <option value="<?= $supplier['id_supplier'] ?>">
                                    <?= $supplier['nama_supplier'] ?>
                                </option>
                            <?php endwhile;
                        }
                        ?>
                    </select>
                    <div class="mt-2">
                        <button type="button" id="debug_supplier" class="text-xs text-blue-500 hover:text-blue-700" onclick="debugSupplierData()">
                            Debug Supplier Data
                        </button>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_barang">
                        Pilih Barang
                    </label>
                    <select id="id_barang" name="id_barang" required disabled
                            class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent select-barang">
                        <option value="">Pilih Supplier Terlebih Dahulu</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="qty_retur">
                            Jumlah Retur
                        </label>
                        <input type="number" id="qty_retur" name="qty_retur" min="1" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Jumlah yang akan diretur</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="barang_satuan">
                            Satuan
                        </label>
                        <input type="text" id="barang_satuan" name="barang_satuan" 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100" readonly>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="supplier">
                        Nama Supplier
                    </label>
                    <input type="text" id="supplier" name="supplier" 
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100" readonly>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="harga_satuan">
                        Harga Satuan
                    </label>
                    <input type="number" id="harga_satuan" name="harga_satuan" min="0" step="0.01" required 
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="periode">
                        Periode
                    </label>
                    <input type="number" id="periode" name="periode" min="1" value="<?= date('Y') ?>" required 
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="alasan_retur">
                        Alasan Retur
                    </label>
                    <textarea id="alasan_retur" name="alasan_retur" required 
                              class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              rows="3" placeholder="Masukkan alasan retur barang"></textarea>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('addDirectReturModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_direct_retur" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Bahan Baku Retur Modal -->
<div id="viewBahanBakuReturModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Detail Retur Bahan Baku</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('viewBahanBakuReturModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mt-4" id="bahan_baku_retur_detail_content">
                <!-- Content will be loaded dynamically -->
                <div class="flex justify-center">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('viewBahanBakuReturModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Retur Modal -->
<div id="deleteReturModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Hapus Retur</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_retur_text">Anda yakin ingin menghapus retur ini?</p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_bahan_baku" name="id_bahan_baku">
                <input type="hidden" id="delete_id_retur" name="id_retur">
                
                <div class="items-center px-4 py-3">
                    <button type="submit" name="delete_retur" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Ya, Hapus Retur
                    </button>
                    <button type="button" onclick="closeModal('deleteReturModal')" class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Direct Retur Modal -->
<div id="deleteDirectReturModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Hapus Retur</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_direct_retur_text">Anda yakin ingin menghapus retur ini?</p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_direct_id_retur" name="id_retur">
                
                <div class="items-center px-4 py-3">
                    <button type="submit" name="delete_direct_retur" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Ya, Hapus Retur
                    </button>
                    <button type="button" onclick="closeModal('deleteDirectReturModal')" class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Initialize select2
    $(document).ready(function() {
        // Debug info
        console.log("Document ready, initializing select2...");
        
        // Check if select2 is loaded
        if (typeof $.fn.select2 === 'undefined') {
            console.error("Select2 is not loaded!");
            alert("Error: Select2 library tidak ditemukan. Silakan refresh halaman.");
            return;
        }
        
        // Check if supplier dropdown exists
        const supplierSelect = $('#id_supplier');
        if (supplierSelect.length === 0) {
            console.error("Supplier dropdown not found!");
        } else {
            console.log("Supplier dropdown found, options count:", supplierSelect.find('option').length);
        }
        
        // Initialize select2 for bahan baku dropdown
        $('.select-bahan-baku').select2({
            dropdownParent: $('#addReturModal'),
            placeholder: "Pilih Bahan Baku",
            width: '100%'
        }).on('change', function() {
            const bahanBakuId = $(this).val();
            if (bahanBakuId) {
                // Fetch and show bahan baku details
                $('#bahan_baku_detail').removeClass('hidden');
                
                fetch('ajax_get_bahan_baku.php?id=' + bahanBakuId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const bahan = data.bahan_baku;
                            const content = `
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <p class="text-xs text-gray-500">Nama Barang</p>
                                        <p class="font-medium">${bahan.nama_barang}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Jumlah</p>
                                        <p class="font-medium">${bahan.qty} ${bahan.satuan}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Periode</p>
                                        <p class="font-medium">${bahan.periode}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Lokasi</p>
                                        <p class="font-medium">${bahan.lokasi}</p>
                                    </div>
                                </div>
                            `;
                            $('#bahan_baku_detail_content').html(content);
                            
                            // Set max value for jumlah_retur
                            $('#jumlah_retur').attr('max', bahan.qty);
                            
                            // Set satuan field
                            $('#satuan').val(bahan.satuan);
                        } else {
                            $('#bahan_baku_detail_content').html('<div class="text-center text-red-500">Terjadi kesalahan saat memuat data!</div>');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        $('#bahan_baku_detail_content').html('<div class="text-center text-red-500">Terjadi kesalahan saat memuat data!</div>');
                    });
            } else {
                $('#bahan_baku_detail').addClass('hidden');
            }
        });
        
        // Function to initialize supplier select2
        function initSupplierSelect2() {
            try {
                console.log("Initializing supplier select2...");
                $('.select-supplier').select2({
                    dropdownParent: $('#addDirectReturModal'),
                    placeholder: "Pilih Supplier",
                    width: '100%',
                    allowClear: true,
                    minimumResultsForSearch: 0
                }).on('change', function() {
                    const supplierId = $(this).val();
                    const barangSelect = $('#id_barang');
                    
                    if (supplierId) {
                        // Enable barang dropdown
                        barangSelect.prop('disabled', false);
                        
                        // Show loading indicator
                        barangSelect.empty().append('<option value="">Memuat data...</option>');
                        
                        // Fetch barang data from selected supplier
                        fetch('ajax_get_barang_by_supplier.php?id_supplier=' + supplierId)
                            .then(response => response.json())
                            .then(data => {
                                // Log response for debugging
                                console.log('Supplier API response:', data);
                                
                                // Clear previous options
                                barangSelect.empty().append('<option value="">Pilih Barang</option>');
                                
                                if (data.success && data.barang && data.barang.length > 0) {
                                    // Add new options
                                    data.barang.forEach(item => {
                                        barangSelect.append(`<option value="${item.id_barang}" 
                                                            data-satuan="${item.satuan || ''}"
                                                            data-stok="${item.stok || 0}">
                                                            ${item.nama_barang} (Stok: ${item.stok || 0} ${item.satuan || ''})
                                                            </option>`);
                                    });
                                    
                                    // Set supplier name
                                    $('#supplier').val(data.supplier_name || '');
                                } else {
                                    barangSelect.append('<option value="">Tidak ada barang untuk supplier ini</option>');
                                }
                                
                                // Refresh select2
                                barangSelect.trigger('change');
                            })
                            .catch(error => {
                                console.error('Error fetching barang data:', error);
                                barangSelect.empty().append('<option value="">Error: Gagal memuat data</option>');
                                alert('Terjadi kesalahan saat memuat data barang. Silakan coba lagi.');
                            });
                    } else {
                        // Disable and reset barang dropdown
                        barangSelect.prop('disabled', true);
                        barangSelect.empty().append('<option value="">Pilih Supplier Terlebih Dahulu</option>');
                        $('#barang_satuan').val('');
                        $('#supplier').val('');
                    }
                });
                console.log("Supplier select2 initialized successfully");
            } catch (error) {
                console.error("Error initializing supplier select2:", error);
            }
        }
        
        // Initialize select2 for barang dropdown in direct retur modal
        function initBarangSelect2() {
            try {
                console.log("Initializing barang select2...");
                $('.select-barang').select2({
                    dropdownParent: $('#addDirectReturModal'),
                    placeholder: "Pilih Barang",
                    width: '100%'
                }).on('change', function() {
                    const selectedOption = $(this).find('option:selected');
                    const satuan = selectedOption.data('satuan');
                    const stok = selectedOption.data('stok');
                    
                    // Set values to form fields
                    $('#barang_satuan').val(satuan || '');
                    
                    // Set max value for qty_retur
                    $('#qty_retur').attr('max', stok);
                });
                console.log("Barang select2 initialized successfully");
            } catch (error) {
                console.error("Error initializing barang select2:", error);
            }
        }
        
        // Initialize select2 for supplier and barang dropdowns with delay to ensure modal is fully loaded
        $('#addReturDropdown').on('click', function() {
            setTimeout(function() {
                initSupplierSelect2();
                initBarangSelect2();
            }, 500);
        });
        
        // Also initialize when document is ready
        setTimeout(function() {
            initSupplierSelect2();
            initBarangSelect2();
        }, 1000);
        
        // Event handler for direct retur modal button
        $(document).on('click', '#showDirectReturModal', function() {
            showModal('addDirectReturModal');
            setTimeout(function() {
                initSupplierSelect2();
                initBarangSelect2();
            }, 300);
        });
    });
    
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    function deleteRetur(id, id_retur, tanggal, namaBarang) {
        document.getElementById('delete_id_bahan_baku').value = id;
        document.getElementById('delete_id_retur').value = id_retur || '';
        document.getElementById('delete_retur_text').textContent = `Anda yakin ingin menghapus retur bahan baku "${namaBarang}" tanggal ${tanggal}?`;
        showModal('deleteReturModal');
    }
    
    function viewBahanBakuRetur(id) {
        // Load detail via AJAX
        const detailContainer = document.getElementById('bahan_baku_retur_detail_content');
        if (detailContainer) {
            detailContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-blue-500 text-2xl"></i><p class="mt-2 text-gray-500">Memuat data...</p></div>';
            
            // Show modal
            showModal('viewBahanBakuReturModal');
            
            // Fetch data
            fetch('ajax_get_bahan_baku_retur.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    detailContainer.innerHTML = html;
                })
                .catch(error => {
                    detailContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-exclamation-circle text-red-500 text-2xl"></i><p class="mt-2 text-red-500">Gagal memuat data: ' + error.message + '</p></div>';
                });
        }
    }
    
    function deleteDirectRetur(id, tanggal, namaBarang) {
        document.getElementById('delete_direct_id_retur').value = id;
        document.getElementById('delete_direct_retur_text').textContent = `Anda yakin ingin menghapus retur "${namaBarang}" tanggal ${tanggal}?`;
        showModal('deleteDirectReturModal');
    }
    
    // Initialize variables
    let totalPages = <?= isset($total_pages) ? $total_pages : 1 ?>;
    let recordsPerPage = <?= isset($records_per_page) ? $records_per_page : 10 ?>;
    
    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        if (dropdown) {
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
            }
        }
    }
    
    function changePerPage(perPage) {
        let url = new URL(window.location.href);
        url.searchParams.set('page', 1); // Reset to first page
        url.searchParams.set('per_page', perPage);
        window.location.href = url.toString();
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('#exportDropdown') && !event.target.closest('#exportOptions')) {
            const dropdown = document.getElementById('exportOptions');
            if (dropdown && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        }
        
        if (!event.target.closest('#addReturDropdown') && !event.target.closest('#returOptions')) {
            const dropdown = document.getElementById('returOptions');
            if (dropdown && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        }
    });
    
    function debugSupplierData() {
        // Get supplier dropdown
        const supplierSelect = document.getElementById('id_supplier');
        const options = supplierSelect.options;
        
        // Create debug info
        let debugInfo = "Supplier Dropdown Debug Info:\n";
        debugInfo += "Total options: " + options.length + "\n\n";
        
        for (let i = 0; i < options.length; i++) {
            debugInfo += "Option " + i + ": value='" + options[i].value + "', text='" + options[i].text + "'\n";
        }
        
        // Check if select2 is initialized
        const select2Container = document.querySelector('.select2-container--default');
        debugInfo += "\nSelect2 initialized: " + (select2Container !== null);
        
        // Display debug info
        alert(debugInfo);
        console.log(debugInfo);
        
        // Fetch all suppliers from database for debugging
        fetch('ajax_debug_suppliers.php')
            .then(response => response.json())
            .then(data => {
                console.log("All suppliers from database:", data);
                if (data.suppliers && data.suppliers.length > 0) {
                    let supplierInfo = "All Suppliers in Database:\n\n";
                    data.suppliers.forEach((supplier, index) => {
                        supplierInfo += `${index + 1}. ID: ${supplier.id_supplier}, Name: ${supplier.nama_supplier}\n`;
                    });
                    console.log(supplierInfo);
                    alert(supplierInfo);
                } else {
                    alert("No suppliers found in database!");
                }
            })
            .catch(error => {
                console.error("Error fetching suppliers:", error);
                alert("Error fetching suppliers: " + error.message);
            });
        
        // Try to manually trigger select2 initialization
        try {
            $('.select-supplier').select2('destroy').select2({
                dropdownParent: $('#addDirectReturModal'),
                placeholder: "Pilih Supplier",
                width: '100%',
                allowClear: true,
                minimumResultsForSearch: 0
            });
            alert("Select2 re-initialized successfully");
        } catch (error) {
            alert("Error re-initializing Select2: " + error.message);
        }
    }
</script>

<?php
require_once 'includes/footer.php';
?> 