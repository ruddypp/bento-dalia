<?php
require_once 'database.php';

// Fungsi untuk mengamankan input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Fungsi untuk mengecek login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Fungsi untuk mencatat log aktivitas
function logActivity($user_id, $activity) {
    global $conn;
    $query = "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return false;
    }
    mysqli_stmt_bind_param($stmt, "is", $user_id, $activity);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Fungsi untuk mendapatkan data barang
function getItems($id = null) {
    global $conn;
    
    if ($id) {
        $query = "SELECT * FROM barang WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    } else {
        $query = "SELECT * FROM barang ORDER BY nama_barang ASC";
        $result = mysqli_query($conn, $query);
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
        return $items;
    }
}

// Fungsi untuk mendapatkan data supplier
function getSuppliers($id = null) {
    global $conn;
    
    if ($id) {
        $query = "SELECT * FROM supplier WHERE id_supplier = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    } else {
        $query = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
        $result = mysqli_query($conn, $query);
        $suppliers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $suppliers[] = $row;
        }
        return $suppliers;
    }
}

// Fungsi untuk mendapatkan data pengguna
function getUsers($id = null) {
    global $conn;
    
    if ($id) {
        $query = "SELECT * FROM users WHERE id_user = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    } else {
        $query = "SELECT * FROM users ORDER BY nama_lengkap ASC";
        $result = mysqli_query($conn, $query);
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        return $users;
    }
}

// Fungsi untuk mendapatkan akses role pengguna
function getUserRole($role_id) {
    global $conn;
    $query = "SELECT nama_aktor FROM aktor WHERE id_aktor = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $role_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $role = mysqli_fetch_assoc($result);
    return $role['nama_aktor'];
}

// Fungsi untuk mengupdate stok barang
function updateStock($item_id, $qty, $type = 'in') {
    global $conn;
    
    // Ambil stok saat ini
    $query = "SELECT stok FROM barang WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($result);
    
    // Hitung stok baru
    $new_stock = ($type == 'in') ? $item['stok'] + $qty : $item['stok'] - $qty;
    
    // Update stok
    $query = "UPDATE barang SET stok = ? WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $new_stock, $item_id);
    mysqli_stmt_execute($stmt);
    
    return $new_stock;
}

// Fungsi cek stok mencukupi untuk pengeluaran
function checkStockAvailability($item_id, $qty) {
    global $conn;
    
    $query = "SELECT stok FROM barang WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($result);
    
    return $item['stok'] >= $qty;
}

// Fungsi untuk mendapatkan data toko
function getStoreInfo() {
    global $conn;
    $query = "SELECT * FROM data_toko LIMIT 1";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Fungsi alert
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Fungsi menampilkan alert
function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $type = $_SESSION['alert']['type'];
        $message = $_SESSION['alert']['message'];
        
        $alertClass = '';
        switch ($type) {
            case 'success':
                $alertClass = 'bg-green-100 border-green-400 text-green-700';
                break;
            case 'error':
                $alertClass = 'bg-red-100 border-red-400 text-red-700';
                break;
            case 'warning':
                $alertClass = 'bg-yellow-100 border-yellow-400 text-yellow-700';
                break;
            case 'info':
                $alertClass = 'bg-blue-100 border-blue-400 text-blue-700';
                break;
        }
        
        echo "<div class='$alertClass border px-4 py-3 mb-4 rounded relative alert'>";
        echo $message;
        echo "<span class='absolute top-0 bottom-0 right-0 px-4 py-3 alert-close'>";
        echo "<svg class='fill-current h-6 w-6' role='button' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'>";
        echo "<title>Close</title>";
        echo "<path d='M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z'/>";
        echo "</svg>";
        echo "</span>";
        echo "</div>";
        
        unset($_SESSION['alert']);
    }
}

/**
 * Functions for Report (Laporan) Module
 */

// Create laporan barang masuk
function createLaporanMasuk($conn, $tanggal_laporan, $items) {
    // Insert into laporan_masuk table
    $query = "INSERT INTO laporan_masuk (tanggal_laporan) VALUES (?)";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $tanggal_laporan);
    $result = mysqli_stmt_execute($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $id_laporan = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Insert details into laporan_masuk_detail
foreach ($items as $id_masuk) {
    $query_detail = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk) VALUES (?, ?)";
    $stmt_detail = mysqli_prepare($conn, $query_detail);
    if (!$stmt_detail) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        continue;
    }
    
    mysqli_stmt_bind_param($stmt_detail, "ii", $id_laporan, $id_masuk);
    mysqli_stmt_execute($stmt_detail);
    mysqli_stmt_close($stmt_detail);
}
    
    return $id_laporan;
}

