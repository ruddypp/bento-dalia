<?php
$pageTitle = "Retur Barang";
require_once 'includes/header.php';
checkLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_retur'])) {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Ambil data retur
            $id_penerimaan = (int)$_POST['id_penerimaan'];
            $tanggal_retur = sanitize($_POST['tanggal_retur']);
            $alasan_retur = sanitize($_POST['alasan_retur']);
            $id_pengguna = $_SESSION['user_id'];
            
            // Insert data retur
            $query = "INSERT INTO retur_barang (id_penerimaan, tanggal_retur, alasan_retur, id_pengguna) 
                     VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "issi", $id_penerimaan, $tanggal_retur, $alasan_retur, $id_pengguna);
            mysqli_stmt_execute($stmt);
            
            // Update status penerimaan menjadi 'diretur'
            $query = "UPDATE penerimaan SET status_penerimaan = 'diretur' WHERE id_penerimaan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
            mysqli_stmt_execute($stmt);
            
            // Ambil detail barang dari penerimaan untuk dikurangi stoknya
            $query = "SELECT dt.id_barang, dt.jumlah_diterima FROM detail_terima dt WHERE dt.id_penerimaan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            // Kurangi stok untuk setiap barang yang diretur
            while ($row = mysqli_fetch_assoc($result)) {
                updateStock($row['id_barang'], $row['jumlah_diterima'], 'out');
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log aktivitas
            logActivity($id_pengguna, "Melakukan retur barang untuk ID Penerimaan: $id_penerimaan dengan alasan: $alasan_retur");
            setAlert("success", "Retur barang berhasil dicatat!");
            
            // Redirect agar tidak terjadi double submit jika user refresh
            header("Location: retur_barang.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
    }
    
    if (isset($_POST['delete_retur'])) {
        $id_retur = (int)$_POST['id_retur'];
        
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Dapatkan ID penerimaan dari retur
            $query = "SELECT id_penerimaan FROM retur_barang WHERE id_retur = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_retur);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $retur = mysqli_fetch_assoc($result);
            $id_penerimaan = $retur['id_penerimaan'];
            
            // Ambil detail barang dari penerimaan untuk kembalikan stoknya
            $query = "SELECT dt.id_barang, dt.jumlah_diterima FROM detail_terima dt WHERE dt.id_penerimaan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            // Kembalikan stok untuk setiap barang yang diretur
            while ($row = mysqli_fetch_assoc($result)) {
                updateStock($row['id_barang'], $row['jumlah_diterima'], 'in');
            }
            
            // Update status penerimaan kembali menjadi 'diterima'
            $query = "UPDATE penerimaan SET status_penerimaan = 'diterima' WHERE id_penerimaan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
            mysqli_stmt_execute($stmt);
            
            // Hapus data retur
            $query = "DELETE FROM retur_barang WHERE id_retur = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_retur);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log aktivitas
            logActivity($_SESSION['user_id'], "Menghapus data retur barang ID: $id_retur");
            setAlert("success", "Data retur barang berhasil dihapus!");
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
        
        // Redirect
        header("Location: retur_barang.php");
        exit();
    }
}

// Get all retur
$query = "SELECT rb.*, p.tanggal_terima, s.nama_supplier, u.nama_pengguna 
          FROM retur_barang rb 
          JOIN penerimaan p ON rb.id_penerimaan = p.id_penerimaan 
          JOIN supplier s ON p.id_supplier = s.id_supplier 
          JOIN pengguna u ON rb.id_pengguna = u.id_pengguna 
          ORDER BY rb.tanggal_retur DESC";
$retur_list = mysqli_query($conn, $query);

// Get all penerimaan yang belum diretur (status = 'diterima')
$query = "SELECT p.*, s.nama_supplier 
          FROM penerimaan p 
          JOIN supplier s ON p.id_supplier = s.id_supplier 
          WHERE p.status_penerimaan = 'diterima' 
          ORDER BY p.tanggal_terima DESC";
