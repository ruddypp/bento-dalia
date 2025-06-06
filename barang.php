<?php
$pageTitle = "Data Barang";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php';

// Handle AJAX request for item data
if (isset($_GET['action']) && $_GET['action'] === 'get_item' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM barang WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($item = mysqli_fetch_assoc($result)) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
        
        mysqli_stmt_close($stmt);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
}

// Get all suppliers
$query_suppliers = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
$suppliers = mysqli_query($conn, $query_suppliers);
$all_suppliers = [];
while ($supplier = mysqli_fetch_assoc($suppliers)) {
    $all_suppliers[] = $supplier;
}

// Check if harga column exists and add it if it doesn't
$check_column = "SHOW COLUMNS FROM barang LIKE 'harga'";
$result = mysqli_query($conn, $check_column);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE barang ADD COLUMN harga DECIMAL(10,2) DEFAULT 0";
    if (!mysqli_query($conn, $sql)) {
        die("Error adding harga column: " . mysqli_error($conn));
    }
    
    // Add some sample prices to existing data
    $update_sql = "UPDATE barang SET harga = 10000 WHERE id_barang = 1"; // susu
    mysqli_query($conn, $update_sql);
    
    $update_sql = "UPDATE barang SET harga = 25000 WHERE id_barang = 2"; // kopi
    mysqli_query($conn, $update_sql);
    
    $update_sql = "UPDATE barang SET harga = 50000 WHERE id_barang = 3"; // rudy
    mysqli_query($conn, $update_sql);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_barang'])) {
        // Add new item
        $nama = sanitize($_POST['nama_barang']);
        $satuan = sanitize($_POST['satuan']);
        $jenis = sanitize($_POST['jenis']);
        $stok = (int)$_POST['stok'];
        $stok_minimum = (int)$_POST['stok_minimum'];
        $harga = (float)$_POST['harga'];
        
        // Validate lokasi to ensure it's not too long
        $lokasi = sanitize($_POST['lokasi']);
        
        // Check the column definition in the database
        $check_column = "SHOW COLUMNS FROM barang LIKE 'lokasi'";
        $column_result = mysqli_query($conn, $check_column);
        $column_info = mysqli_fetch_assoc($column_result);
        
        // If lokasi is an ENUM type, make sure the value is valid
        if (strpos($column_info['Type'], 'enum') === 0) {
            // Extract valid values from enum('value1','value2',...) format
            preg_match("/^enum\(\'(.*)\'\)$/", $column_info['Type'], $matches);
            $valid_values = explode("','", $matches[1]);
            
            if (!in_array($lokasi, $valid_values)) {
                // Set a default value if not valid
                $lokasi = $valid_values[0];
            }
        }
        
        $id_supplier = (int)$_POST['id_supplier'];
        
        $query = "INSERT INTO barang (nama_barang, satuan, jenis, stok, stok_minimum, harga, lokasi, id_supplier) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssiidsi", $nama, $satuan, $jenis, $stok, $stok_minimum, $harga, $lokasi, $id_supplier);
        
        try {
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Menambahkan barang baru: $nama");
            setAlert("success", "Barang berhasil ditambahkan!");
        } else {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        } catch (Exception $e) {
            setAlert("error", "Gagal menambahkan barang: " . $e->getMessage());
        }
        
        mysqli_stmt_close($stmt);
        header("Location: barang.php");
        exit();
    } 
    elseif (isset($_POST['edit_barang'])) {
        // Edit barang
        $id = (int)$_POST['id_barang'];
        $nama = sanitize($_POST['nama_barang']);
        $satuan = sanitize($_POST['satuan']);
        $jenis = sanitize($_POST['jenis']);
        $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
        $stok = (int)$_POST['stok'];
        $stok_minimum = (int)$_POST['stok_minimum'];
        $harga = (float)$_POST['harga'];
        
        // Validate lokasi to ensure it's not too long
        $lokasi = sanitize($_POST['lokasi']);
        
        // Check the column definition in the database
        $check_column = "SHOW COLUMNS FROM barang LIKE 'lokasi'";
        $column_result = mysqli_query($conn, $check_column);
        $column_info = mysqli_fetch_assoc($column_result);
        
        // If lokasi is an ENUM type, make sure the value is valid
        if (strpos($column_info['Type'], 'enum') === 0) {
            // Extract valid values from enum('value1','value2',...) format
            preg_match("/^enum\(\'(.*)\'\)$/", $column_info['Type'], $matches);
            $valid_values = explode("','", $matches[1]);
            
            if (!in_array($lokasi, $valid_values)) {
                // Set a default value if not valid
                $lokasi = $valid_values[0];
            }
        }
        
        // Proceed with update
        $query = "UPDATE barang SET nama_barang = ?, satuan = ?, jenis = ?, id_supplier = ?, stok = ?, stok_minimum = ?, harga = ?, lokasi = ? WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssiiidsi", $nama, $satuan, $jenis, $id_supplier, $stok, $stok_minimum, $harga, $lokasi, $id);
        
        try {
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Mengubah data barang: $nama");
            setAlert("success", "Data barang berhasil diperbarui!");
        } else {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        } catch (Exception $e) {
            setAlert("error", "Gagal memperbarui data barang: " . $e->getMessage());
        }
        
        mysqli_stmt_close($stmt);
        header("Location: barang.php");
        exit();
    }
    elseif (isset($_POST['delete_barang'])) {
        // Hapus barang
        $id = (int)$_POST['id_barang'];
        $force_delete = isset($_POST['force_delete']) && $_POST['force_delete'] == 1;
        
        // Dapatkan nama barang sebelum dihapus untuk log
        $query = "SELECT nama_barang FROM barang WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $barang = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($force_delete && isAdmin()) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Delete counts to track how many records are deleted
                $masuk_count = 0;
                $retur_count = 0;
                $keluar_count = 0;
                $terima_count = 0;
                $opname_count = 0;
                
                // First delete related records in stok_opname
                $query = "DELETE FROM stok_opname WHERE id_barang = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $opname_count = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                
                // Next delete related records in detail_terima
                $query = "DELETE FROM detail_terima WHERE id_barang = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $terima_count = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                
                // Next delete related records in barang_keluar
                $query = "DELETE FROM barang_keluar WHERE id_barang = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $keluar_count = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                
                // Then delete related records in retur_barang
                $query = "DELETE FROM retur_barang WHERE id_barang = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $retur_count = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                
                // Then delete related records in barang_masuk
                $query = "DELETE FROM barang_masuk WHERE id_barang = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $masuk_count = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                
                // Finally delete the barang itself
        $query = "DELETE FROM barang WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                
                // Commit transaction
                mysqli_commit($conn);
                
                $total_related = $masuk_count + $retur_count + $keluar_count + $terima_count + $opname_count;
                $message = "Barang berhasil dihapus";
                if ($total_related > 0) {
                    $message .= " beserta " . $total_related . " transaksi terkait";
                    if ($total_related > 1) {
                        $details = [];
                        if ($masuk_count > 0) $details[] = $masuk_count . " barang masuk";
                        if ($retur_count > 0) $details[] = $retur_count . " retur";
                        if ($keluar_count > 0) $details[] = $keluar_count . " barang keluar";
                        if ($terima_count > 0) $details[] = $terima_count . " detail terima";
                        if ($opname_count > 0) $details[] = $opname_count . " stok opname";
                        
                        $message .= " (" . implode(", ", $details) . ")";
                    }
                }
                $message .= "!";
                
                logActivity($_SESSION['user_id'], "Menghapus barang dan transaksi terkait: " . $barang['nama_barang']);
                setAlert("success", $message);
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                setAlert("error", "Gagal menghapus barang: " . $e->getMessage());
            }
        } else {
            // Regular delete (will fail if foreign key constraints exist)
            $query = "DELETE FROM barang WHERE id_barang = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            try {
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Menghapus barang: " . $barang['nama_barang']);
            setAlert("success", "Barang berhasil dihapus!");
        } else {
            setAlert("error", "Gagal menghapus barang. Pastikan barang tidak terkait dengan transaksi lain.");
                }
            } catch (mysqli_sql_exception $e) {
                // Check if this is a foreign key constraint error
                if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                    setAlert("error", "Barang tidak dapat dihapus karena masih digunakan dalam transaksi barang masuk atau transaksi lainnya. Hapus transaksi terkait terlebih dahulu atau gunakan opsi 'Hapus Paksa'.");
                } else {
                    setAlert("error", "Gagal menghapus barang: " . $e->getMessage());
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        header("Location: barang.php");
        exit();
    }
    elseif (isset($_POST['update_stock'])) {
        // Only admins can directly update stock
        if (isAdmin()) {
            $id = (int)$_POST['id_barang'];
            $new_stock = (int)$_POST['new_stock'];
            $old_stock = (int)$_POST['old_stock'];
            $adjustment = $new_stock - $old_stock;
            
            // Get barang info
            $query = "SELECT nama_barang FROM barang WHERE id_barang = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $barang = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            // Update stock
            $query = "UPDATE barang SET stok = ? WHERE id_barang = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $new_stock, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $action = $adjustment > 0 ? "menambah" : "mengurangi";
                logActivity($_SESSION['user_id'], "Admin {$action} stok {$barang['nama_barang']} dari {$old_stock} menjadi {$new_stock} (Adjustment: " . abs($adjustment) . ")");
                setAlert("success", "Stok berhasil diperbarui dari {$old_stock} menjadi {$new_stock}");
            } else {
                setAlert("error", "Gagal memperbarui stok: " . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);
        header("Location: barang.php");
        exit();
        } else {
            setAlert("error", "Hanya admin yang dapat melakukan update stok langsung.");
        header("Location: barang.php");
        exit();
        }
    }
}

