<?php
$pageTitle = "Manajemen Supplier";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php';
checkLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_supplier'])) {
        // Tambah supplier baru
        $nama = sanitize($_POST['nama_supplier']);
        $alamat = sanitize($_POST['alamat']);
        $kontak = sanitize($_POST['kontak']);
        
        // Process bahan baku items
        $bahan_baku_items = isset($_POST['bahan_baku_items']) ? $_POST['bahan_baku_items'] : [];
        $satuan_items = isset($_POST['satuan_items']) ? $_POST['satuan_items'] : [];
        
        // Combine bahan_baku_items into a comma-separated string
        $bahan_baku_array = [];
        $satuan_array = [];
        
        foreach ($bahan_baku_items as $key => $item) {
            if (!empty($item)) {
                $bahan_baku_array[] = sanitize($item);
                $satuan_array[] = isset($satuan_items[$key]) ? sanitize($satuan_items[$key]) : '';
            }
        }
        
        $bahan_baku = implode(', ', $bahan_baku_array);
        $satuan = implode(', ', $satuan_array);
        
        $query = "INSERT INTO supplier (nama_supplier, alamat, kontak, bahan_baku, satuan) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $nama, $alamat, $kontak, $bahan_baku, $satuan);
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Menambahkan supplier baru: $nama");
            setAlert("success", "Supplier berhasil ditambahkan!");
        } else {
            setAlert("error", "Gagal menambahkan supplier: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
        header("Location: supplier.php");
        exit();
    } 
    elseif (isset($_POST['edit_supplier'])) {
        // Edit supplier
        $id = (int)$_POST['id_supplier'];
        $nama = sanitize($_POST['nama_supplier']);
        $alamat = sanitize($_POST['alamat']);
        $kontak = sanitize($_POST['kontak']);
        
        // Process bahan baku items
        $bahan_baku_items = isset($_POST['edit_bahan_baku_items']) ? $_POST['edit_bahan_baku_items'] : [];
        $satuan_items = isset($_POST['edit_satuan_items']) ? $_POST['edit_satuan_items'] : [];
        
        // Combine bahan_baku_items into a comma-separated string
        $bahan_baku_array = [];
        $satuan_array = [];
        
        foreach ($bahan_baku_items as $key => $item) {
            if (!empty($item)) {
                $bahan_baku_array[] = sanitize($item);
                $satuan_array[] = isset($satuan_items[$key]) ? sanitize($satuan_items[$key]) : '';
            }
        }
        
        $bahan_baku = implode(', ', $bahan_baku_array);
        $satuan = implode(', ', $satuan_array);
        
        $query = "UPDATE supplier SET nama_supplier = ?, alamat = ?, kontak = ?, bahan_baku = ?, satuan = ? WHERE id_supplier = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssi", $nama, $alamat, $kontak, $bahan_baku, $satuan, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Mengubah data supplier: $nama");
            setAlert("success", "Data supplier berhasil diperbarui!");
        } else {
            setAlert("error", "Gagal memperbarui data supplier: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
        header("Location: supplier.php");
        exit();
    }
    elseif (isset($_POST['delete_supplier'])) {
        // Hapus supplier
        $id = (int)$_POST['id_supplier'];
        $force_delete = isset($_POST['force_delete']) && $_POST['force_delete'] == 1;
        
        // Dapatkan nama supplier sebelum dihapus untuk log
        $query = "SELECT nama_supplier FROM supplier WHERE id_supplier = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $supplier = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($force_delete && isAdmin()) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Delete counts to track how many records are deleted
                $penerimaan_count = 0;
                $barang_count = 0;
                
                // First handle barang - need to handle all foreign key dependencies
                // Get all barang IDs for this supplier
                $query = "SELECT id_barang FROM barang WHERE id_supplier = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $barang_result = mysqli_stmt_get_result($stmt);
                $barang_ids = [];
                
                while ($row = mysqli_fetch_assoc($barang_result)) {
                    $barang_ids[] = $row['id_barang'];
                }
                mysqli_stmt_close($stmt);
                
                // Delete related records for each barang
                foreach ($barang_ids as $barang_id) {
                    // stok_opname
                    $query = "DELETE FROM stok_opname WHERE id_barang = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $barang_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    // detail_terima
                    $query = "DELETE FROM detail_terima WHERE id_barang = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $barang_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    // barang_keluar
                    $query = "DELETE FROM barang_keluar WHERE id_barang = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $barang_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    // retur_barang
                    $query = "DELETE FROM retur_barang WHERE id_barang = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $barang_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    // barang_masuk
                    $query = "DELETE FROM barang_masuk WHERE id_barang = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $barang_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                
                // Now delete all barang records for this supplier
                $query = "DELETE FROM barang WHERE id_supplier = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $barang_count = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                
                // Delete records from penerimaan
                $query = "DELETE FROM penerimaan WHERE id_supplier = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $penerimaan_count = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                
                // Finally delete the supplier
                $query = "DELETE FROM supplier WHERE id_supplier = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                
                // Commit transaction
                mysqli_commit($conn);
                
                $total_related = $penerimaan_count + $barang_count;
                $message = "Supplier berhasil dihapus";
                if ($total_related > 0) {
                    $message .= " beserta " . $total_related . " data terkait";
                    $details = [];
                    if ($penerimaan_count > 0) $details[] = $penerimaan_count . " penerimaan";
                    if ($barang_count > 0) $details[] = $barang_count . " barang";
                    
                    if (count($details) > 0) {
                        $message .= " (" . implode(", ", $details) . ")";
                    }
                }
                $message .= "!";
                
                logActivity($_SESSION['user_id'], "Menghapus supplier dan data terkait: " . $supplier['nama_supplier']);
                setAlert("success", $message);
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                setAlert("error", "Gagal menghapus supplier: " . $e->getMessage());
            }
        } else {
            // Regular delete (will fail if foreign key constraints exist)
        $query = "DELETE FROM supplier WHERE id_supplier = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
            try {
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Menghapus supplier: " . $supplier['nama_supplier']);
            setAlert("success", "Supplier berhasil dihapus!");
        } else {
                    throw new Exception(mysqli_stmt_error($stmt));
                }
            } catch (mysqli_sql_exception $e) {
                // Check if this is a foreign key constraint error
                if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                    setAlert("error", "Supplier tidak dapat dihapus karena masih digunakan dalam transaksi lain. Gunakan opsi 'Hapus Paksa' sebagai administrator.");
                } else {
                    setAlert("error", "Gagal menghapus supplier: " . $e->getMessage());
                }
            } catch (Exception $e) {
                setAlert("error", "Gagal menghapus supplier: " . $e->getMessage());
            }
        
        mysqli_stmt_close($stmt);
        }
        
        header("Location: supplier.php");
        exit();
    }
}

