<?php
$pageTitle = "Form Laporan Barang Masuk Baru";
require_once 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_laporan'])) {
    // Get form data
    $tanggal_masuk = $_POST['tanggal_masuk'] ?? '';
    $nama_barang = $_POST['nama_barang'] ?? '';
    $jumlah = $_POST['jumlah'] ?? '';
    $satuan = $_POST['satuan'] ?? '';
    $supplier = $_POST['supplier'] ?? '';
    $status = $_POST['status'] ?? 'Pending';
    
    // Validate data
    $errors = [];
    
    if (empty($tanggal_masuk)) {
        $errors[] = "Tanggal masuk wajib diisi";
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
    
    // If no errors, process the form
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Create laporan_masuk entry
            $laporan_query = "INSERT INTO laporan_masuk (tanggal_laporan, status) VALUES (?, ?)";
            $laporan_stmt = $conn->prepare($laporan_query);
            if (!$laporan_stmt) {
                throw new Exception("Error preparing laporan_masuk insert query: " . $conn->error);
            }
            
            $laporan_stmt->bind_param("ss", $tanggal_masuk, $status);
            $laporan_stmt->execute();
            $id_laporan = $conn->insert_id;
            $laporan_stmt->close();
            
            // Find barang ID based on the name
            $find_barang = "SELECT id_barang FROM barang WHERE nama_barang LIKE ?";
            $find_stmt = $conn->prepare($find_barang);
            if (!$find_stmt) {
                throw new Exception("Error preparing find barang query: " . $conn->error);
            }
            
            $search_term = "%{$nama_barang}%";
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
            
            // Find supplier ID based on the name
            $find_supplier = "SELECT id_supplier FROM supplier WHERE nama_supplier LIKE ?";
            $supplier_stmt = $conn->prepare($find_supplier);
            if (!$supplier_stmt) {
                throw new Exception("Error preparing find supplier query: " . $conn->error);
            }
            
            $search_term = "%{$supplier}%";
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
            
            // Create barang_masuk entry
            $masuk_query = "INSERT INTO barang_masuk (id_barang, tanggal_masuk, id_user, qty_masuk, id_supplier) VALUES (?, ?, ?, ?, ?)";
            $stmt_masuk = mysqli_prepare($conn, $masuk_query);
            
            // Gunakan id_user dari session
            $user_id = $_SESSION['user_id'];
            
            mysqli_stmt_bind_param($stmt_masuk, "isidi", $barang_id, $tanggal_masuk, $user_id, $jumlah, $supplier_id);
            mysqli_stmt_execute($stmt_masuk);
            $id_masuk = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_masuk);
            
            // Create laporan_masuk_detail entry
            $detail_query = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk) VALUES (?, ?)";
            $detail_stmt = $conn->prepare($detail_query);
            if (!$detail_stmt) {
                throw new Exception("Error preparing laporan_masuk_detail insert query: " . $conn->error);
            }
            
            $detail_stmt->bind_param("ii", $id_laporan, $id_masuk);
            $detail_stmt->execute();
            $detail_stmt->close();
            
            // Update stock
            $update_stock = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
            $stock_stmt = $conn->prepare($update_stock);
            if (!$stock_stmt) {
                throw new Exception("Error preparing stock update query: " . $conn->error);
            }
            
            $stock_stmt->bind_param("di", $jumlah, $barang_id);
            $stock_stmt->execute();
            $stock_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Log activity
            if (function_exists('logActivity')) {
                logActivity($user_id, "Membuat laporan barang masuk baru #$id_laporan");
            }
            
            // Set success message
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Laporan berhasil dibuat'
            ];
            
            // Redirect to laporan_masuk.php
            header('Location: laporan_masuk.php');
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $errors[] = "Gagal menyimpan laporan: " . $e->getMessage();
        }
    }
}