// Get all items
$query = "SELECT b.*, s.nama_supplier, s.bahan_baku 
          FROM barang b 
          LEFT JOIN supplier s ON b.id_supplier = s.id_supplier 
          ORDER BY b.nama_barang ASC";
$items_result = mysqli_query($conn, $query);

// Calculate total inventory value
$total_inventory_value = 0;
$total_items = 0;

// Store items in an array to use twice (once for calculations, once for display)
$all_items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $all_items[] = $item;
    $total_inventory_value += $item['stok'] * $item['harga'];
    $total_items += $item['stok'];
}

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($records_per_page, [10, 25, 50, 100])) {
    $records_per_page = 10; // Default to 10 if invalid value
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Ensure page is at least 1
$offset = ($page - 1) * $records_per_page;

// Total number of pages
$total_records = count($all_items);
$total_pages = ceil($total_records / $records_per_page);

// Format rupiah function
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function checkRelatedTransactions($conn, $id_barang) {
    $result = [];
    
    // Check barang_masuk table
    $query = "SELECT COUNT(*) as count FROM barang_masuk WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Error preparing query for barang_masuk: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Error executing query for barang_masuk: " . $error);
    }
    
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $result['barang_masuk'] = $data['count'];
    mysqli_stmt_close($stmt);
    
    // Check retur_barang table
    $query = "SELECT COUNT(*) as count FROM retur_barang WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Error preparing query for retur_barang: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Error executing query for retur_barang: " . $error);
    }
    
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $result['retur_barang'] = $data['count'];
    mysqli_stmt_close($stmt);
    
    // Check barang_keluar table
    $query = "SELECT COUNT(*) as count FROM barang_keluar WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Error preparing query for barang_keluar: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Error executing query for barang_keluar: " . $error);
    }
    
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $result['barang_keluar'] = $data['count'];
    mysqli_stmt_close($stmt);
    
    // Check detail_terima table
    $query = "SELECT COUNT(*) as count FROM detail_terima WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Error preparing query for detail_terima: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Error executing query for detail_terima: " . $error);
    }
    
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $result['detail_terima'] = $data['count'];
    mysqli_stmt_close($stmt);
    
    // Check stok_opname table
    $query = "SELECT COUNT(*) as count FROM stok_opname WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Error preparing query for stok_opname: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Error executing query for stok_opname: " . $error);
    }
    
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $result['stok_opname'] = $data['count'];
    mysqli_stmt_close($stmt);
    
    // Add more tables as needed
    
    return $result;
}

