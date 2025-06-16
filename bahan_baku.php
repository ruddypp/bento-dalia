<?php
$pageTitle = "Data Bahan Baku";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php'; // Tambahkan ini untuk memeriksa hak akses

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_bahan_baku'])) {
        // Add new bahan baku
        $id_barang_array = isset($_POST['id_barang']) ? $_POST['id_barang'] : [];
        $qty_array = isset($_POST['qty']) ? $_POST['qty'] : [];
        $periode_array = isset($_POST['periode']) ? $_POST['periode'] : [];
        $harga_satuan_array = isset($_POST['harga_satuan']) ? $_POST['harga_satuan'] : [];
        $lokasi_array = isset($_POST['lokasi']) ? $_POST['lokasi'] : [];
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Check if we have items
            if (empty($id_barang_array)) {
                throw new Exception("Tidak ada item yang dipilih");
            }
            
            $user_id = $_SESSION['user_id']; // Use session user ID
            $success_count = 0;
            
            // Process each item
            foreach ($id_barang_array as $key => $id_barang) {
                // Skip empty items
                if (empty($id_barang) || empty($qty_array[$key])) {
                    continue;
                }
                
                $id_barang = (int)$id_barang;
                $qty = (int)$qty_array[$key];
                $periode = (int)$periode_array[$key];
                $harga_satuan = (float)$harga_satuan_array[$key];
                $lokasi = sanitize($lokasi_array[$key]);
                
                // Calculate total
                $total = $qty * $harga_satuan;
                
                // Insert into bahan_baku table with pending status
                // Barang belum masuk ke stok sampai diverifikasi/diapprove
                $query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iiiddsi", $id_barang, $qty, $periode, $harga_satuan, $total, $lokasi, $user_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Gagal menambahkan bahan baku: " . mysqli_stmt_error($stmt));
                }
                
                $id_bahan_baku = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                
                // Get barang details for log
                $barang_query = "SELECT nama_barang FROM barang WHERE id_barang = ?";
                $barang_stmt = mysqli_prepare($conn, $barang_query);
                mysqli_stmt_bind_param($barang_stmt, "i", $id_barang);
                mysqli_stmt_execute($barang_stmt);
                $barang_result = mysqli_stmt_get_result($barang_stmt);
                $barang_data = mysqli_fetch_assoc($barang_result);
                $nama_barang = $barang_data['nama_barang'];
                mysqli_stmt_close($barang_stmt);
                
                // Log activity
                logActivity($user_id, "Menambahkan bahan baku: $nama_barang, qty: $qty, periode: $periode");
                
                $success_count++;
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            if ($success_count > 0) {
                setAlert("success", "$success_count bahan baku berhasil ditambahkan dengan status pending! Silakan verifikasi untuk memasukkan ke stok.");
            } else {
                setAlert("warning", "Tidak ada bahan baku yang ditambahkan.");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            setAlert("error", $e->getMessage());
        }
        
        // Redirect to refresh page
        header("Location: bahan_baku.php");
        exit();
    }
    elseif (isset($_POST['delete_bahan_baku'])) {
        // Check if user has permission to delete bahan baku
        if ($_SESSION['user_role'] === 'kasir') {
            setAlert("error", "Anda tidak memiliki akses untuk menghapus bahan baku");
            header("Location: bahan_baku.php");
            exit();
        }
        
        // Delete bahan baku
        $id = (int)$_POST['id_bahan_baku'];
        
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
            
            // Update stock in barang table (reduce it back)
            $update_query = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ii", $bahan_baku['qty'], $bahan_baku['id_barang']);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stmt));
            }
            mysqli_stmt_close($update_stmt);
            
            // Reset periode value in barang table
            $periode = $bahan_baku['periode'];
            $periode_field = "periode_" . $periode;
            $reset_periode_query = "UPDATE barang SET $periode_field = NULL WHERE id_barang = ?";
            $reset_periode_stmt = mysqli_prepare($conn, $reset_periode_query);
            mysqli_stmt_bind_param($reset_periode_stmt, "i", $bahan_baku['id_barang']);
            
            if (!mysqli_stmt_execute($reset_periode_stmt)) {
                throw new Exception("Gagal mereset nilai periode barang: " . mysqli_stmt_error($reset_periode_stmt));
            }
            mysqli_stmt_close($reset_periode_stmt);
            
            // Delete bahan baku
            $delete_query = "DELETE FROM bahan_baku WHERE id_bahan_baku = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $id);
            
            if (!mysqli_stmt_execute($delete_stmt)) {
                throw new Exception("Gagal menghapus bahan baku: " . mysqli_stmt_error($delete_stmt));
            }
            mysqli_stmt_close($delete_stmt);
            
            // Log activity
            logActivity(1, "Menghapus bahan baku: " . $bahan_baku['nama_barang']);
            
            // Commit transaction
            mysqli_commit($conn);
            
            setAlert("success", "Bahan baku berhasil dihapus!");
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            setAlert("error", $e->getMessage());
        }
        
        // Redirect to refresh page
        header("Location: bahan_baku.php");
        exit();
    }
    elseif (isset($_POST['edit_bahan_baku'])) {
        // Edit bahan baku
        $id = (int)$_POST['id_bahan_baku'];
        $qty = (int)$_POST['qty'];
        $periode = (int)$_POST['periode'];
        $harga_satuan = (float)$_POST['harga_satuan'];
        $lokasi = sanitize($_POST['lokasi']);
        $status = sanitize($_POST['status']);
        
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
            
            // Hanya boleh mengubah status dari pending ke approved
            if ($bahan_baku['status'] != 'pending' && $status != $bahan_baku['status']) {
                throw new Exception("Status hanya dapat diubah dari Pending ke Approved");
            }
            
            // Pastikan status valid - hanya pending atau approved yang diperbolehkan
            if ($status != 'pending' && $status != 'approved') {
                $status = $bahan_baku['status']; // Kembalikan ke status asli
            }
            
            // Update bahan baku
            $update_query = "UPDATE bahan_baku SET qty = ?, periode = ?, harga_satuan = ?, total = ?, lokasi = ?, status = ? WHERE id_bahan_baku = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "iiddssi", $qty, $periode, $harga_satuan, $total, $lokasi, $status, $id);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Gagal mengupdate bahan baku: " . mysqli_stmt_error($update_stmt));
            }
            mysqli_stmt_close($update_stmt);
            
            // Jika status diubah menjadi approved, update stok barang
            if ($status == 'approved' && $bahan_baku['status'] != 'approved') {
                // Update stock in barang table
                $update_stock_query = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
                $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
                mysqli_stmt_bind_param($update_stock_stmt, "ii", $qty, $bahan_baku['id_barang']);
                
                if (!mysqli_stmt_execute($update_stock_stmt)) {
                    throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stock_stmt));
                }
                mysqli_stmt_close($update_stock_stmt);
            
                // Update jumlah_masuk field to indicate the full quantity was accepted
                $update_jumlah_masuk = "UPDATE bahan_baku SET jumlah_masuk = ? WHERE id_bahan_baku = ?";
                $update_jumlah_stmt = mysqli_prepare($conn, $update_jumlah_masuk);
                mysqli_stmt_bind_param($update_jumlah_stmt, "ii", $qty, $id);

                if (!mysqli_stmt_execute($update_jumlah_stmt)) {
                    throw new Exception("Gagal mengupdate jumlah masuk: " . mysqli_stmt_error($update_jumlah_stmt));
                }
                mysqli_stmt_close($update_jumlah_stmt);
                
                // Create entry in barang_masuk
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
                
                mysqli_stmt_bind_param($masuk_stmt, "iisddii", 
                                      $bahan_baku['id_barang'], 
                                      $qty, 
                                      $_SESSION['user_id'],
                                      $lokasi, 
                                      $harga_satuan,
                                      $periode,
                                      $supplier_id);
                
                if (!mysqli_stmt_execute($masuk_stmt)) {
                    throw new Exception("Gagal menambahkan barang masuk: " . mysqli_stmt_error($masuk_stmt));
                }
                
                $id_masuk = mysqli_insert_id($conn);
                mysqli_stmt_close($masuk_stmt);
                
                // Check if there's already a laporan_masuk for today with this periode
                $today = date('Y-m-d');
                $check_laporan_query = "SELECT id_laporan_masuk FROM laporan_masuk WHERE DATE(tanggal_laporan) = ? AND periode = ?";
                $check_laporan_stmt = mysqli_prepare($conn, $check_laporan_query);
                mysqli_stmt_bind_param($check_laporan_stmt, "si", $today, $periode);
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
                    mysqli_stmt_bind_param($laporan_stmt, "ii", $user_id, $periode);
                    
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
                
                // If this bahan_baku is linked to a pesanan, update the pesanan status
                if (!empty($bahan_baku['id_pesanan'])) {
                    // Check if all items in the pesanan are now approved or retur
                    $check_pesanan_query = "SELECT COUNT(*) as total_items, 
                                           SUM(CASE WHEN status IN ('approved', 'retur') THEN 1 ELSE 0 END) as processed_items 
                                           FROM bahan_baku 
                                           WHERE id_pesanan = ?";
                    $check_pesanan_stmt = mysqli_prepare($conn, $check_pesanan_query);
                    mysqli_stmt_bind_param($check_pesanan_stmt, "i", $bahan_baku['id_pesanan']);
                    mysqli_stmt_execute($check_pesanan_stmt);
                    $check_result = mysqli_stmt_get_result($check_pesanan_stmt);
                    $pesanan_status = mysqli_fetch_assoc($check_result);
                    mysqli_stmt_close($check_pesanan_stmt);
                    
                    if ($pesanan_status['total_items'] == $pesanan_status['processed_items']) {
                        // All items are processed, mark pesanan as completed
                        $update_pesanan_query = "UPDATE pesanan_barang SET status = 'selesai' WHERE id_pesanan = ?";
                        
                        $update_pesanan_stmt = mysqli_prepare($conn, $update_pesanan_query);
                        mysqli_stmt_bind_param($update_pesanan_stmt, "i", $bahan_baku['id_pesanan']);
                        mysqli_stmt_execute($update_pesanan_stmt);
                        mysqli_stmt_close($update_pesanan_stmt);
                    }
                    // If not all items are processed, keep status as pending - no action needed
                }
                        
                // Log the approval
                logActivity($user_id, "Menyetujui bahan baku " . $bahan_baku['nama_barang'] . " dengan jumlah " . $qty . " " . $bahan_baku['satuan']);
            } else {
                // Log activity
                logActivity($_SESSION['user_id'], "Mengubah data bahan baku: " . $bahan_baku['nama_barang'] . ", status: " . $status);
                
                setAlert("success", "Data bahan baku berhasil diperbarui!");
            }
            
            // Commit transaction
            mysqli_commit($conn);
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            setAlert("error", $e->getMessage());
        }
        
        // Redirect to refresh page
        header("Location: bahan_baku.php");
        exit();
    }
    elseif (isset($_POST['retur_bahan_baku'])) {
        // Process retur bahan baku
        $id = (int)$_POST['id_bahan_baku'];
        $qty_retur = (int)$_POST['qty_retur'];
        $alasan_retur = sanitize($_POST['alasan_retur']);
        $supplier_retur = isset($_POST['supplier_retur']) ? sanitize($_POST['supplier_retur']) : null;
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Debug: Log supplier value
            error_log("Proses retur dimulai untuk bahan_baku ID: $id, qty_retur: $qty_retur, supplier: " . ($supplier_retur ? $supplier_retur : "empty"));
            
            // ALUR PROSES RETUR:
            // 1. Ambil data bahan baku yang akan diretur
            // 2. Simpan data retur ke tabel retur_barang dengan informasi lengkap
            // 3. Jika ada sisa (qty - qty_retur > 0), buat entry baru untuk sisa dengan status approved
            // 4. Hapus data bahan baku yang diretur dari tabel bahan_baku
            // 5. Update stok barang hanya untuk jumlah yang tidak diretur
            
            // Get bahan baku details first
            $query = "SELECT bb.*, b.nama_barang, b.satuan 
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
            
            // Validate qty_retur
            if ($qty_retur <= 0 || $qty_retur > $bahan_baku['qty']) {
                throw new Exception("Jumlah retur tidak valid");
            }
            
            // Calculate values for retur
            $harga_satuan = $bahan_baku['harga_satuan'];
            $total_retur = $qty_retur * $harga_satuan;
            $periode = $bahan_baku['periode'];
            
            // Create retur record
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

            // Pastikan supplier_retur tidak NULL untuk binding parameter
            $supplier_value = $supplier_retur;
            if ($supplier_value === null) {
                $supplier_value = '';
            }
            
            // Pastikan id_pesanan tidak NULL
            $id_pesanan_value = !empty($bahan_baku['id_pesanan']) ? $bahan_baku['id_pesanan'] : 0;

            // Debug: Log parameter values
            error_log("Retur parameters - id_barang: {$bahan_baku['id_barang']}, qty_retur: {$qty_retur}, alasan: {$alasan_retur}, user: {$_SESSION['user_id']}, supplier: {$supplier_value}, harga: {$harga_satuan}, total: {$total_retur}, periode: {$periode}, id_pesanan: {$id_pesanan_value}");

            mysqli_stmt_bind_param($retur_stmt, "iisisidii", 
                                  $bahan_baku['id_barang'], 
                                  $qty_retur, 
                                  $alasan_retur,
                                  $_SESSION['user_id'],
                                  $supplier_value,
                                  $harga_satuan,
                                  $total_retur,
                                  $periode,
                                  $id_pesanan_value);
            
            if (!mysqli_stmt_execute($retur_stmt)) {
                $error_message = mysqli_stmt_error($retur_stmt);
                error_log("Error executing retur_stmt: " . $error_message);
                throw new Exception("Gagal menyimpan data retur: " . $error_message);
            }
            
            $id_retur = mysqli_insert_id($conn);
            error_log("Berhasil insert data retur dengan ID: " . $id_retur);
            mysqli_stmt_close($retur_stmt);
            
            // If the bahan_baku has approved status, update stock
            if ($bahan_baku['status'] == 'approved') {
                // Calculate the total price for retur items
                $total_retur = $qty_retur * $bahan_baku['harga_satuan'];
                
                // Reduce stock by qty_retur
                $update_stock_query = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
                $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
                mysqli_stmt_bind_param($update_stock_stmt, "ii", $qty_retur, $bahan_baku['id_barang']);
                
                if (!mysqli_stmt_execute($update_stock_stmt)) {
                    throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stock_stmt));
                }
                mysqli_stmt_close($update_stock_stmt);
            }
            
            // Handle partial retur
            if ($qty_retur < $bahan_baku['qty']) {
                // Calculate remaining qty and total
                $remaining_qty = $bahan_baku['qty'] - $qty_retur;
                $remaining_total = $remaining_qty * $harga_satuan;
                
                // Create new entry for remaining qty
                $remaining_query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input, id_pesanan) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)";
                $remaining_stmt = mysqli_prepare($conn, $remaining_query);
                mysqli_stmt_bind_param($remaining_stmt, "iiiddsii", 
                                      $bahan_baku['id_barang'], 
                                      $remaining_qty, 
                                      $periode,
                                      $harga_satuan,
                                      $remaining_total,
                                      $bahan_baku['lokasi'],
                                      $_SESSION['user_id'],
                                      $bahan_baku['id_pesanan']);
                
                if (!mysqli_stmt_execute($remaining_stmt)) {
                    throw new Exception("Gagal menyimpan data sisa: " . mysqli_stmt_error($remaining_stmt));
                }
                
                $new_bahan_baku_id = mysqli_insert_id($conn);
                mysqli_stmt_close($remaining_stmt);
                
                // If the original status was 'pending', we need to approve the remaining qty
                if ($bahan_baku['status'] == 'pending') {
                    // Update the new bahan_baku to approved and set jumlah_masuk
                    $approve_query = "UPDATE bahan_baku SET status = 'approved', jumlah_masuk = ? WHERE id_bahan_baku = ?";
                    $approve_stmt = mysqli_prepare($conn, $approve_query);
                    mysqli_stmt_bind_param($approve_stmt, "ii", $remaining_qty, $new_bahan_baku_id);
                    
                    if (!mysqli_stmt_execute($approve_stmt)) {
                        throw new Exception("Gagal mengupdate status bahan baku sisa: " . mysqli_stmt_error($approve_stmt));
                    }
                    mysqli_stmt_close($approve_stmt);
                    
                    // Update stock for the remaining qty
                    $update_stock_query = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
                    $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
                    mysqli_stmt_bind_param($update_stock_stmt, "ii", $remaining_qty, $bahan_baku['id_barang']);
                    
                    if (!mysqli_stmt_execute($update_stock_stmt)) {
                        throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stock_stmt));
                    }
                    mysqli_stmt_close($update_stock_stmt);
                    
                    // Create entry in barang_masuk for the remaining qty
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
                    
                    mysqli_stmt_bind_param($masuk_stmt, "iisddii", 
                                         $bahan_baku['id_barang'], 
                                         $remaining_qty,
                                         $_SESSION['user_id'],
                                         $bahan_baku['lokasi'],
                                          $bahan_baku['harga_satuan'],
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
                    mysqli_stmt_bind_param($check_laporan_stmt, "si", $today, $periode);
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
                        mysqli_stmt_bind_param($laporan_stmt, "ii", $user_id, $periode);
                        
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
            }
            
            // Update original bahan_baku to retur status
            $update_query = "UPDATE bahan_baku SET status = 'retur', 
                           jumlah_retur = ?, 
                           jumlah_masuk = ?, 
                           catatan_retur = ? WHERE id_bahan_baku = ?";
            
            $remaining_qty = $bahan_baku['qty'] - $qty_retur;
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "iisi", $qty_retur, $remaining_qty, $alasan_retur, $id);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Gagal mengupdate status bahan baku: " . mysqli_stmt_error($update_stmt));
            }
            mysqli_stmt_close($update_stmt);
            
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
                if ($pesanan_status['total_items'] == $pesanan_status['processed_items']) {
                    $update_pesanan_query = "UPDATE pesanan_barang SET status = 'selesai' WHERE id_pesanan = ?";
                    $update_pesanan_stmt = mysqli_prepare($conn, $update_pesanan_query);
                    mysqli_stmt_bind_param($update_pesanan_stmt, "i", $bahan_baku['id_pesanan']);
                    
                    if (!mysqli_stmt_execute($update_pesanan_stmt)) {
                        throw new Exception("Gagal mengupdate status pesanan: " . mysqli_stmt_error($update_pesanan_stmt));
                    }
                    mysqli_stmt_close($update_pesanan_stmt);
                    
                    // Log activity for pesanan status update
                    logActivity($_SESSION['user_id'], "Menyelesaikan pesanan #{$bahan_baku['id_pesanan']} karena semua item telah diproses");
                } else {
                    // Keep status as 'pending' until all items are processed
                    // No need to update status here as we're removing the 'diproses' state
                    
                    // Log activity for pesanan status update
                    logActivity($_SESSION['user_id'], "Memproses item pesanan #{$bahan_baku['id_pesanan']}, menunggu semua item selesai diproses");
                }
            }
            
            // Log retur activity
            logActivity($_SESSION['user_id'], "Melakukan retur bahan baku {$bahan_baku['nama_barang']} sebanyak {$qty_retur} {$bahan_baku['satuan']} dari total {$bahan_baku['qty']} {$bahan_baku['satuan']}");
            
            // Commit transaction
            mysqli_commit($conn);
            
            setAlert("success", "Retur bahan baku berhasil diproses! " . ($remaining_qty > 0 ? "Sebanyak $remaining_qty {$bahan_baku['satuan']} telah ditambahkan ke stok." : "Tidak ada item yang ditambahkan ke stok."));
            
            // Redirect agar tidak terjadi double submit jika user refresh
            header("Location: bahan_baku.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan saat melakukan retur: " . $e->getMessage());
        }
        
        // Redirect to refresh page
        header("Location: bahan_baku.php");
        exit();
    }
}

