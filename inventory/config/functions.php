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
    $query = "INSERT INTO log_aktivitas (id_pengguna, waktu, aktivitas) VALUES (?, NOW(), ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $activity);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
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
        $query = "SELECT p.*, a.nama_aktor FROM pengguna p 
                  JOIN aktor a ON p.id_aktor = a.id_aktor 
                  WHERE p.id_pengguna = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    } else {
        $query = "SELECT p.*, a.nama_aktor FROM pengguna p 
                  JOIN aktor a ON p.id_aktor = a.id_aktor 
                  ORDER BY p.nama_pengguna ASC";
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
                    b.satuan as satuan_barang, bm.qty_masuk, bm.tanggal_masuk, s.nama_supplier, p.nama_pengguna
              FROM laporan_masuk lm
              JOIN laporan_masuk_detail lmd ON lm.id_laporan_masuk = lmd.id_laporan
              JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
              JOIN barang b ON bm.id_barang = b.id_barang
              JOIN supplier s ON bm.id_supplier = s.id_supplier
              JOIN pengguna p ON bm.id_user = p.id_pengguna
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
                     b.satuan as satuan_barang, bk.qty_keluar, bk.tanggal_keluar, p.nama_pengguna
              FROM laporan_keluar lk
              JOIN detail_laporan_keluar dlk ON lk.id_laporan_keluar = dlk.id_laporan
              JOIN barang_keluar bk ON dlk.id_keluar = bk.id_keluar
              JOIN barang b ON bk.id_barang = b.id_barang
              JOIN pengguna p ON bk.id_user = p.id_pengguna
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
                     bm.qty_masuk, bm.tanggal_masuk, s.nama_supplier, p.nama_pengguna
              FROM barang_masuk bm
              JOIN barang b ON bm.id_barang = b.id_barang
              JOIN supplier s ON bm.id_supplier = s.id_supplier
              JOIN pengguna p ON bm.id_user = p.id_pengguna
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
                     bk.qty_keluar, bk.tanggal_keluar, p.nama_pengguna
              FROM barang_keluar bk
              JOIN barang b ON bk.id_barang = b.id_barang
              JOIN pengguna p ON bk.id_user = p.id_pengguna
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
?> 