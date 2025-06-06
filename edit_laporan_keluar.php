<?php
$pageTitle = "Edit Laporan Barang Keluar";
require_once 'includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'ID laporan tidak valid'
    ];
    header('Location: laporan_keluar.php');
    exit;
}

$id_laporan = $_GET['id'];

// Get report details
$query = "SELECT lk.* FROM laporan_keluar lk WHERE lk.id_laporan_keluar = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_laporan);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();

if (!$report) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Laporan tidak ditemukan'
    ];
    header('Location: laporan_keluar.php');
    exit;
}

// Get report details
$query = "SELECT lkd.*, bk.qty_keluar, bk.tanggal_keluar, b.nama_barang, b.id_barang, b.satuan
          FROM laporan_keluar_detail lkd
          JOIN barang_keluar bk ON lkd.id_keluar = bk.id_keluar
          JOIN barang b ON bk.id_barang = b.id_barang
          WHERE lkd.id_laporan = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_laporan);
$stmt->execute();
$details_result = $stmt->get_result();
$details = [];
while ($row = $details_result->fetch_assoc()) {
    $details[] = $row;
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_laporan'])) {
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $item_ids = $_POST['item_id'] ?? [];
    $barang_ids = $_POST['barang_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $errors = [];
    
    // Validate required fields
    if (empty($tanggal_laporan)) {
        $errors[] = "Tanggal laporan wajib diisi";
    }
    
    // If no errors, update the report
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update the main report
            $update_report = "UPDATE laporan_keluar SET tanggal_laporan = ? WHERE id_laporan_keluar = ?";
            $report_stmt = $conn->prepare($update_report);
            $report_stmt->bind_param("si", $tanggal_laporan, $id_laporan);
            $report_stmt->execute();
            $report_stmt->close();
            
            // Update each item
            for ($i = 0; $i < count($item_ids); $i++) {
                $item_id = $item_ids[$i];
                $barang_id = $barang_ids[$i];
                $quantity = $quantities[$i];
                
                // Get current quantity to adjust stock
                $get_current = "SELECT bk.qty_keluar, bk.id_barang FROM barang_keluar bk WHERE bk.id_keluar = ?";
                $current_stmt = $conn->prepare($get_current);
                $current_stmt->bind_param("i", $item_id);
                $current_stmt->execute();
                $current_result = $current_stmt->get_result();
                $current_data = $current_result->fetch_assoc();
                $current_stmt->close();
                
                if ($current_data) {
                    $old_quantity = $current_data['qty_keluar'];
                    $old_barang_id = $current_data['id_barang'];
                    
                    // Adjust stock - add back old quantity and remove new quantity
                    if ($old_barang_id == $barang_id) {
                        // Same item, just adjust the difference
                        $stock_diff = $old_quantity - $quantity;
                        if ($stock_diff != 0) {
                            $adjust_stock = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
                            $adjust_stmt = $conn->prepare($adjust_stock);
                            $adjust_stmt->bind_param("di", $stock_diff, $barang_id);
                            $adjust_stmt->execute();
                            $adjust_stmt->close();
                        }
                    } else {
                        // Different item, add back old item stock and remove from new item
                        $add_back = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
                        $add_stmt = $conn->prepare($add_back);
                        $add_stmt->bind_param("di", $old_quantity, $old_barang_id);
                        $add_stmt->execute();
                        $add_stmt->close();
                        
                        $remove_new = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
                        $remove_stmt = $conn->prepare($remove_new);
                        $remove_stmt->bind_param("di", $quantity, $barang_id);
                        $remove_stmt->execute();
                        $remove_stmt->close();
                    }
                    
                    // Update barang_keluar
                    $update_item = "UPDATE barang_keluar SET id_barang = ?, qty_keluar = ?, tanggal_keluar = ? WHERE id_keluar = ?";
                    $item_stmt = $conn->prepare($update_item);
                    $item_stmt->bind_param("idsi", $barang_id, $quantity, $tanggal_laporan, $item_id);
                    $item_stmt->execute();
                    $item_stmt->close();
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Log activity
            logActivity($user_id, "Mengubah detail laporan barang keluar #$id_laporan");
            
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

// Handle add item form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $barang_id = $_POST['barang_id'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $errors = [];
    
    // Validate required fields
    if (empty($barang_id)) {
        $errors[] = "Barang wajib dipilih";
    }
    
    if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
        $errors[] = "Jumlah harus berupa angka positif";
    }
    
    // If no errors, add the item
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Create barang_keluar entry
            $keluar_query = "INSERT INTO barang_keluar (id_barang, tanggal_keluar, id_user, qty_keluar) VALUES (?, ?, ?, ?)";
            $keluar_stmt = $conn->prepare($keluar_query);
            $keluar_stmt->bind_param("isid", $barang_id, $report['tanggal_laporan'], $user_id, $quantity);
            $keluar_stmt->execute();
            $id_keluar = $conn->insert_id;
            $keluar_stmt->close();
            
            // Create laporan_keluar_detail entry
            $detail_query = "INSERT INTO laporan_keluar_detail (id_laporan, id_keluar) VALUES (?, ?)";
            $detail_stmt = $conn->prepare($detail_query);
            $detail_stmt->bind_param("ii", $id_laporan, $id_keluar);
            $detail_stmt->execute();
            $detail_stmt->close();
            
            // Update stock
            $update_stock = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
            $stock_stmt = $conn->prepare($update_stock);
            $stock_stmt->bind_param("di", $quantity, $barang_id);
            $stock_stmt->execute();
            $stock_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Log activity
            logActivity($user_id, "Menambahkan item ke laporan barang keluar #$id_laporan");
            
            // Set success message
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Item berhasil ditambahkan'
            ];
            
            // Redirect to refresh page
            header("Location: edit_laporan_keluar.php?id=$id_laporan");
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $errors[] = "Gagal menambahkan item: " . $e->getMessage();
        }
    }
}

