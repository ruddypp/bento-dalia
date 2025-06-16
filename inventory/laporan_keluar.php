<?php
$pageTitle = "Laporan Barang Keluar";
require_once 'includes/header.php';

// Handle report deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id_laporan = $_GET['delete'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get associated id_keluar first
        $get_keluar_query = "SELECT id_keluar FROM laporan_keluar_detail WHERE id_laporan = ?";
        $get_keluar_stmt = $conn->prepare($get_keluar_query);
        
        if (!$get_keluar_stmt) {
            throw new Exception("Error preparing query: " . $conn->error);
        }
        
        $get_keluar_stmt->bind_param("i", $id_laporan);
        $get_keluar_stmt->execute();
        $keluar_result = $get_keluar_stmt->get_result();
        
        $id_keluar_list = [];
        while ($row = $keluar_result->fetch_assoc()) {
            $id_keluar_list[] = $row['id_keluar'];
        }
        $get_keluar_stmt->close();
        
        // Delete detail records first
        $detail_query = "DELETE FROM laporan_keluar_detail WHERE id_laporan = ?";
        $detail_stmt = $conn->prepare($detail_query);
        
        if (!$detail_stmt) {
            throw new Exception("Error preparing detail query: " . $conn->error);
        }
        
        $detail_stmt->bind_param("i", $id_laporan);
        $detail_stmt->execute();
        $detail_stmt->close();
        
        // Delete main record
        $main_query = "DELETE FROM laporan_keluar WHERE id_laporan_keluar = ?";
        $main_stmt = $conn->prepare($main_query);
        
        if (!$main_stmt) {
            throw new Exception("Error preparing main query: " . $conn->error);
        }
        
        $main_stmt->bind_param("i", $id_laporan);
        $main_stmt->execute();
        $main_stmt->close();
        
        // Optionally, delete associated barang_keluar entries
        if (!empty($id_keluar_list)) {
            foreach ($id_keluar_list as $id_keluar) {
                $keluar_query = "DELETE FROM barang_keluar WHERE id_keluar = ?";
                $keluar_stmt = $conn->prepare($keluar_query);
                
                if (!$keluar_stmt) {
                    continue; // Skip if can't prepare
                }
                
                $keluar_stmt->bind_param("i", $id_keluar);
                $keluar_stmt->execute();
                $keluar_stmt->close();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log activity
        logActivity($_SESSION['user_id'], "Menghapus laporan barang keluar #$id_laporan");
        
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
    header('Location: laporan_keluar.php');
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
    $edit_data = getLaporanKeluarForEdit($conn, $edit_id);
}

// Handle form submission for edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_laporan'])) {
    $id_laporan = $_POST['id_laporan'] ?? '';
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $keperluan = $_POST['keperluan'] ?? '';
    $errors = [];
    
    // Get arrays from form
    $id_keluar = $_POST['id_keluar'] ?? [];
    $tanggal_keluar = $_POST['tanggal_keluar'] ?? [];
    $kode_barang = $_POST['kode_barang'] ?? [];
    $nama_barang = $_POST['nama_barang'] ?? [];
    $jumlah = $_POST['jumlah'] ?? [];
    $satuan = $_POST['satuan'] ?? [];
    
    // Validate required fields
    if (empty($tanggal_laporan)) {
        $errors[] = "Tanggal laporan wajib diisi";
    }
    
    if (empty($keperluan)) {
        $errors[] = "Keperluan wajib diisi";
    }
    
    if (empty($nama_barang)) {
        $errors[] = "Minimal satu barang harus diisi";
    }
    
    // Validate each row of data
    $valid_items = [];
    foreach ($nama_barang as $index => $nama) {
        if (!empty($nama)) {
            $item_jumlah = $jumlah[$index] ?? '';
            $item_satuan = $satuan[$index] ?? '';
            $item_tanggal = $tanggal_keluar[$index] ?? '';
            $item_id_keluar = $id_keluar[$index] ?? null;
            
            if (empty($item_jumlah) || !is_numeric($item_jumlah) || $item_jumlah <= 0) {
                $errors[] = "Jumlah untuk barang '$nama' harus berupa angka positif";
                continue;
            }
            
            if (empty($item_satuan)) {
                $errors[] = "Satuan untuk barang '$nama' wajib diisi";
                continue;
            }
            
            if (empty($item_tanggal)) {
                $errors[] = "Tanggal keluar untuk barang '$nama' wajib diisi";
                continue;
            }
            
            $valid_items[] = [
                'id_keluar' => $item_id_keluar,
                'tanggal_keluar' => $item_tanggal,
                'kode' => $kode_barang[$index] ?? '',
                'nama' => $nama,
                'jumlah' => $item_jumlah,
                'satuan' => $item_satuan
            ];
        }
    }
    
    if (empty($valid_items)) {
        $errors[] = "Minimal satu barang harus diisi dengan lengkap";
    }
    
    // If no errors, update the report
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update the main report
            $query = "UPDATE laporan_keluar SET tanggal_laporan = ? WHERE id_laporan_keluar = ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error in query: " . $conn->error);
            }
            $stmt->bind_param("si", $tanggal_laporan, $id_laporan);
            $stmt->execute();
            
            // Process each valid item
            foreach ($valid_items as $item) {
                // Find a matching barang ID based on the name provided
                $find_barang = "SELECT id_barang FROM barang WHERE nama_barang LIKE ?";
                $find_stmt = $conn->prepare($find_barang);
                if (!$find_stmt) {
                    throw new Exception("Error preparing find barang query: " . $conn->error);
                }
                
                $search_term = "%{$item['nama']}%";
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
                
                if (!empty($item['id_keluar'])) {
                    // Update existing barang_keluar
                    $keluar_query = "UPDATE barang_keluar SET id_barang = ?, qty_keluar = ?, tanggal_keluar = ? WHERE id_keluar = ?";
                    
                    $keluar_stmt = $conn->prepare($keluar_query);
                    if (!$keluar_stmt) {
                        throw new Exception("Error preparing barang_keluar update query: " . $conn->error);
                    }
                    
                    $keluar_stmt->bind_param("idsi", $barang_id, $item['jumlah'], $item['tanggal_keluar'], $item['id_keluar']);
                    $keluar_stmt->execute();
                    $keluar_stmt->close();
                } else {
                    // Create new barang_keluar entry
                    $keluar_query = "INSERT INTO barang_keluar (id_barang, tanggal_keluar, id_pengguna, qty_keluar) VALUES (?, ?, ?, ?)";
                    $keluar_stmt = $conn->prepare($keluar_query);
                    if (!$keluar_stmt) {
                        throw new Exception("Error preparing barang_keluar insert query: " . $conn->error);
                    }
                    
                    $keluar_stmt->bind_param("isid", $barang_id, $item['tanggal_keluar'], $user_id, $item['jumlah']);
                    $keluar_stmt->execute();
                    $id_keluar = $conn->insert_id;
                    $keluar_stmt->close();
                    
                    // Create laporan_keluar_detail entry
                    $detail_query = "INSERT INTO laporan_keluar_detail (id_laporan, id_keluar) VALUES (?, ?)";
                    $detail_stmt = $conn->prepare($detail_query);
                    if (!$detail_stmt) {
                        throw new Exception("Error preparing laporan_keluar_detail insert query: " . $conn->error);
                    }
                    
                    $detail_stmt->bind_param("ii", $id_laporan, $id_keluar);
                    $detail_stmt->execute();
                    $detail_stmt->close();
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Log activity
            logActivity($user_id, "Mengubah laporan barang keluar #$id_laporan");
            
            // Set success message
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Laporan berhasil diupdate'
            ];
            
            // Redirect to refresh page
            header('Location: laporan_keluar.php');
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
    $keperluan = $_POST['keperluan'] ?? '';
    $errors = [];
    
    // Get arrays from form
    $tanggal_keluar = $_POST['tanggal_keluar'] ?? [];
    $kode_barang = $_POST['kode_barang'] ?? [];
    $nama_barang = $_POST['nama_barang'] ?? [];
    $jumlah = $_POST['jumlah'] ?? [];
    $satuan = $_POST['satuan'] ?? [];
    
    // Validate required fields
    if (empty($tanggal_laporan)) {
        $errors[] = "Tanggal laporan wajib diisi";
    }
    
    if (empty($keperluan)) {
        $errors[] = "Keperluan wajib diisi";
    }
    
    if (empty($nama_barang)) {
        $errors[] = "Minimal satu barang harus diisi";
    }
    
    // Validate each row of data
    $valid_items = [];
    foreach ($nama_barang as $index => $nama) {
        if (!empty($nama)) {
            $item_jumlah = $jumlah[$index] ?? '';
            $item_satuan = $satuan[$index] ?? '';
            $item_tanggal = $tanggal_keluar[$index] ?? '';
            
            if (empty($item_jumlah) || !is_numeric($item_jumlah) || $item_jumlah <= 0) {
                $errors[] = "Jumlah untuk barang '$nama' harus berupa angka positif";
                continue;
            }
            
            if (empty($item_satuan)) {
                $errors[] = "Satuan untuk barang '$nama' wajib diisi";
                continue;
            }
            
            if (empty($item_tanggal)) {
                $errors[] = "Tanggal keluar untuk barang '$nama' wajib diisi";
                continue;
            }
            
            $valid_items[] = [
                'tanggal_keluar' => $item_tanggal,
                'kode' => $kode_barang[$index] ?? '',
                'nama' => $nama,
                'jumlah' => $item_jumlah,
                'satuan' => $item_satuan
            ];
        }
    }
    
    if (empty($valid_items)) {
        $errors[] = "Minimal satu barang harus diisi dengan lengkap";
    }
    
    // If no errors, create the report
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Create laporan_keluar entry
            $laporan_query = "INSERT INTO laporan_keluar (tanggal_laporan) VALUES (?)";
            $laporan_stmt = $conn->prepare($laporan_query);
            if (!$laporan_stmt) {
                throw new Exception("Error preparing laporan_keluar insert query: " . $conn->error);
            }
            
            $laporan_stmt->bind_param("s", $tanggal_laporan);
            $laporan_stmt->execute();
            $id_laporan = $conn->insert_id;
            $laporan_stmt->close();
            
            // Process each valid item
            foreach ($valid_items as $item) {
                // Find barang ID based on the name
                $find_barang = "SELECT id_barang FROM barang WHERE nama_barang LIKE ?";
                $find_stmt = $conn->prepare($find_barang);
                if (!$find_stmt) {
                    throw new Exception("Error preparing find barang query: " . $conn->error);
                }
                
                $search_term = "%{$item['nama']}%";
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
                
                // Create barang_keluar entry
                $keluar_query = "INSERT INTO barang_keluar (id_barang, tanggal_keluar, id_pengguna, qty_keluar) VALUES (?, ?, ?, ?)";
                $keluar_stmt = $conn->prepare($keluar_query);
                if (!$keluar_stmt) {
                    throw new Exception("Error preparing barang_keluar insert query: " . $conn->error);
                }
                
                $keluar_stmt->bind_param("isid", $barang_id, $item['tanggal_keluar'], $user_id, $item['jumlah']);
                $keluar_stmt->execute();
                $id_keluar = $conn->insert_id;
                $keluar_stmt->close();
                
                // Create laporan_keluar_detail entry
                $detail_query = "INSERT INTO laporan_keluar_detail (id_laporan, id_keluar) VALUES (?, ?)";
                $detail_stmt = $conn->prepare($detail_query);
                if (!$detail_stmt) {
                    throw new Exception("Error preparing laporan_keluar_detail insert query: " . $conn->error);
                }
                
                $detail_stmt->bind_param("ii", $id_laporan, $id_keluar);
                $detail_stmt->execute();
                $detail_stmt->close();
                
                // Update stock
                $update_stock = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
                $stock_stmt = $conn->prepare($update_stock);
                if (!$stock_stmt) {
                    throw new Exception("Error preparing stock update query: " . $conn->error);
                }
                
                $stock_stmt->bind_param("di", $item['jumlah'], $barang_id);
                $stock_stmt->execute();
                $stock_stmt->close();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Log activity
            logActivity($user_id, "Membuat laporan barang keluar #$id_laporan");
            
            // Set success message
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Laporan berhasil dibuat'
            ];
            
            // Redirect to refresh page
            header('Location: laporan_keluar.php');
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $errors[] = "Gagal membuat laporan: " . $e->getMessage();
        }
    }
}
?>