// Filter periode
$filter_periode = isset($_GET['periode']) ? (int)$_GET['periode'] : 0;

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($records_per_page, [10, 25, 50, 100])) {
    $records_per_page = 10; // Default to 10 if invalid value
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Ensure page is at least 1
$offset = ($page - 1) * $records_per_page;

// Build query
$query = "SELECT bb.*, b.nama_barang, b.satuan 
          FROM bahan_baku bb 
          JOIN barang b ON bb.id_barang = b.id_barang
          LEFT JOIN pesanan_barang pb ON bb.id_pesanan = pb.id_pesanan";

// Add filter conditions if periode is selected
if ($filter_periode > 0) {
    $query .= " WHERE bb.periode = $filter_periode AND (pb.status != 'dibatalkan' OR pb.status IS NULL)";
} else {
    $query .= " WHERE (pb.status != 'dibatalkan' OR pb.status IS NULL)";
}

// Add ordering
$query .= " ORDER BY bb.tanggal_input DESC";

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM bahan_baku bb 
                JOIN barang b ON bb.id_barang = b.id_barang
                LEFT JOIN pesanan_barang pb ON bb.id_pesanan = pb.id_pesanan";
                
// Add filter conditions to count query
if ($filter_periode > 0) {
    $count_query .= " WHERE bb.periode = $filter_periode AND (pb.status != 'dibatalkan' OR pb.status IS NULL)";
} else {
    $count_query .= " WHERE (pb.status != 'dibatalkan' OR pb.status IS NULL)";
}

$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add LIMIT and OFFSET for pagination
$query .= " LIMIT $records_per_page OFFSET $offset";

// Execute the query
$bahan_baku_list = mysqli_query($conn, $query);

// Calculate totals for summary cards
$total_per_periode = array();
$total_retur_per_periode = array();

// Get totals per periode
for ($i = 1; $i <= 4; $i++) {
    // Get total for approved and pending items
    $total_query = "SELECT SUM(total) as total_nilai FROM bahan_baku WHERE periode = $i AND status IN ('approved', 'pending')";
    $total_result = mysqli_query($conn, $total_query);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_per_periode[$i] = $total_row['total_nilai'] ? $total_row['total_nilai'] : 0;
    
    // Get total for retur items
    $retur_query = "SELECT SUM(total) as total_retur FROM bahan_baku WHERE periode = $i AND status = 'retur'";
    $retur_result = mysqli_query($conn, $retur_query);
    $retur_row = mysqli_fetch_assoc($retur_result);
    if ($retur_row['total_retur']) {
        $total_retur_per_periode[$i] = $retur_row['total_retur'];
    }
}

?>

<!-- Main Content -->
<div class="container px-6 mx-auto">
    <h2 class="text-2xl font-semibold text-gray-700 mb-4">
        <i class="fas fa-cube mr-2"></i> Data Bahan Baku
    </h2>
    
    <div class="flex justify-between items-center mb-4">
        <nav class="text-black" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="index.php" class="text-gray-500 hover:text-blue-600">Dashboard</a>
                    <svg class="fill-current w-3 h-3 mx-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                        <path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                    </svg>
                </li>
                <li>
                    <span class="text-gray-700">Data Bahan Baku</span>
                </li>
            </ol>
        </nav>
        
        <div class="flex space-x-2">
            <div class="dropdown inline-block relative">
                <button class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md flex items-center">
                    <i class="fas fa-file-export mr-2"></i> Export 
                    <svg class="fill-current h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                    </svg>
                </button>
                <ul class="dropdown-menu absolute hidden text-gray-700 pt-1 z-10 w-32 right-0">
                    <li><a class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap border border-gray-200" href="export_bahan_baku.php?format=pdf<?= $filter_periode ? '&periode='.$filter_periode : '' ?>">
                        <i class="far fa-file-pdf text-red-500 mr-2"></i> PDF
                    </a></li>
                    <li><a class="rounded-b bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap border border-t-0 border-gray-200" href="export_bahan_baku.php?format=excel<?= $filter_periode ? '&periode='.$filter_periode : '' ?>">
                        <i class="far fa-file-excel text-green-600 mr-2"></i> Excel
                    </a></li>
                </ul>
            </div>
            <a href="print_bahan_baku.php" target="_blank" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-print mr-2"></i> Cetak
            </a>
            <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
            <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md" onclick="showModal('addBahanBakuModal')">
                <i class="fas fa-plus-circle mr-2"></i> Tambah Bahan Baku
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
        <h3 class="text-lg font-semibold mb-3">Filter Data</h3>
        <form method="GET" action="" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="periode" class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                <select id="periode" name="periode" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="0">Semua Periode</option>
                    <option value="1" <?= $filter_periode == 1 ? 'selected' : '' ?>>Periode 1</option>
                    <option value="2" <?= $filter_periode == 2 ? 'selected' : '' ?>>Periode 2</option>
                    <option value="3" <?= $filter_periode == 3 ? 'selected' : '' ?>>Periode 3</option>
                    <option value="4" <?= $filter_periode == 4 ? 'selected' : '' ?>>Periode 4</option>
                </select>
            </div>
            
            <div>
                <label for="per_page" class="block text-sm font-medium text-gray-700 mb-1">Data Per Halaman</label>
                <select id="per_page" name="per_page" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="10" <?= $records_per_page == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $records_per_page == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $records_per_page == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $records_per_page == 100 ? 'selected' : '' ?>>100</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <a href="bahan_baku.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md ml-2">
                    <i class="fas fa-sync-alt mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">

<?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500">Total Periode <?= $i ?></p>
                    <p class="text-xl font-semibold mt-1"><?= isset($total_per_periode[$i]) ? formatRupiah($total_per_periode[$i]) : formatRupiah(0) ?></p>
                </div>
                <div class="bg-blue-100 rounded-full p-2">
                    <i class="fas fa-calendar-alt text-blue-500"></i>
                </div>
            </div>
            <?php if (isset($total_retur_per_periode[$i])): ?>
            <div class="mt-2 pt-2 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <p class="text-xs text-gray-500">Total Retur:</p>
                    <p class="text-sm font-medium text-red-600"><?= formatRupiah($total_retur_per_periode[$i]) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
    
    <!-- Bahan Baku Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-gray-50 py-3 px-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-700">
                Daftar Bahan Baku
            </h3>
                <a href="retur_barang.php" class="text-blue-600 hover:text-blue-800 flex items-center">
                    <i class="fas fa-undo mr-1"></i> Lihat Daftar Retur Barang
                </a>
            </div>
        </div>
        
        <div class="p-4">
            <!-- Information note -->
            <div class="p-4 bg-blue-50 border-l-4 border-blue-500 mb-4">
                <p class="text-sm text-blue-700">
                    <strong>Catatan:</strong> Status "Approved" menunjukkan jumlah barang yang masuk ke stok, sedangkan status "Retur" menunjukkan jumlah barang yang diretur. 
                    Total harga dihitung sesuai jumlah barang di masing-masing status (800gr = Rp 8.000, 200gr = Rp 2.000).
                </p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white bahan-baku-table">
                    <thead>
                        <tr class="bg-gray-200 text-gray-700">
                            <th class="py-2 px-4 text-left">No</th>
                            <th class="py-2 px-4 text-left">Nama Barang</th>
                            <th class="py-2 px-4 text-left">Qty</th>
                            <th class="py-2 px-4 text-left">Satuan</th>
                            <th class="py-2 px-4 text-left">Periode</th>
                            <th class="py-2 px-4 text-left">Harga Satuan</th>
                            <th class="py-2 px-4 text-left">Total Harga</th>
                            <th class="py-2 px-4 text-left">Lokasi</th>
                            <th class="py-2 px-4 text-left">Tanggal Input</th>
                            <th class="py-2 px-4 text-left">Status</th>
                            <th class="py-2 px-4 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($item = mysqli_fetch_assoc($bahan_baku_list)): 
                        ?>
                        <tr class="border-b hover:bg-gray-100">
                            <td class="py-2 px-4"><?= $no++ ?></td>
                            <td class="py-2 px-4"><?= $item['nama_barang'] ?></td>
                            <td class="py-2 px-4">
                                <?php 
                                if ($item['status'] == 'approved') {
                                    // For approved items, show the approved quantity
                                    echo $item['qty'];
                                } 
                                elseif ($item['status'] == 'retur') {
                                    // For retur items, show the returned quantity
                                    echo $item['jumlah_retur'];
                                } 
                                else {
                                    // For pending items, show normal quantity
                                    echo $item['qty'];
                                }
                                ?>
                            </td>
                            <td class="py-2 px-4"><?= $item['satuan'] ?></td>
                            <td class="py-2 px-4">Periode <?= $item['periode'] ?></td>
                            <td class="py-2 px-4"><?= formatRupiah($item['harga_satuan']) ?></td>
                            <td class="py-2 px-4">
                                <?php
                                if ($item['status'] == 'retur') {
                                    // For retur items, calculate total based on returned quantity (in thousand format)
                                    $qty_retur = isset($item['jumlah_retur']) ? $item['jumlah_retur'] : $item['qty'];
                                    $harga_per_gram = $item['harga_satuan'] / 1000; // Convert to per gram
                                    $total_retur = $qty_retur * $harga_per_gram;
                                    echo formatRupiah($total_retur);
                                } 
                                elseif ($item['status'] == 'approved') {
                                    // For approved items, calculate based on approved quantity (in thousand format)
                                    $qty_approved = isset($item['jumlah_masuk']) && $item['jumlah_masuk'] > 0 ? 
                                                  $item['jumlah_masuk'] : $item['qty'];
                                    $harga_per_gram = $item['harga_satuan'] / 1000; // Convert to per gram
                                    $total_approved = $qty_approved * $harga_per_gram;
                                    echo formatRupiah($total_approved);
                                }
                                else {
                                    // For pending items, calculate based on quantity (in thousand format)
                                    $harga_per_gram = $item['harga_satuan'] / 1000; // Convert to per gram
                                    $total = $item['qty'] * $harga_per_gram;
                                    echo formatRupiah($total);
                                }
                                ?>
                            </td>
                            <td class="py-2 px-4"><?= $item['lokasi'] ?></td>
                            <td class="py-2 px-4"><?= date('d M Y H:i', strtotime($item['tanggal_input'])) ?></td>
                            <td class="py-2 px-4">
                                <?php
                                $statusClass = '';
                                switch($item['status']) {
                                    case 'pending':
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'approved':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        break;
                                    case 'retur':
                                        $statusClass = 'bg-red-100 text-red-800';
                                        break;
                                }
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                    <?= ucfirst($item['status']) ?>
                                </span>
                            </td>
                            <td class="py-2 px-4">
                                <?php if ($item['status'] == 'pending'): ?>
                                <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                        onclick="viewBahanBaku(<?= $item['id_bahan_baku'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
                                <button class="text-green-500 hover:text-green-700 mr-2" 
                                        onclick="editBahanBaku(<?= $item['id_bahan_baku'] ?>, '<?= $item['nama_barang'] ?>', <?= $item['qty'] ?>, '<?= $item['satuan'] ?>', <?= $item['periode'] ?>, <?= $item['harga_satuan'] ?>, '<?= $item['lokasi'] ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($_SESSION['user_role'] !== 'kasir'): // Only allow admin and other roles to delete ?>
                                <button class="text-red-500 hover:text-red-700 mr-2" 
                                        onclick="deleteBahanBaku(<?= $item['id_bahan_baku'] ?>, '<?= $item['nama_barang'] ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                                <button class="bg-yellow-600 hover:bg-yellow-700 text-white p-2 rounded mr-1" title="Retur"
                                        onclick="processRetur(<?= $item['id_bahan_baku'] ?>, '<?= $item['nama_barang'] ?>', <?= $item['qty'] ?>, '<?= $item['satuan'] ?>')">
                                    <i class="fas fa-undo-alt"></i>
                                </button>
                                <?php endif; ?>
                                <?php else: // approved ?>
                                <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                        onclick="viewBahanBaku(<?= $item['id_bahan_baku'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if(mysqli_num_rows($bahan_baku_list) == 0): ?>
                        <tr>
                            <td colspan="10" class="py-4 px-4 text-center text-gray-500">Tidak ada data bahan baku</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <?php if($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if($page > 1): ?>
                    <a href="?page=<?= $page-1 ?><?= $filter_periode ? '&periode='.$filter_periode : '' ?><?= '&per_page='.$records_per_page ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
                        echo '<a href="?page=1'.($filter_periode ? '&periode='.$filter_periode : '').'&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                        if($start_page > 2) {
                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }
                    }
                    
                    // Display page links
                    for($i = $start_page; $i <= $end_page; $i++) {
                        if($i == $page) {
                            echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">'.$i.'</span>';
                        } else {
                            echo '<a href="?page='.$i.($filter_periode ? '&periode='.$filter_periode : '').'&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$i.'</a>';
                        }
                    }
                    
                    // Always show last page
                    if($end_page < $total_pages) {
                        if($end_page < $total_pages - 1) {
                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }
                        echo '<a href="?page='.$total_pages.($filter_periode ? '&periode='.$filter_periode : '').'&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$total_pages.'</a>';
                    }
                    ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?><?= $filter_periode ? '&periode='.$filter_periode : '' ?><?= '&per_page='.$records_per_page ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
        </div>
    </div>
