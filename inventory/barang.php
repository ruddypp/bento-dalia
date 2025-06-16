<?php
$pageTitle = "Data Barang";
require_once 'includes/header.php';
checkLogin();

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
    if (isset($_POST['add_item'])) {
        // Tambah barang baru
        $nama = sanitize($_POST['nama_barang']);
        $satuan = sanitize($_POST['satuan']);
        $jenis = sanitize($_POST['jenis']);
        $stok = (int)$_POST['stok'];
        $stok_minimum = (int)$_POST['stok_minimum'];
        $harga = (float)$_POST['harga'];
        
        $query = "INSERT INTO barang (nama_barang, satuan, jenis, stok, stok_minimum, harga) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssiid", $nama, $satuan, $jenis, $stok, $stok_minimum, $harga);
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Menambahkan barang baru: $nama");
            setAlert("success", "Barang berhasil ditambahkan!");
        } else {
            setAlert("error", "Gagal menambahkan barang: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
        header("Location: barang.php");
        exit();
    } 
    elseif (isset($_POST['edit_item'])) {
        // Edit barang
        $id = (int)$_POST['id_barang'];
        $nama = sanitize($_POST['nama_barang']);
        $satuan = sanitize($_POST['satuan']);
        $jenis = sanitize($_POST['jenis']);
        $stok = (int)$_POST['stok'];
        $stok_minimum = (int)$_POST['stok_minimum'];
        $harga = (float)$_POST['harga'];
        
        $query = "UPDATE barang SET nama_barang = ?, satuan = ?, jenis = ?, stok = ?, stok_minimum = ?, harga = ? WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssiidi", $nama, $satuan, $jenis, $stok, $stok_minimum, $harga, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Mengubah data barang: $nama");
            setAlert("success", "Data barang berhasil diperbarui!");
        } else {
            setAlert("error", "Gagal memperbarui data barang: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
        header("Location: barang.php");
        exit();
    }
    elseif (isset($_POST['delete_item'])) {
        // Hapus barang
        $id = (int)$_POST['id_barang'];
        
        // Dapatkan nama barang sebelum dihapus untuk log
        $query = "SELECT nama_barang FROM barang WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $barang = mysqli_fetch_assoc($result);
        
        // Hapus barang
        $query = "DELETE FROM barang WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Menghapus barang: " . $barang['nama_barang']);
            setAlert("success", "Barang berhasil dihapus!");
        } else {
            setAlert("error", "Gagal menghapus barang. Pastikan barang tidak terkait dengan transaksi lain.");
        }
        
        mysqli_stmt_close($stmt);
        header("Location: barang.php");
        exit();
    }
}

// Get all items
$query = "SELECT *, (stok * harga) as total_harga FROM barang ORDER BY nama_barang ASC";
$items = mysqli_query($conn, $query);

// Calculate total value of all inventory
$query = "SELECT SUM(stok * harga) as total_inventory_value FROM barang";
$total_result = mysqli_query($conn, $query);
$total_value = mysqli_fetch_assoc($total_result)['total_inventory_value'] ?? 0;
?>

