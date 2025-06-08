<?php
$pageTitle = "Laporan Barang Masuk";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php';

// Handle report deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id_laporan = $_GET['delete'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get associated id_masuk first
        $get_masuk_query = "SELECT id_masuk FROM laporan_masuk_detail WHERE id_laporan = ?";
        $get_masuk_stmt = $conn->prepare($get_masuk_query);
        
        if (!$get_masuk_stmt) {
            throw new Exception("Error preparing query: " . $conn->error);
        }
        
        $get_masuk_stmt->bind_param("i", $id_laporan);
        $get_masuk_stmt->execute();
        $masuk_result = $get_masuk_stmt->get_result();
        
        $id_masuk_list = [];
        while ($row = $masuk_result->fetch_assoc()) {
            $id_masuk_list[] = $row['id_masuk'];
        }
        $get_masuk_stmt->close();
        
        // Delete detail records first
        $detail_query = "DELETE FROM laporan_masuk_detail WHERE id_laporan = ?";
        $detail_stmt = $conn->prepare($detail_query);
        
        if (!$detail_stmt) {
            throw new Exception("Error preparing detail query: " . $conn->error);
        }
        
        $detail_stmt->bind_param("i", $id_laporan);
        $detail_stmt->execute();
        $detail_stmt->close();
        
        // Delete main record
        $main_query = "DELETE FROM laporan_masuk WHERE id_laporan_masuk = ?";
        $main_stmt = $conn->prepare($main_query);
        
        if (!$main_stmt) {
            throw new Exception("Error preparing main query: " . $conn->error);
        }
        
        $main_stmt->bind_param("i", $id_laporan);
        $main_stmt->execute();
        $main_stmt->close();
        
        // We do NOT delete barang_masuk entries or adjust stock to ensure stock remains accurate
        // This is intentional - deleting the report should not affect inventory levels
        
        // Commit transaction
        $conn->commit();
        
        // Log activity
        logActivity($_SESSION['user_id'], "Menghapus laporan barang masuk #$id_laporan");
        
        // Set success message
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Laporan berhasil dihapus'
        ];
    } catch (Exception $e) {
        // Roll back on error
        $conn->rollback();
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Gagal menghapus laporan: ' . $e->getMessage()
        ];
    }
    
    // Redirect to refresh page
    header('Location: laporan_masuk.php');
    exit;
}

// Variables for edit mode
$edit_mode = false;
$edit_data = null;
$edit_id = null;

// Check if we're in edit mode
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_mode = true;
    
    // Get the data for editing
    $edit_data = getLaporanMasukForEdit($conn, $edit_id);
}