$penerimaan_list = mysqli_query($conn, $query);
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-undo text-blue-500 mr-2"></i> Daftar Retur Barang
        </h2>
        
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                onclick="showModal('addReturModal')">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Retur
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg overflow-hidden data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal Retur</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID Penerimaan</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal Terima</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Supplier</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Alasan</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Petugas</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php while ($retur = mysqli_fetch_assoc($retur_list)): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $retur['id_retur'] ?></td>
                    <td class="py-2 px-3 text-sm"><?= date('d/m/Y', strtotime($retur['tanggal_retur'])) ?></td>
                    <td class="py-2 px-3 text-sm"><?= $retur['id_penerimaan'] ?></td>
                    <td class="py-2 px-3 text-sm"><?= date('d/m/Y', strtotime($retur['tanggal_terima'])) ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $retur['nama_supplier'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $retur['alasan_retur'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $retur['nama_pengguna'] ?></td>
                    <td class="py-2 px-3 text-sm">
                        <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                onclick="viewDetail(<?= $retur['id_penerimaan'] ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deleteRetur(<?= $retur['id_retur'] ?>, '<?= date('d/m/Y', strtotime($retur['tanggal_retur'])) ?>', '<?= $retur['nama_supplier'] ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Retur Modal -->
<div id="addReturModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Retur Barang</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addReturModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="returForm" method="POST" action="" class="mt-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_penerimaan">
                        Pilih Penerimaan Barang
                    </label>
                    <select id="id_penerimaan" name="id_penerimaan" required 
                            class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent select-penerimaan">
                        <option value="">Pilih Penerimaan</option>
                        <?php mysqli_data_seek($penerimaan_list, 0); ?>
                        <?php while ($penerimaan = mysqli_fetch_assoc($penerimaan_list)): ?>
                            <option value="<?= $penerimaan['id_penerimaan'] ?>">
                                No.<?= $penerimaan['id_penerimaan'] ?> - <?= date('d/m/Y', strtotime($penerimaan['tanggal_terima'])) ?> - <?= $penerimaan['nama_supplier'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="tanggal_retur">
                            Tanggal Retur
                        </label>
                        <input type="date" id="tanggal_retur" name="tanggal_retur" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="alasan_retur">
                        Alasan Retur
                    </label>
                    <textarea id="alasan_retur" name="alasan_retur" required 
                              class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              rows="3" placeholder="Masukkan alasan retur barang"></textarea>
                </div>
                
                <div id="penerimaan_detail" class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg hidden">
                    <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <span>Detail Penerimaan Barang</span>
                    </h4>
                    <div id="penerimaan_detail_content" class="text-sm">
                        <!-- Content will be loaded dynamically -->
                        <div class="animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('addReturModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_retur" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
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
<div id="deleteReturModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_retur_text"></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_retur" name="id_retur">
                
                <div class="flex justify-center gap-4 mt-4">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('deleteReturModal')">
                        Batal
                    </button>
                    <button type="submit" name="delete_retur" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
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
        $('.select-penerimaan').select2({
            dropdownParent: $('#addReturModal'),
            placeholder: "Pilih Penerimaan Barang",
            width: '100%'
        }).on('change', function() {
            const penerimaanId = $(this).val();
            if (penerimaanId) {
                // Fetch and show penerimaan details
                $('#penerimaan_detail').removeClass('hidden');
                
                fetch('ajax/get_penerimaan_detail.php?id=' + penerimaanId)
                    .then(response => response.text())
                    .then(data => {
                        $('#penerimaan_detail_content').html(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        $('#penerimaan_detail_content').html('<div class="text-center text-red-500">Terjadi kesalahan saat memuat data!</div>');
                    });
            } else {
                $('#penerimaan_detail').addClass('hidden');
            }
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
    
    function deleteRetur(id, tanggal, supplier) {
        document.getElementById('delete_id_retur').value = id;
        document.getElementById('delete_retur_text').textContent = `Anda yakin ingin menghapus retur barang dari supplier "${supplier}" tanggal ${tanggal}?`;
        
        showModal('deleteReturModal');
    }
</script>

<?php
require_once 'includes/footer.php';
?> 