// Handle AJAX request for checking related transactions
if (isset($_GET['action']) && $_GET['action'] === 'check_related' && isset($_GET['id'])) {
    // Disable error output - we'll handle errors ourselves
    ini_set('display_errors', 0);
    error_reporting(0);
    
    // Ensure we only output JSON
    if (ob_get_level()) {
        ob_clean(); // Clear any previous output
    }
    header('Content-Type: application/json');
    
    try {
        $id = (int)$_GET['id'];
        
        // Make sure we have a valid database connection
        if (!$conn) {
            throw new Exception("Database connection is not available");
        }
        
        // Validate the ID
        if ($id <= 0) {
            throw new Exception("Invalid item ID provided");
        }
        
        // First check if the item exists
        $check_query = "SELECT id_barang FROM barang WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Database execute error: " . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            throw new Exception("Item with ID $id not found");
        }
        
        mysqli_stmt_close($stmt);
        
        // Now check related transactions
        $related = checkRelatedTransactions($conn, $id);
        
        echo json_encode([
            'success' => true, 
            'related' => $related,
            'can_delete' => array_sum($related) === 0
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Debug endpoint to check database connection
if (isset($_GET['action']) && $_GET['action'] === 'check_db') {
    header('Content-Type: application/json');
    
    $status = [
        'connection' => $conn ? true : false,
        'server_info' => $conn ? mysqli_get_server_info($conn) : 'Not connected',
        'host_info' => $conn ? mysqli_get_host_info($conn) : 'Not connected',
        'db_name' => $conn ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db"))['db'] : 'Not connected',
        'time' => date('Y-m-d H:i:s'),
        'php_version' => phpversion()
    ];
    
    echo json_encode($status);
    exit;
}

// Add isAdmin function if it doesn't exist
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?>

<style>
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<!-- Main Content -->
<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg shadow border border-blue-100">
            <h3 class="text-sm text-blue-700 font-medium mb-1">Total Jenis Barang</h3>
            <p class="text-2xl font-bold text-blue-800"><?= count($all_items) ?></p>
        </div>
        
        <div class="bg-green-50 p-4 rounded-lg shadow border border-green-100">
            <h3 class="text-sm text-green-700 font-medium mb-1">Total Quantity Barang</h3>
            <p class="text-2xl font-bold text-green-800"><?= $total_items ?> items</p>
        </div>
        
        <div class="bg-purple-50 p-4 rounded-lg shadow border border-purple-100">
            <h3 class="text-sm text-purple-700 font-medium mb-1">Total Nilai Inventaris</h3>
            <p class="text-2xl font-bold text-purple-800"><?= formatRupiah($total_inventory_value) ?></p>
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Daftar Barang</h2>
        
        <div class="flex space-x-2">
            <a href="print_barang.php" target="_blank" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-print mr-2"></i> Cetak Data
            </a>
            <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
            <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md" data-bs-toggle="modal" data-bs-target="#addBarangModal">
                <i class="fas fa-plus-circle mr-2"></i> Tambah Barang
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr class="bg-gray-100 border-b">
                    <th class="py-2 px-4 text-left">No</th>
                    <th class="py-2 px-4 text-left">Nama Barang</th>
                    <th class="py-2 px-4 text-left">Jenis</th>
                    <th class="py-2 px-4 text-left">Satuan</th>
                    <th class="py-2 px-4 text-left">Stok</th>
                    <th class="py-2 px-4 text-left">Stok Min</th>
                    <th class="py-2 px-4 text-left">Harga</th>
                    <th class="py-2 px-4 text-left">Supplier</th>
                    <th class="py-2 px-4 text-left">Lokasi</th>
                    <th class="py-2 px-4 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1 + $offset;
                
                // Get paginated items
                $paginated_items = array_slice($all_items, $offset, $records_per_page);
                
                foreach ($paginated_items as $row): 
                    // Determine row class based on stock level
                    $rowClass = "";
                    if ($row['stok'] <= 0) {
                        $rowClass = "bg-red-100";
                    } elseif ($row['stok'] <= $row['stok_minimum']) {
                        $rowClass = "bg-yellow-100";
                    }
                ?>
                <tr class="border-b hover:bg-gray-50 <?= $rowClass ?>">
                    <td class="py-2 px-4"><?= $no++ ?></td>
                    <td class="py-2 px-4"><?= $row['nama_barang'] ?></td>
                    <td class="py-2 px-4"><?= $row['jenis'] ?></td>
                    <td class="py-2 px-4"><?= $row['satuan'] ?></td>
                    <td class="py-2 px-4"><?= $row['stok'] ?></td>
                    <td class="py-2 px-4"><?= $row['stok_minimum'] ?></td>
                    <td class="py-2 px-4"><?= number_format($row['harga'], 0, ',', '.') ?></td>
                    <td class="py-2 px-4"><?= $row['nama_supplier'] ?? '-' ?></td>
                    <td class="py-2 px-4"><?= ucfirst($row['lokasi'] ?? '-') ?></td>
                    <td class="py-2 px-4">
                        <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
                        <div class="flex items-center space-x-2">
                            <button type="button" class="bg-yellow-500 hover:bg-yellow-600 text-white p-1 rounded-md edit-button" data-id="<?= $row['id_barang'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="bg-red-500 hover:bg-red-600 text-white p-1 rounded-md delete-button" data-id="<?= $row['id_barang'] ?>" data-name="<?= $row['nama_barang'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="text-gray-400">
                            <i class="fas fa-lock"></i> View Only
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($paginated_items)): ?>
                <tr>
                    <td colspan="10" class="py-4 px-4 text-center text-gray-500">Tidak ada data barang</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination Controls -->
        <?php if($total_pages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&per_page=<?= $records_per_page ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
                    echo '<a href="?page=1&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                    if($start_page > 2) {
                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                    }
                }
                
                // Display page links
                for($i = $start_page; $i <= $end_page; $i++) {
                    if($i == $page) {
                        echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">'.$i.'</span>';
                    } else {
                        echo '<a href="?page='.$i.'&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$i.'</a>';
                    }
                }
                
                // Always show last page
                if($end_page < $total_pages) {
                    if($end_page < $total_pages - 1) {
                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                    }
                    echo '<a href="?page='.$total_pages.'&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$total_pages.'</a>';
                }
                ?>
                
                <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&per_page=<?= $records_per_page ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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

<!-- Tambah Barang Modal -->
<div id="addBarangModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Barang Baru</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addBarangModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="nama_barang" class="block text-gray-700 text-sm font-semibold mb-2">Nama Barang</label>
                        <input type="text" id="nama_barang" name="nama_barang" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="id_supplier" class="block text-gray-700 text-sm font-semibold mb-2">Supplier</label>
                        <select id="id_supplier" name="id_supplier" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="updateSupplierInfo(this, 'jenis', 'satuan')">
                            <option value="">-- Pilih Supplier --</option>
                            <?php 
                            $supplier_query = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
                            $supplier_result = mysqli_query($conn, $supplier_query);
                            
                            while ($supplier = mysqli_fetch_assoc($supplier_result)) {
                                $supplier_info = json_encode([
                                    'bahan_baku' => $supplier['bahan_baku'],
                                    'satuan' => $supplier['satuan']
                                ]);
                                echo "<option value='{$supplier['id_supplier']}' data-supplier-info='{$supplier_info}'>{$supplier['nama_supplier']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="bahan_baku_select" class="block text-gray-700 text-sm font-semibold mb-2">Bahan Baku</label>
                        <select id="bahan_baku_select" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="updateBahanInfo(this)" disabled>
                            <option value="">-- Pilih Bahan Baku --</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="jenis" class="block text-gray-700 text-sm font-semibold mb-2">Jenis</label>
                        <input type="text" id="jenis" name="jenis" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="satuan" class="block text-gray-700 text-sm font-semibold mb-2">Satuan</label>
                        <input type="text" id="satuan" name="satuan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="stok" class="block text-gray-700 text-sm font-semibold mb-2">Stok Awal</label>
                        <input type="number" id="stok" name="stok" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="0" min="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="stok_minimum" class="block text-gray-700 text-sm font-semibold mb-2">Stok Minimum</label>
                        <input type="number" id="stok_minimum" name="stok_minimum" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="10" min="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="harga" class="block text-gray-700 text-sm font-semibold mb-2">Harga</label>
                        <input type="number" id="harga" name="harga" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="0" min="0" step="0.01" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="lokasi" class="block text-gray-700 text-sm font-semibold mb-2">Lokasi</label>
                        <select id="lokasi" name="lokasi" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <option value="">-- Pilih Lokasi --</option>
                            <option value="kitchen">Kitchen</option>
                            <option value="bar">Bar</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2" onclick="closeModal('addBarangModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_barang" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editBarangModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Edit Barang</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('editBarangModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="edit_id_barang" name="id_barang">
                
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="edit_nama_barang" class="block text-gray-700 text-sm font-semibold mb-2">Nama Barang</label>
                        <input type="text" id="edit_nama_barang" name="nama_barang" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_id_supplier" class="block text-gray-700 text-sm font-semibold mb-2">Supplier</label>
                        <select id="edit_id_supplier" name="id_supplier" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Pilih Supplier --</option>
                            <?php foreach ($all_suppliers as $supplier): ?>
                            <option value="<?= $supplier['id_supplier'] ?>" data-supplier-info='{"bahan_baku": "<?= $supplier['bahan_baku'] ?>", "satuan": "<?= $supplier['satuan'] ?>"}'><?= $supplier['nama_supplier'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_jenis" class="block text-gray-700 text-sm font-semibold mb-2">Jenis</label>
                        <input type="text" id="edit_jenis" name="jenis" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_satuan" class="block text-gray-700 text-sm font-semibold mb-2">Satuan</label>
                        <input type="text" id="edit_satuan" name="satuan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_stok" class="block text-gray-700 text-sm font-semibold mb-2">Stok</label>
                        <input type="number" id="edit_stok" name="stok" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_stok_minimum" class="block text-gray-700 text-sm font-semibold mb-2">Stok Minimum</label>
                        <input type="number" id="edit_stok_minimum" name="stok_minimum" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_harga" class="block text-gray-700 text-sm font-semibold mb-2">Harga</label>
                        <input type="number" id="edit_harga" name="harga" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0" step="0.01" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_lokasi" class="block text-gray-700 text-sm font-semibold mb-2">Lokasi</label>
                        <select id="edit_lokasi" name="lokasi" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Pilih Lokasi --</option>
                            <option value="kitchen">Kitchen</option>
                            <option value="bar">Bar</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2" onclick="closeModal('editBarangModal')">
                        Batal
                    </button>
                    <button type="submit" name="edit_barang" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
            
            <?php if (isAdmin()): ?>
            <!-- Quick Stock Update Form for Admins -->
            <div class="mt-6 pt-4 border-t border-gray-300">
                <h4 class="text-md font-medium text-gray-800 mb-3">Update Stok Cepat (Admin Only)</h4>
                <form method="POST" action="" id="stock_update_form">
                    <input type="hidden" id="quick_update_id" name="id_barang">
                    <input type="hidden" id="old_stock" name="old_stock">
                    
                    <div class="flex items-center mb-3">
                        <label for="new_stock" class="block text-gray-700 text-sm font-semibold mr-3">Stok Baru:</label>
                        <input type="number" id="new_stock" name="new_stock" class="shadow-sm border border-gray-300 rounded w-24 py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0" required>
                        
                        <button type="submit" name="update_stock" class="ml-4 bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update Stok
                        </button>
        </div>
                    <p class="text-xs text-gray-500">Catatan: Update stok cepat hanya akan mengubah jumlah stok tanpa membuat catatan transaksi.</p>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteBarangModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_confirmation_text"></p>
                <div id="related_info"></div>
                
                <?php if (isAdmin()): ?>
                <div class="mt-4 text-left">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="force_delete_checkbox" class="form-checkbox h-5 w-5 text-red-600 rounded">
                        <span class="ml-2 text-sm font-medium text-red-600">Hapus Paksa (Semua transaksi terkait akan ikut terhapus)</span>
                    </label>
                </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="" id="delete_form">
                <input type="hidden" id="delete_id_barang" name="id_barang">
                <input type="hidden" id="force_delete_input" name="force_delete" value="0">
                
                <div class="items-center px-4 py-3">
                    <button id="delete_button" type="submit" name="delete_barang" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Hapus
                    </button>
                    <button type="button" onclick="closeModal('deleteBarangModal')" class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show modal function
    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
    
    // Close modal function
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    // Function to update fields based on selected supplier
    function updateSupplierInfo(selectElement, targetJenisId, targetSatuanId) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const supplierData = JSON.parse(selectedOption.getAttribute('data-supplier-info') || '{}');
        
        if (supplierData.satuan) {
            document.getElementById(targetSatuanId).value = supplierData.satuan;
        }
        
        // Populate bahan baku dropdown
        const supplierId = selectElement.value;
        if (supplierId) {
            // Clear and populate bahan baku dropdown
            const bahanSelect = document.getElementById('bahan_baku_select');
            if (bahanSelect) {
                bahanSelect.innerHTML = '<option value="">-- Pilih Bahan Baku --</option>';
                
                if (supplierData.bahan_baku && supplierData.satuan) {
                    const bahanItems = supplierData.bahan_baku.split(',');
                    const satuanItems = supplierData.satuan.split(',');
                    
                    for (let i = 0; i < bahanItems.length; i++) {
                        const bahan = bahanItems[i].trim();
                        const satuan = satuanItems[i] ? satuanItems[i].trim() : '';
                        
                        if (bahan) {
                            const option = document.createElement('option');
                            option.value = bahan;
                            option.textContent = bahan;
                            option.setAttribute('data-satuan', satuan);
                            bahanSelect.appendChild(option);
                        }
                    }
                    
                    bahanSelect.disabled = false;
                    
                    // Set nama_barang to first bahan baku item if available
                    if (bahanItems.length > 0) {
                        const firstBahan = bahanItems[0].trim();
                        const firstSatuan = satuanItems[0] ? satuanItems[0].trim() : '';
                        
                        if (firstBahan) {
                            // If we're in the add form
                            const namaBarangInput = document.getElementById('nama_barang');
                            if (namaBarangInput) {
                                namaBarangInput.value = firstBahan;
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Function to load supplier items
    function loadSupplierItems(supplierId, targetSelectId) {
        const targetSelect = document.getElementById(targetSelectId);
        
        if (!supplierId) {
            targetSelect.innerHTML = '<option value="">-- Pilih Supplier Terlebih Dahulu --</option>';
            targetSelect.disabled = true;
            return;
        }
        
        // Show loading state
        targetSelect.innerHTML = '<option value="">Loading...</option>';
        targetSelect.disabled = true;
        
        // Create AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_supplier_items.php?id_supplier=' + supplierId, true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    // Clear and populate dropdown
                    targetSelect.innerHTML = '';
                    
                    if (response.length === 0) {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Tidak ada item untuk supplier ini';
                        targetSelect.appendChild(option);
                    } else {
                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = '-- Pilih Item --';
                        targetSelect.appendChild(defaultOption);
                        
                        response.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.nama_item;
                            option.setAttribute('data-satuan', item.satuan);
                            targetSelect.appendChild(option);
                        });
                        
                        targetSelect.disabled = false;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    targetSelect.innerHTML = '<option value="">Error loading items</option>';
                }
            } else {
                targetSelect.innerHTML = '<option value="">Error loading items</option>';
            }
        };
        
        xhr.onerror = function() {
            targetSelect.innerHTML = '<option value="">Error loading items</option>';
        };
        
        xhr.send();
    }
    
    // Function to update fields based on selected item
    function updateItemInfo(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const satuan = selectedOption.getAttribute('data-satuan') || '';
            const namaItem = selectedOption.textContent || '';
            
            document.getElementById('nama_barang').value = namaItem;
            document.getElementById('satuan').value = satuan;
        }
    }
    
    // Add event listeners for supplier dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        const addSupplierSelect = document.getElementById('id_supplier');
        const editSupplierSelect = document.getElementById('edit_id_supplier');
        
        if (addSupplierSelect) {
            addSupplierSelect.addEventListener('change', function() {
                updateSupplierInfo(this, 'jenis', 'satuan');
            });
        }
        
        if (editSupplierSelect) {
            editSupplierSelect.addEventListener('change', function() {
                updateSupplierInfo(this, 'edit_jenis', 'edit_satuan');
            });
        }
    });
    
    // Function to load item data for editing
    function editItem(itemId) {
        console.log("Fetching item data for ID:", itemId);
        
        // Use jQuery AJAX
        $.ajax({
            url: 'get_barang_data.php',
            type: 'GET',
            data: {
                id: itemId
            },
            dataType: 'json',
            success: function(data) {
                console.log("Received data:", data);
                
                if (data.success) {
                    const item = data.item;
                    
                    // Fill form fields
                    $('#edit_id_barang').val(item.id_barang);
                    $('#edit_nama_barang').val(item.nama_barang);
                    $('#edit_satuan').val(item.satuan);
                    $('#edit_jenis').val(item.jenis);
                    $('#edit_stok').val(item.stok);
                    $('#edit_stok_minimum').val(item.stok_minimum);
                    $('#edit_harga').val(item.harga);
                    $('#edit_lokasi').val(item.lokasi || '');
                    
                    // Set supplier dropdown
                    const supplierSelect = document.getElementById('edit_id_supplier');
                    if (supplierSelect) {
                        for (let i = 0; i < supplierSelect.options.length; i++) {
                            if (supplierSelect.options[i].value == item.id_supplier) {
                                supplierSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                    
                    // Fill quick stock update form if it exists
                    $('#quick_update_id').val(item.id_barang);
                    $('#old_stock').val(item.stok);
                    $('#new_stock').val(item.stok);
                    
                    showModal('editBarangModal');
                } else {
                    alert('Failed to load item data: ' + (data.message || 'Unknown error'));
                    console.error('Error details:', data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('Error loading item data: ' + error);
            }
        });
    }
    
    // Delete item function
    function deleteItem(id, nama) {
        document.getElementById('delete_id_barang').value = id;
        document.getElementById('delete_confirmation_text').innerText = 'Memverifikasi apakah barang dapat dihapus...';
        
        // Reset force delete checkbox
        const forceDeleteCheckbox = document.getElementById('force_delete_checkbox');
        if (forceDeleteCheckbox) {
            forceDeleteCheckbox.checked = false;
        }
        document.getElementById('force_delete_input').value = "0";
        
        // Reset button state
        const deleteButton = document.getElementById('delete_button');
        deleteButton.textContent = "Hapus";
        deleteButton.classList.remove('bg-red-700');
        deleteButton.disabled = true;
        
        // Show modal first
        showModal('deleteBarangModal');
        
        // Add a loading indicator
        const relatedInfoEl = document.getElementById('related_info');
        relatedInfoEl.innerHTML = '<div class="flex justify-center my-2"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div></div>';
        
        // Check if the item can be deleted
        fetch(`?action=check_related&id=${id}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text(); // Get as text first to check
            })
            .then(text => {
                // Try to parse as JSON, and if it fails, throw a more helpful error
                try {
                    return JSON.parse(text);
                } catch (error) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned invalid JSON: ' + error.message);
                }
            })
            .then(data => {
                console.log("Response data:", data); // Debug: log the full response
                
                if (data.success) {
                    if (data.can_delete) {
                        document.getElementById('delete_confirmation_text').innerText = `Apakah Anda yakin ingin menghapus barang "${nama}"?`;
                        deleteButton.disabled = false;
                        relatedInfoEl.innerHTML = '';
                    } else {
                        let relatedText = '<div class="text-left mt-2">';
                        relatedText += '<p class="text-red-600 font-semibold">Barang ini tidak dapat dihapus karena masih digunakan pada:</p>';
                        relatedText += '<ul class="list-disc ml-5 mt-1">';
                        
                        let totalRelated = 0;
                        
                        if (data.related.barang_masuk > 0) {
                            relatedText += `<li>${data.related.barang_masuk} transaksi barang masuk</li>`;
                            totalRelated += data.related.barang_masuk;
                        }
                        
                        if (data.related.retur_barang > 0) {
                            relatedText += `<li>${data.related.retur_barang} transaksi retur barang</li>`;
                            totalRelated += data.related.retur_barang;
                        }
                        
                        if (data.related.barang_keluar > 0) {
                            relatedText += `<li>${data.related.barang_keluar} transaksi barang keluar</li>`;
                            totalRelated += data.related.barang_keluar;
                        }
                        
                        if (data.related.detail_terima > 0) {
                            relatedText += `<li>${data.related.detail_terima} detail penerimaan barang</li>`;
                            totalRelated += data.related.detail_terima;
                        }
                        
                        if (data.related.stok_opname > 0) {
                            relatedText += `<li>${data.related.stok_opname} catatan stok opname</li>`;
                            totalRelated += data.related.stok_opname;
                        }
                        
                        relatedText += '</ul>';
                        
                        // Add different text based on whether the user is admin
                        const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
                        
                        if (isAdmin) {
                            relatedText += `<p class="mt-2">Sebagai administrator, Anda dapat menggunakan opsi "Hapus Paksa" di bawah untuk menghapus barang ini beserta ${totalRelated} transaksi terkait.</p>`;
                            deleteButton.disabled = false;
                        } else {
                            relatedText += '<p class="mt-2">Anda perlu menghapus semua transaksi terkait terlebih dahulu sebelum dapat menghapus barang ini.</p>';
                            deleteButton.disabled = true;
                        }
                        
                        relatedText += '</div>';
                        
                        document.getElementById('delete_confirmation_text').innerText = `Tidak dapat menghapus barang "${nama}" secara normal.`;
                        relatedInfoEl.innerHTML = relatedText;
                    }
                } else {
                    console.error('Server error:', data); // Debug: log error details
                    
                    document.getElementById('delete_confirmation_text').innerText = 'Terjadi kesalahan saat memeriksa relasi barang.';
                    
                    let errorDetails = '';
                    if (data.message) {
                        errorDetails = `<p class="text-red-500">Error: ${data.message}</p>`;
                    }
                    
                    relatedInfoEl.innerHTML = errorDetails;
                    
                    // Still allow force delete for admins if error occurs
                    const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
                    if (isAdmin) {
                        relatedInfoEl.innerHTML += '<p class="mt-2 text-left">Sebagai administrator, Anda masih dapat menggunakan opsi "Hapus Paksa" di bawah untuk mencoba menghapus barang ini.</p>';
                        deleteButton.disabled = false;
                    } else {
                        deleteButton.disabled = true;
                    }
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                
                document.getElementById('delete_confirmation_text').innerText = 'Terjadi kesalahan saat memeriksa relasi barang.';
                relatedInfoEl.innerHTML = `<p class="text-red-500">Error koneksi: ${error.message || 'Silakan coba lagi.'}</p>`;
                
                // Still allow force delete for admins if error occurs
                const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
                if (isAdmin) {
                    relatedInfoEl.innerHTML += '<p class="mt-2 text-left">Sebagai administrator, Anda masih dapat menggunakan opsi "Hapus Paksa" di bawah untuk mencoba menghapus barang ini.</p>';
                    deleteButton.disabled = false;
                } else {
                    deleteButton.disabled = true;
                }
            });
    }
    
    // Show add modal when button is clicked
    document.querySelector('[data-bs-target="#addBarangModal"]').addEventListener('click', function() {
        showModal('addBarangModal');
    });

    // Function to update satuan based on selected bahan baku
    function updateBahanInfo(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const satuan = selectedOption.getAttribute('data-satuan');
        const bahan = selectedOption.value;
        
        if (bahan) {
            document.getElementById('nama_barang').value = bahan;
        }
        
        if (satuan) {
            document.getElementById('satuan').value = satuan;
        }
    }

    $(document).ready(function() {
        // Edit item handler
        $(document).on('click', '.edit-item-btn', function() {
            const itemId = $(this).data('id');
            console.log("Edit button clicked for ID:", itemId);
            
            // Clear form first
            $('#edit_id_barang').val('');
            $('#edit_nama_barang').val('');
            $('#edit_satuan').val('');
            $('#edit_jenis').val('');
            $('#edit_stok').val('');
            $('#edit_stok_minimum').val('');
            $('#edit_harga').val('');
            $('#edit_lokasi').val('');
            
            // Show loading indicator
            $('#editBarangModal .modal-content').append('<div class="loading-overlay"><div class="spinner"></div></div>');
            
            // Get item data
            $.ajax({
                url: 'get_barang_data.php',
                type: 'GET',
                data: { id: itemId },
                dataType: 'json',
                success: function(data) {
                    console.log("Received data:", data);
                    $('.loading-overlay').remove();
                    
                    if (data.success) {
                        const item = data.item;
                        
                        // Fill form fields
                        $('#edit_id_barang').val(item.id_barang);
                        $('#edit_nama_barang').val(item.nama_barang);
                        $('#edit_satuan').val(item.satuan);
                        $('#edit_jenis').val(item.jenis);
                        $('#edit_stok').val(item.stok);
                        $('#edit_stok_minimum').val(item.stok_minimum);
                        $('#edit_harga').val(item.harga);
                        $('#edit_lokasi').val(item.lokasi || '');
                        
                        // Set supplier dropdown
                        $('#edit_id_supplier').val(item.id_supplier || '');
                        
                        // Show modal
                        showModal('editBarangModal');
                    } else {
                        alert('Failed to load item data: ' + (data.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    $('.loading-overlay').remove();
                    console.error('AJAX error:', status, error);
                    alert('Error loading item data: ' + error);
                }
            });
        });
    });

    // Event listener for force delete checkbox
    document.addEventListener('DOMContentLoaded', function() {
        const forceDeleteCheckbox = document.getElementById('force_delete_checkbox');
        if (forceDeleteCheckbox) {
            forceDeleteCheckbox.addEventListener('change', function() {
                document.getElementById('force_delete_input').value = this.checked ? "1" : "0";
                
                // Update button text and style
                const deleteButton = document.getElementById('delete_button');
                if (this.checked) {
                    deleteButton.textContent = "Hapus Paksa";
                    deleteButton.classList.add('bg-red-700');
                } else {
                    deleteButton.textContent = "Hapus";
                    deleteButton.classList.remove('bg-red-700');
                }
                
                // Always enable the button when admin selects force delete
                if (this.checked) {
                    deleteButton.disabled = false;
                }
            });
        }
        
        // Ensure the delete form submits properly
        const deleteForm = document.getElementById('delete_form');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                const forceDeleteCheckbox = document.getElementById('force_delete_checkbox');
                const forceDeleteInput = document.getElementById('force_delete_input');
                
                // Make sure the force delete value is correctly set
                if (forceDeleteCheckbox && forceDeleteCheckbox.checked) {
                    forceDeleteInput.value = "1";
                } else {
                    forceDeleteInput.value = "0";
                }
                
                // Continue with form submission
                return true;
            });
        }
        
        // For edit buttons
        const editButtons = document.querySelectorAll('.edit-item-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                editItem(itemId);
            });
        });
    });

    function deleteItemConfirmed() {
        document.getElementById('deleteItemForm').submit();
    }
    
    function changePerPage(perPage) {
        window.location.href = "?page=1&per_page=" + perPage;
    }
</script>

<?php require_once 'includes/footer.php'; ?>