// Handle form submission for edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_laporan'])) {
    // Check if user has permission to edit
    if ($_SESSION['user_role'] === 'crew') {
        // Redirect with error message
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Anda tidak memiliki akses untuk mengubah laporan'
        ];
        header('Location: laporan_masuk.php');
        exit;
    }
    
    $id_laporan = $_POST['id_laporan'] ?? '';
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $id_masuk = $_POST['id_masuk'] ?? '';
    $nama_barang = $_POST['nama_barang'] ?? '';
    $jumlah = $_POST['jumlah'] ?? '';
    $satuan = $_POST['satuan'] ?? '';
    $supplier = $_POST['supplier'] ?? '';
    $status = $_POST['status'] ?? 'pending'; // Get status from edit form
    $periode = (int)($_POST['periode'] ?? 1); // Get periode from edit form
    $errors = [];
    
    // Validate required fields
    if (empty($tanggal_laporan)) {
        $errors[] = "Tanggal laporan wajib diisi";
    }
    
    if (empty($nama_barang)) {
        $errors[] = "Nama barang wajib diisi";
    }
    
    if (empty($jumlah) || !is_numeric($jumlah) || $jumlah <= 0) {
        $errors[] = "Jumlah harus berupa angka positif";
    }
    
    if (empty($satuan)) {
        $errors[] = "Satuan wajib diisi";
    }
    
    if (empty($supplier)) {
        $errors[] = "Supplier wajib diisi";
    }
    
    if ($periode < 1 || $periode > 4) {
        $errors[] = "Periode harus antara 1-4";
    }
    
    // If no errors, update the report
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update the main report including status
            $query = "UPDATE laporan_masuk SET tanggal_laporan = ?, status = ?, periode = ? WHERE id_laporan_masuk = ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error in query: " . $conn->error);
            }
            $stmt->bind_param("ssii", $tanggal_laporan, $status, $periode, $id_laporan);
            $stmt->execute();
            
            // Find a matching barang ID based on the name provided
            $find_barang = "SELECT id_barang FROM barang WHERE nama_barang LIKE ?";
            $find_stmt = $conn->prepare($find_barang);
            if (!$find_stmt) {
                throw new Exception("Error preparing find barang query: " . $conn->error);
            }
            
            $search_term = "%$nama_barang%";
            $find_stmt->bind_param("s", $search_term);
            $find_stmt->execute();
            $barang_result = $find_stmt->get_result();
            
            // Get barang ID or use default 1
            $barang_id = 1;
            if ($barang_result && $barang_result->num_rows > 0) {
                $barang_row = $barang_result->fetch_assoc();
                $barang_id = $barang_row['id_barang'];
            }
            $find_stmt->close();
            
            // Find a matching supplier ID based on the name provided
            $find_supplier = "SELECT id_supplier FROM supplier WHERE nama_supplier LIKE ?";
            $supplier_stmt = $conn->prepare($find_supplier);
            if (!$supplier_stmt) {
                throw new Exception("Error preparing find supplier query: " . $conn->error);
            }
            
            $search_term = "%$supplier%";
            $supplier_stmt->bind_param("s", $search_term);
            $supplier_stmt->execute();
            $supplier_result = $supplier_stmt->get_result();
            
            // Get supplier ID or use default 1
            $supplier_id = 1;
            if ($supplier_result && $supplier_result->num_rows > 0) {
                $supplier_row = $supplier_result->fetch_assoc();
                $supplier_id = $supplier_row['id_supplier'];
            }
            $supplier_stmt->close();
            
            // Update barang_masuk
            $masuk_query = "UPDATE barang_masuk SET id_barang = ?, qty_masuk = ?, tanggal_masuk = ?, id_supplier = ?, periode = ? WHERE id_masuk = ?";
            
            $masuk_stmt = $conn->prepare($masuk_query);
            if (!$masuk_stmt) {
                throw new Exception("Error preparing barang_masuk update query: " . $conn->error);
            }
            
            $masuk_stmt->bind_param("idsiii", $barang_id, $jumlah, $tanggal_laporan, $supplier_id, $periode, $id_masuk);
            $masuk_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Log activity
            logActivity($user_id, "Mengubah laporan barang masuk #$id_laporan");
            
            // Set success message
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Laporan berhasil diupdate'
            ];
            
            // Redirect to refresh page
            header('Location: laporan_masuk.php');
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $errors[] = "Gagal mengupdate laporan: " . $e->getMessage();
        }
    }
}