// Handle remove item
if (isset($_GET['remove_item']) && !empty($_GET['remove_item'])) {
    $item_id = $_GET['remove_item'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get item details first
        $get_item = "SELECT bk.id_barang, bk.qty_keluar FROM barang_keluar bk WHERE bk.id_keluar = ?";
        $item_stmt = $conn->prepare($get_item);
        $item_stmt->bind_param("i", $item_id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        $item_data = $item_result->fetch_assoc();
        $item_stmt->close();
        
        if ($item_data) {
            $barang_id = $item_data['id_barang'];
            $quantity = $item_data['qty_keluar'];
            
            // Remove detail record
            $detail_query = "DELETE FROM laporan_keluar_detail WHERE id_keluar = ?";
            $detail_stmt = $conn->prepare($detail_query);
            $detail_stmt->bind_param("i", $item_id);
            $detail_stmt->execute();
            $detail_stmt->close();
            
            // Remove barang_keluar record
            $keluar_query = "DELETE FROM barang_keluar WHERE id_keluar = ?";
            $keluar_stmt = $conn->prepare($keluar_query);
            $keluar_stmt->bind_param("i", $item_id);
            $keluar_stmt->execute();
            $keluar_stmt->close();
            
            // Update stock
            $update_stock = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
            $stock_stmt = $conn->prepare($update_stock);
            $stock_stmt->bind_param("di", $quantity, $barang_id);
            $stock_stmt->execute();
            $stock_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log activity
        logActivity($_SESSION['user_id'], "Menghapus item dari laporan barang keluar #$id_laporan");
        
        // Set success message
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Item berhasil dihapus'
        ];
    } catch (Exception $e) {
        // Roll back on error
        $conn->rollback();
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Gagal menghapus item: ' . $e->getMessage()
        ];
    }
    
    // Redirect to refresh page
    header("Location: edit_laporan_keluar.php?id=$id_laporan");
    exit;
}

// Get all items for dropdown
$items_query = "SELECT id_barang, nama_barang, satuan FROM barang ORDER BY nama_barang ASC";
$items_result = mysqli_query($conn, $items_query);
$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = $row;
}
?>