// Create laporan barang keluar
function createLaporanKeluar($conn, $tanggal_laporan, $items) {
    // Insert into laporan_keluar table
    $query = "INSERT INTO laporan_keluar (tanggal_laporan) VALUES (?)";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $tanggal_laporan);
    $result = mysqli_stmt_execute($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $id_laporan = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Insert details into detail_laporan_keluar
    foreach ($items as $id_keluar) {
        $query_detail = "INSERT INTO detail_laporan_keluar (id_laporan, id_keluar) VALUES (?, ?)";
        $stmt_detail = mysqli_prepare($conn, $query_detail);
        if (!$stmt_detail) {
            // Handle error when prepare fails
            error_log("Query prepare failed: " . mysqli_error($conn));
            continue;
        }
        
        mysqli_stmt_bind_param($stmt_detail, "ii", $id_laporan, $id_keluar);
        mysqli_stmt_execute($stmt_detail);
        mysqli_stmt_close($stmt_detail);
    }
    
    return $id_laporan;
}

// Get all laporan masuk
function getAllLaporanMasuk($conn) {
    $query = "SELECT * FROM laporan_masuk ORDER BY tanggal_laporan DESC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $laporan = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $laporan[] = $row;
    }
    
    return $laporan;
}

// Get all laporan keluar
function getAllLaporanKeluar($conn) {
    $query = "SELECT * FROM laporan_keluar ORDER BY tanggal_laporan DESC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $laporan = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $laporan[] = $row;
    }
    
    return $laporan;
}

// Get detail of laporan masuk
function getLaporanMasukDetail($conn, $id_laporan) {
    $query = "SELECT lm.id_laporan_masuk, lm.tanggal_laporan, b.nama_barang, 
                    b.satuan as satuan_barang, bm.qty_masuk, bm.tanggal_masuk, s.nama_supplier, p.nama_lengkap as nama_pengguna
              FROM laporan_masuk lm
              JOIN laporan_masuk_detail lmd ON lm.id_laporan_masuk = lmd.id_laporan
              JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
              JOIN barang b ON bm.id_barang = b.id_barang
              JOIN supplier s ON bm.id_supplier = s.id_supplier
              JOIN users p ON bm.id_user = p.id_user
              WHERE lm.id_laporan_masuk = ?
              ORDER BY bm.tanggal_masuk DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return ['header' => null, 'detail' => []];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_laporan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        return ['header' => null, 'detail' => []];
    }
    
    $detail = [];
    $header = null;
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($header === null) {
            $header = [
                'id_laporan_masuk' => $row['id_laporan_masuk'],
                'tanggal_laporan' => $row['tanggal_laporan']
            ];
        }
        
        $detail[] = [
            'nama_barang' => $row['nama_barang'],
            'satuan_barang' => $row['satuan_barang'],
            'qty_masuk' => $row['qty_masuk'],
            'tanggal_masuk' => $row['tanggal_masuk'],
            'nama_supplier' => $row['nama_supplier'],
            'penerima' => $row['nama_pengguna']
        ];
    }
    
    mysqli_stmt_close($stmt);
    return ['header' => $header, 'detail' => $detail];
}

// Get detail of laporan keluar
function getLaporanKeluarDetail($conn, $id_laporan) {
    $query = "SELECT lk.id_laporan_keluar, lk.tanggal_laporan, b.nama_barang, 
                     b.satuan as satuan_barang, bk.qty_keluar, bk.tanggal_keluar, p.nama_lengkap as nama_pengguna
              FROM laporan_keluar lk
              JOIN detail_laporan_keluar dlk ON lk.id_laporan_keluar = dlk.id_laporan
              JOIN barang_keluar bk ON dlk.id_keluar = bk.id_keluar
              JOIN barang b ON bk.id_barang = b.id_barang
              JOIN users p ON bk.id_user = p.id_user
              WHERE lk.id_laporan_keluar = ?
              ORDER BY bk.tanggal_keluar DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return ['header' => null, 'detail' => []];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_laporan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        return ['header' => null, 'detail' => []];
    }
    
    $detail = [];
    $header = null;
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($header === null) {
            $header = [
                'id_laporan_keluar' => $row['id_laporan_keluar'],
                'tanggal_laporan' => $row['tanggal_laporan']
            ];
        }
        
        $detail[] = [
            'nama_barang' => $row['nama_barang'],
            'satuan_barang' => $row['satuan_barang'],
            'qty_keluar' => $row['qty_keluar'],
            'tanggal_keluar' => $row['tanggal_keluar'],
            'pengeluaran_oleh' => $row['nama_pengguna']
        ];
    }
    
    mysqli_stmt_close($stmt);
    return ['header' => $header, 'detail' => $detail];
}

