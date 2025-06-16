<?php
$pageTitle = "Manajemen Supplier";
require_once 'includes/header.php';
checkLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_supplier'])) {
        // Tambah supplier baru
        $nama = sanitize($_POST['nama_supplier']);
        $alamat = sanitize($_POST['alamat']);
        $kontak = sanitize($_POST['kontak']);
        
        $query = "INSERT INTO supplier (nama_supplier, alamat, kontak) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $nama, $alamat, $kontak);
        
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
        
        $query = "UPDATE supplier SET nama_supplier = ?, alamat = ?, kontak = ? WHERE id_supplier = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $nama, $alamat, $kontak, $id);
        
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
        
        // Dapatkan nama supplier sebelum dihapus untuk log
        $query = "SELECT nama_supplier FROM supplier WHERE id_supplier = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $supplier = mysqli_fetch_assoc($result);
        
        // Hapus supplier
        $query = "DELETE FROM supplier WHERE id_supplier = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Menghapus supplier: " . $supplier['nama_supplier']);
            setAlert("success", "Supplier berhasil dihapus!");
        } else {
            setAlert("error", "Gagal menghapus supplier. Pastikan supplier tidak terkait dengan transaksi lain.");
        }
        
        mysqli_stmt_close($stmt);
        header("Location: supplier.php");
        exit();
    }
}

// Get all suppliers
$query = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
$suppliers = mysqli_query($conn, $query);
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-truck text-blue-500 mr-2"></i> Daftar Supplier
        </h2>
        
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" onclick="showModal('addSupplierModal')">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Supplier
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Nama Supplier</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Alamat</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Kontak</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php 
                $no = 1;
                while ($supplier = mysqli_fetch_assoc($suppliers)): 
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $no++ ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $supplier['nama_supplier'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $supplier['alamat'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $supplier['kontak'] ?></td>
                    <td class="py-2 px-3 text-sm">
                        <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                onclick="editSupplier(<?= $supplier['id_supplier'] ?>, '<?= addslashes($supplier['nama_supplier']) ?>', '<?= addslashes($supplier['alamat']) ?>', '<?= addslashes($supplier['kontak']) ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deleteSupplier(<?= $supplier['id_supplier'] ?>, '<?= addslashes($supplier['nama_supplier']) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
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
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_supplier" name="id_supplier">
                
                <div class="flex justify-center gap-4 mt-4">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('deleteSupplierModal')">
                        Batal
                    </button>
                    <button type="submit" name="delete_supplier" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
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

    function editSupplier(id, nama, alamat, kontak) {
        document.getElementById('edit_id_supplier').value = id;
        document.getElementById('edit_nama_supplier').value = nama;
        document.getElementById('edit_alamat').value = alamat;
        document.getElementById('edit_kontak').value = kontak;
        
        showModal('editSupplierModal');
    }

    function deleteSupplier(id, nama) {
        document.getElementById('delete_id_supplier').value = id;
        document.getElementById('delete_supplier_text').textContent = `Anda yakin ingin menghapus supplier "${nama}"?`;
        
        showModal('deleteSupplierModal');
    }
</script>

<?php require_once 'includes/footer.php'; 
// End output buffering and send content to browser
ob_end_flush();
?> 