<!-- Main Content -->
<div class="ml-64 p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?= $pageTitle ?></h1>
        <a href="laporan_keluar.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-sm flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
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
    
    <!-- Report Info -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4">Informasi Laporan</h2>
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="id_laporan" class="block text-gray-700 font-medium mb-2">ID Laporan</label>
                    <input type="text" id="id_laporan" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" value="<?= $report['id_laporan_keluar'] ?>" readonly>
                </div>
                
                <div class="mb-4">
                    <label for="tanggal_laporan" class="block text-gray-700 font-medium mb-2">Tanggal Laporan</label>
                    <input type="date" id="tanggal_laporan" name="tanggal_laporan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $report['tanggal_laporan'] ?>" required>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" name="update_laporan" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Update Informasi Laporan
                </button>
            </div>
        </form>
    </div>
    
    <!-- Items Table -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4">Daftar Item</h2>
                
                <div class="overflow-x-auto">
            <table id="itemsTable" class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b text-left">ID</th>
                        <th class="py-2 px-4 border-b text-left">Nama Barang</th>
                        <th class="py-2 px-4 border-b text-left">Jumlah</th>
                        <th class="py-2 px-4 border-b text-left">Satuan</th>
                        <th class="py-2 px-4 border-b text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php if (!empty($details)): ?>
                        <form method="POST" action="">
                            <?php foreach ($details as $index => $item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-4 border-b">
                                        <?= $item['id_keluar'] ?>
                                        <input type="hidden" name="item_id[]" value="<?= $item['id_keluar'] ?>">
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        <select name="barang_id[]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 select2">
                                            <?php foreach ($items as $barang): ?>
                                                <option value="<?= $barang['id_barang'] ?>" <?= $barang['id_barang'] == $item['id_barang'] ? 'selected' : '' ?>>
                                                    <?= $barang['nama_barang'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        <input type="number" name="quantity[]" min="0.01" step="0.01" class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $item['qty_keluar'] ?>" required>
                                    </td>
                                    <td class="py-2 px-4 border-b"><?= $item['satuan'] ?></td>
                                    <td class="py-2 px-4 border-b text-center">
                                        <a href="edit_laporan_keluar.php?id=<?= $id_laporan ?>&remove_item=<?= $item['id_keluar'] ?>" class="text-red-500 hover:text-red-700 delete-btn" data-id="<?= $item['id_keluar'] ?>" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td colspan="5" class="py-4 px-4 text-right">
                                    <button type="submit" name="update_laporan" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                                        Update Semua Item
                                    </button>
                                </td>
                            </tr>
                        </form>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-4 px-4 text-center text-gray-500">Tidak ada data item</td>
                        </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
        </div>
    </div>
    
    <!-- Add Item Form -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">Tambah Item Baru</h2>
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="mb-4">
                    <label for="barang_id" class="block text-gray-700 font-medium mb-2">Nama Barang</label>
                    <select id="barang_id" name="barang_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 select2" required>
                        <option value="">Pilih Barang</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= $item['id_barang'] ?>"><?= $item['nama_barang'] ?> (<?= $item['satuan'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="quantity" class="block text-gray-700 font-medium mb-2">Jumlah</label>
                    <input type="number" id="quantity" name="quantity" min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-4 flex items-end">
                    <button type="submit" name="add_item" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-plus mr-2"></i> Tambah Item
                    </button>
                </div>
                </div>
            </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm w-full">
        <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
        <p class="text-gray-700 mb-4">Apakah Anda yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.</p>
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
        // Initialize select2 for better dropdowns
        $('.select2').select2({
            width: '100%'
        });
        
        // Delete confirmation
        $('.delete-btn').click(function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            $('#confirmDeleteBtn').attr('href', href);
            $('#deleteModal').removeClass('hidden');
        });
        
        $('#cancelDeleteBtn').click(function() {
            $('#deleteModal').addClass('hidden');
        });
});
</script>

<?php require_once 'includes/footer.php'; ?> 