// Delete laporan masuk
function deleteLaporanMasuk($conn, $id_laporan) {
    // Delete the detail first (foreign key constraint)
    $query_detail = "DELETE FROM laporan_masuk_detail WHERE id_laporan = ?";
    $stmt_detail = mysqli_prepare($conn, $query_detail);
    if (!$stmt_detail) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt_detail, "i", $id_laporan);
    $result_detail = mysqli_stmt_execute($stmt_detail);
    mysqli_stmt_close($stmt_detail);
    
    // Delete the header
    $query = "DELETE FROM laporan_masuk WHERE id_laporan_masuk = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_laporan);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Delete laporan keluar
function deleteLaporanKeluar($conn, $id_laporan) {
    // Delete the detail first (foreign key constraint)
    $query_detail = "DELETE FROM detail_laporan_keluar WHERE id_laporan = ?";
    $stmt_detail = mysqli_prepare($conn, $query_detail);
    if (!$stmt_detail) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt_detail, "i", $id_laporan);
    $result_detail = mysqli_stmt_execute($stmt_detail);
    mysqli_stmt_close($stmt_detail);
    
    // Delete the header
    $query = "DELETE FROM laporan_keluar WHERE id_laporan_keluar = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_laporan);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Get count of detail items for laporan masuk
function getLaporanMasukDetailCount($conn, $id_laporan) {
    $query = "SELECT COUNT(*) as item_count FROM laporan_masuk_detail WHERE id_laporan = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("Query prepare failed: " . mysqli_error($conn));
        return 0;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_laporan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $row['item_count'] ?? 0;
}

// Get count of detail items for laporan keluar
function getLaporanKeluarDetailCount($conn, $id_laporan) {
    $query = "SELECT COUNT(*) as item_count FROM detail_laporan_keluar WHERE id_laporan = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("Query prepare failed: " . mysqli_error($conn));
        return 0;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_laporan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $row['item_count'] ?? 0;
}

// Get barang masuk in date range
function getBarangMasukInDateRange($conn, $dari_tanggal, $sampai_tanggal) {
    $query = "SELECT bm.id_masuk, b.nama_barang, b.satuan as satuan_barang, 
                     bm.qty_masuk, bm.tanggal_masuk, s.nama_supplier, p.nama_lengkap as nama_pengguna
              FROM barang_masuk bm
              JOIN barang b ON bm.id_barang = b.id_barang
              JOIN supplier s ON bm.id_supplier = s.id_supplier
              JOIN users p ON bm.id_user = p.id_user
              WHERE bm.tanggal_masuk BETWEEN ? AND ?
              ORDER BY bm.tanggal_masuk DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $dari_tanggal, $sampai_tanggal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        return [];
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $data;
}

// Get barang keluar in date range
function getBarangKeluarInDateRange($conn, $dari_tanggal, $sampai_tanggal) {
    $query = "SELECT bk.id_keluar, b.nama_barang, b.satuan as satuan_barang, 
                     bk.qty_keluar, bk.tanggal_keluar, p.nama_lengkap as nama_pengguna
              FROM barang_keluar bk
              JOIN barang b ON bk.id_barang = b.id_barang
              JOIN users p ON bk.id_user = p.id_user
              WHERE bk.tanggal_keluar BETWEEN ? AND ?
              ORDER BY bk.tanggal_keluar DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error when prepare fails
        error_log("Query prepare failed: " . mysqli_error($conn));
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $dari_tanggal, $sampai_tanggal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        return [];
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $data;
}

