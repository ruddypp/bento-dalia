<?php
$pageTitle = "Barang Lost";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php';

// Force full permission for headproduksi
if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'headproduksi' || $_SESSION['user_role'] === 'purchasing')) {
    $permission = 'full';
    $VIEW_ONLY = false;
}

// Buat direktori uploads/lost jika belum ada
$upload_dir = 'uploads/lost';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Periksa apakah tabel lost_barang sudah ada
$table_exists = false;
$check_table_query = "SHOW TABLES LIKE 'lost_barang'";
$check_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_result) > 0) {
    $table_exists = true;
}

// Jika tabel tidak ada, tampilkan pesan untuk menginstal
if (!$table_exists) {
    setAlert("warning", "Tabel lost_barang belum ada. Silahkan jalankan install_lost_barang.php terlebih dahulu.");
    // Tampilkan konten alternatif
    ?>
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Daftar Barang Lost
            </h2>
        </div>
        
        <div class="p-5 text-center">
            <p class="text-gray-600 mb-4">Modul Barang Lost belum terinstal. Silahkan jalankan installer terlebih dahulu.</p>
            <a href="install_lost_barang.php" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all">
                <i class="fas fa-download mr-2"></i> Install Modul Barang Lost
            </a>
        </div>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tambah barang lost
    if (isset($_POST['add_lost'])) {
        $id_barang = (int)$_POST['id_barang'];
        $jumlah = (float)$_POST['jumlah'];
        $alasan = sanitize($_POST['alasan']);
        $foto_bukti = '';
        $id_user = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        
        // echo "Debug - User ID from session: " . var_export($id_user, true) . "<br>";
        
        // Verifikasi user
        if ($id_user) {
            $query = "SELECT * FROM users WHERE id_user = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_user);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 0) {
                error_log("User ID $id_user tidak ditemukan di database");
                // Fallback ke admin user jika user tidak ditemukan
                $admin_query = "SELECT id_user FROM users WHERE username = 'admin' LIMIT 1";
                $admin_result = mysqli_query($conn, $admin_query);
                if ($admin_row = mysqli_fetch_assoc($admin_result)) {
                    $id_user = $admin_row['id_user'];
                    error_log("Menggunakan admin user ID: $id_user sebagai fallback");
                } else {
                    $id_user = null;
                }
            }
        }
        
        // Upload foto bukti jika ada
        if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $filename = $_FILES['foto_bukti']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = time() . '_' . $filename;
                $upload_path = 'uploads/lost/' . $new_filename;
                
                if (move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $upload_path)) {
                    $foto_bukti = $new_filename;
                }
            }
        }
        
        // Dapatkan data barang
        $query = "SELECT * FROM barang WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_barang);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $barang = mysqli_fetch_assoc($result);
        
        if (!$barang) {
            setAlert("error", "Barang tidak ditemukan!");
            header("Location: barang_lost.php");
            exit();
        }
        
        // Cek stok mencukupi
        if ($barang['stok'] < $jumlah) {
            setAlert("error", "Stok tidak mencukupi! Stok saat ini: {$barang['stok']} {$barang['satuan']}");
            header("Location: barang_lost.php");
            exit();
        }
        
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert data lost
            error_log("User ID yang akan digunakan untuk insert: " . var_export($id_user, true));
            
            $query = "INSERT INTO lost_barang (id_barang, jumlah, alasan, foto_bukti, dibuat_oleh) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "idssi", $id_barang, $jumlah, $alasan, $foto_bukti, $id_user);
            mysqli_stmt_execute($stmt);
            
            // Update stok barang
            $new_stock = $barang['stok'] - $jumlah;
            $query = "UPDATE barang SET stok = ? WHERE id_barang = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "di", $new_stock, $id_barang);
            mysqli_stmt_execute($stmt);
            
            // Catat di stok opname sebagai kerugian
            $selisih = -$jumlah; // Selisih negatif karena kerugian
            $query = "INSERT INTO stok_opname (id_barang, tanggal_opname, stok_fisik, stok_sistem, selisih, jenis, id_user)
                      VALUES (?, CURRENT_DATE(), ?, ?, ?, 'kerugian', ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "idddi", $id_barang, $new_stock, $barang['stok'], $selisih, $id_user);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log aktivitas
            logActivity($id_user, "Menambahkan barang lost: " . $barang['nama_barang'] . " (Jumlah: $jumlah, Alasan: $alasan)");
            setAlert("success", "Data barang lost berhasil disimpan!");
            
            header("Location: barang_lost.php");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
    }
    
    // Hapus barang lost
    if (isset($_POST['delete_lost'])) {
        $id_lost = (int)$_POST['id_lost'];
        $id_user = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        
        try {
            // Get lost item details
            $query = "SELECT l.*, b.nama_barang, b.stok FROM lost_barang l
                      JOIN barang b ON l.id_barang = b.id_barang
                      WHERE l.id_lost = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_lost);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $lost_item = mysqli_fetch_assoc($result);
            
            if (!$lost_item) {
                setAlert("error", "Data barang lost tidak ditemukan!");
                header("Location: barang_lost.php");
                exit();
            }
            
            // Mulai transaction
            mysqli_begin_transaction($conn);
            
            // Hapus data lost
            $query = "DELETE FROM lost_barang WHERE id_lost = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_lost);
            mysqli_stmt_execute($stmt);
            
            // Kembalikan stok barang
            $new_stock = $lost_item['stok'] + $lost_item['jumlah'];
            $query = "UPDATE barang SET stok = ? WHERE id_barang = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "di", $new_stock, $lost_item['id_barang']);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log aktivitas
            if ($id_user) {
                logActivity($id_user, "Menghapus data barang lost: " . $lost_item['nama_barang'] . " (Jumlah: " . $lost_item['jumlah'] . ")");
            }
            
            setAlert("success", "Data barang lost berhasil dihapus!");
        } catch (Exception $e) {
            mysqli_rollback($conn);
            setAlert("error", "Terjadi kesalahan: " . $e->getMessage());
        }
        
        // Redirect
        header("Location: barang_lost.php");
        exit();
    }
}

