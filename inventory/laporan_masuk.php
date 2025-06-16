<?php
$pageTitle = "Laporan Barang Masuk";
require_once 'includes/header.php';

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
        
        // Optionally, delete associated barang_masuk entries
        if (!empty($id_masuk_list)) {
            foreach ($id_masuk_list as $id_masuk) {
                $masuk_query = "DELETE FROM barang_masuk WHERE id_masuk = ?";
                $masuk_stmt = $conn->prepare($masuk_query);
                
                if (!$masuk_stmt) {
                    continue; // Skip if can't prepare
                }
                
                $masuk_stmt->bind_param("i", $id_masuk);
                $masuk_stmt->execute();
                $masuk_stmt->close();
            }
        }
        
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
    $id_laporan = $_POST['id_laporan'] ?? '';
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $id_masuk = $_POST['id_masuk'] ?? '';
    $nama_barang = $_POST['nama_barang'] ?? '';
    $jumlah = $_POST['jumlah'] ?? '';
    $satuan = $_POST['satuan'] ?? '';
    $supplier = $_POST['supplier'] ?? '';
    $status = $_POST['status'] ?? 'pending'; // Get status from edit form
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
    
    // If no errors, update the report
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update the main report including status
            $query = "UPDATE laporan_masuk SET tanggal_laporan = ?, status = ? WHERE id_laporan_masuk = ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error in query: " . $conn->error);
            }
            $stmt->bind_param("ssi", $tanggal_laporan, $status, $id_laporan);
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
            $masuk_query = "UPDATE barang_masuk SET id_barang = ?, qty_masuk = ?, tanggal_masuk = ?, id_supplier = ? WHERE id_masuk = ?";
            
            $masuk_stmt = $conn->prepare($masuk_query);
            if (!$masuk_stmt) {
                throw new Exception("Error preparing barang_masuk update query: " . $conn->error);
            }
            
            $masuk_stmt->bind_param("idsii", $barang_id, $jumlah, $tanggal_laporan, $supplier_id, $id_masuk);
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
    // Validate form data
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $nama_barang = $_POST['nama_barang'] ?? '';
    $jumlah = $_POST['jumlah'] ?? '';
    $satuan = $_POST['satuan'] ?? '';
    $supplier = $_POST['supplier'] ?? '';
    $status = $_POST['status'] ?? 'pending'; // Get status from form
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
            $query = "INSERT INTO laporan_masuk (tanggal_laporan, created_by, created_at, status) 
                      VALUES (?, ?, NOW(), ?)";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error in query: " . $conn->error);
            }
            $stmt->bind_param("sis", $tanggal_laporan, $user_id, $status);
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
            $masuk_query = "INSERT INTO barang_masuk (id_barang, qty_masuk, tanggal_masuk, id_supplier, id_user) 
                          VALUES (?, ?, ?, ?, ?)";
            
            $masuk_stmt = $conn->prepare($masuk_query);
            if (!$masuk_stmt) {
                throw new Exception("Error preparing barang_masuk query: " . $conn->error);
            }
            
            $masuk_stmt->bind_param("idsii", $barang_id, $jumlah, $tanggal_laporan, $supplier_id, $user_id);
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
$laporan_list = getAllLaporanMasuk($conn);

// Formating functions for reusability
function formatStatus($detailCount) {
    $status = $detailCount > 0 ? 'Lengkap' : 'Belum Lengkap';
    $statusClass = $status == 'Lengkap' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
    return "<span class=\"$statusClass text-xs font-medium px-2.5 py-0.5 rounded-full\">$status</span>";
}

// Function to get laporan data for editing
function getLaporanMasukForEdit($conn, $id_laporan) {
    try {
        // Check if the laporan exists first
        $check_query = "SELECT * FROM laporan_masuk WHERE id_laporan_masuk = ?";
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) {
            error_log("Check prepare failed: " . $conn->error);
            return null;
        }
        
        $check_stmt->bind_param("i", $id_laporan);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            error_log("No laporan found with ID: " . $id_laporan);
            $check_stmt->close();
            return null;
        }
        
        $laporan = $check_result->fetch_assoc();
        $check_stmt->close();
        
        // Now get the detail
        $query = "SELECT lmd.id_detail, lmd.id_laporan, lmd.id_masuk, 
                        bm.qty_masuk, bm.tanggal_masuk, b.nama_barang, b.satuan, 
                        s.nama_supplier, s.id_supplier, b.id_barang 
                FROM laporan_masuk_detail lmd
                JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
                JOIN barang b ON bm.id_barang = b.id_barang
                JOIN supplier s ON bm.id_supplier = s.id_supplier
                WHERE lmd.id_laporan = ?
                LIMIT 1";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Detail prepare failed: " . $conn->error);
            return $laporan; // Return just the main laporan data
        }
        
        $stmt->bind_param("i", $id_laporan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $detail = $result->fetch_assoc();
            $stmt->close();
            // Merge laporan and detail data
            return array_merge($laporan, $detail);
        }
        
        $stmt->close();
        return $laporan;
    } catch (Exception $e) {
        error_log("Error in getLaporanMasukForEdit: " . $e->getMessage());
        return null;
    }
}