/**
 * Process bahan baku retur
 * 
 * Handles the retur process for bahan baku items
 * 
 * @param int $id_bahan_baku ID of the bahan baku to be returned
 * @param int $qty_retur Quantity to be returned
 * @param string $alasan_retur Reason for return
 * @param string $supplier Supplier name (optional)
 * @return bool|array Returns true on success or array with error message on failure
 */
function processBahanBakuRetur($conn, $id_bahan_baku, $qty_retur, $alasan_retur, $supplier = null) {
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get bahan baku details
        $query = "SELECT bb.*, b.nama_barang, b.satuan 
                  FROM bahan_baku bb 
                  JOIN barang b ON bb.id_barang = b.id_barang 
                  WHERE bb.id_bahan_baku = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_bahan_baku);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $bahan_baku = mysqli_fetch_assoc($result);
        
        if (!$bahan_baku) {
            throw new Exception("Bahan baku tidak ditemukan");
        }
        
        if ($qty_retur > $bahan_baku['qty']) {
            throw new Exception("Jumlah retur tidak boleh lebih dari jumlah bahan baku");
        }
        
        // Calculate remaining qty
        $qty_remaining = $bahan_baku['qty'] - $qty_retur;
        
        // Update the bahan_baku record with retur information
        $update_query = "UPDATE bahan_baku SET 
                        status = 'approved',
                        jumlah_retur = ?,
                        jumlah_masuk = ?,
                        catatan_retur = ?
                        WHERE id_bahan_baku = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "iisi", 
                             $qty_retur, 
                             $qty_remaining, 
                             $alasan_retur,
                             $id_bahan_baku);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Gagal mengupdate bahan baku: " . mysqli_stmt_error($update_stmt));
        }
        mysqli_stmt_close($update_stmt);
        
        // Add entry to retur_barang table
        $insert_retur_query = "INSERT INTO retur_barang (id_bahan_baku, tanggal_retur, jumlah_retur, alasan_retur, id_user, supplier) 
                             VALUES (?, NOW(), ?, ?, ?, ?)";
        $insert_retur_stmt = mysqli_prepare($conn, $insert_retur_query);
        $user_id = $_SESSION['user_id'];
        mysqli_stmt_bind_param($insert_retur_stmt, "iisis", 
                             $id_bahan_baku, 
                             $qty_retur, 
                             $alasan_retur,
                             $user_id,
                             $supplier);
        
        if (!mysqli_stmt_execute($insert_retur_stmt)) {
            throw new Exception("Gagal membuat entry retur: " . mysqli_stmt_error($insert_retur_stmt));
        }
        mysqli_stmt_close($insert_retur_stmt);
        
        // If there are remaining items, add them to stock
        if ($qty_remaining > 0) {
            // Update stock in barang table
            $update_stock_query = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
            $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
            mysqli_stmt_bind_param($update_stock_stmt, "ii", $qty_remaining, $bahan_baku['id_barang']);
            
            if (!mysqli_stmt_execute($update_stock_stmt)) {
                throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stock_stmt));
            }
            mysqli_stmt_close($update_stock_stmt);
            
            // Create entry in barang_masuk
            $masuk_query = "INSERT INTO barang_masuk (id_barang, qty_masuk, tanggal_masuk, id_user, lokasi, harga_satuan, periode) 
                           VALUES (?, ?, NOW(), ?, ?, ?, ?)";
            $masuk_stmt = mysqli_prepare($conn, $masuk_query);
            mysqli_stmt_bind_param($masuk_stmt, "iisddi", 
                                  $bahan_baku['id_barang'], 
                                  $qty_remaining, 
                                  $user_id,
                                  $bahan_baku['lokasi'],
                                  $bahan_baku['harga_satuan'],
                                  $bahan_baku['periode']);
            
            if (!mysqli_stmt_execute($masuk_stmt)) {
                throw new Exception("Gagal menambahkan data barang masuk: " . mysqli_stmt_error($masuk_stmt));
            }
            
            $id_masuk = mysqli_insert_id($conn);
            mysqli_stmt_close($masuk_stmt);
            
            // Create or update laporan_masuk for today
            $today = date('Y-m-d');
            $check_laporan_query = "SELECT id_laporan_masuk FROM laporan_masuk 
                                   WHERE DATE(tanggal_laporan) = ? AND periode = ?";
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
                mysqli_stmt_bind_param($laporan_stmt, "ii", $user_id, $bahan_baku['periode']);
                
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
        
        // Commit transaction
        mysqli_commit($conn);
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        return ['error' => $e->getMessage()];
    }
}