// Get all items for dropdown - PERBAIKAN QUERY
$items_query = "SELECT * FROM barang ORDER BY nama_barang ASC";
$items = mysqli_query($conn, $items_query);

// Debug: Check if items query is successful
if (!$items) {
    echo '<div class="bg-red-100 p-4 mb-4 rounded">Error query barang: ' . mysqli_error($conn) . '</div>';
}

// Debug: Count items
$items_count = mysqli_num_rows($items);
if ($items_count == 0) {
    echo '<div class="bg-yellow-100 p-4 mb-4 rounded">Tidak ada data barang yang ditemukan.</div>';
}

// Get all lost items
if ($table_exists) {
    $query = "SELECT l.*, b.nama_barang, b.satuan, u.nama_lengkap 
              FROM lost_barang l
              JOIN barang b ON l.id_barang = b.id_barang
              LEFT JOIN users u ON l.dibuat_oleh = u.id_user
              ORDER BY l.created_at DESC";
    $lost_items_result = mysqli_query($conn, $query);

    // Hitung jumlah data
    $lost_items_count = mysqli_num_rows($lost_items_result);
    
    // Proses data untuk menangani nilai NULL
    $lost_items = [];
    if ($lost_items_count > 0) {
        while ($item = mysqli_fetch_assoc($lost_items_result)) {
            // Pastikan nama_lengkap ada, jika tidak set ke N/A
            if (!isset($item['nama_lengkap']) || $item['nama_lengkap'] === null) {
                $item['nama_lengkap'] = 'N/A';
            }
            $lost_items[] = $item;
        }
    }
} else {
    $lost_items = [];
    $lost_items_count = 0;
}
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Daftar Barang Lost
            <?php if (isset($items_count)): ?>
            <span class="ml-2 text-sm text-gray-500">(<?= $items_count ?> barang tersedia)</span>
            <?php endif; ?>
        </h2>
        
        <div class="flex space-x-2">
            <button type="button" class="bg-green-500 hover:bg-green-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                    onclick="window.location.href='print_barang_lost.php'">
                <i class="fas fa-print mr-2"></i> Cetak Laporan
            </button>
            
            <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
            <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                    onclick="showModal('addLostModal')">
                <i class="fas fa-plus-circle mr-2"></i> Tambah Barang Lost
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table id="lost-items-table" class="min-w-full bg-white rounded-lg overflow-hidden">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Barang</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Jumlah</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Alasan</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Dibuat Oleh</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Bukti</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($lost_items && $lost_items_count > 0): ?>
                    <?php foreach ($lost_items as $item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 text-sm"><?= $item['id_lost'] ?></td>
                        <td class="py-2 px-3 text-sm"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                        <td class="py-2 px-3 text-sm font-medium"><?= $item['nama_barang'] ?></td>
                        <td class="py-2 px-3 text-sm text-gray-600">
                            <?= $item['jumlah'] ?> <span class="text-xs text-gray-500"><?= $item['satuan'] ?></span>
                        </td>
                        <td class="py-2 px-3 text-sm"><?= $item['alasan'] ?></td>
                        <td class="py-2 px-3 text-sm text-gray-600"><?= $item['nama_lengkap'] ?></td>
                        <td class="py-2 px-3 text-sm">
                            <?php if (!empty($item['foto_bukti'])): ?>
                                <a href="uploads/lost/<?= $item['foto_bukti'] ?>" target="_blank" class="text-blue-500 hover:underline">
                                    <i class="fas fa-image"></i> Lihat
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400">Tidak ada</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3 text-sm">
                            <div class="flex space-x-2">
                                <button class="text-blue-500 hover:text-blue-700" onclick="viewLostDetail(<?= $item['id_lost'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'headproduksi'): ?>
                                <button class="text-yellow-500 hover:text-yellow-700" onclick="showEditModal(<?= $item['id_lost'] ?>, <?= $item['id_barang'] ?>, '<?= htmlspecialchars($item['nama_barang']) ?>', <?= $item['jumlah'] ?>, '<?= htmlspecialchars($item['alasan']) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <button class="text-red-500 hover:text-red-700" onclick="confirmDelete(<?= $item['id_lost'] ?>, '<?= htmlspecialchars($item['nama_barang']) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="py-4 text-center text-gray-500">Tidak ada data yang ditemukan</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Lost Item Modal -->
<div id="addLostModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Barang Lost</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addLostModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="lostItemForm" method="POST" action="" enctype="multipart/form-data" class="mt-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_barang">
                        Barang
                    </label>
                    <select id="id_barang" name="id_barang" required 
                            class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih Barang</option>
                        <?php 
                        // Reset pointer ke awal result set
                        if ($items && mysqli_num_rows($items) > 0) {
                            mysqli_data_seek($items, 0);
                            // Tampilkan daftar barang
                            while ($item = mysqli_fetch_assoc($items)) {
                                echo '<option value="' . $item['id_barang'] . '" data-stok="' . $item['stok'] . '" data-satuan="' . $item['satuan'] . '">';
                                echo $item['nama_barang'] . ' (Stok: ' . $item['stok'] . ' ' . $item['satuan'] . ')';
                                echo '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>Tidak ada data barang</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="jumlah">
                            Jumlah
                        </label>
                        <div class="flex">
                            <input type="number" id="jumlah" name="jumlah" required min="0.01" step="0.01"
                                   class="shadow-sm border border-gray-300 rounded-l w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Jumlah barang">
                            <span id="satuan_input" class="inline-flex items-center px-3 text-sm rounded-r border border-l-0 border-gray-300 bg-gray-50 text-gray-500"></span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="alasan">
                            Alasan
                        </label>
                        <select id="alasan" name="alasan" required 
                                class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Alasan</option>
                            <option value="Rusak">Rusak</option>
                            <option value="Kadaluarsa">Kadaluarsa</option>
                            <option value="Hilang">Hilang</option>
                            <option value="Tumpah">Tumpah</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4" id="alasan_lainnya_container" style="display: none;">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="alasan_lainnya">
                        Alasan Lainnya
                    </label>
                    <textarea id="alasan_lainnya" name="alasan_lainnya" rows="2"
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Masukkan alasan lainnya"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="foto_bukti">
                        Foto Bukti (Opsional)
                    </label>
                    <input type="file" id="foto_bukti" name="foto_bukti" accept="image/*"
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, atau JPEG. Maks: 2MB</p>
                </div>
                
                <div class="mb-4">
                    <div class="bg-blue-50 p-3 rounded-md border border-blue-200">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-1"></i> Kolom <strong>Dibuat Oleh</strong> akan otomatis diisi dengan user yang sedang login (<?= isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Administrator' ?>).
                        </p>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('addLostModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_lost" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tambahkan CSS untuk memastikan Select2 dropdown muncul di atas modal -->
<style>
.select2-container {
    z-index: 9999;
}
</style>

<!-- Delete Modal -->
<div id="deleteLostModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Konfirmasi Hapus</h3>
        <p id="delete_confirmation_text" class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
        
        <form method="POST" action="">
            <input type="hidden" id="delete_id_lost" name="id_lost">
            <div class="flex justify-end space-x-3">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-all" onclick="closeModal('deleteLostModal')">
                    Batal
                </button>
                <button type="submit" name="delete_lost" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-all">
                    Hapus
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl">
        <div class="flex justify-between items-center border-b px-4 py-3">
            <h3 class="font-semibold text-lg">Detail Barang Lost</h3>
            <button onclick="closeModal('detailModal')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="detailModalContent" class="p-4">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editLostModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Edit Barang Lost</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('editLostModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editLostForm" method="POST" action="" class="mt-4">
                <input type="hidden" id="edit_id_lost" name="id_lost" value="">
                <input type="hidden" id="edit_id_barang" name="id_barang" value="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_nama_barang">
                        Nama Barang
                    </label>
                    <input type="text" id="edit_nama_barang" name="nama_barang" required 
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_jumlah">
                        Jumlah
                    </label>
                    <div class="flex">
                        <input type="number" id="edit_jumlah" name="jumlah" required min="0.01" step="0.01"
                               class="shadow-sm border border-gray-300 rounded-l w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Jumlah barang">
                        <span id="edit_satuan_input" class="inline-flex items-center px-3 text-sm rounded-r border border-l-0 border-gray-300 bg-gray-50 text-gray-500"></span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_alasan">
                        Alasan
                    </label>
                    <select id="edit_alasan" name="alasan" required 
                            class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih Alasan</option>
                        <option value="Rusak">Rusak</option>
                        <option value="Kadaluarsa">Kadaluarsa</option>
                        <option value="Hilang">Hilang</option>
                        <option value="Tumpah">Tumpah</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                
                <div class="mb-4" id="edit_alasan_lainnya_container" style="display: none;">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_alasan_lainnya">
                        Alasan Lainnya
                    </label>
                    <textarea id="edit_alasan_lainnya" name="alasan_lainnya" rows="2"
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Masukkan alasan lainnya"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_foto_bukti">
                        Foto Bukti (Opsional)
                    </label>
                    <input type="file" id="edit_foto_bukti" name="foto_bukti" accept="image/*"
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, atau JPEG. Maks: 2MB</p>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('editLostModal')">
                        Batal
                    </button>
                    <button type="submit" name="edit_lost" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Solusi alternatif untuk menampilkan data barang -->
<script>
// Tambahkan data barang langsung ke JavaScript untuk digunakan jika Select2 gagal
var barangData = [
<?php
// Reset pointer ke awal result set
if ($items && mysqli_num_rows($items) > 0) {
    mysqli_data_seek($items, 0);
    // Tampilkan daftar barang
    while ($item = mysqli_fetch_assoc($items)) {
        echo '{';
        echo 'id: ' . $item['id_barang'] . ',';
        echo 'nama: "' . addslashes($item['nama_barang']) . '",';
        echo 'stok: ' . $item['stok'] . ',';
        echo 'satuan: "' . addslashes($item['satuan']) . '"';
        echo '},';
    }
}
?>
];

// DOM Ready function
document.addEventListener('DOMContentLoaded', function() {
    // Debug: Cek apakah DOM sudah dimuat
    console.log('DOM loaded');
    
    // Show/hide alasan lainnya field
    $('#alasan').on('change', function() {
        if ($(this).val() === 'Lainnya') {
            $('#alasan_lainnya_container').show();
            $('#alasan_lainnya').attr('required', true);
        } else {
            $('#alasan_lainnya_container').hide();
            $('#alasan_lainnya').attr('required', false);
        }
    });
    
    // Initialize DataTable only if table has data
    if ($('#lost-items-table tbody tr').length > 0 && !$('#lost-items-table tbody tr td:first-child').text().includes('Tidak ada data')) {
        try {
            $('#lost-items-table').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 25,
                "language": {
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Tidak ada data yang ditemukan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada data tersedia",
                    "infoFiltered": "(difilter dari _MAX_ total data)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });
        } catch (e) {
            console.error('Error initializing DataTable:', e);
        }
    }
});

function showModal(modalId) {
    // Show modal
    var modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    
    // Initialize Select2 when modal is shown
    if (modalId === 'addLostModal') {
        setTimeout(function() {
            try {
                // Basic select2 initialization
                $('#id_barang').select2({
                    dropdownParent: $('#addLostModal'),
                    width: '100%',
                    placeholder: 'Pilih Barang'
                });
                
                // Update satuan when barang is selected
                $('#id_barang').on('change', function() {
                    var selectedOption = $(this).find('option:selected');
                    var satuan = selectedOption.data('satuan');
                    $('#satuan_input').text(satuan || '');
                });
                
                console.log('Select2 initialized in modal');
            } catch (e) {
                console.error('Error initializing Select2:', e);
                
                // Solusi alternatif jika Select2 gagal
                try {
                    // Hapus opsi yang ada
                    $('#id_barang').empty();
                    $('#id_barang').append('<option value="">Pilih Barang</option>');
                    
                    // Tambahkan opsi dari data JavaScript
                    barangData.forEach(function(item) {
                        $('#id_barang').append(
                            '<option value="' + item.id + '" data-stok="' + item.stok + '" data-satuan="' + item.satuan + '">' +
                            item.nama + ' (Stok: ' + item.stok + ' ' + item.satuan + ')' +
                            '</option>'
                        );
                    });
                    
                    // Update satuan when barang is selected
                    $('#id_barang').on('change', function() {
                        var selectedId = $(this).val();
                        var selectedItem = barangData.find(function(item) {
                            return item.id == selectedId;
                        });
                        if (selectedItem) {
                            $('#satuan_input').text(selectedItem.satuan || '');
                        }
                    });
                    
                    console.log('Fallback barang dropdown initialized');
                } catch (fallbackError) {
                    console.error('Error initializing fallback dropdown:', fallbackError);
                }
            }
        }, 100);
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function deleteLostItem(id, name) {
    document.getElementById('delete_id_lost').value = id;
    document.getElementById('delete_confirmation_text').textContent = `Apakah Anda yakin ingin menghapus data lost untuk ${name}?`;
    showModal('deleteLostModal');
}

// View detail function
function viewLostDetail(id) {
    // Show loading
    showModal('detailModal');
    document.getElementById('detailModalContent').innerHTML = '<div class="flex justify-center p-4"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div></div>';
    
    // Fetch detail data with AJAX
    fetch('get_lost_detail.php?id=' + id)
        .then(response => response.text())
        .then(data => {
            document.getElementById('detailModalContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('detailModalContent').innerHTML = '<div class="text-red-500 p-4">Error: ' + error.message + '</div>';
        });
}

// Show edit modal function
function showEditModal(id, idBarang, namaBarang, jumlah, alasan) {
    // Populate edit form
    document.getElementById('edit_id_lost').value = id;
    document.getElementById('edit_id_barang').value = idBarang;
    document.getElementById('edit_nama_barang').value = namaBarang;
    document.getElementById('edit_jumlah').value = jumlah;
    document.getElementById('edit_alasan').value = alasan;
    
    // Show modal
    showModal('editLostModal');
}

// Confirm delete function
function confirmDelete(id, nama) {
    document.getElementById('delete_id_lost').value = id;
    document.getElementById('delete_confirmation_text').textContent = `Apakah Anda yakin ingin menghapus data lost untuk ${nama}?`;
    showModal('deleteLostModal');
}

// Update nama barang ketika select berubah
function updateBarangInfo(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const namaBarang = selectedOption.text;
    
    document.getElementById('nama_barang_display').value = namaBarang;
}
</script>

<?php require_once 'includes/footer.php'; ?>