<!-- Main Content -->
<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Daftar Barang</h2>
        
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Barang
        </button>
    </div>
    
    <div class="mb-4 text-right">
        <h3 class="text-lg font-semibold">Total Nilai Inventaris: Rp <?= number_format($total_value, 0, ',', '.') ?></h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white data-table">
            <thead>
                <tr class="bg-gray-200 text-gray-700">
                    <th class="py-2 px-4 text-left">No</th>
                    <th class="py-2 px-4 text-left">Nama Barang</th>
                    <th class="py-2 px-4 text-left">Satuan</th>
                    <th class="py-2 px-4 text-left">Jenis</th>
                    <th class="py-2 px-4 text-left">Stok</th>
                    <th class="py-2 px-4 text-left">Stok Minimum</th>
                    <th class="py-2 px-4 text-left">Harga Satuan</th>
                    <th class="py-2 px-4 text-left">Total Nilai</th>
                    <th class="py-2 px-4 text-left">Status</th>
                    <th class="py-2 px-4 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while ($item = mysqli_fetch_assoc($items)): 
                    $status = $item['stok'] <= $item['stok_minimum'] ? 'Low' : 'Normal';
                    $statusClass = $status == 'Low' ? 'text-red-600' : 'text-green-600';
                    $statusIcon = $status == 'Low' ? 'fa-exclamation-triangle' : 'fa-check-circle';
                ?>
                <tr class="border-b hover:bg-gray-100">
                    <td class="py-2 px-4"><?= $no++ ?></td>
                    <td class="py-2 px-4"><?= $item['nama_barang'] ?></td>
                    <td class="py-2 px-4"><?= $item['satuan'] ?></td>
                    <td class="py-2 px-4"><?= $item['jenis'] ?></td>
                    <td class="py-2 px-4"><?= $item['stok'] ?></td>
                    <td class="py-2 px-4"><?= $item['stok_minimum'] ?></td>
                    <td class="py-2 px-4">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                    <td class="py-2 px-4">Rp <?= number_format($item['total_harga'], 0, ',', '.') ?></td>
                    <td class="py-2 px-4">
                        <span class="<?= $statusClass ?>">
                            <i class="fas <?= $statusIcon ?> mr-1"></i> <?= $status ?>
                        </span>
                    </td>
                    <td class="py-2 px-4">
                        <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                onclick="editItem(<?= $item['id_barang'] ?>, '<?= $item['nama_barang'] ?>', '<?= $item['satuan'] ?>', '<?= $item['jenis'] ?>', <?= $item['stok'] ?>, <?= $item['stok_minimum'] ?>, <?= $item['harga'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deleteItem(<?= $item['id_barang'] ?>, '<?= $item['nama_barang'] ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Tambah Barang Baru</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addItemModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <div class="mt-2 px-7 py-3">
                    <div class="mb-4">
                        <label for="nama_barang" class="block text-gray-700 text-sm font-bold mb-2 text-left">Nama Barang</label>
                        <input type="text" id="nama_barang" name="nama_barang" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="satuan" class="block text-gray-700 text-sm font-bold mb-2 text-left">Satuan</label>
                        <input type="text" id="satuan" name="satuan" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required placeholder="Kg, Liter, Pack, dll">
                    </div>
                    
                    <div class="mb-4">
                        <label for="jenis" class="block text-gray-700 text-sm font-bold mb-2 text-left">Jenis</label>
                        <input type="text" id="jenis" name="jenis" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="stok" class="block text-gray-700 text-sm font-bold mb-2 text-left">Stok Awal</label>
                        <input type="number" id="stok" name="stok" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" value="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="stok_minimum" class="block text-gray-700 text-sm font-bold mb-2 text-left">Stok Minimum</label>
                        <input type="number" id="stok_minimum" name="stok_minimum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" value="10" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="harga" class="block text-gray-700 text-sm font-bold mb-2 text-left">Harga</label>
                        <input type="number" id="harga" name="harga" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" value="0" required>
                    </div>
                </div>
                
                <div class="items-center px-4 py-3">
                    <button type="submit" name="add_item" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Barang</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('editItemModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="edit_id_barang" name="id_barang">
                
                <div class="mt-2 px-7 py-3">
                    <div class="mb-4">
                        <label for="edit_nama_barang" class="block text-gray-700 text-sm font-bold mb-2 text-left">Nama Barang</label>
                        <input type="text" id="edit_nama_barang" name="nama_barang" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_satuan" class="block text-gray-700 text-sm font-bold mb-2 text-left">Satuan</label>
                        <input type="text" id="edit_satuan" name="satuan" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_jenis" class="block text-gray-700 text-sm font-bold mb-2 text-left">Jenis</label>
                        <input type="text" id="edit_jenis" name="jenis" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_stok" class="block text-gray-700 text-sm font-bold mb-2 text-left">Stok</label>
                        <input type="number" id="edit_stok" name="stok" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_stok_minimum" class="block text-gray-700 text-sm font-bold mb-2 text-left">Stok Minimum</label>
                        <input type="number" id="edit_stok_minimum" name="stok_minimum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_harga" class="block text-gray-700 text-sm font-bold mb-2 text-left">Harga</label>
                        <input type="number" id="edit_harga" name="harga" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" required>
                    </div>
                </div>
                
                <div class="items-center px-4 py-3">
                    <button type="submit" name="edit_item" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_confirmation_text"></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_barang" name="id_barang">
                
                <div class="items-center px-4 py-3">
                    <button type="submit" name="delete_item" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Hapus
                    </button>
                    <button type="button" onclick="closeModal('deleteItemModal')" class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
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
    
    // Edit item function
    function editItem(id, nama, satuan, jenis, stok, stokMinimum, harga) {
        document.getElementById('edit_id_barang').value = id;
        document.getElementById('edit_nama_barang').value = nama;
        document.getElementById('edit_satuan').value = satuan;
        document.getElementById('edit_jenis').value = jenis;
        document.getElementById('edit_stok').value = stok;
        document.getElementById('edit_stok_minimum').value = stokMinimum;
        document.getElementById('edit_harga').value = harga;
        
        showModal('editItemModal');
    }
    
    // Delete item function
    function deleteItem(id, nama) {
        document.getElementById('delete_id_barang').value = id;
        document.getElementById('delete_confirmation_text').innerText = 'Apakah Anda yakin ingin menghapus barang "' + nama + '"?';
        
        showModal('deleteItemModal');
    }
    
    // Show add modal when button is clicked
    document.querySelector('[data-bs-target="#addItemModal"]').addEventListener('click', function() {
        showModal('addItemModal');
    });
</script>

<?php require_once 'includes/footer.php'; ?>