// Handle new report form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_laporan'])) {
    // Check if user has permission to add new reports
    if ($_SESSION['user_role'] === 'crew') {
        // Redirect with error message
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Anda tidak memiliki akses untuk menambah laporan'
        ];
        header('Location: laporan_masuk.php');
        exit;
    }
    
    // Validate form data
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $nama_barang = $_POST['nama_barang'] ?? '';
    $jumlah = $_POST['jumlah'] ?? '';
    $satuan = $_POST['satuan'] ?? '';
    $supplier = $_POST['supplier'] ?? '';
    $status = $_POST['status'] ?? 'pending'; // Get status from form
    $periode = (int)($_POST['periode'] ?? 1); // Get periode from form
    $errors = [];
    
    // Validate required fields
    if (empty($tanggal_laporan)) {
        $errors[] = "Tanggal laporan wajib diisi";
    }
    
    if (empty($nama_barang)) {
        $errors[] = "Nama barang wajib diisi";
    }
    
    if (empty($jumlah) || !is_numeric($jumlah) || $jumlah <= 0) {
        $errors[] = "Jumlah harus berupa angka positif";
    }
    
    if (empty($satuan)) {
        $errors[] = "Satuan wajib diisi";
    }
    
    if (empty($supplier)) {
        $errors[] = "Supplier wajib diisi";
    }
    
    if ($periode < 1 || $periode > 4) {
        $errors[] = "Periode harus antara 1-4";
    }
    
    // If no errors, save the report
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Check if tables exist first
            $table_check = $conn->query("SHOW TABLES LIKE 'laporan_masuk'");
            if ($table_check->num_rows == 0) {
                // Table doesn't exist, create it
                $create_laporan_masuk = "
                CREATE TABLE IF NOT EXISTS `laporan_masuk` (
                  `id_laporan_masuk` int(11) NOT NULL AUTO_INCREMENT,
                  `tanggal_laporan` date DEFAULT NULL,
                  `created_by` int(11) DEFAULT NULL,
                  `created_at` datetime DEFAULT NULL,
                  `status` varchar(50) DEFAULT 'pending',
                  `periode` int(11) DEFAULT NULL,
                  PRIMARY KEY (`id_laporan_masuk`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
                
                if (!$conn->query($create_laporan_masuk)) {
                    throw new Exception("Error creating laporan_masuk table: " . $conn->error);
                }
            } else {
                // Check if status column exists
                $check_status = $conn->query("SHOW COLUMNS FROM `laporan_masuk` LIKE 'status'");
                if ($check_status->num_rows == 0) {
                    // Add the status column
                    $add_status = "ALTER TABLE `laporan_masuk` ADD COLUMN `status` varchar(50) DEFAULT 'pending'";
                    if (!$conn->query($add_status)) {
                        throw new Exception("Error adding status column: " . $conn->error);
                    }
                }
                
                // Check if periode column exists
                $check_periode = $conn->query("SHOW COLUMNS FROM `laporan_masuk` LIKE 'periode'");
                if ($check_periode->num_rows == 0) {
                    // Add the periode column
                    $add_periode = "ALTER TABLE `laporan_masuk` ADD COLUMN `periode` int(11) DEFAULT NULL";
                    if (!$conn->query($add_periode)) {
                        throw new Exception("Error adding periode column: " . $conn->error);
                    }
                }
            }
            
            // Check if detail table exists
            $detail_table_check = $conn->query("SHOW TABLES LIKE 'laporan_masuk_detail'");
            if ($detail_table_check->num_rows == 0) {
                // Table doesn't exist, create it
                $create_detail = "
                CREATE TABLE IF NOT EXISTS `laporan_masuk_detail` (
                  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_laporan` int(11) DEFAULT NULL,
                  `id_masuk` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_laporan` (`id_laporan`),
  KEY `id_masuk` (`id_masuk`),
  CONSTRAINT `laporan_masuk_detail_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_masuk` (`id_laporan_masuk`) ON DELETE CASCADE,
  CONSTRAINT `laporan_masuk_detail_ibfk_2` FOREIGN KEY (`id_masuk`) REFERENCES `barang_masuk` (`id_masuk`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
                
                if (!$conn->query($create_detail)) {
                    throw new Exception("Error creating laporan_masuk_detail table: " . $conn->error);
                }
            }
            
            // Insert the main report with all columns from the check_tables.php output
            $query = "INSERT INTO laporan_masuk (tanggal_laporan, created_by, created_at, status, periode) 
                      VALUES (?, ?, NOW(), ?, ?)";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error in query: " . $conn->error);
            }
            $stmt->bind_param("sisi", $tanggal_laporan, $user_id, $status, $periode);
            $stmt->execute();
            
            $id_laporan = $conn->insert_id;
            $stmt->close();
            
            // Find a matching barang ID based on the name provided
            $find_barang = "SELECT id_barang FROM barang WHERE nama_barang LIKE ?";
            $find_stmt = $conn->prepare($find_barang);
            if (!$find_stmt) {
                throw new Exception("Error preparing find barang query: " . $conn->error);
            }
            
            $search_term = "%$nama_barang%";
            $find_stmt->bind_param("s", $search_term);
            $find_stmt->execute();
            $barang_result = $find_stmt->get_result();
            
            // Get barang ID or use default 1
            $barang_id = 1;
            if ($barang_result && $barang_result->num_rows > 0) {
                $barang_row = $barang_result->fetch_assoc();
                $barang_id = $barang_row['id_barang'];
            }
            $find_stmt->close();
            
            // Find a matching supplier ID based on the name provided
            $find_supplier = "SELECT id_supplier FROM supplier WHERE nama_supplier LIKE ?";
            $supplier_stmt = $conn->prepare($find_supplier);
            if (!$supplier_stmt) {
                throw new Exception("Error preparing find supplier query: " . $conn->error);
            }
            
            $search_term = "%$supplier%";
            $supplier_stmt->bind_param("s", $search_term);
            $supplier_stmt->execute();
            $supplier_result = $supplier_stmt->get_result();
            
            // Get supplier ID or use default 1
            $supplier_id = 1;
            if ($supplier_result && $supplier_result->num_rows > 0) {
                $supplier_row = $supplier_result->fetch_assoc();
                $supplier_id = $supplier_row['id_supplier'];
            }
            $supplier_stmt->close();
            
            // Create an entry in barang_masuk table
            $masuk_query = "INSERT INTO barang_masuk (id_barang, qty_masuk, tanggal_masuk, id_supplier, id_user, periode) 
                          VALUES (?, ?, ?, ?, ?, ?)";

            $masuk_stmt = $conn->prepare($masuk_query);
            if (!$masuk_stmt) {
                throw new Exception("Error preparing barang_masuk query: " . $conn->error);
            }

            $masuk_stmt->bind_param("idsiii", $barang_id, $jumlah, $tanggal_laporan, $supplier_id, $user_id, $periode);
            $masuk_stmt->execute();

            $id_masuk = $conn->insert_id;
            
            // Now insert into laporan_masuk_detail
            $query = "INSERT INTO laporan_masuk_detail 
          (id_laporan, id_masuk) 
          VALUES (?, ?)";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing detail query: " . $conn->error);
            }
            
            $stmt->bind_param("ii", $id_laporan, $id_masuk);
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Error executing detail query: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Log activity
            logActivity($user_id, "Membuat laporan barang masuk baru #$id_laporan");
            
            // Set success message
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Laporan berhasil dibuat'
            ];
            
            // Redirect to refresh page
            header('Location: laporan_masuk.php');
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $errors[] = "Gagal menyimpan laporan: " . $e->getMessage();
        }
    }
}

