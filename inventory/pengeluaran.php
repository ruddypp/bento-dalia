<?php
$pageTitle = "Pengeluaran Barang";
require_once 'includes/header.php';
checkLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_pengeluaran'])) {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Tambah data pengeluaran
            $tanggal = sanitize($_POST['tanggal_keluar']);
            $keperluan = sanitize($_POST['keperluan']);
            $id_pengguna = $_SESSION['user_id'];
            
            $query = "INSERT INTO pengeluaran (tanggal_keluar, id_pengguna, keperluan) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sis", $tanggal, $id_pengguna, $keperluan);
            mysqli_stmt_execute($stmt);
            
            // Dapatkan ID pengeluaran yang baru dibuat
            $id_pengeluaran = mysqli_insert_id($conn);
            
            // Tambahkan detail pengeluaran
            $id_barang = $_POST['id_barang'];
            $jumlah = $_POST['jumlah'];
            
            // Pastikan semua array memiliki panjang yang sama
            $countItems = count($id_barang);
            $error = false;
            $error_message = '';
            
            for ($i = 0; $i < $countItems; $i++) {
                // Pastikan id_barang dan jumlah valid
                if (!empty($id_barang[$i]) && !empty($jumlah[$i]) && $jumlah[$i] > 0) {
                    $barangId = (int)$id_barang[$i];
                    $qty = (int)$jumlah[$i];
                    
                    // Cek ketersediaan stok
                    if (!checkStockAvailability($barangId, $qty)) {
                        $barang_info = getItems($barangId);
                        $error = true;
                        $error_message = "Stok tidak mencukupi untuk barang: " . $barang_info['nama_barang'] . " (Tersedia: " . $barang_info['stok'] . ")";
                        break;
                    }
                    
                    // Insert ke detail_keluar
                    $query = "INSERT INTO detail_keluar (id_pengeluaran, id_barang, jumlah_keluar) VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "iii", $id_pengeluaran, $barangId, $qty);
                    mysqli_stmt_execute($stmt);
                    
                    // Update stok barang (kurangi)
                    updateStock($barangId, $qty, 'out');
                }
            }
            
            if ($error) {
                // Rollback jika ada error
                mysqli_rollback($conn);
                setAlert("error", $error_message);
            } else {
                // Commit transaction
                mysqli_commit($conn);
                
                // Log aktivitas
                logActivity($id_pengguna, "Menambahkan pengeluaran barang baru untuk keperluan: $keperluan");
                setAlert("success", "Pengeluaran barang berhasil dicatat!");
                
                // Redirect agar tidak terjadi double submit jika user refresh
                header("Location: pengeluaran.php");
                exit();
            }
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
    }
    
    if (isset($_POST['delete_pengeluaran'])) {
        $id_pengeluaran = (int)$_POST['id_pengeluaran'];
        
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Ambil detail pengeluaran untuk update stok
            $query = "SELECT id_barang, jumlah_keluar FROM detail_keluar WHERE id_pengeluaran = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_pengeluaran);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            // Kembalikan stok barang yang sudah dikeluarkan
            while ($row = mysqli_fetch_assoc($result)) {
                updateStock($row['id_barang'], $row['jumlah_keluar'], 'in');
            }
            
            // Hapus detail pengeluaran
            $query = "DELETE FROM detail_keluar WHERE id_pengeluaran = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_pengeluaran);
            mysqli_stmt_execute($stmt);
            
            // Hapus pengeluaran
            $query = "DELETE FROM pengeluaran WHERE id_pengeluaran = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_pengeluaran);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log aktivitas
            logActivity($_SESSION['user_id'], "Menghapus data pengeluaran barang ID: $id_pengeluaran");
            setAlert("success", "Data pengeluaran barang berhasil dihapus!");
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
        
        // Redirect
        header("Location: pengeluaran.php");
        exit();
    }
}

// Get all pengeluaran
$query = "SELECT p.*, u.nama_pengguna 
          FROM pengeluaran p 
          JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
          ORDER BY p.tanggal_keluar DESC";
$pengeluaran_list = mysqli_query($conn, $query);

// Get all items for select option
$query = "SELECT * FROM barang ORDER BY nama_barang ASC";
$items = mysqli_query($conn, $query);
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-shipping-fast text-blue-500 mr-2"></i> Daftar Pengeluaran Barang
        </h2>
        
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                onclick="showModal('addPengeluaranModal')">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Pengeluaran
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg overflow-hidden data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Petugas</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Keperluan</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php while ($pengeluaran = mysqli_fetch_assoc($pengeluaran_list)): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $pengeluaran['id_pengeluaran'] ?></td>
                    <td class="py-2 px-3 text-sm"><?= date('d/m/Y', strtotime($pengeluaran['tanggal_keluar'])) ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $pengeluaran['nama_pengguna'] ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $pengeluaran['keperluan'] ?></td>
                    <td class="py-2 px-3 text-sm">
                        <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                onclick="viewDetail(<?= $pengeluaran['id_pengeluaran'] ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deletePengeluaran(<?= $pengeluaran['id_pengeluaran'] ?>, '<?= date('d/m/Y', strtotime($pengeluaran['tanggal_keluar'])) ?>', '<?= substr($pengeluaran['keperluan'], 0, 30) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Pengeluaran Modal -->
