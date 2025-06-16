<?php
$pageTitle = "Stok Opname";
require_once 'includes/header.php';
checkLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_opname'])) {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Ambil data opname
            $tanggal = sanitize($_POST['tanggal_opname']);
            $id_barang = (int)$_POST['id_barang'];
            $stok_fisik = (int)$_POST['stok_fisik'];
            $id_pengguna = $_SESSION['user_id'];
            
            // Dapatkan stok sistem
            $query = "SELECT stok FROM barang WHERE id_barang = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_barang);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $barang = mysqli_fetch_assoc($result);
            $stok_sistem = $barang['stok'];
            
            // Hitung selisih
            $selisih = $stok_fisik - $stok_sistem;
            
            // Insert data opname
            $query = "INSERT INTO stok_opname (id_barang, tanggal_opname, stok_fisik, stok_sistem, selisih, id_pengguna) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "isiiis", $id_barang, $tanggal, $stok_fisik, $stok_sistem, $selisih, $id_pengguna);
            mysqli_stmt_execute($stmt);
            
            // Update stok barang
            $query = "UPDATE barang SET stok = ? WHERE id_barang = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $stok_fisik, $id_barang);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log aktivitas
            $barang_info = getItems($id_barang);
            logActivity($id_pengguna, "Melakukan stok opname untuk barang: " . $barang_info['nama_barang'] . " (Selisih: $selisih)");
            setAlert("success", "Stok opname berhasil disimpan!");
            
            // Redirect agar tidak terjadi double submit jika user refresh
            header("Location: stok_opname.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
    }
    
    if (isset($_POST['delete_opname'])) {
        $id_opname = (int)$_POST['id_opname'];
        $id_pengguna = $_SESSION['user_id'];
        
        try {
            // Dapatkan data opname
            $query = "SELECT so.*, b.nama_barang FROM stok_opname so 
                      JOIN barang b ON so.id_barang = b.id_barang
                      WHERE so.id_opname = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_opname);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $opname = mysqli_fetch_assoc($result);
            
            // Hapus data opname
            $query = "DELETE FROM stok_opname WHERE id_opname = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_opname);
            mysqli_stmt_execute($stmt);
            
            // Log aktivitas
            logActivity($id_pengguna, "Menghapus data stok opname ID: $id_opname untuk barang: " . $opname['nama_barang']);
            setAlert("success", "Data stok opname berhasil dihapus!");
            
        } catch (Exception $e) {
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
        
        // Redirect
        header("Location: stok_opname.php");
        exit();
    }
}

// Get all stok opname
$query = "SELECT so.*, b.nama_barang, b.satuan, p.nama_pengguna
          FROM stok_opname so
          JOIN barang b ON so.id_barang = b.id_barang
          JOIN pengguna p ON so.id_pengguna = p.id_pengguna
          ORDER BY so.tanggal_opname DESC";
$opname_list = mysqli_query($conn, $query);

// Get all items for select option
$query = "SELECT * FROM barang ORDER BY nama_barang ASC";
$items = mysqli_query($conn, $query);
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-clipboard-check text-blue-500 mr-2"></i> Daftar Stok Opname
        </h2>
        
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                onclick="showModal('addOpnameModal')">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Stok Opname
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg overflow-hidden data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Barang</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Stok Sistem</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Stok Fisik</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Selisih</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Petugas</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php while ($opname = mysqli_fetch_assoc($opname_list)): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $opname['id_opname'] ?></td>
                    <td class="py-2 px-3 text-sm"><?= date('d/m/Y', strtotime($opname['tanggal_opname'])) ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $opname['nama_barang'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $opname['stok_sistem'] ?> <span class="text-xs text-gray-500"><?= $opname['satuan'] ?></span></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $opname['stok_fisik'] ?> <span class="text-xs text-gray-500"><?= $opname['satuan'] ?></span></td>
                    <td class="py-2 px-3 text-sm">
                        <?php if ($opname['selisih'] > 0): ?>
                            <span class="text-green-600 font-medium">+<?= $opname['selisih'] ?> <span class="text-xs"><?= $opname['satuan'] ?></span></span>
                        <?php elseif ($opname['selisih'] < 0): ?>
                            <span class="text-red-600 font-medium"><?= $opname['selisih'] ?> <span class="text-xs"><?= $opname['satuan'] ?></span></span>
                        <?php else: ?>
                            <span class="text-gray-600">0 <span class="text-xs"><?= $opname['satuan'] ?></span></span>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $opname['nama_pengguna'] ?></td>
                    <td class="py-2 px-3 text-sm">
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deleteOpname(<?= $opname['id_opname'] ?>, '<?= $opname['nama_barang'] ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Opname Modal -->
<div id="addOpnameModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Stok Opname</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addOpnameModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="opnameForm" method="POST" action="" class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="tanggal_opname">
                            Tanggal Opname
                        </label>
                        <input type="date" id="tanggal_opname" name="tanggal_opname" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_barang">
                        Barang
                    </label>
                    <select id="id_barang" name="id_barang" required 
                            class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent select-barang">
                        <option value="">Pilih Barang</option>
                        <?php mysqli_data_seek($items, 0); ?>
                        <?php while ($item = mysqli_fetch_assoc($items)): ?>
                            <option value="<?= $item['id_barang'] ?>" data-stok="<?= $item['stok'] ?>" data-satuan="<?= $item['satuan'] ?>">
                                <?= $item['nama_barang'] ?> (Stok Sistem: <?= $item['stok'] ?> <?= $item['satuan'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <div>
                        <div class="text-sm font-semibold text-gray-700 mb-2">Stok Sistem</div>
                        <div class="flex items-baseline">
                            <span id="stok_sistem_display" class="text-lg font-bold text-gray-800">0</span>
                            <span id="satuan_display" class="ml-1 text-sm text-gray-600"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="stok_fisik">
                            Stok Fisik Terhitung
                        </label>
                        <div class="flex">
                            <input type="number" id="stok_fisik" name="stok_fisik" required min="0"
                                   class="shadow-sm border border-gray-300 rounded-l w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Masukkan stok fisik">
                            <span id="satuan_input" class="inline-flex items-center px-3 text-sm rounded-r border border-l-0 border-gray-300 bg-gray-50 text-gray-500"></span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('addOpnameModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_opname" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteOpnameModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_opname_text"></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_opname" name="id_opname">
                
                <div class="flex justify-center gap-4 mt-4">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('deleteOpnameModal')">
                        Batal
                    </button>
                    <button type="submit" name="delete_opname" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Initialize select2
    $(document).ready(function() {
        $('.select-barang').select2({
            dropdownParent: $('#addOpnameModal'),
            placeholder: "Pilih Barang",
            width: '100%'
        }).on('change', function() {
            // Update stok sistem display
            const selectedOption = $(this).find('option:selected');
            const stok = selectedOption.data('stok') || 0;
            const satuan = selectedOption.data('satuan') || '';
            
            $('#stok_sistem_display').text(stok);
            $('#satuan_display').text(satuan);
            $('#satuan_input').text(satuan);
        });
    });
    
    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(modalId).classList.add('modal-entering');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    function deleteOpname(id, namaBarang) {
        document.getElementById('delete_id_opname').value = id;
        document.getElementById('delete_opname_text').textContent = `Anda yakin ingin menghapus data stok opname untuk barang "${namaBarang}"?`;
        
        showModal('deleteOpnameModal');
    }
</script>

<?php
require_once 'includes/footer.php';
?> 