// Get all laporan masuk
$query = "SELECT lm.*, 
          u.nama_lengkap as created_by_name,
          (SELECT COUNT(*) FROM laporan_masuk_detail WHERE id_laporan = lm.id_laporan_masuk) as detail_count
          FROM laporan_masuk lm
          LEFT JOIN users u ON lm.created_by = u.id_user
          ORDER BY lm.tanggal_laporan DESC";
$laporan_list = mysqli_query($conn, $query);

// Pagination settings
$records_per_page = isset($_GET['records_per_page']) ? (int)$_GET['records_per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get all days with laporan masuk with pagination
$all_days = getAllDaysWithLaporan($conn, $records_per_page, $offset);

// Get total records for pagination
$total_records = getTotalDaysWithLaporan($conn);
$total_pages = ceil($total_records / $records_per_page);

// Records per page options for dropdown
$records_per_page_options = [10, 25, 50, 100];

// Function to get detail count and format status
function formatStatus($detailCount) {
    if ($detailCount > 0) {
        return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Disetujui</span>';
    } else {
        return '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Draft</span>';
    }
}

// Function to get laporan masuk details for edit
function getLaporanMasukForEdit($conn, $id_laporan) {
    $query = "SELECT lm.*, 
              u.nama_lengkap as created_by_name
              FROM laporan_masuk lm
              LEFT JOIN users u ON lm.created_by = u.id_user
              WHERE lm.id_laporan_masuk = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_laporan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $laporan = mysqli_fetch_assoc($result);
    
    if (!$laporan) {
            return null;
        }
        
    // Get all detail items
    $detail_query = "SELECT lmd.*, 
                    bm.id_barang, bm.qty_masuk, bm.tanggal_masuk, bm.harga_satuan, bm.lokasi, bm.periode,
                    b.nama_barang, b.satuan,
                    s.id_supplier, s.nama_supplier
                FROM laporan_masuk_detail lmd
                JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
                JOIN barang b ON bm.id_barang = b.id_barang
                    LEFT JOIN supplier s ON b.id_supplier = s.id_supplier
                WHERE lmd.id_laporan = ?
                    ORDER BY bm.tanggal_masuk DESC";
    $detail_stmt = mysqli_prepare($conn, $detail_query);
    mysqli_stmt_bind_param($detail_stmt, "i", $id_laporan);
    mysqli_stmt_execute($detail_stmt);
    $detail_result = mysqli_stmt_get_result($detail_stmt);
    
    $details = [];
    while ($row = mysqli_fetch_assoc($detail_result)) {
        $details[] = $row;
    }
    
    $laporan['details'] = $details;
    
        return $laporan;
}

// Function to get first detail item for display
function getFirstLaporanMasukDetail($conn, $id_laporan) {
    $query = "SELECT lmd.*, 
              bm.id_barang, bm.qty_masuk, bm.tanggal_masuk, bm.harga_satuan,
              b.nama_barang, b.satuan,
              s.nama_supplier
                FROM laporan_masuk_detail lmd
                JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
                JOIN barang b ON bm.id_barang = b.id_barang
              LEFT JOIN supplier s ON b.id_supplier = s.id_supplier
                WHERE lmd.id_laporan = ? 
              ORDER BY bm.tanggal_masuk DESC
              LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_laporan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

// Function to get daily summary of incoming items
function getDailySummary($conn, $date, $periode) {
    $query = "SELECT 
                DATE(bm.tanggal_masuk) as tanggal,
                COUNT(DISTINCT bm.id_masuk) as total_transaksi,
                SUM(bm.qty_masuk) as total_qty,
                COUNT(DISTINCT bm.id_barang) as total_jenis_barang,
                p.periode
              FROM 
                barang_masuk bm
              LEFT JOIN 
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN 
                laporan_masuk p ON lmd.id_laporan = p.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ?
              GROUP BY 
                DATE(bm.tanggal_masuk), p.periode";
                
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [
            'total_transaksi' => 0,
            'total_qty' => 0,
            'total_jenis_barang' => 0
        ];
    }
    
    $stmt->bind_param("si", $date, $periode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return [
            'total_transaksi' => 0,
            'total_qty' => 0,
            'total_jenis_barang' => 0
        ];
    }
}

// Function to get all days with laporan masuk with pagination
function getAllDaysWithLaporan($conn, $limit = null, $offset = null) {
    $query = "SELECT 
              DATE(bm.tanggal_masuk) as tanggal,
              bm.periode,
              s.id_supplier,
              s.nama_supplier,
              COUNT(DISTINCT bm.id_masuk) as entry_count,
              MAX(lm.status) as status
              FROM barang_masuk bm
              JOIN laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              JOIN laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              JOIN barang b ON bm.id_barang = b.id_barang
              JOIN supplier s ON bm.id_supplier = s.id_supplier
              GROUP BY DATE(bm.tanggal_masuk), bm.periode, s.id_supplier
              ORDER BY DATE(bm.tanggal_masuk) DESC, s.nama_supplier ASC";
    
    // Add limit and offset for pagination if provided
    if ($limit !== null && $offset !== null) {
        $query .= " LIMIT $offset, $limit";
    }
    
    $result = mysqli_query($conn, $query);
    
    $days = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $days[] = $row;
    }
    
    return $days;
}