<!-- Main Content -->
<div class="ml-64 p-4">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-700 flex items-center">
            <i class="fas fa-file-export mr-2"></i> Laporan Barang Keluar
        </h1>
        <div class="flex items-center mt-2 text-sm">
            <a href="dashboard.php" class="text-blue-500 hover:text-blue-700">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-600">Laporan Barang Keluar</span>
        </div>
    </div>
    
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700">Daftar Laporan Barang Keluar</h2>
        <div class="flex space-x-2">
            <a href="laporan_barang_keluar.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow-sm flex items-center">
                <i class="fas fa-plus mr-2"></i> Buat Laporan Baru
            </a>
            <a href="print_all_laporan_keluar.php" target="_blank" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow-sm flex items-center">
                <i class="fas fa-print mr-2"></i> Cetak Semua
            </a>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['alert'])): ?>
        <div class="bg-<?= $_SESSION['alert']['type'] == 'success' ? 'green' : 'red' ?>-100 border-l-4 border-<?= $_SESSION['alert']['type'] == 'success' ? 'green' : 'red' ?>-500 text-<?= $_SESSION['alert']['type'] == 'success' ? 'green' : 'red' ?>-700 p-4 mb-6" role="alert">
            <p><?= $_SESSION['alert']['message'] ?></p>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p class="font-bold">Terdapat kesalahan:</p>
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- New Report Form -->
    <div id="reportForm" class="bg-white p-6 rounded-lg shadow-md mb-6 <?= (!empty($errors) || $edit_mode) ? '' : 'hidden' ?>">
        <h2 class="text-xl font-semibold mb-4"><?= $edit_mode ? 'Edit Laporan' : 'Buat Laporan Baru' ?></h2>
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id_laporan" value="<?= $edit_id ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="tanggal_laporan" class="block text-gray-700 font-medium mb-2">Tanggal Laporan</label>
                    <input type="date" id="tanggal_laporan" name="tanggal_laporan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $edit_mode ? $edit_data['tanggal_laporan'] : date('Y-m-d') ?>" required>
                </div>
            </div>
            
            <div class="mb-4">
                <h3 class="text-lg font-medium mb-2">Detail Barang Keluar</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border" id="detailTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-3 border-b text-left">No</th>
                                <th class="py-2 px-3 border-b text-left">Tanggal Keluar</th>
                                <th class="py-2 px-3 border-b text-left">Kode</th>
                                <th class="py-2 px-3 border-b text-left">Nama Barang</th>
                                <th class="py-2 px-3 border-b text-left">Jumlah</th>
                                <th class="py-2 px-3 border-b text-left">Satuan</th>
                                <th class="py-2 px-3 border-b text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($edit_mode && !empty($edit_data['details'])): ?>
                                <?php foreach ($edit_data['details'] as $index => $detail): ?>
                                    <tr id="row-<?= $index ?>">
                                        <td class="py-2 px-3 border-b"><?= $index + 1 ?></td>
                                        <td class="py-2 px-3 border-b">
                                            <input type="hidden" name="id_keluar[]" value="<?= $detail['id_keluar'] ?>">
                                            <input type="date" name="tanggal_keluar[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" value="<?= $detail['tanggal_keluar'] ?>" required>
                                        </td>
                                        <td class="py-2 px-3 border-b">
                                            <input type="text" name="kode_barang[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Kode" value="<?= $detail['kode_barang'] ?? '' ?>">
                                        </td>
                                        <td class="py-2 px-3 border-b">
                                            <input type="text" name="nama_barang[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Nama Barang" value="<?= $detail['nama_barang'] ?>" required>
                                        </td>
                                        <td class="py-2 px-3 border-b">
                                            <input type="number" name="jumlah[]" min="1" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Jumlah" value="<?= $detail['qty_keluar'] ?>" required>
                                        </td>
                                        <td class="py-2 px-3 border-b">
                                            <input type="text" name="satuan[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Satuan" value="<?= $detail['satuan'] ?>" required>
                                        </td>
                                        <td class="py-2 px-3 border-b text-center">
                                            <button type="button" class="text-red-500 hover:text-red-700 delete-row" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="row-0">
                                    <td class="py-2 px-3 border-b">1</td>
                                    <td class="py-2 px-3 border-b">
                                        <input type="date" name="tanggal_keluar[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" value="<?= date('Y-m-d') ?>" required>
                                    </td>
                                    <td class="py-2 px-3 border-b">
                                        <input type="text" name="kode_barang[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Kode">
                                    </td>
                                    <td class="py-2 px-3 border-b">
                                        <input type="text" name="nama_barang[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Nama Barang" required>
                                    </td>
                                    <td class="py-2 px-3 border-b">
                                        <input type="number" name="jumlah[]" min="1" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Jumlah" required>
                                    </td>
                                    <td class="py-2 px-3 border-b">
                                        <input type="text" name="satuan[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Satuan" required>
                                    </td>
                                    <td class="py-2 px-3 border-b text-center">
                                        <button type="button" class="text-red-500 hover:text-red-700 delete-row" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="addRowBtn" class="mt-2 bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded-lg">
                    <i class="fas fa-plus mr-1"></i> Tambah Baris
                </button>
            </div>
            
            <div class="mb-4">
                <label for="keperluan" class="block text-gray-700 font-medium mb-2">Keperluan</label>
                <textarea id="keperluan" name="keperluan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required><?= $edit_mode ? $edit_data['keperluan'] : '' ?></textarea>
            </div>
            
            <div class="flex justify-end mt-4">
                <button type="button" id="cancelBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg mr-2">
                    Batal
                </button>
                <button type="submit" name="<?= $edit_mode ? 'update_laporan' : 'buat_laporan' ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <?= $edit_mode ? 'Update Laporan' : 'Simpan Laporan' ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Reports Table -->
    <div class="bg-white p-4 rounded-lg shadow-md max-w-full">
        <div class="overflow-x-auto">
            <table id="reportsTable" class="w-full table-auto divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NO</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TANGGAL KELUAR</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NAMA BARANG</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">JUMLAH</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SATUAN</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">AKSI</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    // Get all reports
                    $query = "SELECT lk.*, COUNT(lkd.id_detail_keluar) as item_count 
                              FROM laporan_keluar lk
                              LEFT JOIN laporan_keluar_detail lkd ON lk.id_laporan_keluar = lkd.id_laporan
                              GROUP BY lk.id_laporan_keluar
                              ORDER BY lk.tanggal_laporan DESC";
                    $result = mysqli_query($conn, $query);
                    
                    if (mysqli_num_rows($result) > 0) {
                        $rowNumber = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Get first item details for display
                            $detailQuery = "SELECT b.nama_barang, bk.qty_keluar, b.satuan
                                           FROM laporan_keluar_detail lkd
                                           JOIN barang_keluar bk ON lkd.id_keluar = bk.id_keluar
                                           JOIN barang b ON bk.id_barang = b.id_barang
                                           WHERE lkd.id_laporan = ?
                                           LIMIT 1";
                            $stmt = $conn->prepare($detailQuery);
                            $stmt->bind_param("i", $row['id_laporan_keluar']);
                            $stmt->execute();
                            $detailResult = $stmt->get_result();
                            $firstItem = $detailResult->fetch_assoc();
                            ?>
                            <tr>
                                <td class="px-3 py-3 whitespace-nowrap"><?= $rowNumber++ ?></td>
                                <td class="px-3 py-3 whitespace-nowrap"><?= date('d M Y', strtotime($row['tanggal_laporan'])) ?></td>
                                <td class="px-3 py-3 whitespace-nowrap"><?= $firstItem['nama_barang'] ?? '-' ?></td>
                                <td class="px-3 py-3 whitespace-nowrap"><?= $firstItem['qty_keluar'] ?? '-' ?></td>
                                <td class="px-3 py-3 whitespace-nowrap"><?= $firstItem['satuan'] ?? '-' ?></td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        <?= formatStatus($row['item_count']) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    <div class="flex justify-center space-x-1">
                                        <a href="edit_laporan_keluar.php?id=<?= $row['id_laporan_keluar'] ?>" class="bg-blue-100 text-blue-600 p-1.5 rounded" title="Lihat">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_laporan_keluar.php?id=<?= $row['id_laporan_keluar'] ?>" class="bg-blue-100 text-blue-600 p-1.5 rounded" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="print_laporan_keluar.php?id=<?= $row['id_laporan_keluar'] ?>" target="_blank" class="bg-green-100 text-green-600 p-1.5 rounded" title="Cetak">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="#" class="bg-red-100 text-red-600 p-1.5 rounded delete-btn" data-id="<?= $row['id_laporan_keluar'] ?>" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="7" class="px-3 py-3 text-center text-gray-500">Tidak ada data laporan</td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm w-full">
        <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
        <p class="text-gray-700 mb-4">Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="flex justify-end">
            <button id="cancelDeleteBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg mr-2">
                Batal
            </button>
            <a id="confirmDeleteBtn" href="#" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                Hapus
            </a>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTables
        $('#reportsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
            },
            "order": [[0, "asc"]],
            "searching": false,
            "lengthChange": false,
            "pageLength": 10,
            "info": false,
            "dom": 'tp'
        });
        
        // Show/hide form
        $('#showFormBtn').click(function() {
            $('#reportForm').removeClass('hidden');
            $('html, body').animate({
                scrollTop: $("#reportForm").offset().top - 20
            }, 500);
        });
        
        $('#cancelBtn').click(function() {
            $('#reportForm').addClass('hidden');
            // If in edit mode, redirect to main page
            <?php if ($edit_mode): ?>
            window.location.href = 'laporan_keluar.php';
            <?php endif; ?>
        });
        
        // Delete confirmation
        $('.delete-btn').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            $('#confirmDeleteBtn').attr('href', 'laporan_keluar.php?delete=' + id);
            $('#deleteModal').removeClass('hidden');
        });
        
        $('#cancelDeleteBtn').click(function() {
            $('#deleteModal').addClass('hidden');
        });
        
        // Initialize select2 for better dropdowns
        $('.select2').select2({
            width: '100%'
        });
        
        // Add new row to detail table
        $('#addRowBtn').click(function() {
            const rowCount = $('#detailTable tbody tr').length + 1;
            const rowId = 'row-' + rowCount;
            
            const newRow = `
                <tr id="${rowId}">
                    <td class="py-2 px-3 border-b">${rowCount}</td>
                    <td class="py-2 px-3 border-b">
                        <input type="date" name="tanggal_keluar[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" value="<?= date('Y-m-d') ?>" required>
                    </td>
                    <td class="py-2 px-3 border-b">
                        <input type="text" name="kode_barang[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Kode">
                    </td>
                    <td class="py-2 px-3 border-b">
                        <input type="text" name="nama_barang[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Nama Barang" required>
                    </td>
                    <td class="py-2 px-3 border-b">
                        <input type="number" name="jumlah[]" min="1" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Jumlah" required>
                    </td>
                    <td class="py-2 px-3 border-b">
                        <input type="text" name="satuan[]" class="w-full px-2 py-1 border border-gray-300 rounded-md" placeholder="Satuan" required>
                    </td>
                    <td class="py-2 px-3 border-b text-center">
                        <button type="button" class="text-red-500 hover:text-red-700 delete-row" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#detailTable tbody').append(newRow);
            updateRowNumbers();
        });
        
        // Delete row from detail table
        $(document).on('click', '.delete-row', function() {
            // Don't delete if it's the only row
            if ($('#detailTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateRowNumbers();
            } else {
                alert('Minimal harus ada satu item barang!');
            }
        });
        
        // Update row numbers after deletion
        function updateRowNumbers() {
            $('#detailTable tbody tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
                $(this).attr('id', 'row-' + index);
            });
        }
    });
</script>

<?php
// Helper functions

/**
 * Format the status based on the number of items in the report
 */
function formatStatus($detailCount) {
    return $detailCount > 0 ? 'Lengkap' : 'Belum Lengkap';
}

/**
 * Get report data for editing
 */
function getLaporanKeluarForEdit($conn, $id_laporan) {
    // Get the main report data
    $query = "SELECT lk.* FROM laporan_keluar lk WHERE lk.id_laporan_keluar = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_laporan);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();
    
    if (!$report) {
        return null;
    }
    
    // Get all detail records for this report
    $query = "SELECT lkd.*, bk.qty_keluar, bk.tanggal_keluar, b.nama_barang, b.satuan, b.kode_barang, bk.id_keluar
              FROM laporan_keluar_detail lkd
              JOIN barang_keluar bk ON lkd.id_keluar = bk.id_keluar
              JOIN barang b ON bk.id_barang = b.id_barang
              WHERE lkd.id_laporan = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_laporan);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    $stmt->close();
    
    $report['details'] = $details;
    
    return $report;
}

require_once 'includes/footer.php';
?>