// Get all suppliers
$query = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
$suppliers = mysqli_query($conn, $query);

// Add isAdmin function if it doesn't exist
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to check related transactions for a supplier
function checkRelatedTransactions($conn, $id_supplier) {
    $result = [];
    
    // Check penerimaan table
    $query = "SELECT COUNT(*) as count FROM penerimaan WHERE id_supplier = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Error preparing query for penerimaan: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_supplier);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Error executing query for penerimaan: " . $error);
    }
    
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $result['penerimaan'] = $data['count'];
    mysqli_stmt_close($stmt);
    
    // Check barang table
    $query = "SELECT COUNT(*) as count FROM barang WHERE id_supplier = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Error preparing query for barang: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_supplier);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Error executing query for barang: " . $error);
    }
    
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $result['barang'] = $data['count'];
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Handle AJAX request for checking related transactions
if (isset($_GET['action']) && $_GET['action'] === 'check_related_supplier' && isset($_GET['id'])) {
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
            throw new Exception("Invalid supplier ID provided");
        }
        
        // First check if the item exists
        $check_query = "SELECT id_supplier FROM supplier WHERE id_supplier = ?";
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
            throw new Exception("Supplier with ID $id not found");
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
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-truck text-blue-500 mr-2"></i> Daftar Supplier
        </h2>
        
        <div class="flex space-x-2">
            <div class="relative">
                <button id="exportDropdown" class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-4 py-2 rounded-md transition-all flex items-center" onclick="toggleDropdown('exportOptions')">
                    <i class="fas fa-file-export mr-2"></i> Export
                    <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="exportOptions" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden z-10">
                    <ul class="py-1">
                        <li><a class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap border border-gray-200" href="export_supplier.php?format=pdf">
                            <i class="far fa-file-pdf text-red-500 mr-2"></i> Export PDF
                        </a></li>
                        <li><a class="rounded-b bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap border border-l border-r border-b border-gray-200" href="export_supplier.php?format=excel">
                            <i class="far fa-file-excel text-green-500 mr-2"></i> Export Excel
                        </a></li>
                    </ul>
                </div>
            </div>
            <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" onclick="showModal('addSupplierModal')">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Supplier
        </button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Nama Supplier</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Alamat</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Kontak</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Bahan Baku</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php 
                $no = 1;
                while ($supplier = mysqli_fetch_assoc($suppliers)): 
                    // Format bahan baku dan satuan untuk tampilan
                    $bahan_array = explode(',', $supplier['bahan_baku']);
                    $satuan_array = explode(',', $supplier['satuan']);
                    $bahan_items = [];
                    
                    for ($i = 0; $i < count($bahan_array); $i++) {
                        $bahan = trim($bahan_array[$i]);
                        $satuan = isset($satuan_array[$i]) ? trim($satuan_array[$i]) : '';
                        if (!empty($bahan)) {
                            $bahan_items[] = $bahan . ' (' . $satuan . ')';
                        }
                    }
                    
                    $bahan_display = implode('<br>', $bahan_items);
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $no++ ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $supplier['nama_supplier'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $supplier['alamat'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $supplier['kontak'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $bahan_display ?></td>
                    <td class="py-2 px-3 text-sm">
                        <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
                        <div class="flex space-x-2">
                            <button class="text-blue-500 hover:text-blue-700 edit-supplier-btn" data-id="<?= $supplier['id_supplier'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                            <button class="text-red-500 hover:text-red-700" onclick="deleteSupplier(<?= $supplier['id_supplier'] ?>, '<?= addslashes($supplier['nama_supplier']) ?>')">
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
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Supplier Baru</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addSupplierModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <div class="mt-4">
                    <div class="mb-4">
                        <label for="nama_supplier" class="block text-gray-700 text-sm font-semibold mb-2">Nama Supplier</label>
                        <input type="text" id="nama_supplier" name="nama_supplier" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="alamat" class="block text-gray-700 text-sm font-semibold mb-2">Alamat</label>
                        <textarea id="alamat" name="alamat" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="kontak" class="block text-gray-700 text-sm font-semibold mb-2">Kontak</label>
                        <input type="text" id="kontak" name="kontak" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="No. Telepon / Email">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Bahan Baku & Satuan</label>
                        <div id="bahan_baku_container">
                            <div class="flex items-center mb-2 bahan-row">
                                <div class="flex-1 mr-2">
                                    <input type="text" name="bahan_baku_items[]" placeholder="Nama bahan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                </div>
                                <div class="w-1/3">
                                    <input type="text" name="satuan_items[]" placeholder="Satuan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                </div>
                                <div class="ml-2">
                                    <button type="button" class="text-red-500 hover:text-red-700" onclick="removeBahanRow(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="text-blue-500 hover:text-blue-700 text-sm mt-1" onclick="addBahanRow()">
                            <i class="fas fa-plus-circle mr-1"></i> Tambah Bahan Baku
                        </button>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2" onclick="closeModal('addSupplierModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_supplier" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div id="editSupplierModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Edit Supplier</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('editSupplierModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="edit_id_supplier" name="id_supplier">
                
                <div class="mt-4">
                    <div class="mb-4">
                        <label for="edit_nama_supplier" class="block text-gray-700 text-sm font-semibold mb-2">Nama Supplier</label>
                        <input type="text" id="edit_nama_supplier" name="nama_supplier" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_alamat" class="block text-gray-700 text-sm font-semibold mb-2">Alamat</label>
                        <textarea id="edit_alamat" name="alamat" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_kontak" class="block text-gray-700 text-sm font-semibold mb-2">Kontak</label>
                        <input type="text" id="edit_kontak" name="kontak" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Bahan Baku & Satuan</label>
                        <div id="edit_bahan_baku_container">
                            <div class="flex items-center mb-2 bahan-row">
                                <div class="flex-1 mr-2">
                                    <input type="text" name="edit_bahan_baku_items[]" placeholder="Nama bahan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                </div>
                                <div class="w-1/3">
                                    <input type="text" name="edit_satuan_items[]" placeholder="Satuan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                </div>
                                <div class="ml-2">
                                    <button type="button" class="text-red-500 hover:text-red-700" onclick="removeEditBahanRow(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="text-blue-500 hover:text-blue-700 text-sm mt-1" onclick="addEditBahanRow()">
                            <i class="fas fa-plus-circle mr-1"></i> Tambah Bahan Baku
                        </button>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2" onclick="closeModal('editSupplierModal')">
                        Batal
                    </button>
                    <button type="submit" name="edit_supplier" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Supplier Modal -->
<div id="deleteSupplierModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_supplier_text"></p>
                <div id="related_info"></div>
                
                <?php if (isAdmin()): ?>
                <div class="mt-4 text-left">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="force_delete_checkbox" class="form-checkbox h-5 w-5 text-red-600 rounded">
                        <span class="ml-2 text-sm font-medium text-red-600">Hapus Paksa (Semua data terkait akan ikut terhapus)</span>
                    </label>
                </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="" id="delete_form">
                <input type="hidden" id="delete_id_supplier" name="id_supplier">
                <input type="hidden" id="force_delete_input" name="force_delete" value="0">
                
                <div class="flex justify-center gap-4 mt-4">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('deleteSupplierModal')">
                        Batal
                    </button>
                    <button id="delete_button" type="submit" name="delete_supplier" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(modalId).classList.add('modal-entering');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
        } else {
            dropdown.classList.add('hidden');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInside = dropdown.contains(event.target) || 
                                  event.target.id === 'exportDropdown' || 
                                  event.target.parentNode.id === 'exportDropdown';
            if (!isClickInside && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        });
    }

    function createBahanRow(bahanValue = '', satuanValue = '', bahanName = 'bahan_baku_items[]', satuanName = 'satuan_items[]', removeFunction = 'removeBahanRow(this)') {
        const row = document.createElement('div');
        row.className = 'flex items-center mb-2 bahan-row';
        
        row.innerHTML = `
            <div class="flex-1 mr-2">
                <input type="text" name="${bahanName}" value="${bahanValue}" placeholder="Nama bahan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>
            <div class="w-1/3">
                <input type="text" name="${satuanName}" value="${satuanValue}" placeholder="Satuan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>
            <div class="ml-2">
                <button type="button" class="text-red-500 hover:text-red-700" onclick="${removeFunction}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        return row;
    }
    
    function editSupplier(id) {
        // Show loading state
        showModal('editSupplierModal');
        const editContainer = document.getElementById('edit_bahan_baku_container');
        editContainer.innerHTML = '<div class="flex justify-center my-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div></div>';
        
        // Fetch supplier data
        fetch(`ajax/get_supplier_data.php?id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Populate form fields
                    document.getElementById('edit_id_supplier').value = data.supplier.id_supplier;
                    document.getElementById('edit_nama_supplier').value = data.supplier.nama_supplier;
                    document.getElementById('edit_alamat').value = data.supplier.alamat;
                    document.getElementById('edit_kontak').value = data.supplier.kontak;
                    
                    // Clear container
                    editContainer.innerHTML = '';
                    
                    // Parse bahan baku and satuan
                    let bahanArray = [];
                    let satuanArray = [];
                    
                    if (data.supplier.bahan_baku) {
                        bahanArray = data.supplier.bahan_baku.split(',').map(item => item.trim());
                    }
                    
                    if (data.supplier.satuan) {
                        satuanArray = data.supplier.satuan.split(',').map(item => item.trim());
                    }
                    
                    // Add rows for each bahan
                    if (bahanArray.length > 0) {
                        for (let i = 0; i < bahanArray.length; i++) {
                            const bahanValue = bahanArray[i] || '';
                            const satuanValue = satuanArray[i] || '';
                            
                            if (bahanValue) {
                                const row = createBahanRow(
                                    bahanValue, 
                                    satuanValue, 
                                    'edit_bahan_baku_items[]', 
                                    'edit_satuan_items[]', 
                                    'removeEditBahanRow(this)'
                                );
                                editContainer.appendChild(row);
                            }
                        }
                    } else {
                        // Add at least one empty row
                        const row = createBahanRow(
                            '', 
                            '', 
                            'edit_bahan_baku_items[]', 
                            'edit_satuan_items[]', 
                            'removeEditBahanRow(this)'
                        );
                        editContainer.appendChild(row);
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to load supplier data'));
                    closeModal('editSupplierModal');
                }
            })
            .catch(error => {
                console.error('Error fetching supplier data:', error);
                alert('Failed to load supplier data. Please try again.');
                closeModal('editSupplierModal');
            });
    }
    
    function deleteSupplier(id, nama) {
        const deleteForm = document.getElementById('delete_form');
        const deleteButton = document.getElementById('delete_button');
        
        // Set the supplier ID in the form
        document.getElementById('delete_id_supplier').value = id;
        document.getElementById('delete_supplier_text').textContent = 'Memverifikasi apakah supplier dapat dihapus...';
        
        // Reset force delete checkbox
        const forceDeleteCheckbox = document.getElementById('force_delete_checkbox');
        if (forceDeleteCheckbox) {
            forceDeleteCheckbox.checked = false;
        }
        document.getElementById('force_delete_input').value = "0";
        
        // Reset button state
        deleteButton.textContent = "Hapus";
        deleteButton.classList.remove('bg-red-700');
        deleteButton.disabled = true;
        
        // Show modal first
        showModal('deleteSupplierModal');
        
        // Add a loading indicator
        const relatedInfoEl = document.getElementById('related_info');
        relatedInfoEl.innerHTML = '<div class="flex justify-center my-2"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div></div>';
        
        // Check if the supplier can be deleted
        fetch(`?action=check_related_supplier&id=${id}`)
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
                        document.getElementById('delete_supplier_text').textContent = `Anda yakin ingin menghapus supplier "${nama}"?`;
                        deleteButton.disabled = false;
                        relatedInfoEl.innerHTML = '';
                    } else {
                        let relatedText = '<div class="text-left mt-2">';
                        relatedText += '<p class="text-red-600 font-semibold">Supplier ini tidak dapat dihapus karena masih digunakan pada:</p>';
                        relatedText += '<ul class="list-disc ml-5 mt-1">';
                        
                        let totalRelated = 0;
                        
                        if (data.related.penerimaan > 0) {
                            relatedText += `<li>${data.related.penerimaan} transaksi penerimaan</li>`;
                            totalRelated += data.related.penerimaan;
                        }
                        
                        if (data.related.barang > 0) {
                            relatedText += `<li>${data.related.barang} data barang</li>`;
                            totalRelated += data.related.barang;
                        }
                        
                        relatedText += '</ul>';
                        
                        // Add different text based on whether the user is admin
                        const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
                        
                        if (isAdmin) {
                            relatedText += `<p class="mt-2">Sebagai administrator, Anda dapat menggunakan opsi "Hapus Paksa" di bawah untuk menghapus supplier ini beserta ${totalRelated} data terkait.</p>`;
                            deleteButton.disabled = false;
                        } else {
                            relatedText += '<p class="mt-2">Anda perlu menghapus semua data terkait terlebih dahulu sebelum dapat menghapus supplier ini.</p>';
                            deleteButton.disabled = true;
                        }
                        
                        relatedText += '</div>';
                        
                        document.getElementById('delete_supplier_text').textContent = `Tidak dapat menghapus supplier "${nama}" secara normal.`;
                        relatedInfoEl.innerHTML = relatedText;
                    }
                } else {
                    console.error('Server error:', data); // Debug: log error details
                    
                    document.getElementById('delete_supplier_text').textContent = 'Terjadi kesalahan saat memeriksa relasi supplier.';
                    
                    let errorDetails = '';
                    if (data.message) {
                        errorDetails = `<p class="text-red-500">Error: ${data.message}</p>`;
                    }
                    
                    relatedInfoEl.innerHTML = errorDetails;
                    
                    // Still allow force delete for admins if error occurs
                    const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
                    if (isAdmin) {
                        relatedInfoEl.innerHTML += '<p class="mt-2 text-left">Sebagai administrator, Anda masih dapat menggunakan opsi "Hapus Paksa" di bawah untuk mencoba menghapus supplier ini.</p>';
                        deleteButton.disabled = false;
                    } else {
                        deleteButton.disabled = true;
                    }
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                
                document.getElementById('delete_supplier_text').textContent = 'Terjadi kesalahan saat memeriksa relasi supplier.';
                relatedInfoEl.innerHTML = `<p class="text-red-500">Error koneksi: ${error.message || 'Silakan coba lagi.'}</p>`;
                
                // Still allow force delete for admins if error occurs
                const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
                if (isAdmin) {
                    relatedInfoEl.innerHTML += '<p class="mt-2 text-left">Sebagai administrator, Anda masih dapat menggunakan opsi "Hapus Paksa" di bawah untuk mencoba menghapus supplier ini.</p>';
                    deleteButton.disabled = false;
                } else {
                    deleteButton.disabled = true;
                }
            });
    }
    
    function addBahanRow() {
        const container = document.getElementById('bahan_baku_container');
        const row = createBahanRow();
        container.appendChild(row);
    }
    
    function removeBahanRow(button) {
        const container = document.getElementById('bahan_baku_container');
        const row = button.closest('.bahan-row');
        
        // Don't remove if it's the last row
        if (container.querySelectorAll('.bahan-row').length > 1) {
            container.removeChild(row);
        }
    }
    
    function addEditBahanRow() {
        const container = document.getElementById('edit_bahan_baku_container');
        const row = createBahanRow('', '', 'edit_bahan_baku_items[]', 'edit_satuan_items[]', 'removeEditBahanRow(this)');
        container.appendChild(row);
    }
    
    function removeEditBahanRow(button) {
        const container = document.getElementById('edit_bahan_baku_container');
        const row = button.closest('.bahan-row');
        
        // Don't remove if it's the last row
        if (container.querySelectorAll('.bahan-row').length > 1) {
            container.removeChild(row);
        }
    }
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Make sure at least one bahan baku row exists in add form
        const addContainer = document.getElementById('bahan_baku_container');
        if (addContainer && addContainer.querySelectorAll('.bahan-row').length === 0) {
            addBahanRow();
        }
        
        // Event listener for force delete checkbox
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
        
        // Check if view-only mode is active
        const viewOnly = <?= isset($VIEW_ONLY) && $VIEW_ONLY === true ? 'true' : 'false' ?>;
        
        // If view-only, disable certain functionality
        if (viewOnly) {
            // For example, disable any add/edit/delete functionality
            console.log("View-only mode active. Edit functionality disabled.");
            // You might want to remove event listeners or hide elements
        }
        
        // Add event listeners to edit buttons
        const editButtons = document.querySelectorAll('.edit-supplier-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const supplierId = this.getAttribute('data-id');
                editSupplier(supplierId);
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; 
// End output buffering and send content to browser
ob_end_flush();
?> 