// Function to get first detail item for a laporan
function getFirstLaporanMasukDetail($conn, $id_laporan) {
    try {
        // From check_tables.php we see we need to join with barang_masuk to get item details
        $query = "SELECT bm.*, b.nama_barang, b.satuan, s.nama_supplier 
                FROM laporan_masuk_detail lmd
                JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
                JOIN barang b ON bm.id_barang = b.id_barang
                JOIN supplier s ON bm.id_supplier = s.id_supplier
                WHERE lmd.id_laporan = ? 
                ORDER BY lmd.id_detail LIMIT 1";
                
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in getFirstLaporanMasukDetail: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $id_laporan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $detail = $result->fetch_assoc();
            $stmt->close();
            return $detail;
        }
        
        $stmt->close();
        return null;
    } catch (Exception $e) {
        error_log("Error in getFirstLaporanMasukDetail: " . $e->getMessage());
        return null;
    }
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
            <a href="laporan_barang_masuk.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow-md transition duration-200 flex items-center">
                <i class="fas fa-plus mr-2"></i> Buat Laporan Baru
            </a>
            
            <button id="printAllBtn" onclick="printAllReports()" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg shadow-md transition duration-200 flex items-center">
                <i class="fas fa-print mr-2"></i> Cetak Semua
            </button>
        </div>
    </div>
    
    <!-- New Report Form (Initially Hidden) -->
    <div id="addReportForm" class="bg-white rounded-lg shadow-md overflow-hidden mb-6" style="display: <?= ($edit_mode || !empty($errors)) ? 'block' : 'none' ?>;">
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
                            <th class="px-4 py-3">Nama Barang</th>
                            <th class="px-4 py-3">Jumlah</th>
                            <th class="px-4 py-3">Satuan</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3" width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach($laporan_list as $laporan): 
                            // Get status from detail count
                            $detailCount = getLaporanMasukDetailCount($conn, $laporan['id_laporan_masuk']);
                            
                            // Get first item detail for display in table
                            $firstItem = getFirstLaporanMasukDetail($conn, $laporan['id_laporan_masuk']);
                            
                            // Get status badge color based on status
                            $statusBadgeClass = 'bg-yellow-100 text-yellow-800'; // Default for 'pending'
                            $statusText = $laporan['status'] ?? 'pending';
                            
                            if ($statusText == 'diproses') {
                                $statusBadgeClass = 'bg-blue-100 text-blue-800';
                            } else if ($statusText == 'selesai') {
                                $statusBadgeClass = 'bg-green-100 text-green-800';
                            } else if ($statusText == 'dibatalkan') {
                                $statusBadgeClass = 'bg-red-100 text-red-800';
                            }
                        ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-4 py-3"><?= $no++ ?></td>
                            <td class="px-4 py-3"><?= date('d F Y', strtotime($laporan['tanggal_laporan'])) ?></td>
                            <td class="px-4 py-3"><?= $firstItem ? htmlspecialchars($firstItem['nama_barang']) : '-' ?></td>
                            <td class="px-4 py-3"><?= $firstItem ? htmlspecialchars($firstItem['qty_masuk']) : '-' ?></td>
                            <td class="px-4 py-3"><?= $firstItem ? htmlspecialchars($firstItem['satuan']) : '-' ?></td>
                            <td class="px-4 py-3"><?= $firstItem ? htmlspecialchars($firstItem['nama_supplier']) : '-' ?></td>
                            <td class="px-4 py-3">
                                <span class="<?= $statusBadgeClass ?> text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    <?= ucfirst($statusText) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1">
                                    <button onclick="viewDetail(<?= $laporan['id_laporan_masuk'] ?>)" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-md" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="laporan_masuk.php?edit=<?= $laporan['id_laporan_masuk'] ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white p-2 rounded-md" title="Edit Laporan">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="printReport(<?= $laporan['id_laporan_masuk'] ?>)" class="bg-green-600 hover:bg-green-700 text-white p-2 rounded-md" title="Cetak Laporan">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <a href="laporan_masuk.php?delete=<?= $laporan['id_laporan_masuk'] ?>" onclick="return confirm('Yakin ingin menghapus laporan ini?')" class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-md" title="Hapus Laporan">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($laporan_list)): ?>
                        <tr class="bg-white border-b">
                            <td colspan="8" class="px-4 py-3 text-center text-gray-500">
                                Belum ada laporan yang dibuat
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
function viewDetail(id) {
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Load content via AJAX
    content.innerHTML = '<div class="flex justify-center"><div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div></div>';
    
    fetch('ajax_get_laporan_detail.php?id=' + id)
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
function printReport(id) {
    const printFrame = document.getElementById('printFrame');
    printFrame.src = 'print_laporan_masuk.php?id=' + id;
    
    printFrame.onload = function() {
        printFrame.contentWindow.print();
    };
}

// Fungsi untuk mengisi satuan otomatis ketika barang dipilih
document.addEventListener('DOMContentLoaded', function() {
    const barangSelect = document.getElementById('nama_barang');
    const satuanInput = document.getElementById('satuan');
    
    if (barangSelect && satuanInput) {
        barangSelect.addEventListener('change', function() {
            const selectedOption = barangSelect.options[barangSelect.selectedIndex];
            const satuan = selectedOption.getAttribute('data-satuan');
            satuanInput.value = satuan || '';
        });
        
        // Trigger change event on page load if a value is selected (for edit mode)
        if (barangSelect.value) {
            const event = new Event('change');
            barangSelect.dispatchEvent(event);
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>