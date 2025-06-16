<?php
$pageTitle = "Penerimaan Barang";
require_once 'includes/header.php';
checkLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_penerimaan'])) {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Tambah data penerimaan
            $tanggal = sanitize($_POST['tanggal_terima']);
            $id_supplier = (int)$_POST['id_supplier'];
            $id_pengguna = $_SESSION['user_id'];
            
            $query = "INSERT INTO penerimaan (tanggal_terima, id_supplier, id_pengguna, status_penerimaan) VALUES (?, ?, ?, 'diterima')";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sii", $tanggal, $id_supplier, $id_pengguna);
            mysqli_stmt_execute($stmt);
            
            // Dapatkan ID penerimaan yang baru dibuat
            $id_penerimaan = mysqli_insert_id($conn);
            
            // Tambahkan detail penerimaan
            $id_barang = $_POST['id_barang'];
            $jumlah = $_POST['jumlah'];
            $kualitas = $_POST['kualitas'];
            $expired = $_POST['expired'];
            
            // Pastikan semua array memiliki panjang yang sama
            $countItems = count($id_barang);
            
            for ($i = 0; $i < $countItems; $i++) {
                // Pastikan id_barang dan jumlah valid
                if (!empty($id_barang[$i]) && !empty($jumlah[$i]) && $jumlah[$i] > 0) {
                    $barangId = (int)$id_barang[$i];
                    $qty = (int)$jumlah[$i];
                    $quality = sanitize($kualitas[$i]);
                    $exp = !empty($expired[$i]) ? sanitize($expired[$i]) : NULL;
                    
                    // Insert ke detail_terima
                    $query = "INSERT INTO detail_terima (id_penerimaan, id_barang, jumlah_diterima, kualitas, tanggal_expired) VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "iiiss", $id_penerimaan, $barangId, $qty, $quality, $exp);
                    mysqli_stmt_execute($stmt);
                    
                    // Update stok barang
                    updateStock($barangId, $qty, 'in');
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log aktivitas
            logActivity($id_pengguna, "Menambahkan penerimaan barang baru dari supplier ID: $id_supplier");
            setAlert("success", "Penerimaan barang berhasil dicatat!");
            
            // Redirect agar tidak terjadi double submit jika user refresh
            header("Location: penerimaan.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
    }
    
    if (isset($_POST['delete_penerimaan'])) {
        $id_penerimaan = (int)$_POST['id_penerimaan'];
        
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Ambil detail terima untuk update stok
            $query = "SELECT id_barang, jumlah_diterima FROM detail_terima WHERE id_penerimaan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            // Kurangi stok barang yang sudah diterima
            while ($row = mysqli_fetch_assoc($result)) {
                updateStock($row['id_barang'], $row['jumlah_diterima'], 'out');
            }
            
            // Hapus detail terima
            $query = "DELETE FROM detail_terima WHERE id_penerimaan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
            mysqli_stmt_execute($stmt);
            
            // Hapus penerimaan
            $query = "DELETE FROM penerimaan WHERE id_penerimaan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log aktivitas
            logActivity($_SESSION['user_id'], "Menghapus data penerimaan barang ID: $id_penerimaan");
            setAlert("success", "Data penerimaan barang berhasil dihapus!");
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
        
        // Redirect
        header("Location: penerimaan.php");
        exit();
    }
}

// Get all penerimaan
$query = "SELECT p.*, s.nama_supplier, u.nama_pengguna 
          FROM penerimaan p 
          JOIN supplier s ON p.id_supplier = s.id_supplier 
          JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
          ORDER BY p.tanggal_terima DESC";
$penerimaan_list = mysqli_query($conn, $query);

// Get all suppliers for select option
$query = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
$suppliers = mysqli_query($conn, $query);

// Get all items for select option
$query = "SELECT * FROM barang ORDER BY nama_barang ASC";
$items = mysqli_query($conn, $query);
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-dolly text-blue-500 mr-2"></i> Daftar Penerimaan Barang
        </h2>
        
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                onclick="showModal('addPenerimaanModal')">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Penerimaan
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg overflow-hidden data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Supplier</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Petugas</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php while ($penerimaan = mysqli_fetch_assoc($penerimaan_list)): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $penerimaan['id_penerimaan'] ?></td>
                    <td class="py-2 px-3 text-sm"><?= date('d/m/Y', strtotime($penerimaan['tanggal_terima'])) ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $penerimaan['nama_supplier'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $penerimaan['nama_pengguna'] ?></td>
                    <td class="py-2 px-3 text-sm">
                        <?php if ($penerimaan['status_penerimaan'] == 'diterima'): ?>
                            <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs">Diterima</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-xs">Diretur</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 px-3 text-sm">
                        <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                onclick="viewDetail(<?= $penerimaan['id_penerimaan'] ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deletePenerimaan(<?= $penerimaan['id_penerimaan'] ?>, '<?= date('d/m/Y', strtotime($penerimaan['tanggal_terima'])) ?>', '<?= $penerimaan['nama_supplier'] ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Penerimaan Modal -->
<div id="addPenerimaanModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Penerimaan Barang</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addPenerimaanModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="penerimaanForm" method="POST" action="" class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="tanggal_terima">
                            Tanggal Penerimaan
                        </label>
                        <input type="date" id="tanggal_terima" name="tanggal_terima" 
                               value="<?= date('Y-m-d') ?>" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_supplier">
                            Supplier
                        </label>
                        <select id="id_supplier" name="id_supplier" required 
                                class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Supplier</option>
                            <?php mysqli_data_seek($suppliers, 0); ?>
                            <?php while ($supplier = mysqli_fetch_assoc($suppliers)): ?>
                            <option value="<?= $supplier['id_supplier'] ?>"><?= $supplier['nama_supplier'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <h4 class="font-medium text-gray-700 mb-3 mt-6 flex items-center">
                    <i class="fas fa-list-ul text-blue-500 mr-2"></i>
                    <span>Detail Barang</span>
                </h4>
                
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <div id="barang_container">
                        <div class="barang-item mb-4 p-3 bg-white rounded shadow-sm">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-5">
                                    <label class="block text-gray-700 text-sm font-semibold mb-2">Barang</label>
                                    <select name="id_barang[]" required class="barang-select shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Pilih Barang</option>
                                        <?php mysqli_data_seek($items, 0); ?>
                                        <?php while ($item = mysqli_fetch_assoc($items)): ?>
                                        <option value="<?= $item['id_barang'] ?>"><?= $item['nama_barang'] ?> (<?= $item['satuan'] ?>)</option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 text-sm font-semibold mb-2">Jumlah</label>
                                    <input type="number" name="jumlah[]" min="1" required class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                
                                <div class="md:col-span-3">
                                    <label class="block text-gray-700 text-sm font-semibold mb-2">Kualitas</label>
                                    <select name="kualitas[]" required class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="baik">Baik</option>
                                        <option value="cukup">Cukup</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 text-sm font-semibold mb-2">Kadaluarsa</label>
                                    <input type="date" name="expired[]" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="mt-3 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-3 rounded" onclick="addBarangItem()">
                        <i class="fas fa-plus text-xs mr-1"></i> Tambah Barang
                    </button>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('addPenerimaanModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_penerimaan" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Detail Penerimaan Modal -->
<div id="detailPenerimaanModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Detail Penerimaan Barang</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('detailPenerimaanModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mt-4" id="detailPenerimaanContent">
                <!-- Content will be loaded dynamically -->
                <div class="flex justify-center">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('detailPenerimaanModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deletePenerimaanModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_penerimaan_text"></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_penerimaan" name="id_penerimaan">
                
                <div class="flex justify-center gap-4 mt-4">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('deletePenerimaanModal')">
                        Batal
                    </button>
                    <button type="submit" name="delete_penerimaan" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
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
        $('.barang-select').select2({
            dropdownParent: $('#addPenerimaanModal'),
            placeholder: "Pilih Barang",
            width: '100%'
        });
        
        $('#id_supplier').select2({
            dropdownParent: $('#addPenerimaanModal'),
            placeholder: "Pilih Supplier",
            width: '100%'
        });
    });
    
    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(modalId).classList.add('modal-entering');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    function viewDetail(id) {
        showModal('detailPenerimaanModal');
        
        // Fetch detail data
        fetch('ajax/get_penerimaan_detail.php?id=' + id)
            .then(response => response.text())
            .then(data => {
                document.getElementById('detailPenerimaanContent').innerHTML = data;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('detailPenerimaanContent').innerHTML = '<div class="text-center text-red-500">Terjadi kesalahan saat memuat data!</div>';
            });
    }
    
    function deletePenerimaan(id, tanggal, supplier) {
        document.getElementById('delete_id_penerimaan').value = id;
        document.getElementById('delete_penerimaan_text').textContent = `Anda yakin ingin menghapus penerimaan barang dari "${supplier}" tanggal ${tanggal}?`;
        
        showModal('deletePenerimaanModal');
    }
    
    function addBarangItem() {
        // Get container
        const container = document.getElementById('barang_container');
        
        // Get first item to clone
        const firstItem = container.querySelector('.barang-item');
        const newItem = firstItem.cloneNode(true);
        
        // Clear input values
        newItem.querySelectorAll('input').forEach(input => {
            input.value = '';
        });
        
        // Reset select options
        newItem.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });
        
        // Add remove button if it doesn't exist
        if (!newItem.querySelector('.remove-btn')) {
            const btnCol = document.createElement('div');
            btnCol.className = 'flex justify-end mt-2';
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-btn text-xs text-red-500 hover:text-red-700';
            removeBtn.innerHTML = '<i class="fas fa-trash"></i> Hapus Barang';
            removeBtn.onclick = function() {
                container.removeChild(this.closest('.barang-item'));
            };
            
            btnCol.appendChild(removeBtn);
            newItem.appendChild(btnCol);
        }
        
        // Append new item
        container.appendChild(newItem);
        
        // Initialize Select2 for the new select
        $(newItem).find('.barang-select').select2({
            dropdownParent: $('#addPenerimaanModal'),
            placeholder: "Pilih Barang",
            width: '100%'
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?> 