/**
 * Approve bahan baku
 * 
 * Approves a bahan baku item and adds it to stock
 * 
 * @param int $id_bahan_baku ID of the bahan baku to approve
 * @return bool|array Returns true on success or array with error message on failure
 */
function approveBahanBaku($conn, $id_bahan_baku) {
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get bahan baku details
        $query = "SELECT bb.*, b.nama_barang 
                  FROM bahan_baku bb 
                  JOIN barang b ON bb.id_barang = b.id_barang 
                  WHERE bb.id_bahan_baku = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_bahan_baku);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $bahan_baku = mysqli_fetch_assoc($result);
        
        if (!$bahan_baku) {
            throw new Exception("Bahan baku tidak ditemukan");
        }
        
        // Update bahan baku status to approved
        $update_query = "UPDATE bahan_baku SET 
                        status = 'approved',
                        jumlah_masuk = qty
                        WHERE id_bahan_baku = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $id_bahan_baku);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Gagal mengupdate bahan baku: " . mysqli_stmt_error($update_stmt));
        }
        mysqli_stmt_close($update_stmt);
        
        // Update stock in barang table
        $update_stock_query = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
        $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
        mysqli_stmt_bind_param($update_stock_stmt, "ii", $bahan_baku['qty'], $bahan_baku['id_barang']);
        
        if (!mysqli_stmt_execute($update_stock_stmt)) {
            throw new Exception("Gagal mengupdate stok barang: " . mysqli_stmt_error($update_stock_stmt));
        }
        mysqli_stmt_close($update_stock_stmt);
        
        // Create entry in barang_masuk
        $user_id = $_SESSION['user_id'];
        $masuk_query = "INSERT INTO barang_masuk (id_barang, qty_masuk, tanggal_masuk, id_user, lokasi, harga_satuan, periode) 
                       VALUES (?, ?, NOW(), ?, ?, ?, ?)";
        $masuk_stmt = mysqli_prepare($conn, $masuk_query);
        mysqli_stmt_bind_param($masuk_stmt, "iisddi", 
                              $bahan_baku['id_barang'], 
                              $bahan_baku['qty'], 
                              $user_id,
                              $bahan_baku['lokasi'],
                              $bahan_baku['harga_satuan'],
                              $bahan_baku['periode']);
        
        if (!mysqli_stmt_execute($masuk_stmt)) {
            throw new Exception("Gagal menambahkan data barang masuk: " . mysqli_stmt_error($masuk_stmt));
        }
        
        $id_masuk = mysqli_insert_id($conn);
        mysqli_stmt_close($masuk_stmt);
        
        // Create or update laporan_masuk for today
        $today = date('Y-m-d');
        $check_laporan_query = "SELECT id_laporan_masuk FROM laporan_masuk 
                               WHERE DATE(tanggal_laporan) = ? AND periode = ?";
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
            mysqli_stmt_bind_param($laporan_stmt, "ii", $user_id, $bahan_baku['periode']);
            
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
        
        // Commit transaction
        mysqli_commit($conn);
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get bahan baku report
 * 
 * Gets a report of all bahan baku items with their status, total, retur, and masuk quantities
 * 
 * @param int $periode Optional filter by periode
 * @param string $status Optional filter by status
 * @return array Returns array of bahan baku report data
 */
function getBahanBakuReport($conn, $periode = null, $status = null) {
    $query = "SELECT * FROM v_bahan_baku_report WHERE 1=1";
    
    if ($periode) {
        $query .= " AND periode = " . (int)$periode;
    }
    
    if ($status) {
        $query .= " AND status = '" . mysqli_real_escape_string($conn, $status) . "'";
    }
    
    $query .= " ORDER BY tanggal_input DESC";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get pesanan information for a bahan_baku item
function getPesananInfo($id_pesanan) {
    global $conn;
    
    if(empty($id_pesanan)) return "";
    
    $query = "SELECT pb.id_pesanan, pb.tanggal_pesan, s.nama_supplier
              FROM pesanan_barang pb
              LEFT JOIN supplier s ON pb.id_supplier = s.id_supplier
              WHERE pb.id_pesanan = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($row = mysqli_fetch_assoc($result)) {
        return "Order #" . $row["id_pesanan"] . " from " . $row["nama_supplier"] . " on " . date("d M Y", strtotime($row["tanggal_pesan"]));
    }
    
    return "";
}

function getBarangMasukDetail($id_masuk) {
    global $conn;
    
    $query = "SELECT bm.*, b.nama_barang, b.satuan, s.nama_supplier, p.nama_lengkap as nama_pengguna 
              FROM barang_masuk bm 
              JOIN barang b ON bm.id_barang = b.id_barang 
              JOIN supplier s ON bm.id_supplier = s.id_supplier 
              JOIN users p ON bm.id_user = p.id_user 
              WHERE bm.id_masuk = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_masuk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } else {
        return null;
    }
}