// Function to get total count of days with laporan for pagination
function getTotalDaysWithLaporan($conn) {
    $query = "SELECT COUNT(*) as total FROM (
              SELECT 
              DATE(bm.tanggal_masuk) as tanggal,
              bm.periode,
              s.id_supplier
              FROM barang_masuk bm
              JOIN laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              JOIN laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              JOIN barang b ON bm.id_barang = b.id_barang
              JOIN supplier s ON bm.id_supplier = s.id_supplier
              GROUP BY DATE(bm.tanggal_masuk), bm.periode, s.id_supplier
              ) as subquery";
              
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}

// Function to get daily supplier summary
function getDailySupplierSummary($conn, $date, $periode, $supplier_id) {
    $query = "SELECT 
                DATE(bm.tanggal_masuk) as tanggal,
                COUNT(DISTINCT bm.id_masuk) as total_transaksi,
                SUM(bm.qty_masuk) as total_qty,
                COUNT(DISTINCT bm.id_barang) as total_jenis_barang,
                s.nama_supplier,
                bm.periode,
                COALESCE(p.status, 'Pending') as status
              FROM 
                barang_masuk bm
              JOIN 
                supplier s ON bm.id_supplier = s.id_supplier
              LEFT JOIN 
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN 
                laporan_masuk p ON lmd.id_laporan = p.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              GROUP BY 
                DATE(bm.tanggal_masuk), bm.periode, s.id_supplier, p.status";
                
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [
            'total_transaksi' => 0,
            'total_qty' => 0,
            'total_jenis_barang' => 0,
            'nama_supplier' => 'Unknown',
            'status' => 'Pending'
        ];
    }
    
    $stmt->bind_param("sii", $date, $periode, $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return [
            'total_transaksi' => 0,
            'total_qty' => 0,
            'total_jenis_barang' => 0,
            'nama_supplier' => 'Unknown',
            'status' => 'Pending'
        ];
    }
}