// Get list of barang for dropdown
$barang_list = [];
$barang_query = "SELECT id_barang, nama_barang, satuan FROM barang ORDER BY nama_barang";
$barang_result = mysqli_query($conn, $barang_query);
if ($barang_result) {
    while ($row = mysqli_fetch_assoc($barang_result)) {
        $barang_list[] = $row;
    }
}

// Get list of suppliers for dropdown
$supplier_list = [];
$supplier_query = "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier";
$supplier_result = mysqli_query($conn, $supplier_query);
if ($supplier_result) {
    while ($row = mysqli_fetch_assoc($supplier_result)) {
        $supplier_list[] = $row;
    }
}

// Cek struktur tabel barang_masuk
function checkTableStructure($conn) {
    $result = $conn->query("SHOW COLUMNS FROM barang_masuk");
    if (!$result) {
        return false;
    }
    
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Tampilkan struktur tabel untuk debugging
    echo "<!-- Struktur tabel barang_masuk: " . implode(", ", $columns) . " -->";
    
    return in_array('id_user', $columns);
}

// Cek apakah tabel memiliki kolom yang diperlukan
$hasIdPengguna = checkTableStructure($conn);
?>

<div class="ml-17 p-2">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-700 flex items-center">
            <i class="fas fa-file-import mr-2"></i> Form Laporan Barang Masuk
        </h1>
        <div class="flex items-center mt-2 text-sm">
            <a href="dashboard.php" class="text-blue-500 hover:text-blue-700">Dashboard</a>
            <span class="mx-2">></span>
            <a href="laporan_masuk.php" class="text-blue-500 hover:text-blue-700">Laporan Barang Masuk</a>
            <span class="mx-2">></span>
            <span class="text-gray-600">Form Laporan Baru</span>
        </div>
    </div>
    
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
    
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4">Form Laporan Barang Masuk Baru</h2>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="tanggal_masuk" class="block text-gray-700 font-medium mb-2">Tanggal Masuk</label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= isset($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : date('Y-m-d') ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="nama_barang" class="block text-gray-700 font-medium mb-2">Nama Barang</label>
                    <select id="nama_barang" name="nama_barang" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($barang_list as $barang): ?>
                        <option value="<?= htmlspecialchars($barang['nama_barang']) ?>" data-satuan="<?= htmlspecialchars($barang['satuan']) ?>">
                            <?= htmlspecialchars($barang['nama_barang']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="jumlah" class="block text-gray-700 font-medium mb-2">Jumlah</label>
                    <input type="number" id="jumlah" name="jumlah" min="1" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= isset($_POST['jumlah']) ? $_POST['jumlah'] : '' ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="satuan" class="block text-gray-700 font-medium mb-2">Satuan</label>
                    <input type="text" id="satuan" name="satuan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= isset($_POST['satuan']) ? $_POST['satuan'] : '' ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="supplier" class="block text-gray-700 font-medium mb-2">Supplier</label>
                    <select id="supplier" name="supplier" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Pilih Supplier --</option>
                        <?php foreach ($supplier_list as $supplier): ?>
                        <option value="<?= htmlspecialchars($supplier['nama_supplier']) ?>">
                            <?= htmlspecialchars($supplier['nama_supplier']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="status" class="block text-gray-700 font-medium mb-2">Status</label>
                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="Pending" <?= (isset($_POST['status']) && $_POST['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= (isset($_POST['status']) && $_POST['status'] == 'Approved') ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= (isset($_POST['status']) && $_POST['status'] == 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end mt-4">
                <a href="laporan_masuk.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg mr-2">
                    <i class="fas fa-times mr-1"></i> Batal
                </a>
                <button type="submit" name="simpan_laporan" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-save mr-1"></i> Simpan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill satuan when barang is selected
    const namaBarangSelect = document.getElementById('nama_barang');
    const satuanInput = document.getElementById('satuan');
    
    namaBarangSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.satuan) {
            satuanInput.value = selectedOption.dataset.satuan;
        } else {
            satuanInput.value = '';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 