</div>

<!-- Add Bahan Baku Modal -->
<div id="addBahanBakuModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Tambah Bahan Baku</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addBahanBakuModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="bahanBakuForm">
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="periode" class="block text-gray-700 text-sm font-semibold mb-2">Periode</label>
                        <select id="periode_header" name="periode_header" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateAllPeriodes()">
                            <option value="1">Periode 1</option>
                            <option value="2">Periode 2</option>
                            <option value="3">Periode 3</option>
                            <option value="4">Periode 4</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="lokasi_header" class="block text-gray-700 text-sm font-semibold mb-2">Lokasi Default</label>
                        <select id="lokasi_header" name="lokasi_header" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateAllLokasi()">
                            <option value="">-- Pilih Lokasi --</option>
                            <option value="kitchen">Kitchen</option>
                            <option value="bar">Bar</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h4 class="text-md font-medium text-gray-800 mb-2">Detail Item Bahan Baku</h4>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barang</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="bahan-baku-items-body">
                                <tr class="border-b bahan-baku-item">
                                    <td class="py-2 px-2">
                                        <select name="id_barang[]" class="barang-select shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateSatuan(this)">
                                            <option value="">-- Pilih Barang --</option>
                                            <?php
                                            // Get all barang
                                            $barang_query = "SELECT id_barang, nama_barang, satuan, harga FROM barang ORDER BY nama_barang";
                                            $barang_result = mysqli_query($conn, $barang_query);
                                            
                                            while ($barang = mysqli_fetch_assoc($barang_result)) {
                                                echo "<option value=\"{$barang['id_barang']}\" data-satuan=\"{$barang['satuan']}\" data-harga=\"{$barang['harga']}\">{$barang['nama_barang']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" name="qty[]" class="qty-input shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" min="1" value="1" required onchange="calculateTotal(this.closest('.bahan-baku-item'))">
                                    </td>
                                    <td class="py-2 px-2">
                                        <select name="periode[]" class="periode-select shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="1">Periode 1</option>
                                            <option value="2">Periode 2</option>
                                            <option value="3">Periode 3</option>
                                            <option value="4">Periode 4</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" name="harga_satuan[]" class="harga-input shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" min="0" step="100" required onchange="calculateTotal(this.closest('.bahan-baku-item'))">
                                    </td>
                                    <td class="py-2 px-2">
                                        <select name="lokasi[]" class="lokasi-select shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="kitchen">Kitchen</option>
                                            <option value="bar">Bar</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <span class="total-display">Rp 0</span>
                                    </td>
                                    <td class="py-2 px-2">
                                        <button type="button" class="text-red-500 hover:text-red-700 remove-item-btn" onclick="removeItem(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-2">
                        <button type="button" class="text-blue-500 hover:text-blue-700 text-sm" onclick="addBahanBakuItem()">
                            <i class="fas fa-plus-circle mr-1"></i> Tambah Item
                        </button>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2" onclick="closeModal('addBahanBakuModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_bahan_baku" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Bahan Baku
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteBahanBakuModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_confirmation_text"></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_bahan_baku" name="id_bahan_baku">
                
                <div class="items-center px-4 py-3">
                    <button type="submit" name="delete_bahan_baku" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Hapus
                    </button>
                    <button type="button" onclick="closeModal('deleteBahanBakuModal')" class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Bahan Baku Modal -->
<div id="editBahanBakuModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="mx-auto p-0 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <!-- Modal Header -->
        <div class="bg-blue-50 py-3 px-6 rounded-t-md border-b border-blue-100">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-blue-800">
                    <i class="fas fa-edit mr-2"></i>Edit Bahan Baku
                </h3>
                <button type="button" class="text-blue-500 hover:text-blue-700 transition-colors" onclick="closeModal('editBahanBakuModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            </div>
            
        <!-- Modal Body -->
        <div class="p-5">
            <form method="POST" action="">
                <input type="hidden" id="edit_id_bahan_baku" name="id_bahan_baku">
                
                <!-- Two-column layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div>
                        <!-- Nama Barang -->
                    <div class="mb-4">
                            <label for="edit_nama_barang" class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                            <input type="text" id="edit_nama_barang" name="nama_barang" class="w-full border border-gray-300 rounded-md py-2 px-3 bg-gray-100 text-gray-700 focus:outline-none" readonly>
                    </div>
                    
                        <!-- Quantity -->
                    <div class="mb-4">
                            <label for="edit_qty" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number" id="edit_qty" name="qty" min="1" class="w-full border border-gray-300 rounded-md py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                        <!-- Satuan -->
                    <div class="mb-4">
                            <label for="edit_satuan" class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                            <input type="text" id="edit_satuan" name="satuan" class="w-full border border-gray-300 rounded-md py-2 px-3 bg-gray-100 text-gray-700 focus:outline-none" readonly>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <!-- Periode -->
                    <div class="mb-4">
                            <label for="edit_periode" class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                            <select id="edit_periode" name="periode" class="w-full border border-gray-300 rounded-md py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="1">Periode 1</option>
                            <option value="2">Periode 2</option>
                            <option value="3">Periode 3</option>
                            <option value="4">Periode 4</option>
                        </select>
                    </div>
                    
                        <!-- Harga Satuan -->
                    <div class="mb-4">
                            <label for="edit_harga_satuan" class="block text-sm font-medium text-gray-700 mb-1">Harga Satuan (Rp)</label>
                            <input type="number" id="edit_harga_satuan" name="harga_satuan" min="0" step="0.01" class="w-full border border-gray-300 rounded-md py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                        <!-- Lokasi -->
                    <div class="mb-4">
                            <label for="edit_lokasi" class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                            <select id="edit_lokasi" name="lokasi" class="w-full border border-gray-300 rounded-md py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="kitchen">Kitchen</option>
                            <option value="bar">Bar</option>
                        </select>
                        </div>
                    </div>
                    </div>
                    
                <!-- Status section with horizontal layout -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Status Dropdown -->
                        <div>
                            <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="edit_status" name="status" class="w-full border border-gray-300 rounded-md py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                        </select>
                            
                        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-md text-xs text-blue-700">
                            <p><i class="fas fa-info-circle mr-1"></i> <strong>Catatan:</strong> Status hanya dapat diubah dari Pending ke Approved. Untuk proses retur, gunakan tombol Retur pada tabel.</p>
                        </div>
                        </div>
                        
                        <!-- Status Info Box -->
                        <div class="p-2 bg-gray-50 border border-gray-200 rounded-md">
                            <h4 class="text-xs font-medium text-gray-700 mb-1">Keterangan Status:</h4>
                            <div class="text-xs text-gray-600 mb-1"><span class="font-medium">Pending:</span> Barang belum diverifikasi dan belum masuk stok</div>
                            <div class="text-xs text-gray-600 mb-1"><span class="font-medium">Approved:</span> Barang sudah diverifikasi dan masuk ke stok</div>
                            <div class="text-xs text-gray-600 mb-1"><span class="font-medium">Retur:</span> Barang akan diretur, pilih ini jika ada barang rusak</div>
                            <div class="text-xs text-gray-600"><span class="font-medium">Dibatalkan:</span> Pesanan dibatalkan dari pesan_barang</div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="mt-5 pt-3 flex justify-end space-x-3 border-t border-gray-200">
                    <button type="button" onclick="closeModal('editBahanBakuModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" name="edit_bahan_baku" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Bahan Baku Modal -->
<div id="viewBahanBakuModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900" id="view_modal_title">Detail Bahan Baku</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('viewBahanBakuModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Status Info Banner -->
            <div id="view_status_banner" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4 mt-4 hidden">
                <div class="flex items-start">
                    <div class="text-yellow-600 mr-3">
                        <i class="fas fa-info-circle text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-yellow-800 font-medium mb-1" id="view_banner_title">Informasi Bahan Baku</h4>
                        <p class="text-sm text-yellow-700" id="view_banner_text">
                            Detail bahan baku yang telah diproses.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 mt-4">
                <!-- Informasi Bahan Baku -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <h4 class="text-gray-700 font-medium mb-3 border-b pb-2">Informasi Bahan Baku</h4>
                    <div class="space-y-2">
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Nama Barang</div>
                            <div class="text-sm font-medium" id="view_nama_barang">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Jumlah Total</div>
                            <div class="text-sm font-medium" id="view_qty">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Periode</div>
                            <div class="text-sm font-medium" id="view_periode">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Harga Satuan</div>
                            <div class="text-sm font-medium" id="view_harga_satuan">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Total</div>
                            <div class="text-sm font-medium" id="view_total">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Lokasi</div>
                            <div class="text-sm font-medium" id="view_lokasi">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Tanggal Input</div>
                            <div class="text-sm font-medium" id="view_tanggal_input">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Status</div>
                            <div class="text-sm" id="view_status_container">
                                <span id="view_status" class="px-2 py-1 rounded-full text-xs font-medium">-</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Input Oleh</div>
                            <div class="text-sm font-medium" id="view_input_oleh">-</div>
                        </div>
                    </div>
                </div>
                
                <!-- Informasi Retur/Approved -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" id="view_detail_container">
                    <h4 class="text-gray-700 font-medium mb-3 border-b pb-2" id="view_detail_title">Informasi Retur</h4>
                    <div class="space-y-2" id="view_retur_info">
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Tanggal Retur</div>
                            <div class="text-sm font-medium" id="view_tanggal_retur">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Jumlah Diretur</div>
                            <div class="text-sm font-medium text-red-600" id="view_jumlah_retur">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Jumlah Masuk Stok</div>
                            <div class="text-sm font-medium text-green-600" id="view_jumlah_masuk">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Alasan Retur</div>
                            <div class="text-sm" id="view_catatan_retur">-</div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 hidden" id="view_approved_info">
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Tanggal Approval</div>
                            <div class="text-sm font-medium" id="view_tanggal_approved">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Jumlah Masuk Stok</div>
                            <div class="text-sm font-medium text-green-600" id="view_jumlah_approved">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Status Laporan</div>
                            <div class="text-sm font-medium" id="view_status_laporan">-</div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 hidden" id="view_pending_info">
                        <div class="p-3 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-yellow-700">
                                <i class="fas fa-info-circle mr-2"></i>
                                Bahan baku ini masih dalam status pending. Silakan verifikasi untuk memasukkan ke stok.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ringkasan Biaya -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6" id="view_biaya_container">
                <h4 class="text-gray-700 font-medium mb-3 border-b pb-2">Ringkasan Biaya</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-sm text-gray-500">Total Nilai Awal</div>
                        <div class="text-lg font-semibold" id="view_nilai_awal">-</div>
                        <div class="text-xs text-gray-500 mt-1" id="view_nilai_awal_detail">-</div>
                    </div>
                    
                    <div class="bg-red-50 p-3 rounded-lg" id="view_nilai_retur_container">
                        <div class="text-sm text-red-500">Nilai Diretur</div>
                        <div class="text-lg font-semibold text-red-600" id="view_nilai_retur">-</div>
                        <div class="text-xs text-red-500 mt-1" id="view_nilai_retur_detail">-</div>
                    </div>
                    
                    <div class="bg-green-50 p-3 rounded-lg" id="view_nilai_masuk_container">
                        <div class="text-sm text-green-500">Nilai Masuk Stok</div>
                        <div class="text-lg font-semibold text-green-600" id="view_nilai_masuk">-</div>
                        <div class="text-xs text-green-500 mt-1" id="view_nilai_masuk_detail">-</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('viewBahanBakuModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Retur Modal -->
<div id="returModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
        <div class="bg-yellow-50 py-3 px-4 rounded-t-lg flex justify-between items-center border-b border-yellow-100">
            <h3 class="text-yellow-800 font-medium flex items-center">
                <i class="fas fa-undo-alt mr-2"></i> Proses Retur Bahan Baku
            </h3>
            <button type="button" class="text-yellow-800 hover:text-yellow-900" onclick="closeReturModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4">
            <div class="bg-blue-50 rounded-md p-3 mb-4 text-sm text-blue-800 border border-blue-100">
                <i class="fas fa-info-circle mr-2"></i> 
                Retur memungkinkan Anda untuk mengembalikan sebagian atau seluruh bahan baku yang belum masuk ke stok.
                <ul class="list-disc list-inside mt-2 ml-4">
                    <li>Jumlah Retur: Jumlah barang yang akan dikembalikan/tidak diterima</li>
                    <li>Jumlah Masuk: Jumlah barang yang akan dimasukkan ke stok</li>
                </ul>
            </div>
            
            <form method="POST" id="returForm">
                <input type="hidden" name="id_bahan_baku" id="returId">
                <input type="hidden" name="retur_bahan_baku" value="1">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                        <div id="returNamaBarang" class="py-2 px-3 bg-gray-100 rounded-md text-gray-800"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Total</label>
                        <div id="returJumlahTotal" class="py-2 px-3 bg-gray-100 rounded-md text-gray-800"></div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="qty_retur" class="block text-sm font-medium text-gray-700 mb-1">Jumlah Retur</label>
                            <div class="relative">
                                <input type="number" id="qty_retur" name="qty_retur" min="1" step="1" required 
                                    class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <span id="returSatuan1" class="absolute right-2 top-2 text-gray-500"></span>
                            </div>
                            <p class="mt-1 text-xs text-red-600">Jumlah yang akan diretur (dikembalikan)</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Masuk (Otomatis)</label>
                            <div class="relative">
                                <input type="text" id="jumlah_masuk_display" readonly 
                                    class="w-full border border-gray-200 bg-gray-50 rounded-md py-2 px-3">
                                <span id="returSatuan2" class="absolute right-2 top-2 text-gray-500"></span>
                            </div>
                            <p class="mt-1 text-xs text-green-600">Jumlah yang akan masuk ke stok</p>
                        </div>
                    </div>
                    
                    <div>
                        <label for="supplier_retur" class="block text-sm font-medium text-gray-700 mb-1">Supplier (opsional)</label>
                        <select id="supplier_retur" name="supplier_retur" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Pilih Supplier --</option>
                            <?php
                            // Get all suppliers
                            $supplier_query = "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier";
                            $supplier_result = mysqli_query($conn, $supplier_query);
                            
                            while ($supplier = mysqli_fetch_assoc($supplier_result)) {
                                echo "<option value=\"{$supplier['nama_supplier']}\">{$supplier['nama_supplier']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="alasan_retur" class="block text-sm font-medium text-gray-700 mb-1">Alasan Retur</label>
                        <textarea id="alasan_retur" name="alasan_retur" rows="3" required
                            class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Tuliskan alasan pengembalian barang..."></textarea>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-all"
                            onclick="closeReturModal()">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-all">
                        Proses Retur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show modal function
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        // Remove hidden class and ensure flex display
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        
        // Make sure modal is centered
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        
        // Fix overflow for body to prevent background scrolling
        document.body.style.overflow = 'hidden';
        
        // Set max height for modal content to prevent overflow
        const modalContent = modal.querySelector('.mx-auto');
        if (modalContent) {
            // Calculate maximum height (90% of viewport)
            const maxHeight = window.innerHeight * 0.9;
            modalContent.style.maxHeight = maxHeight + 'px';
            modalContent.style.overflow = 'auto';
            
            // For large modals, ensure proper width
            if (modalId === 'addBahanBakuModal' || 
                modalId === 'editBahanBakuModal' || 
                modalId === 'viewBahanBakuModal') {
                modalContent.style.width = '800px';
                modalContent.style.maxWidth = '90%';
            }
        }
    }
    
    // Close modal function
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        modal.classList.add('hidden');
        modal.style.display = 'none';
        
        // Restore body scrolling
        document.body.style.overflow = '';
    }
    
    // Delete bahan baku function
    function deleteBahanBaku(id, nama) {
        document.getElementById('delete_id_bahan_baku').value = id;
        document.getElementById('delete_confirmation_text').innerText = 'Apakah Anda yakin ingin menghapus bahan baku "' + nama + '"?';
        
        showModal('deleteBahanBakuModal');
    }
    
    // Process retur
    function processRetur(id, namaBarang, qty, satuan) {
        document.getElementById('returId').value = id;
        document.getElementById('returNamaBarang').textContent = namaBarang;
        document.getElementById('returJumlahTotal').textContent = qty + ' ' + satuan;
        document.getElementById('returSatuan1').textContent = satuan;
        document.getElementById('returSatuan2').textContent = satuan;
                    
        // Set max value for qty_retur
        const qtyReturInput = document.getElementById('qty_retur');
        qtyReturInput.max = qty;
        qtyReturInput.value = qty; // Default to full return
        
        // Calculate and display jumlah_masuk (remaining items)
        calculateJumlahMasuk();
        
        // Show the modal
        document.getElementById('returModal').classList.remove('hidden');
    }
    
    function calculateJumlahMasuk() {
                    const qtyReturInput = document.getElementById('qty_retur');
        const jumlahMasukDisplay = document.getElementById('jumlah_masuk_display');
        const totalQtyText = document.getElementById('returJumlahTotal').textContent;
        const totalQty = parseInt(totalQtyText.split(' ')[0]) || 0;
        
        const qtyRetur = parseInt(qtyReturInput.value) || 0;
        const jumlahMasuk = totalQty - qtyRetur;
        
        jumlahMasukDisplay.value = jumlahMasuk >= 0 ? jumlahMasuk : 0;
    }
    
    function closeReturModal() {
        document.getElementById('returModal').classList.add('hidden');
    }
    
    // View bahan baku function
    function viewBahanBaku(id) {
        console.log('Fetching bahan baku details for ID:', id);
        
        // Show loading indicator
        const loadingHtml = `
            <div class="flex items-center justify-center h-40">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                    <p class="mt-2 text-gray-600">Loading...</p>
                </div>
            </div>
        `;
        
        // Show modal with loading indicator
        document.getElementById('viewBahanBakuModal').classList.remove('hidden');
        
        // Set loading state for the right panel
        const detailContainer = document.getElementById('view_detail_container');
        if (detailContainer) {
            detailContainer.innerHTML = loadingHtml;
        }
        
        // Fetch bahan baku details via AJAX
        fetch('ajax_get_bahan_baku.php?id=' + id)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                
                if (data.success) {
                    const bahan = data.bahan_baku;
                    
                    // Reset the detail container to its original state
                    if (detailContainer) {
                        detailContainer.innerHTML = `
                            <h4 class="text-gray-700 font-medium mb-3 border-b pb-2" id="view_detail_title">Informasi</h4>
                            <div class="space-y-2" id="view_retur_info">
                                <div class="grid grid-cols-2">
                                    <div class="text-sm text-gray-500">Tanggal Retur</div>
                                    <div class="text-sm font-medium" id="view_tanggal_retur">-</div>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="text-sm text-gray-500">Jumlah Diretur</div>
                                    <div class="text-sm font-medium text-red-600" id="view_jumlah_retur">-</div>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="text-sm text-gray-500">Jumlah Masuk Stok</div>
                                    <div class="text-sm font-medium text-green-600" id="view_jumlah_masuk">-</div>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="text-sm text-gray-500">Alasan Retur</div>
                                    <div class="text-sm" id="view_catatan_retur">-</div>
                                </div>
                            </div>
                            
                            <div class="space-y-2 hidden" id="view_approved_info">
                                <div class="grid grid-cols-2">
                                    <div class="text-sm text-gray-500">Tanggal Approval</div>
                                    <div class="text-sm font-medium" id="view_tanggal_approved">-</div>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="text-sm text-gray-500">Jumlah Masuk Stok</div>
                                    <div class="text-sm font-medium text-green-600" id="view_jumlah_approved">-</div>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="text-sm text-gray-500">Status Laporan</div>
                                    <div class="text-sm font-medium" id="view_status_laporan">-</div>
                                </div>
                            </div>
                            
                            <div class="space-y-2 hidden" id="view_pending_info">
                                <div class="p-3 bg-yellow-50 rounded-lg">
                                    <p class="text-sm text-yellow-700">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Bahan baku ini masih dalam status pending. Silakan verifikasi untuk memasukkan ke stok.
                                    </p>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Update modal content
                    updateModalContent(bahan);
                } else {
                    console.error('Error from server:', data.message);
                    if (detailContainer) {
                        detailContainer.innerHTML = `
                            <div class="p-4 bg-red-50 text-red-700 rounded-lg">
                                <p><i class="fas fa-exclamation-circle mr-2"></i> Error: ${data.message}</p>
                            </div>
                        `;
                    }
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                if (detailContainer) {
                    detailContainer.innerHTML = `
                        <div class="p-4 bg-red-50 text-red-700 rounded-lg">
                            <p><i class="fas fa-exclamation-circle mr-2"></i> Error: ${error.message}</p>
                        </div>
                    `;
                }
            });
    }
    
    // Helper function to show/hide elements
    function showElement(id) {
        const element = document.getElementById(id);
        if (element) element.classList.remove('hidden');
    }
    
    function hideElement(id) {
        const element = document.getElementById(id);
        if (element) element.classList.add('hidden');
    }
    
    // Update modal content with bahan baku data
    function updateModalContent(bahan) {
        // Set basic info
        document.getElementById('view_nama_barang').textContent = bahan.nama_barang;
        document.getElementById('view_qty').textContent = bahan.qty + ' ' + bahan.satuan;
        document.getElementById('view_periode').textContent = 'Periode ' + bahan.periode;
        document.getElementById('view_harga_satuan').textContent = formatRupiah(bahan.harga_satuan);
        document.getElementById('view_total').textContent = formatRupiah(bahan.total);
        document.getElementById('view_lokasi').textContent = bahan.lokasi || '-';
        document.getElementById('view_tanggal_input').textContent = formatDate(bahan.tanggal_input);
            
            // Set status with appropriate styling
            const statusElement = document.getElementById('view_status');
            if (statusElement) {
            statusElement.textContent = formatStatus(bahan.status);
            
            // Remove all status classes
            statusElement.classList.remove('bg-yellow-100', 'text-yellow-800', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'bg-gray-100', 'text-gray-800');
            
            // Add appropriate class based on status
            if (bahan.status === 'approved') {
                statusElement.classList.add('bg-green-100', 'text-green-800');
            } else if (bahan.status === 'pending') {
                statusElement.classList.add('bg-yellow-100', 'text-yellow-800');
            } else if (bahan.status === 'retur') {
                statusElement.classList.add('bg-red-100', 'text-red-800');
            } else if (bahan.status === 'dibatalkan') {
                statusElement.classList.add('bg-gray-100', 'text-gray-800');
            }
        }
        
        // Set status banner
        const statusBanner = document.getElementById('view_status_banner');
        if (statusBanner) {
            statusBanner.classList.remove('hidden');
            
            // Reset all status classes
            statusBanner.classList.remove('bg-green-50', 'border-green-200', 'bg-yellow-50', 'border-yellow-200', 'bg-red-50', 'border-red-200');
            
            const bannerTitle = document.getElementById('view_banner_title');
            const bannerText = document.getElementById('view_banner_text');
            
            // Add appropriate class based on status
            if (bahan.status === 'approved') {
                statusBanner.classList.add('bg-green-50', 'border-green-200');
                if (bannerTitle) bannerTitle.textContent = 'Bahan Baku Disetujui';
                if (bannerText) bannerText.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Bahan baku ini telah disetujui dan masuk ke stok.';
            } else if (bahan.status === 'pending') {
                statusBanner.classList.add('bg-yellow-50', 'border-yellow-200');
                if (bannerTitle) bannerTitle.textContent = 'Bahan Baku Pending';
                if (bannerText) bannerText.innerHTML = '<i class="fas fa-clock mr-2"></i> Bahan baku ini belum diverifikasi dan belum masuk stok.';
            } else if (bahan.status === 'retur') {
                statusBanner.classList.add('bg-red-50', 'border-red-200');
                if (bannerTitle) bannerTitle.textContent = 'Bahan Baku Diretur';
                if (bannerText) bannerText.innerHTML = '<i class="fas fa-undo-alt mr-2"></i> Bahan baku ini telah diretur sebagian atau seluruhnya.';
            } else if (bahan.status === 'dibatalkan') {
                statusBanner.classList.add('bg-gray-50', 'border-gray-200');
                if (bannerTitle) bannerTitle.textContent = 'Bahan Baku Dibatalkan';
                if (bannerText) bannerText.innerHTML = '<i class="fas fa-ban mr-2"></i> Pesanan ini telah dibatalkan.';
            }
        }
        
        // Show/hide appropriate sections based on status
            if (bahan.status === 'retur') {
                showElement('view_retur_info');
            hideElement('view_approved_info');
            hideElement('view_pending_info');
            hideElement('view_dibatalkan_info');
            
            // Update retur info
            document.getElementById('view_tanggal_retur').textContent = formatDate(bahan.tanggal_input);
            document.getElementById('view_jumlah_retur').textContent = bahan.jumlah_retur + ' ' + bahan.satuan;
            document.getElementById('view_jumlah_masuk').textContent = bahan.jumlah_masuk + ' ' + bahan.satuan;
            document.getElementById('view_catatan_retur').textContent = bahan.catatan_retur || '-';
                
            // Update title
            document.getElementById('view_detail_title').textContent = 'Informasi Retur';
            
            // Update ringkasan biaya
            const hargaSatuan = parseFloat(bahan.harga_satuan) || 0;
            const jumlahRetur = parseInt(bahan.jumlah_retur) || 0;
            const jumlahMasuk = parseInt(bahan.jumlah_masuk) || 0;
            const totalQty = parseInt(bahan.qty) || 0;
            
            const nilaiAwal = hargaSatuan * totalQty;
            const nilaiRetur = hargaSatuan * jumlahRetur;
            const nilaiMasuk = hargaSatuan * jumlahMasuk;
            
            // Untuk nilai total biaya, gunakan perhitungan sesuai tampilan tabel, dibagi 1000
            const nilaiAwalRibu = nilaiAwal / 1000;
            const nilaiReturRibu = nilaiRetur / 1000;
            const nilaiMasukRibu = nilaiMasuk / 1000;
            
            document.getElementById('view_nilai_awal').textContent = formatRupiah(nilaiAwalRibu);
            document.getElementById('view_nilai_awal_detail').textContent = `${totalQty} ${bahan.satuan}`;
            
            document.getElementById('view_nilai_retur').textContent = formatRupiah(nilaiReturRibu);
            document.getElementById('view_nilai_retur_detail').textContent = `${jumlahRetur} ${bahan.satuan}`;
            
            document.getElementById('view_nilai_masuk').textContent = formatRupiah(nilaiMasukRibu);
            document.getElementById('view_nilai_masuk_detail').textContent = `${jumlahMasuk} ${bahan.satuan}`;
                
            } else if (bahan.status === 'approved') {
            hideElement('view_retur_info');
                showElement('view_approved_info');
            hideElement('view_pending_info');
            hideElement('view_dibatalkan_info');
            
            // Update approved info
            document.getElementById('view_tanggal_approved').textContent = formatDate(bahan.tanggal_input);
            document.getElementById('view_jumlah_approved').textContent = bahan.qty + ' ' + bahan.satuan;
            document.getElementById('view_status_laporan').textContent = 'Sudah masuk ke laporan barang masuk';
            
            // Update title
            document.getElementById('view_detail_title').textContent = 'Informasi Approval';
            
            // Update ringkasan biaya
            const hargaSatuan = parseFloat(bahan.harga_satuan) || 0;
            const totalQty = parseInt(bahan.qty) || 0;
            const nilaiTotal = hargaSatuan * totalQty;
            
            // Untuk nilai total biaya, gunakan perhitungan sesuai tampilan tabel, dibagi 1000
            const nilaiTotalRibu = nilaiTotal / 1000;
            
            document.getElementById('view_nilai_awal').textContent = formatRupiah(nilaiTotalRibu);
            document.getElementById('view_nilai_awal_detail').textContent = `${totalQty} ${bahan.satuan}`;
            
            document.getElementById('view_nilai_masuk').textContent = formatRupiah(nilaiTotalRibu);
            document.getElementById('view_nilai_masuk_detail').textContent = `${totalQty} ${bahan.satuan}`;
            
        } else if (bahan.status === 'pending') {
            hideElement('view_retur_info');
            hideElement('view_approved_info');
                showElement('view_pending_info');
            hideElement('view_dibatalkan_info');
                
            // Update title
            document.getElementById('view_detail_title').textContent = 'Informasi Pending';
            
            // Update ringkasan biaya
            const hargaSatuan = parseFloat(bahan.harga_satuan) || 0;
            const totalQty = parseInt(bahan.qty) || 0;
            const nilaiTotal = hargaSatuan * totalQty;
            
            // Untuk nilai total biaya, gunakan perhitungan sesuai tampilan tabel, dibagi 1000
            const nilaiTotalRibu = nilaiTotal / 1000;
            
            document.getElementById('view_nilai_awal').textContent = formatRupiah(nilaiTotalRibu);
            document.getElementById('view_nilai_awal_detail').textContent = `${totalQty} ${bahan.satuan}`;
        } else if (bahan.status === 'dibatalkan') {
            hideElement('view_retur_info');
            hideElement('view_approved_info');
            hideElement('view_pending_info');
                
            // Update title
            document.getElementById('view_detail_title').textContent = 'Informasi Pembatalan';
            
            // Create dibatalkan info if it doesn't exist
            if (!document.getElementById('view_dibatalkan_info')) {
                const dibatalkanInfo = document.createElement('div');
                dibatalkanInfo.id = 'view_dibatalkan_info';
                dibatalkanInfo.className = 'space-y-2';
                dibatalkanInfo.innerHTML = `
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-ban mr-2"></i>
                            Pesanan ini telah dibatalkan dari sistem pesan barang.
                        </p>
                    </div>
                `;
                document.getElementById('view_detail_container').appendChild(dibatalkanInfo);
            } else {
                showElement('view_dibatalkan_info');
            }
            
            // Update ringkasan biaya
            const hargaSatuan = parseFloat(bahan.harga_satuan) || 0;
            const totalQty = parseInt(bahan.qty) || 0;
            const nilaiTotal = hargaSatuan * totalQty;
            
            // Untuk nilai total biaya, gunakan perhitungan sesuai tampilan tabel, dibagi 1000
            const nilaiTotalRibu = nilaiTotal / 1000;
            
            document.getElementById('view_nilai_awal').textContent = formatRupiah(nilaiTotalRibu);
            document.getElementById('view_nilai_awal_detail').textContent = `${totalQty} ${bahan.satuan}`;
        }
        }
        
    // Format date helper
    function formatDate(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }
    
    // Format status helper
    function formatStatus(status) {
        if (status === 'pending') return 'Pending';
        if (status === 'approved') return 'Approved';
        if (status === 'retur') return 'Retur';
        if (status === 'dibatalkan') return 'Dibatalkan';
        return status;
    }
    
    // Format rupiah helper
    function formatRupiah(angka) {
        if (!angka) return 'Rp 0';
        
        return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
    }
    
    // Add event listener to qty_retur input
    document.addEventListener('DOMContentLoaded', function() {
        const qtyReturInput = document.getElementById('qty_retur');
        if (qtyReturInput) {
            qtyReturInput.addEventListener('input', calculateJumlahMasuk);
        }
        
        // Initialize the first row in bahan baku form
        setupBahanBakuItemEvents(document.querySelector('.bahan-baku-item'));
    });
    
    // Add bahan baku item function
    function addBahanBakuItem() {
        const tbody = document.getElementById('bahan-baku-items-body');
        const template = document.querySelector('.bahan-baku-item').cloneNode(true);
        
        // Reset values
        template.querySelector('.barang-select').selectedIndex = 0;
        template.querySelector('.qty-input').value = 1;
        
        // Set periode from header
        const periodeHeader = document.getElementById('periode_header');
        if (periodeHeader) {
            const periodeSelect = template.querySelector('.periode-select');
            if (periodeSelect) {
                periodeSelect.value = periodeHeader.value;
            }
        }
        
        // Set lokasi from header
        const lokasiHeader = document.getElementById('lokasi_header');
        if (lokasiHeader && lokasiHeader.value) {
            const lokasiSelect = template.querySelector('.lokasi-select');
            if (lokasiSelect) {
                lokasiSelect.value = lokasiHeader.value;
            }
        }
        
        template.querySelector('.harga-input').value = '';
        template.querySelector('.total-display').textContent = 'Rp 0';
        
        // Add event listeners
        setupBahanBakuItemEvents(template);
        
        // Append to table
        tbody.appendChild(template);
    }
    
    // Remove item function
    function removeItem(button) {
        const tbody = document.getElementById('bahan-baku-items-body');
        const row = button.closest('.bahan-baku-item');
        
        // Don't remove if it's the last row
        if (tbody.querySelectorAll('.bahan-baku-item').length > 1) {
            tbody.removeChild(row);
        } else {
            // Reset values instead of removing
            row.querySelector('.barang-select').selectedIndex = 0;
            row.querySelector('.qty-input').value = 1;
            row.querySelector('.harga-input').value = '';
            row.querySelector('.total-display').textContent = 'Rp 0';
        }
    }
    
    // Calculate total function
    function calculateTotal(row) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const harga = parseFloat(row.querySelector('.harga-input').value) || 0;
        const total = qty * harga;
        
        row.querySelector('.total-display').textContent = formatRupiah(total);
    }
    
    // Setup events for bahan baku item row
    function setupBahanBakuItemEvents(row) {
        const qtyInput = row.querySelector('.qty-input');
        const hargaInput = row.querySelector('.harga-input');
        const barangSelect = row.querySelector('.barang-select');
        
        qtyInput.addEventListener('input', function() {
            calculateTotal(row);
        });
        
        hargaInput.addEventListener('input', function() {
            calculateTotal(row);
        });
        
        barangSelect.addEventListener('change', function() {
            updateSatuan(this);
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.harga) {
                hargaInput.value = selectedOption.dataset.harga;
                calculateTotal(row);
            }
        });
    }
    
    // Update satuan function
    function updateSatuan(select) {
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.dataset.satuan) return;
        
        // We don't need to update a satuan field anymore as it's shown in the dropdown
        console.log("Selected satuan: " + selectedOption.dataset.satuan);
    }
    
    // Update all periode selects based on header
    function updateAllPeriodes() {
        const periodeHeader = document.getElementById('periode_header');
        if (!periodeHeader) return;
        
        const periodeValue = periodeHeader.value;
        const periodeSelects = document.querySelectorAll('.periode-select');
        
        periodeSelects.forEach(select => {
            select.value = periodeValue;
        });
    }
    
    // Update all lokasi selects based on header
    function updateAllLokasi() {
        const lokasiHeader = document.getElementById('lokasi_header');
        if (!lokasiHeader || !lokasiHeader.value) return;
        
        const lokasiValue = lokasiHeader.value;
        const lokasiSelects = document.querySelectorAll('.lokasi-select');
        
        lokasiSelects.forEach(select => {
            select.value = lokasiValue;
        });
    }

    // Add event listener for edit button
    document.addEventListener('DOMContentLoaded', function() {
        // Set up event listeners for edit buttons
        const editButtons = document.querySelectorAll('.edit-bahan-baku');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                editBahanBaku(id);
            });
        });
        
        // Add event listener for status change
        const editStatusSelect = document.getElementById('edit_status');
        if (editStatusSelect) {
            editStatusSelect.addEventListener('change', function() {
                // Only allow changing from pending to approved
                if (this.value !== 'pending' && this.value !== 'approved') {
                    alert('Status hanya dapat diubah dari Pending ke Approved');
                    this.value = 'pending'; // Reset to pending
                }
            });
        }
    });
    
    // Edit bahan baku function
    function editBahanBaku(id) {
        // Fetch bahan baku data via AJAX
        fetch('ajax_get_bahan_baku.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const bahan = data.bahan_baku;
                    
                    // Populate form fields
                    document.getElementById('edit_id_bahan_baku').value = bahan.id_bahan_baku;
                    document.getElementById('edit_nama_barang').value = bahan.nama_barang;
                    document.getElementById('edit_qty').value = bahan.qty;
                    document.getElementById('edit_satuan').value = bahan.satuan;
                    document.getElementById('edit_periode').value = bahan.periode;
                    document.getElementById('edit_harga_satuan').value = bahan.harga_satuan;
                    document.getElementById('edit_lokasi').value = bahan.lokasi;
                    
                    // Set status value and handle status dropdown based on current status
                    const statusSelect = document.getElementById('edit_status');
                    statusSelect.value = bahan.status;
                    
                    // If status is not pending, disable status dropdown and show message
                    if (bahan.status !== 'pending') {
                        statusSelect.disabled = true;
                        alert('Status hanya dapat diubah dari "Pending" ke "Approved".');
                    } else {
                        statusSelect.disabled = false;
                    }
                    
                    // Show modal
                    showModal('editBahanBakuModal');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching bahan baku data:', error);
                alert('Error fetching data: ' + error.message);
            });
    }
</script>

<style>
    /* Dropdown menu styles */
    .dropdown {
        position: relative;
        display: inline-block;
    }
    
    .dropdown-menu {
        min-width: 160px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 1000;
        right: 0;
    }
    
    /* The dropdown will show on hover by default, but we also handle it with JS */
    .dropdown:hover .dropdown-menu {
        display: block;
    }
    
    /* Style for dropdown items */
    .dropdown-menu li a {
        transition: all 0.2s ease;
    }
    
    /* For mobile compatibility */
    @media (max-width: 768px) {
        .dropdown-menu {
            position: static;
            display: none;
            margin-top: 0.5rem;
            width: 100%;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?> 

<!-- CSS khusus untuk perbaikan modal pada halaman bahan baku -->
<style>
    /* Perbaikan khusus untuk modal pada halaman bahan baku */
    @media (min-width: 992px) {
        /* Mencegah scroll horizontal */
        body, html {
            overflow-x: hidden !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        /* Container utama */
        .container, .container-fluid {
            max-width: 100% !important;
            padding-left: 15px !important;
            padding-right: 15px !important;
            overflow-x: hidden !important;
        }
        
        /* Posisi modal di tengah layar */
        #addBahanBakuModal,
        #editBahanBakuModal,
        #viewBahanBakuModal,
        #returBahanBakuModal,
        #deleteBahanBakuModal {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        #addBahanBakuModal.hidden,
        #editBahanBakuModal.hidden,
        #viewBahanBakuModal.hidden,
        #returBahanBakuModal.hidden,
        #deleteBahanBakuModal.hidden {
            display: none !important;
        }
        
        /* Perbaikan posisi modal */
        #addBahanBakuModal .relative.top-20,
        #editBahanBakuModal .relative.top-20,
        #viewBahanBakuModal .relative.top-20,
        #returBahanBakuModal .relative.top-20,
        #deleteBahanBakuModal .relative.top-20 {
            margin: 0 auto !important;
            top: 0 !important;
            width: 800px !important;
            max-width: 90% !important;
            position: relative !important;
            transform: none !important;
            left: 0 !important;
        }
        
        /* Konten modal */
        #addBahanBakuModal .mt-3,
        #editBahanBakuModal .mt-3,
        #viewBahanBakuModal .mt-3,
        #returBahanBakuModal .mt-3,
        #deleteBahanBakuModal .mt-3 {
            max-height: calc(90vh - 4rem) !important;
            overflow-y: auto !important;
        }
        
        /* Tabel dalam modal */
        #bahan-baku-items-body td {
            padding: 0.5rem !important;
            vertical-align: middle !important;
        }
        
        /* Input dalam modal */
        #addBahanBakuModal input,
        #addBahanBakuModal select,
        #editBahanBakuModal input,
        #editBahanBakuModal select {
            height: calc(1.5em + 0.75rem + 2px) !important;
            font-size: 0.875rem !important;
            padding: 0.25rem 0.5rem !important;
        }
        
        /* Dropdown select */
        .barang-select {
            min-width: 200px !important;
        }
        
        /* Wrapper tabel */
        .overflow-x-auto {
            max-width: 100% !important;
            overflow-x: auto !important;
        }
        
        /* Ukuran kolom dalam tabel bahan baku */
        #addBahanBakuModal th:nth-child(1),
        #addBahanBakuModal td:nth-child(1) {
            width: 30% !important;
        }
        
        #addBahanBakuModal th:nth-child(2),
        #addBahanBakuModal td:nth-child(2) {
            width: 10% !important;
        }
        
        #addBahanBakuModal th:nth-child(3),
        #addBahanBakuModal td:nth-child(3) {
            width: 12% !important;
        }
        
        #addBahanBakuModal th:nth-child(4),
        #addBahanBakuModal td:nth-child(4) {
            width: 15% !important;
        }
        
        #addBahanBakuModal th:nth-child(5),
        #addBahanBakuModal td:nth-child(5) {
            width: 12% !important;
        }
        
        #addBahanBakuModal th:nth-child(6),
        #addBahanBakuModal td:nth-child(6) {
            width: 12% !important;
        }
        
        #addBahanBakuModal th:nth-child(7),
        #addBahanBakuModal td:nth-child(7) {
            width: 9% !important;
        }
    }
    
    /* Fix scroll horizontal pada tabel utama */
    .bg-white.shadow-md.rounded-lg.overflow-hidden {
        overflow-x: hidden !important;
    }
    
    .bg-white.shadow-md.rounded-lg.overflow-hidden .overflow-x-auto {
        max-width: 100% !important;
        overflow-x: auto !important;
    }
    
    /* Perbaikan lebar modal pada semua ukuran layar */
    .fixed.inset-0.bg-gray-600.bg-opacity-50.hidden.overflow-y-auto.h-full.w-full {
        overflow-x: hidden !important;
    }
    
    /* Override tampilan modal agar berada di tengah */
    .fixed.inset-0.bg-gray-600.bg-opacity-50:not(.hidden) {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
</style>

<!-- Script khusus untuk perbaikan modal pada halaman bahan baku -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi Select2 untuk modal
        function initSelect2InModal(modalId) {
            if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') return;
            
            const modal = document.getElementById(modalId);
            if (!modal) return;
            
            // Perbaiki semua select dalam modal
            const selects = modal.querySelectorAll('select');
            selects.forEach(select => {
                const $select = jQuery(select);
                
                // Destroy jika sudah ada select2
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                
                // Inisialisasi select2 dengan opsi yang tepat
                $select.select2({
                    width: '100%',
                    dropdownParent: jQuery(modal),
                    dropdownAutoWidth: true
                });
                
                // Perbaiki lebar container
                setTimeout(() => {
                    const container = $select.next('.select2-container');
                    if (container.length) {
                        container.css('width', '100%');
                    }
                }, 50);
            });
        }
        
        // Fungsi untuk memperbaiki modal bahan baku khusus untuk desktop
        function fixBahanBakuModal() {
            // Hanya untuk desktop
            if (window.innerWidth < 992) return;
            
            // Modal IDs untuk iterasi
            const modalIds = [
                'addBahanBakuModal', 
                'editBahanBakuModal', 
                'viewBahanBakuModal', 
                'returBahanBakuModal',
                'deleteBahanBakuModal'
            ];
            
            // Perbaiki semua modal
            modalIds.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (!modal) return;
                
                // Ubah tampilan modal menjadi flex untuk centering
                modal.style.display = modal.classList.contains('hidden') ? 'none' : 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                
                // Perbaiki konten modal
                const modalContent = modal.querySelector('.relative');
                if (modalContent) {
                    // Force styles dengan !important
                    modalContent.style.setProperty('width', '800px', 'important');
                    modalContent.style.setProperty('max-width', '90%', 'important');
                    modalContent.style.setProperty('top', '0', 'important');
                    modalContent.style.setProperty('margin', '0 auto', 'important');
                    modalContent.style.setProperty('position', 'relative', 'important');
                    modalContent.style.setProperty('transform', 'none', 'important');
                    modalContent.style.setProperty('left', '0', 'important');
                    
                    // Perbaiki body modal
                    const modalBody = modalContent.querySelector('.mt-3, .modal-body');
                    if (modalBody) {
                        // Hitung tinggi viewport
                        const viewportHeight = window.innerHeight;
                        // Maksimal 85% dari tinggi viewport
                        const maxHeight = Math.floor(viewportHeight * 0.85);
                        
                        // Dapatkan tinggi header jika ada
                        const modalHeader = modalContent.querySelector('.flex.justify-between.items-center, .modal-header');
                        const headerHeight = modalHeader ? modalHeader.offsetHeight : 0;
                        
                        // Set tinggi max body (viewport - header - margin)
                        const maxBodyHeight = maxHeight - headerHeight - 64;
                        
                        modalBody.style.setProperty('max-height', `${maxBodyHeight}px`, 'important');
                        modalBody.style.setProperty('overflow-y', 'auto', 'important');
                    }
                }
            });
            
            // Perbaikan untuk tabel dalam modal addBahanBakuModal
            const addBahanBakuModal = document.getElementById('addBahanBakuModal');
            if (addBahanBakuModal) {
                // Perbaiki tabel
                const tables = addBahanBakuModal.querySelectorAll('table');
                tables.forEach(table => {
                    table.style.setProperty('width', '100%', 'important');
                    table.style.setProperty('table-layout', 'fixed', 'important');
                    
                    // Perbaiki header tabel
                    const headerCells = table.querySelectorAll('th');
                    if (headerCells.length > 0) {
                        // Sesuaikan lebar kolom berdasarkan konten
                        headerCells.forEach((cell, index) => {
                            // Lebar khusus berdasarkan indeks kolom
                            if (index === 0) { // Kolom barang
                                cell.style.width = '30%';
                            } else if (index === 5) { // Kolom total
                                cell.style.width = '15%';
                            } else if (index === 6) { // Kolom aksi
                                cell.style.width = '10%';
                            } else { // Kolom lainnya
                                cell.style.width = '11.25%';
                            }
                        });
                    }
                    
                    // Perbaiki cell tabel
                    const cells = table.querySelectorAll('td');
                    cells.forEach(cell => {
                        cell.style.setProperty('padding', '0.5rem', 'important');
                        cell.style.setProperty('vertical-align', 'middle', 'important');
                    });
                });
                
                // Perbaiki select2 jika ada
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
                    // Force refresh select2
                    setTimeout(() => {
                        jQuery(addBahanBakuModal).find('.select2-container').css('width', '100%');
                    }, 100);
                }
            }
            
            // Perbaiki scroll horizontal pada halaman
            document.body.style.overflowX = 'hidden';
            
            // Semua container
            const containers = document.querySelectorAll('.container, .container-fluid');
            containers.forEach(container => {
                container.style.maxWidth = '100%';
                container.style.overflowX = 'hidden';
            });
            
            // Wrapper tabel
            const tableWrappers = document.querySelectorAll('.overflow-x-auto');
            tableWrappers.forEach(wrapper => {
                wrapper.style.maxWidth = '100%';
            });
        }
        
        // Override fungsi showModal untuk posisi di tengah
        const originalShowModal = window.showModal;
        window.showModal = function(modalId) {
            // Panggil fungsi asli
            originalShowModal(modalId);
            
            // Dapatkan elemen modal
            const modal = document.getElementById(modalId);
            if (!modal) return;
            
            // Ubah tampilan modal
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            
            // Perbaiki posisi konten modal
            const modalContent = modal.querySelector('.relative');
            if (modalContent) {
                modalContent.style.margin = '0 auto';
                modalContent.style.top = '0';
                modalContent.style.transform = 'none';
                modalContent.style.left = '0';
            }
            
            // Jalankan perbaikan lengkap
            setTimeout(() => {
                fixBahanBakuModal();
                initSelect2InModal(modalId);
            }, 10);
        };
        
        // Override fungsi closeModal
        const originalCloseModal = window.closeModal;
        window.closeModal = function(modalId) {
            originalCloseModal(modalId);
        };
        
        // Jalankan perbaikan saat halaman dimuat
        fixBahanBakuModal();
        
        // Saat resize jendela
        window.addEventListener('resize', fixBahanBakuModal);
    });
</script>

<!-- Fix modal centering dengan CSS inline langsung -->
<script>
    // Tunggu dokumen selesai loading
    document.addEventListener('DOMContentLoaded', function() {
        // Dapatkan semua elemen modal
        const modals = document.querySelectorAll('.fixed.inset-0.bg-gray-600.bg-opacity-50');
        
        // Terapkan style secara langsung ke setiap modal
        modals.forEach(modal => {
            // Tambahkan event listener untuk mengatur tampilan
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        if (!modal.classList.contains('hidden')) {
                            // Modal ditampilkan - atur posisi tengah
                            modal.style.display = 'flex';
                            modal.style.alignItems = 'center';
                            modal.style.justifyContent = 'center';
                            
                            // Atur konten modal
                            const modalContent = modal.querySelector('.relative.top-20');
                            if (modalContent) {
                                modalContent.style.marginTop = '0';
                                modalContent.style.top = '0';
                            }
                        } else {
                            // Modal disembunyikan
                            modal.style.display = 'none';
                        }
                    }
                });
            });
            
            // Mulai observasi
            observer.observe(modal, { attributes: true });
            
            // Atur style awal
            if (!modal.classList.contains('hidden')) {
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
            }
        });
    });
</script>