// Function to get all supplier items received on a specific day
function getDailySupplierItems($conn, $date, $periode, $supplier_id) {
    $query = "SELECT 
                b.nama_barang,
                b.satuan,
                SUM(bm.qty_masuk) as total_qty,
                AVG(bm.harga_satuan) as avg_harga,
                CASE WHEN bm.lokasi = '' OR bm.lokasi IS NULL THEN '-' ELSE bm.lokasi END as lokasi,
                s.nama_supplier,
                lm.status
              FROM 
                barang_masuk bm
              JOIN 
                barang b ON bm.id_barang = b.id_barang
              JOIN 
                supplier s ON bm.id_supplier = s.id_supplier
              LEFT JOIN
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN
                laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              GROUP BY 
                b.id_barang, bm.lokasi, lm.status
              ORDER BY 
                b.nama_barang ASC";
                
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("sii", $date, $periode, $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}
?>

<div class="container px-6 mx-auto">
    <h2 class="text-2xl font-semibold text-gray-700 mb-4">
        <i class="fas fa-file-import mr-2"></i> Laporan Barang Masuk
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
                    <span class="text-gray-700">Laporan Barang Masuk</span>
                </li>
            </ol>
        </nav>
        
        <div class="flex space-x-2">
            <?php if ($_SESSION['user_role'] !== 'crew'): ?>
            <a href="laporan_barang_masuk.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow-md transition duration-200 flex items-center">
                <i class="fas fa-plus mr-2"></i> Buat Laporan Baru
            </a>
            <?php endif; ?>
            
            <button id="printAllBtn" onclick="printAllReports()" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg shadow-md transition duration-200 flex items-center">
                <i class="fas fa-print mr-2"></i> Cetak Semua
            </button>
        </div>
    </div>
    
    <!-- New Report Form (Initially Hidden) -->
    <div id="addReportForm" class="bg-white rounded-lg shadow-md overflow-hidden mb-6" style="display: <?= ($edit_mode || !empty($errors)) ? 'block' : 'none' ?>;">
        <?php if ($_SESSION['user_role'] !== 'crew'): ?>
        <div class="bg-gray-50 py-3 px-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700">
                <?= $edit_mode ? 'Edit Laporan Barang Masuk' : 'Form Laporan Barang Masuk Baru' ?>
            </h3>
        </div>
        
        <div class="p-4">
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border-red-500 text-red-700 border-l-4 p-4 mb-4 rounded-md">
                <div class="flex items-center">
                    <div class="py-1">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                    </div>
                    <div>
                        <p class="font-medium">Terdapat beberapa kesalahan:</p>
                        <ul class="mt-1 ml-5 list-disc">
                            <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                <?php if ($edit_mode): ?>
                <input type="hidden" name="id_laporan" value="<?= $edit_id ?>">
                <input type="hidden" name="id_masuk" value="<?= $edit_data['id_masuk'] ?? '' ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tanggal_laporan" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Masuk</label>
                        <input type="date" id="tanggal_laporan" name="tanggal_laporan" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?= $edit_mode ? $edit_data['tanggal_laporan'] : date('Y-m-d') ?>" required>
                    </div>
                    
                    <div>
                        <label for="nama_barang" class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                        <select id="nama_barang" name="nama_barang" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php
                            // Get all barang
                            $barang_query = "SELECT id_barang, nama_barang FROM barang ORDER BY nama_barang";
                            $barang_result = mysqli_query($conn, $barang_query);
                            
                            while ($barang = mysqli_fetch_assoc($barang_result)) {
                                $selected = ($edit_mode && isset($edit_data['id_barang']) && $edit_data['id_barang'] == $barang['id_barang']) ? 'selected' : '';
                                echo "<option value=\"{$barang['nama_barang']}\" $selected>{$barang['nama_barang']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?= $edit_mode ? $edit_data['qty_masuk'] : '' ?>" required>
                    </div>
                    
                    <div>
                        <label for="satuan" class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                        <input type="text" id="satuan" name="satuan" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?= $edit_mode ? $edit_data['satuan'] : '' ?>" required>
                    </div>
                    
                    <div>
                        <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                        <select id="supplier" name="supplier" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Pilih Supplier --</option>
                            <?php
                            // Get all suppliers
                            $supplier_query = "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier";
                            $supplier_result = mysqli_query($conn, $supplier_query);
                            
                            while ($supplier = mysqli_fetch_assoc($supplier_result)) {
                                $selected = ($edit_mode && isset($edit_data['id_supplier']) && $edit_data['id_supplier'] == $supplier['id_supplier']) ? 'selected' : '';
                                echo "<option value=\"{$supplier['nama_supplier']}\" $selected>{$supplier['nama_supplier']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="pending" <?= ($edit_mode && $edit_data['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($edit_mode && $edit_data['status'] == 'approved') ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($edit_mode && $edit_data['status'] == 'rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="periode" class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                        <select id="periode" name="periode" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="1" <?= ($edit_mode && isset($edit_data['periode']) && $edit_data['periode'] == 1) ? 'selected' : '' ?>>Periode 1</option>
                            <option value="2" <?= ($edit_mode && isset($edit_data['periode']) && $edit_data['periode'] == 2) ? 'selected' : '' ?>>Periode 2</option>
                            <option value="3" <?= ($edit_mode && isset($edit_data['periode']) && $edit_data['periode'] == 3) ? 'selected' : '' ?>>Periode 3</option>
                            <option value="4" <?= ($edit_mode && isset($edit_data['periode']) && $edit_data['periode'] == 4) ? 'selected' : '' ?>>Periode 4</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end mt-6 space-x-2">
                    <button type="button" id="cancelBtn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition duration-200">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" name="<?= $edit_mode ? 'update_laporan' : 'buat_laporan' ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update Laporan' : 'Simpan Laporan' ?>
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-gray-50 py-3 px-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700">Akses Terbatas</h3>
        </div>
        <div class="p-4">
            <div class="bg-yellow-100 border-yellow-500 text-yellow-700 border-l-4 p-4 mb-4 rounded-md">
                <div class="flex items-center">
                    <div class="py-1">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                    </div>
                    <div>
                        <p class="font-medium">Anda tidak memiliki akses untuk menambah atau mengubah laporan.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['alert'])): ?>
    <div class="<?= $_SESSION['alert']['type'] == 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700' ?> border-l-4 p-4 mb-4 rounded-md">
        <div class="flex items-center">
            <div class="py-1">
                <i class="<?= $_SESSION['alert']['type'] == 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle' ?> mr-2"></i>
            </div>
            <div>
                <p class="font-medium"><?= $_SESSION['alert']['message'] ?></p>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['alert']); endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-gray-50 py-3 px-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700">
                Daftar Laporan Barang Masuk
            </h3>
        </div>
        
        <div class="p-4">
            <div class="overflow-x-auto">
                <table id="laporanTable" class="datatable w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Tanggal Masuk</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3" width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($all_days as $day): 
                            // Get supplier summary
                            $summary = getDailySupplierSummary($conn, $day['tanggal'], $day['periode'], $day['id_supplier']);
                            
                            // Get status badge color based on status
                            $statusBadgeClass = 'bg-yellow-100 text-yellow-800'; // Default for 'pending'
                            $statusText = $summary['status'] ?? 'pending';
                            
                            if ($statusText == 'diproses') {
                                $statusBadgeClass = 'bg-blue-100 text-blue-800';
                            } else if ($statusText == 'selesai' || $statusText == 'approved') {
                                $statusBadgeClass = 'bg-green-100 text-green-800';
                                $statusText = 'selesai';
                            } else if ($statusText == 'dibatalkan') {
                                $statusBadgeClass = 'bg-red-100 text-red-800';
                            }
                        ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-4 py-3"><?= $no++ ?></td>
                            <td class="px-4 py-3"><?= date('d F Y', strtotime($day['tanggal'])) ?></td>
                            <td class="px-4 py-3"><?= $day['nama_supplier'] ?></td>
                            <td class="px-4 py-3">
                                <span class="<?= $statusBadgeClass ?> text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    <?= ucfirst($statusText) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1">
                                    <button onclick="viewDetail('<?= $day['tanggal'] ?>', <?= $day['periode'] ?>, <?= $day['id_supplier'] ?>)" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-md" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="printReport('<?= $day['tanggal'] ?>', <?= $day['periode'] ?>, <?= $day['id_supplier'] ?>)" class="bg-green-600 hover:bg-green-700 text-white p-2 rounded-md" title="Cetak Laporan">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(count($all_days) == 0): ?>
                        <tr class="bg-white border-b">
                            <td colspan="5" class="px-4 py-3 text-center text-gray-500">
                                Belum ada laporan yang dibuat
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <div class="flex flex-col md:flex-row justify-between items-center mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
                <div class="flex items-center mb-4 md:mb-0">
                    <span class="text-sm text-gray-700">
                        Menampilkan
                        <span class="font-medium"><?= min(($page - 1) * $records_per_page + 1, $total_records) ?></span>
                        sampai
                        <span class="font-medium"><?= min($page * $records_per_page, $total_records) ?></span>
                        dari
                        <span class="font-medium"><?= $total_records ?></span>
                        data
                    </span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Records per page dropdown -->
                    <div class="flex items-center">
                        <label for="records_per_page" class="mr-2 text-sm text-gray-600">Tampilkan:</label>
                        <select id="records_per_page" name="records_per_page" onchange="changeRecordsPerPage(this.value)" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                            <?php foreach ($records_per_page_options as $option): ?>
                            <option value="<?= $option ?>" <?= $records_per_page == $option ? 'selected' : '' ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Pagination buttons -->
                    <div class="flex items-center space-x-2">
                        <a href="?page=1&records_per_page=<?= $records_per_page ?>" 
                           class="<?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        
                        <a href="?page=<?= max($page - 1, 1) ?>&records_per_page=<?= $records_per_page ?>" 
                           class="<?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-left"></i>
                        </a>
                        
                        <?php
                        $start_page = max(1, min($page - 2, $total_pages - 4));
                        $end_page = min($total_pages, max(5, $page + 2));
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?page=<?= $i ?>&records_per_page=<?= $records_per_page ?>" 
                           class="<?= $page == $i ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?> px-3 py-1 border rounded-md text-sm font-medium">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?= min($page + 1, $total_pages) ?>&records_per_page=<?= $records_per_page ?>" 
                           class="<?= $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        
                        <a href="?page=<?= $total_pages ?>&records_per_page=<?= $records_per_page ?>" 
                           class="<?= $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl">
            <div class="bg-gray-50 py-3 px-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700">Detail Laporan Barang Masuk</h3>
                <button onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4" id="detailContent">
                <!-- Content will be loaded here -->
                <div class="flex justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Print iframe (hidden) -->
    <iframe id="printFrame" style="display:none;"></iframe>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide form functionality
    const showAddFormBtn = document.getElementById('showAddForm');
    const addReportForm = document.getElementById('addReportForm');
    const cancelBtn = document.getElementById('cancelBtn');
    
    if (showAddFormBtn) {
    showAddFormBtn.addEventListener('click', function() {
        addReportForm.style.display = 'block';
        // Scroll to form
        addReportForm.scrollIntoView({ behavior: 'smooth' });
    });
    }
    
    if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
            // If in edit mode, redirect to main page
            <?php if ($edit_mode): ?>
            window.location.href = 'laporan_masuk.php';
            <?php else: ?>
            addReportForm.style.display = 'none';
            <?php endif; ?>
        });
    }
    
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteModal = document.getElementById('deleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            confirmDeleteBtn.href = `laporan_masuk.php?delete=${id}`;
            deleteModal.classList.remove('hidden');
        });
    });
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.add('hidden');
        });
    }
    
    // Initialize DataTable if available
    if ($.fn.DataTable && document.getElementById('reportsTable')) {
        $('#reportsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
            },
            "pageLength": 10,
            "ordering": true,
            "responsive": true
        });
    }
});

function printAllReports() {
    window.open('print_all_laporan_masuk.php', '_blank');
}

// View detail modal
function viewDetail(date, periode, supplier_id) {
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Load content via AJAX
    content.innerHTML = '<div class="flex justify-center"><div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div></div>';
    
    fetch('ajax_get_supplier_report.php?date=' + date + '&periode=' + periode + '&supplier_id=' + supplier_id)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = '<div class="text-red-500">Error loading details: ' + error.message + '</div>';
        });
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    modal.classList.add('hidden');
}

// Print functionality
function printReport(date, periode, supplier_id) {
    const printFrame = document.getElementById('printFrame');
    printFrame.src = 'print_supplier_report.php?date=' + date + '&periode=' + periode + '&supplier_id=' + supplier_id;
    
    printFrame.onload = function() {
        printFrame.contentWindow.print();
    };
}

// Close the modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('detailModal');
    if (event.target == modal) {
        closeDetailModal();
    }
}

// Function to change records per page
function changeRecordsPerPage(value) {
    window.location.href = '?page=1&records_per_page=' + value;
}
</script>

<?php require_once 'includes/footer.php'; ?>