function getBarangKeluarDetail($id_keluar) {
    global $conn;
    
    $query = "SELECT bk.*, b.nama_barang, b.satuan, p.nama_lengkap as nama_pengguna 
              FROM barang_keluar bk 
              JOIN barang b ON bk.id_barang = b.id_barang 
              JOIN users p ON bk.id_user = p.id_user 
              WHERE bk.id_keluar = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_keluar);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } else {
        return null;
    }
}

// Fungsi untuk mengecek permission berdasarkan role
function checkPermission($page) {
    // Jika user belum login, redirect ke login page
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $user_role = $_SESSION['user_role'];
    
    // Admin memiliki akses ke semua fitur
    if ($user_role === 'admin') {
        return 'full';
    }
    
    // Array permission untuk setiap role sesuai persyaratan
    $permissions = [
        'kasir' => [
            'barang.php' => 'edit',
            'bahan_baku.php' => 'edit',
            'supplier.php' => 'view',
            'pesan_barang.php' => 'view',
            'retur_barang.php' => 'edit',
            'menu_makanan.php' => 'edit',
            'menu_minuman.php' => 'edit',
            'penjualan.php' => 'edit',
            'laporan_penjualan.php' => 'edit',
            'laporan_masuk.php' => 'edit',
            'index.php' => 'edit',
            'profile.php' => 'full',
            'detail_penjualan.php' => 'edit'
        ],
        'crew' => [
            'barang.php' => 'view',
            'bahan_baku.php' => 'view',
            'supplier.php' => 'view',
            'pesan_barang.php' => 'view',
            'retur_barang.php' => 'view',
            'penjualan.php' => 'view',
            'barang_lost.php' => 'view',
            'menu_makanan.php' => 'view',
            'menu_minuman.php' => 'view',
            'laporan_penjualan.php' => 'edit',
            'laporan_masuk.php' => 'edit',
            'index.php' => 'edit',
            'profile.php' => 'full',
            'detail_penjualan.php' => 'edit'
        ],
        'headproduksi' => [
            'barang.php' => 'edit',
            'bahan_baku.php' => 'view',
            'retur_barang.php' => 'edit',
            'barang_lost.php' => 'edit',
            'index.php' => 'edit',
            'profile.php' => 'full'
        ],
        'purchasing' => [
            'supplier.php' => 'edit',
            'pesan_barang.php' => 'edit',
            'barang.php' => 'view',
            'bahan_baku.php' => 'edit',
            'retur_barang.php' => 'view',
            'barang_lost.php' => 'view',
            'laporan_masuk.php' => 'edit',
            'index.php' => 'edit',
            'profile.php' => 'full'
        ]
    ];
    
    // Cek apakah role memiliki akses ke halaman
    if (isset($permissions[$user_role]) && isset($permissions[$user_role][$page])) {
        return $permissions[$user_role][$page];
    }
    
    // Jika tidak memiliki akses, redirect ke halaman error atau dashboard
    header("Location: index.php?error=unauthorized&page=" . urlencode($page));
    exit();
}

// Fungsi untuk memeriksa apakah user memiliki akses edit/delete
function hasEditPermission() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'];
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Admin selalu memiliki akses penuh
    if ($user_role === 'admin') {
        return true;
    }
    
    // Cek permission untuk halaman saat ini
    $permission = checkPermission($current_page);
    
    // Hanya return true jika permission adalah 'full' atau 'edit'
    return $permission === 'full' || $permission === 'edit';
}

// Fungsi untuk memeriksa apakah user memiliki akses delete
function hasDeletePermission() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'];
    
    // Hanya admin yang dapat menghapus data
    return $user_role === 'admin';
}
?>