<div id="addPengeluaranModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Pengeluaran Barang</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addPengeluaranModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="pengeluaranForm" method="POST" action="" class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="tanggal_keluar">
                            Tanggal Pengeluaran
                        </label>
                        <input type="date" id="tanggal_keluar" name="tanggal_keluar" 
                               value="<?= date('Y-m-d') ?>" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="keperluan">
                            Keperluan / Tujuan
                        </label>
                        <input type="text" id="keperluan" name="keperluan" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Masukkan keperluan pengeluaran barang">
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
                                <div class="md:col-span-8">
                                    <label class="block text-gray-700 text-sm font-semibold mb-2">Barang</label>
                                    <select name="id_barang[]" required class="barang-select shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Pilih Barang</option>
                                        <?php mysqli_data_seek($items, 0); ?>
                                        <?php while ($item = mysqli_fetch_assoc($items)): ?>
                                        <option value="<?= $item['id_barang'] ?>" data-stok="<?= $item['stok'] ?>" data-satuan="<?= $item['satuan'] ?>">
                                            <?= $item['nama_barang'] ?> (Stok: <?= $item['stok'] ?> <?= $item['satuan'] ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-4">
                                    <label class="block text-gray-700 text-sm font-semibold mb-2">Jumlah</label>
                                    <div class="flex">
                                        <input type="number" name="jumlah[]" min="1" required 
                                               class="shadow-sm border border-gray-300 rounded-l w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <span class="item-satuan inline-flex items-center px-3 text-sm rounded-r border border-l-0 border-gray-300 bg-gray-50 text-gray-500"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="mt-3 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-3 rounded" onclick="addBarangItem()">
                        <i class="fas fa-plus text-xs mr-1"></i> Tambah Barang
                    </button>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('addPengeluaranModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_pengeluaran" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Detail Pengeluaran Modal -->
<div id="detailPengeluaranModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Detail Pengeluaran Barang</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('detailPengeluaranModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mt-4" id="detailPengeluaranContent">
                <!-- Content will be loaded dynamically -->
                <div class="flex justify-center">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('detailPengeluaranModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deletePengeluaranModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_pengeluaran_text"></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_pengeluaran" name="id_pengeluaran">
                
                <div class="flex justify-center gap-4 mt-4">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('deletePengeluaranModal')">
                        Batal
                    </button>
                    <button type="submit" name="delete_pengeluaran" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
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
        initSelect2();
    });
    
    function initSelect2() {
        $('.barang-select').select2({
            dropdownParent: $('#addPengeluaranModal'),
            placeholder: "Pilih Barang",
            width: '100%'
        }).on('change', function() {
            updateSatuan($(this).closest('.barang-item'));
        });
    }
    
    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(modalId).classList.add('modal-entering');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    function viewDetail(id) {
        showModal('detailPengeluaranModal');
        
        // Fetch detail data
        fetch('ajax/get_pengeluaran_detail.php?id=' + id)
            .then(response => response.text())
            .then(data => {
                document.getElementById('detailPengeluaranContent').innerHTML = data;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('detailPengeluaranContent').innerHTML = '<div class="text-center text-red-500">Terjadi kesalahan saat memuat data!</div>';
            });
    }
    
    function deletePengeluaran(id, tanggal, keperluan) {
        document.getElementById('delete_id_pengeluaran').value = id;
        document.getElementById('delete_pengeluaran_text').textContent = `Anda yakin ingin menghapus pengeluaran barang untuk keperluan "${keperluan}" tanggal ${tanggal}?`;
        
        showModal('deletePengeluaranModal');
    }
    
    function updateSatuan(row) {
        const select = row.find('select[name="id_barang[]"]');
        const satuanElem = row.find('.item-satuan');
        const option = select.find('option:selected');
        
        if (option.val()) {
            const satuan = option.data('satuan') || '';
            satuanElem.text(satuan);
        } else {
            satuanElem.text('');
        }
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
        
        // Reinitialize Select2 for the new select
        $(newItem).find('.barang-select').select2({
            dropdownParent: $('#addPengeluaranModal'),
            placeholder: "Pilih Barang",
            width: '100%'
        }).on('change', function() {
            updateSatuan($(this).closest('.barang-item'));
        });
    }

    // Form validation
    document.getElementById('pengeluaranForm').addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessage = '';
        
        // Check for duplicate items
        const items = {};
        document.querySelectorAll('.barang-select').forEach(select => {
            const value = select.value;
            if (value) {
                if (items[value]) {
                    isValid = false;
                    errorMessage = 'Terdapat barang yang duplikat. Silakan periksa kembali!';
                } else {
                    items[value] = true;
                }
            }
        });
        
        // Check stock availability
        if (isValid) {
            document.querySelectorAll('.barang-item').forEach(item => {
                const select = item.querySelector('select[name="id_barang[]"]');
                const input = item.querySelector('input[name="jumlah[]"]');
                
                if (select.value && input.value) {
                    const option = select.options[select.selectedIndex];
                    const stok = parseInt(option.getAttribute('data-stok'));
                    const jumlah = parseInt(input.value);
                    
                    if (jumlah > stok) {
                        isValid = false;
                        const namaBarang = option.textContent.split('(')[0].trim();
                        errorMessage = `Stok tidak mencukupi untuk barang: ${namaBarang} (Tersedia: ${stok})`;
                    }
                }
            });
        }
        
        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
        }
    });
</script>

<?php
require_once 'includes/footer.php';
?> 