<?php
$pageTitle = "Menu Minuman";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php';

// Additional style to ensure hamburger menu is visible
echo '<style>
    @media (max-width: 1023px) {
        #sidebar-toggle {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 10000 !important;
            position: fixed !important;
            top: 1rem !important;
            left: 1rem !important;
            background-color: #15803d !important;
            color: white !important;
        }
        
        #sidebar-toggle i {
            display: inline-block !important;
        }
    }
</style>';

// Enforce view-only for crew
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'crew') {
    $permission = 'view';
    $VIEW_ONLY = true;
    $EDIT_ALLOWED = false;
    $DELETE_ALLOWED = false;
    
    // Add JavaScript to hide form and action buttons
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Hide the add/edit form
        const menuForm = document.getElementById("menuForm");
        if (menuForm) {
            menuForm.style.display = "none";
        }
        
        // Hide only Edit/Delete buttons but keep the detail view functionality
        const editButtons = document.querySelectorAll(".menu-card a.bg-blue-500, .menu-card button.bg-red-500");
        editButtons.forEach(function(btn) {
            btn.style.display = "none";
        });
        
        // Hide add menu button and entire form
        const menuFormContainer = document.querySelector(".bg-white.p-6.rounded-lg.shadow-md.mb-6");
        if (menuFormContainer) {
            menuFormContainer.style.display = "none";
        }
        
        // Add view-only message at the top of the page
        const contentDiv = document.querySelector(".ml-17.p-2");
        if (contentDiv) {
            const viewMsg = document.createElement("div");
            viewMsg.className = "bg-gray-100 p-4 rounded-md text-gray-700 mb-6";
            viewMsg.innerHTML = "<i class=\'fas fa-lock mr-2\'></i> Akses terbatas. Anda hanya dapat melihat menu.";
            const firstChild = contentDiv.firstChild;
            contentDiv.insertBefore(viewMsg, firstChild.nextSibling);
        }
    });
    </script>';
}

// Fungsi untuk mendapatkan harga barang berdasarkan id_barang
function getHargaBarang($conn, $id_barang) {
    $query = "SELECT harga FROM barang WHERE id_barang = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_barang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['harga'];
    }
    
    return 0;
}

// Fungsi untuk mendapatkan data barang dari database
function getBarangData($conn) {
    $query = "SELECT id_barang, nama_barang, satuan, harga FROM barang ORDER BY nama_barang";
    $result = $conn->query($query);
    
    $barang_list = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $barang_list[$row['id_barang']] = $row;
        }
    }
    
    return $barang_list;
}

// Fungsi untuk menghitung harga modal dari bahan-bahan
function hitungHargaModal($conn, $bahan_list) {
    $total = 0;
    $bahan_array = explode(',', $bahan_list);
    
    foreach ($bahan_array as $bahan_item) {
        // Format: nama_bahan:jumlah
        $item_parts = explode(':', trim($bahan_item));
        
        if (count($item_parts) >= 2) {
            $nama_bahan = trim($item_parts[0]);
            $jumlah = floatval(trim($item_parts[1]));
            
            // Cari id_barang berdasarkan nama
            $query = "SELECT id_barang, harga FROM barang WHERE nama_barang = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $nama_bahan);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $harga_satuan = $row['harga'];
                $total += $harga_satuan * $jumlah;
            }
        }
    }
    
    return $total;
}

// Fungsi untuk memformat angka ke format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Pastikan folder uploads/menu ada
$uploadDir = 'uploads/menu/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Inisialisasi variabel
$id_menu = $nama_menu = $harga = $bahan = $deskripsi = $foto = '';
$harga_modal = $keuntungan = 0;
$errors = [];
$edit_mode = false;
$bahan_items = [];
$barang_list = getBarangData($conn);

// Handle form submission untuk tambah/edit menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_menu']) && (!isset($VIEW_ONLY) || $VIEW_ONLY !== true)) {
    // Ambil data dari form
    $id_menu = isset($_POST['id_menu']) ? $_POST['id_menu'] : '';
    $nama_menu = trim($_POST['nama_menu']);
    $harga = trim($_POST['harga']);
    $bahan = trim($_POST['bahan']);
    $deskripsi = trim($_POST['deskripsi']);
    $kategori = 'minuman'; // Kategori tetap minuman
    $harga_modal = isset($_POST['harga_modal']) ? trim($_POST['harga_modal']) : 0;
    $keuntungan = isset($_POST['keuntungan']) ? abs(trim($_POST['keuntungan'])) : 0; // Ensure keuntungan is positive
    
    // Validasi data
    if (empty($nama_menu)) {
        $errors[] = "Nama menu wajib diisi";
    }
    
    if (empty($harga) || !is_numeric($harga) || $harga <= 0) {
        $errors[] = "Harga harus berupa angka positif";
    }
    
    if (empty($bahan)) {
        $errors[] = "Bahan-bahan wajib diisi";
    }
    
    // Proses upload foto jika ada
    $foto_name = '';
    if (!empty($_FILES['foto']['name'])) {
        $foto_name = time() . '_' . basename($_FILES['foto']['name']);
        $foto_path = $uploadDir . $foto_name;
        $foto_tmp = $_FILES['foto']['tmp_name'];
        $foto_size = $_FILES['foto']['size'];
        $foto_ext = strtolower(pathinfo($foto_path, PATHINFO_EXTENSION));
        
        // Validasi ekstensi file
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($foto_ext, $allowed_ext)) {
            $errors[] = "Format foto tidak didukung. Format yang diizinkan: JPG, JPEG, PNG, GIF";
        }
        
        // Validasi ukuran file (max 2MB)
        if ($foto_size > 2097152) {
            $errors[] = "Ukuran foto tidak boleh lebih dari 2MB";
        }
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Cek apakah tabel menu sudah ada, jika belum buat tabel
            $table_check = $conn->query("SHOW TABLES LIKE 'menu'");
            if ($table_check->num_rows == 0) {
                $create_table = "CREATE TABLE `menu` (
                    `id_menu` int(11) NOT NULL AUTO_INCREMENT,
                    `nama_menu` varchar(100) NOT NULL,
                    `kategori` enum('makanan','minuman') NOT NULL,
                    `harga` decimal(10,2) NOT NULL,
                    `bahan` text NOT NULL,
                    `deskripsi` text,
                    `foto` varchar(255) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id_menu`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                if (!$conn->query($create_table)) {
                    throw new Exception("Error creating menu table: " . $conn->error);
                }
            }
            
            // Upload foto jika ada
            if (!empty($_FILES['foto']['name'])) {
                if (!move_uploaded_file($foto_tmp, $foto_path)) {
                    throw new Exception("Gagal mengupload foto");
                }
                $foto = $foto_name;
            }
            
            if (!empty($id_menu)) {
                // Mode edit
                $query = "UPDATE menu SET nama_menu = ?, kategori = ?, harga = ?, bahan = ?, deskripsi = ?, harga_modal = ?, keuntungan = ?";
                
                // Jika ada foto baru, update juga field foto
                if (!empty($foto)) {
                    // Hapus foto lama jika ada
                    $get_old_foto = $conn->prepare("SELECT foto FROM menu WHERE id_menu = ?");
                    $get_old_foto->bind_param("i", $id_menu);
                    $get_old_foto->execute();
                    $old_foto_result = $get_old_foto->get_result();
                    
                    if ($old_foto_result->num_rows > 0) {
                        $old_foto = $old_foto_result->fetch_assoc()['foto'];
                        if (!empty($old_foto) && file_exists($uploadDir . $old_foto)) {
                            unlink($uploadDir . $old_foto);
                        }
                    }
                    
                    $query .= ", foto = ? WHERE id_menu = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssdssddsi", $nama_menu, $kategori, $harga, $bahan, $deskripsi, $harga_modal, $keuntungan, $foto, $id_menu);
                } else {
                    $query .= " WHERE id_menu = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssdssddi", $nama_menu, $kategori, $harga, $bahan, $deskripsi, $harga_modal, $keuntungan, $id_menu);
                }
                
                $action_msg = "Menu berhasil diupdate";
                $log_action = "Mengupdate menu minuman: $nama_menu";
            } else {
                // Mode tambah
                $query = "INSERT INTO menu (nama_menu, kategori, harga, bahan, deskripsi, foto, harga_modal, keuntungan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssdsssdd", $nama_menu, $kategori, $harga, $bahan, $deskripsi, $foto, $harga_modal, $keuntungan);
                
                $action_msg = "Menu berhasil ditambahkan";
                $log_action = "Menambahkan menu minuman baru: $nama_menu";
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            // Log aktivitas
            if (function_exists('logActivity')) {
                logActivity($_SESSION['user_id'], $log_action);
            }
            
            $conn->commit();
            
            // Set alert success
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => $action_msg
            ];
            
            // Redirect untuk refresh halaman
            header("Location: menu_minuman.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Handle delete menu
if (isset($_GET['delete']) && !empty($_GET['delete']) && (!isset($VIEW_ONLY) || $VIEW_ONLY !== true)) {
    $id_to_delete = $_GET['delete'];
    
    try {
        $conn->begin_transaction();
        
        // Ambil info foto sebelum dihapus
        $get_foto = $conn->prepare("SELECT foto, nama_menu FROM menu WHERE id_menu = ?");
        $get_foto->bind_param("i", $id_to_delete);
        $get_foto->execute();
        $foto_result = $get_foto->get_result();
        
        if ($foto_result->num_rows > 0) {
            $menu_data = $foto_result->fetch_assoc();
            $foto_to_delete = $menu_data['foto'];
            $menu_name = $menu_data['nama_menu'];
            
            // Hapus data dari database
            $delete_stmt = $conn->prepare("DELETE FROM menu WHERE id_menu = ?");
            $delete_stmt->bind_param("i", $id_to_delete);
            
            if (!$delete_stmt->execute()) {
                throw new Exception("Gagal menghapus menu: " . $delete_stmt->error);
            }
            
            // Hapus file foto jika ada
            if (!empty($foto_to_delete) && file_exists($uploadDir . $foto_to_delete)) {
                unlink($uploadDir . $foto_to_delete);
            }
            
            // Log aktivitas
            if (function_exists('logActivity')) {
                logActivity($_SESSION['user_id'], "Menghapus menu minuman: $menu_name");
            }
            
            $conn->commit();
            
            // Set alert success
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => "Menu berhasil dihapus"
            ];
        } else {
            throw new Exception("Menu tidak ditemukan");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => "Error: " . $e->getMessage()
        ];
    }
    
    // Redirect untuk refresh halaman
    header("Location: menu_minuman.php");
    exit;
}

// Handle edit menu (load data untuk edit)
if (isset($_GET['edit']) && !empty($_GET['edit']) && (!isset($VIEW_ONLY) || $VIEW_ONLY !== true)) {
    $id_to_edit = $_GET['edit'];
    $edit_mode = true;
    
    $edit_query = $conn->prepare("SELECT * FROM menu WHERE id_menu = ?");
    $edit_query->bind_param("i", $id_to_edit);
    $edit_query->execute();
    $edit_result = $edit_query->get_result();
    
    if ($edit_result->num_rows > 0) {
        $menu_data = $edit_result->fetch_assoc();
        $id_menu = $menu_data['id_menu'];
        $nama_menu = $menu_data['nama_menu'];
        $harga = $menu_data['harga'];
        $bahan = $menu_data['bahan'];
        $deskripsi = $menu_data['deskripsi'];
        $foto = $menu_data['foto'];
        $harga_modal = $menu_data['harga_modal'];
        $keuntungan = $menu_data['keuntungan'];
        
        // Parse bahan untuk ditampilkan di form
        $bahan_items = [];
        $bahan_array = explode(',', $bahan);
        foreach ($bahan_array as $bahan_item) {
            $item_parts = explode(':', trim($bahan_item));
            if (count($item_parts) >= 2) {
                $nama_bahan = trim($item_parts[0]);
                $jumlah = floatval(trim($item_parts[1]));
                $bahan_items[] = [
                    'nama' => $nama_bahan,
                    'jumlah' => $jumlah
                ];
            }
        }
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => "Menu tidak ditemukan"
        ];
        header("Location: menu_minuman.php");
        exit;
    }
}

// Ambil semua data menu minuman
$menu_list = [];
$query = "SELECT * FROM menu WHERE kategori = 'minuman' ORDER BY nama_menu";

// Cek apakah tabel menu sudah ada
$table_check = $conn->query("SHOW TABLES LIKE 'menu'");
if ($table_check->num_rows > 0) {
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $menu_list[] = $row;
        }
    }
}
?>

<div class="ml-17 p-2">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-700 flex items-center">
            <i class="fas fa-coffee mr-2"></i> Data Menu Minuman
        </h1>
        <div class="flex items-center mt-2 text-sm">
            <a href="dashboard.php" class="text-blue-500 hover:text-blue-700">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-600">Menu Minuman</span>
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
    
    <?php if (isset($_SESSION['alert'])): ?>
    <div class="bg-<?= $_SESSION['alert']['type'] == 'success' ? 'green' : 'red' ?>-100 border-l-4 border-<?= $_SESSION['alert']['type'] == 'success' ? 'green' : 'red' ?>-500 text-<?= $_SESSION['alert']['type'] == 'success' ? 'green' : 'red' ?>-700 p-4 mb-6" role="alert">
        <p><?= $_SESSION['alert']['message'] ?></p>
    </div>
    <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <!-- Form Tambah/Edit Menu -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-6 flex items-center">
            <i class="fas fa-<?= $edit_mode ? 'edit' : 'plus-circle' ?> text-blue-500 mr-2"></i>
            <?= $edit_mode ? 'Edit Menu Minuman' : 'Tambah Menu Minuman Baru' ?>
        </h2>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" enctype="multipart/form-data" class="transition-all duration-300">
            <?php if ($edit_mode): ?>
            <input type="hidden" name="id_menu" value="<?= $id_menu ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group transition-all duration-200">
                    <label for="nama_menu" class="block text-sm font-medium text-gray-700 mb-1">Nama Menu</label>
                    <input type="text" id="nama_menu" name="nama_menu" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" value="<?= htmlspecialchars($nama_menu) ?>" required>
                </div>
                
                <div class="form-group transition-all duration-200">
                    <label for="harga" class="block text-sm font-medium text-gray-700 mb-1">Harga Jual</label>
                    <div class="flex items-center">
                        <span class="bg-gray-100 px-3 py-2 text-gray-500 border border-r-0 border-gray-300 rounded-l-lg">Rp</span>
                        <input type="number" id="harga" name="harga" min="0" step="1000" class="w-full py-2 px-3 border border-gray-300 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" value="<?= htmlspecialchars($harga) ?>" required>
                    </div>
                </div>
                
                <div class="form-group transition-all duration-200 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bahan-bahan</label>
                    <div id="bahanContainer" class="mb-2 border border-gray-300 rounded-md p-3 bg-gray-50">
                        <!-- Bahan items will be added here -->
                    </div>
                    <input type="hidden" id="bahanInput" name="bahan" value="<?= htmlspecialchars($bahan) ?>">
                    <button type="button" id="addBahanBtn" class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded-md text-sm transition-colors duration-200">
                        <i class="fas fa-plus mr-1"></i> Tambah Bahan
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="harga_modal" class="block text-gray-700 text-sm font-medium mb-2">Harga Modal (otomatis)</label>
                        <div class="flex items-center">
                            <span class="bg-gray-100 px-3 py-2 text-gray-500 border border-r-0 border-gray-300 rounded-l-lg">Rp</span>
                            <input type="number" id="harga_modal" name="harga_modal" value="<?= $harga_modal ?>" 
                                   class="shadow-sm border border-gray-300 rounded-r-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="keuntungan" class="block text-gray-700 text-sm font-medium mb-2">Keuntungan (otomatis)</label>
                        <div class="flex items-center">
                            <span class="bg-gray-100 px-3 py-2 text-gray-500 border border-r-0 border-gray-300 rounded-l-lg">Rp</span>
                            <input type="number" id="keuntungan" name="keuntungan" value="<?= $keuntungan ?>" 
                                   class="shadow-sm border border-gray-300 rounded-r-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
                
                <div class="form-group transition-all duration-200">
                    <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"><?= htmlspecialchars($deskripsi) ?></textarea>
                </div>
                
                <div class="form-group transition-all duration-200">
                    <label for="foto" class="block text-sm font-medium text-gray-700 mb-1">Foto Menu</label>
                    <div class="flex items-center">
                        <label class="w-full flex flex-col items-center px-4 py-2 bg-white text-blue-500 rounded-lg border border-blue-500 border-dashed cursor-pointer hover:bg-blue-50 transition-colors duration-200">
                            <span class="flex items-center">
                                <i class="fas fa-cloud-upload-alt mr-2"></i>
                                <span>Pilih Gambar</span>
                            </span>
                            <input type="file" id="foto" name="foto" class="hidden" accept="image/*">
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, JPEG, PNG, GIF. Maks: 2MB</p>
                    
                    <?php if ($edit_mode && !empty($foto)): ?>
                    <div class="mt-3">
                        <p class="text-sm text-gray-600 mb-1">Foto saat ini:</p>
                        <div class="relative group">
                            <img src="<?= $uploadDir . $foto ?>" alt="<?= htmlspecialchars($nama_menu) ?>" class="w-32 h-32 object-cover rounded-md border border-gray-200">
                            <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity duration-200 rounded-md">
                                <span class="text-white text-xs">Foto saat ini</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex justify-end mt-6 space-x-2">
                <a href="menu_minuman.php" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition duration-200 flex items-center">
                    <i class="fas fa-times mr-1"></i> Batal
                </a>
                <button type="submit" name="submit_menu" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 flex items-center">
                    <i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update Menu' : 'Simpan Menu' ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Daftar Menu -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">Daftar Menu Minuman</h2>
        
        <?php if (empty($menu_list)): ?>
        <div class="text-center py-4 text-gray-500">
            <p>Belum ada data menu minuman</p>
        </div>
        <?php else: ?>
        <!-- Filter dan Pencarian -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <div class="flex items-center mb-4 md:mb-0">
                <span class="mr-2 text-gray-600">Tampilkan</span>
                <select id="itemsPerPage" class="border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="8">8</option>
                    <option value="16" selected>16</option>
                    <option value="32">32</option>
                    <option value="all">Semua</option>
                </select>
                <span class="ml-2 text-gray-600">entri</span>
            </div>
            
            <div class="relative w-full md:w-64">
                <input type="text" id="searchInput" placeholder="Cari menu..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>
        
        <!-- Menu Cards Grid -->
        <div id="menuGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($menu_list as $menu): ?>
            <div class="menu-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 border border-gray-100 transform hover:-translate-y-1 group">
                <!-- Menu Image -->
                <div class="relative h-48 overflow-hidden bg-gray-200">
                    <?php if (!empty($menu['foto']) && file_exists($uploadDir . $menu['foto'])): ?>
                    <img src="<?= $uploadDir . $menu['foto'] ?>" alt="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gray-200">
                        <i class="fas fa-coffee text-gray-400 text-4xl"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Price Badge -->
                    <div class="absolute top-0 right-0 bg-blue-500 text-white font-bold py-1 px-3 m-2 rounded-full shadow-md">
                        Rp <?= number_format($menu['harga'], 0, ',', '.') ?>
                    </div>
                    
                    <!-- Quick View Button -->
                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity duration-300">
                        <button onclick="showDetailModal(this.closest('.menu-card'))" class="bg-white text-gray-800 hover:bg-gray-100 px-4 py-2 rounded-full font-medium transform transition-transform duration-300 hover:scale-105">
                            <i class="fas fa-eye mr-2"></i> Lihat Detail
                        </button>
                    </div>
                </div>
                
                <!-- Menu Info -->
                <div class="p-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-2"><?= htmlspecialchars($menu['nama_menu']) ?></h3>
                    
                    <div class="mb-3">
                        <span class="text-sm text-gray-600 font-medium">Bahan:</span>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($menu['bahan']) ?></p>
                    </div>
                    
                    <?php if (!empty($menu['deskripsi'])): ?>
                    <div class="mb-3">
                        <span class="text-sm text-gray-600 font-medium">Deskripsi:</span>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars(mb_strimwidth($menu['deskripsi'], 0, 60, "...")) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 font-medium">Harga Modal:</span>
                            <span class="text-sm font-semibold text-gray-700"><?= formatRupiah($menu['harga_modal']) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 font-medium">Harga Jual:</span>
                            <span class="text-sm font-semibold text-blue-600"><?= formatRupiah($menu['harga']) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 font-medium">Keuntungan:</span>
                            <span class="text-sm font-semibold text-blue-600"><?= formatRupiah(abs($menu['keuntungan'])) ?></span>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-sm text-gray-600 font-medium">Persentase:</span>
                            <?php 
                            $persentase = 0;
                            if ($menu['harga_modal'] > 0) {
                                // Make sure keuntungan is always treated as positive for percentage calculation
                                $keuntunganValue = abs($menu['keuntungan']);
                                $persentase = ($keuntunganValue / $menu['harga_modal']) * 100;
                            }
                            ?>
                            <span class="text-sm font-semibold <?= $persentase >= 30 ? 'text-green-600' : 'text-yellow-600' ?>"><?= number_format($persentase, 1) ?>%</span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex justify-end mt-2 space-x-2">
                        <a href="menu_minuman.php?edit=<?= $menu['id_menu'] ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm transition-colors duration-200">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                        <button onclick="confirmDelete(<?= $menu['id_menu'] ?>, '<?= htmlspecialchars($menu['nama_menu']) ?>')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition-colors duration-200">
                            <i class="fas fa-trash mr-1"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Menampilkan <span id="startItem">1</span> sampai <span id="endItem"><?= min(16, count($menu_list)) ?></span> dari <span id="totalItems"><?= count($menu_list) ?></span> entri
            </div>
            
            <div class="flex space-x-1">
                <button id="prevPage" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div id="pagination" class="flex space-x-1">
                    <!-- Pagination buttons will be generated here -->
                </div>
                <button id="nextPage" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full transform transition-all duration-300 scale-90 opacity-0" id="deleteModalContent">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Konfirmasi Hapus</h3>
        <p class="text-gray-700 mb-6">Apakah Anda yakin ingin menghapus menu <span id="menuToDelete" class="font-semibold"></span>? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="flex justify-end">
            <button id="cancelDeleteBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg mr-2 transition-colors duration-200">
                Batal
            </button>
            <a id="confirmDeleteBtn" href="#" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                Hapus
            </a>
        </div>
    </div>
</div>

<!-- Modal Detail Menu -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl sm:max-w-md md:max-w-lg lg:max-w-2xl w-[95%] md:w-full mx-auto transform transition-all duration-300 scale-90 opacity-0" id="modalContent" style="max-height: 90vh; overflow-y: auto;">
        <div class="flex justify-between items-center p-3 sm:p-4 border-b">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800" id="detailTitle">Detail Menu</h3>
            <button id="closeDetailBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none transition-transform hover:rotate-90 duration-300">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-3 sm:p-4" id="detailContent">
            <!-- Content will be filled dynamically -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Variables for pagination
        let currentPage = 1;
        let itemsPerPage = 16;
        let menuItems = [];
        try {
            menuItems = Array.from(document.querySelectorAll('.menu-card') || []);
        } catch (e) {
            console.error('Error initializing menu items:', e);
        }
        let totalItems = menuItems.length;
        
        // Initialize
        updatePagination();
        showCurrentPage();
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                menuItems.forEach(item => {
                    const menuName = item.querySelector('h3')?.textContent.toLowerCase() || '';
                    const menuDesc = item.querySelector('.text-gray-600')?.textContent.toLowerCase() || '';
                    
                    if (menuName.includes(searchTerm) || menuDesc.includes(searchTerm)) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
                
                // Reset pagination after search
                currentPage = 1;
                menuItems = Array.from(document.querySelectorAll('.menu-card:not(.hidden)') || []);
                totalItems = menuItems.length;
                updatePagination();
                showCurrentPage();
            });
        }
        
        // Items per page change
        const itemsPerPageSelect = document.getElementById('itemsPerPage');
        if (itemsPerPageSelect) {
            itemsPerPageSelect.addEventListener('change', function() {
                if (this.value === 'all') {
                    itemsPerPage = totalItems;
                } else {
                    itemsPerPage = parseInt(this.value);
                }
                
                currentPage = 1;
                updatePagination();
                showCurrentPage();
            });
        }
        
        // Previous page button
        const prevPageBtn = document.getElementById('prevPage');
        if (prevPageBtn) {
            prevPageBtn.addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    updatePagination();
                    showCurrentPage();
                }
            });
        }
        
        // Next page button
        const nextPageBtn = document.getElementById('nextPage');
        if (nextPageBtn) {
            nextPageBtn.addEventListener('click', function() {
                if (currentPage < Math.ceil(totalItems / itemsPerPage)) {
                    currentPage++;
                    updatePagination();
                    showCurrentPage();
                }
            });
        }
        
        // Function to update pagination
        function updatePagination() {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            const paginationContainer = document.getElementById('pagination');
            
            // Check if elements exist before trying to access them
            if (!paginationContainer) {
                console.warn('Pagination container not found');
                return;
            }
            
            paginationContainer.innerHTML = '';
            
            // Update prev/next button states
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');
            
            if (prevPageBtn) {
                prevPageBtn.disabled = currentPage === 1;
            }
            
            if (nextPageBtn) {
                nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
            }
            
            // Create page buttons
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageButton = document.createElement('button');
                pageButton.textContent = i;
                pageButton.className = `px-3 py-1 rounded-md ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}`;
                pageButton.addEventListener('click', function() {
                    currentPage = i;
                    updatePagination();
                    showCurrentPage();
                });
                paginationContainer.appendChild(pageButton);
            }
            
            // Update info text
            const startItem = totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
            const endItem = Math.min(currentPage * itemsPerPage, totalItems);
            
            const startItemEl = document.getElementById('startItem');
            const endItemEl = document.getElementById('endItem');
            const totalItemsEl = document.getElementById('totalItems');
            
            if (startItemEl) startItemEl.textContent = startItem;
            if (endItemEl) endItemEl.textContent = endItem;
            if (totalItemsEl) totalItemsEl.textContent = totalItems;
        }
        
        // Function to show current page
        function showCurrentPage() {
            try {
                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                
                if (!menuItems || !Array.isArray(menuItems)) {
                    console.warn('menuItems is not properly initialized:', menuItems);
                    return;
                }
                
                menuItems.forEach((item, index) => {
                    if (!item) {
                        console.warn('Invalid menu item at index', index);
                        return;
                    }
                    
                    if (index >= startIndex && index < endIndex) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            } catch (error) {
                console.error('Error in showCurrentPage:', error);
            }
        }
        
        // Function to show detail modal
        function showDetailModal(card) {
            try {
                const detailModal = document.getElementById('detailModal');
                const modalContent = document.getElementById('modalContent');
                
                if (!detailModal || !modalContent) {
                    console.error('Detail modal elements not found');
                    return;
                }
                
                const title = card.querySelector('h3')?.textContent || 'Detail Menu';
                const image = card.querySelector('img')?.src || '';
                const description = card.querySelector('.text-gray-600')?.textContent || '';
                
                // Get bahan (ingredients) information - it's in the div with "Bahan:" label
                let bahan = '';
                const bahanElement = card.querySelector('div.mb-3:nth-child(2) p.text-sm.text-gray-600');
                if (bahanElement) {
                    bahan = bahanElement.textContent || '';
                }
                
                // Get modal and harga data
                const hargaModal = card.querySelector('.flex.justify-between.items-center:nth-child(1) .text-sm.font-semibold')?.textContent || '';
                const hargaJual = card.querySelector('.flex.justify-between.items-center:nth-child(2) .text-sm.font-semibold')?.textContent || '';
                const keuntungan = card.querySelector('.flex.justify-between.items-center:nth-child(3) .text-sm.font-semibold')?.textContent || '';
                
                const detailTitle = document.getElementById('detailTitle');
                const detailContent = document.getElementById('detailContent');
                
                if (detailTitle) detailTitle.textContent = title;
                if (detailContent) {
                    detailContent.innerHTML = `
                        <div class="flex flex-col">
                            <div class="w-full mb-3">
                                <img src="${image}" alt="${title}" class="w-full max-w-xs mx-auto rounded-lg shadow-md">
                            </div>
                            <div class="w-full">
                                <h4 class="text-base sm:text-lg font-semibold">Deskripsi</h4>
                                <div class="mt-1 sm:mt-2">
                                    <p class="text-sm sm:text-base text-gray-700"><span class="font-medium">Bahan:</span></p>
                                    <div class="text-sm sm:text-base text-gray-700 mb-3">
                                        <p class="font-medium mb-1">Bahan-bahan</p>
                                        <p>${bahan}</p>
                                    </div>
                                </div>
                                
                                <div class="mt-2 sm:mt-3">
                                    <h4 class="text-base sm:text-lg font-semibold">Harga</h4>
                                    <div class="grid grid-cols-2 gap-1 mt-1 sm:mt-2 text-sm sm:text-base">
                                        <div>
                                            <p class="text-gray-700">Harga Jual:</p>
                                            <p class="text-gray-700">Harga Modal:</p>
                                            <p class="text-gray-700">Keuntungan:</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-900 font-medium">${hargaJual}</p>
                                            <p class="text-gray-900 font-medium">${hargaModal}</p>
                                            <p class="text-gray-900 font-medium">${keuntungan}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                // Center modal
                detailModal.style.display = 'flex';
                detailModal.style.alignItems = 'center';
                detailModal.style.justifyContent = 'center';
                
                // Show modal with animation
                detailModal.classList.remove('hidden');
                setTimeout(() => {
                    modalContent.classList.remove('scale-90', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 50);
            } catch (error) {
                console.error('Error showing detail modal:', error);
            }
        }
        
        // Detail view functionality
        const menuCards = document.querySelectorAll('.menu-card');
        if (menuCards && menuCards.length > 0) {
            menuCards.forEach(card => {
                if (card) {
                    // Add click handler to the entire card
                    card.addEventListener('click', function(e) {
                        // Skip if clicking on a button or link
                        if (e.target.tagName === 'BUTTON' || 
                            e.target.tagName === 'A' || 
                            e.target.closest('button') || 
                            e.target.closest('a')) {
                            return;
                        }
                        
                        // Show detail modal
                        showDetailModal(this);
                    });
                    
                    // Add specific click handler for the "Lihat Detail" button in the hover overlay
                    const viewDetailBtn = card.querySelector('.group-hover\\:opacity-100 button');
                    if (viewDetailBtn) {
                        viewDetailBtn.addEventListener('click', function(e) {
                            e.stopPropagation(); // Prevent card click from firing
                            showDetailModal(card);
                        });
                    }
                }
            });
        }
        
        // Close detail modal
        const closeDetailBtn = document.getElementById('closeDetailBtn');
        const detailModal = document.getElementById('detailModal');
        const modalContent = document.getElementById('modalContent');
        
        if (closeDetailBtn && detailModal && modalContent) {
            closeDetailBtn.addEventListener('click', function() {
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-90', 'opacity-0');
                
                setTimeout(() => {
                    detailModal.classList.add('hidden');
                }, 300);
            });
            
            // Close modal when clicking outside
            detailModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeDetailBtn.click();
                }
            });
        }

        // Bahan-bahan management
        const bahanContainer = document.getElementById('bahanContainer');
        const addBahanBtn = document.getElementById('addBahanBtn');
        const bahanInput = document.getElementById('bahanInput');
        const hargaInput = document.getElementById('harga');
        const hargaModalInput = document.getElementById('harga_modal');
        const keuntunganInput = document.getElementById('keuntungan');
        
        // Add event listener to add bahan button if element exists
        if (addBahanBtn && bahanContainer && bahanInput) {
            addBahanBtn.addEventListener('click', function() {
                addBahanItem();
            });
            
            // Initialize bahan items if we have existing value
            const existingBahan = bahanInput.value;
            if (existingBahan) {
                const bahanItems = existingBahan.split(',');
                bahanItems.forEach(item => {
                    const parts = item.split(':');
                    if (parts.length >= 2) {
                        const nama = parts[0].trim();
                        const jumlah = parts[1].trim();
                        addBahanItem(nama, jumlah);
                    }
                });
            } else {
                // Add an empty bahan item if there are none
                addBahanItem();
            }
        } else {
            console.warn('Some bahan-bahan elements are missing:', {
                bahanContainer: !!bahanContainer,
                addBahanBtn: !!addBahanBtn,
                bahanInput: !!bahanInput
            });
        }
        
        // Function to add new bahan item
        function addBahanItem(selectedBahan = '', selectedQty = '') {
            try {
                if (!bahanContainer) {
                    console.warn('Cannot add bahan item: bahanContainer not found');
                    return;
                }
                
                const bahanItem = document.createElement('div');
                bahanItem.className = 'bahan-item flex items-center space-x-2 mb-2';
                
                // Generate options HTML for all barang items
                let optionsHtml = '<option value="">-- Pilih Bahan --</option>';
                <?php foreach ($barang_list as $id_barang => $barang): ?>
                optionsHtml += `<option value="<?= $id_barang ?>" data-satuan="<?= htmlspecialchars($barang['satuan']) ?>" data-harga="<?= $barang['harga'] ?>"><?= htmlspecialchars($barang['nama_barang']) ?></option>`;
                <?php endforeach; ?>
                
                bahanItem.innerHTML = `
                    <select class="bahan-select shadow-sm border border-gray-300 rounded-md flex-grow py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        ${optionsHtml}
                    </select>
                    <div class="flex items-center w-1/3">
                        <input type="number" class="bahan-qty shadow-sm border border-gray-300 rounded-l-md w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="${selectedQty}" min="0.01" step="0.01" placeholder="Qty">
                        <span class="bahan-satuan inline-flex items-center px-3 py-2 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"></span>
                    </div>
                    <button type="button" class="remove-bahan text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                bahanContainer.appendChild(bahanItem);
                
                // Setup event listeners
                setupBahanItem(bahanItem);
                
                // Set the selected bahan if provided
                if (selectedBahan) {
                    const select = bahanItem.querySelector('.bahan-select');
                    if (select) {
                        for (let i = 0; i < select.options.length; i++) {
                            if (select.options[i].textContent.trim() === selectedBahan) {
                                select.selectedIndex = i;
                                // Trigger change event to update satuan
                                const event = new Event('change');
                                select.dispatchEvent(event);
                                break;
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error adding bahan item:', error);
            }
        }
        
        // Function to setup bahan item event listeners
        function setupBahanItem(item) {
            try {
                const select = item.querySelector('.bahan-select');
                const qtyInput = item.querySelector('.bahan-qty');
                const satuanSpan = item.querySelector('.bahan-satuan');
                const removeBtn = item.querySelector('.remove-bahan');
                
                if (!select || !qtyInput || !satuanSpan || !removeBtn) {
                    console.warn('Missing elements in bahan item');
                    return;
                }
                
                // Update satuan when select changes
                select.addEventListener('change', function() {
                    try {
                        const selectedOption = this.options[this.selectedIndex];
                        if (selectedOption && selectedOption.value) {
                            satuanSpan.textContent = selectedOption.dataset.satuan || '';
                        } else {
                            satuanSpan.textContent = '';
                        }
                        updateBahanInput();
                        calculateHargaModal();
                    } catch (error) {
                        console.error('Error in select change handler:', error);
                    }
                });
                
                // Update bahan input when qty changes
                qtyInput.addEventListener('input', function() {
                    updateBahanInput();
                    calculateHargaModal();
                });
                
                // Remove bahan item
                removeBtn.addEventListener('click', function() {
                    item.remove();
                    updateBahanInput();
                    calculateHargaModal();
                });
            } catch (error) {
                console.error('Error setting up bahan item:', error);
            }
        }
        
        // Function to update hidden bahan input
        function updateBahanInput() {
            try {
                if (!bahanInput) return;
                
                const bahanItems = document.querySelectorAll('.bahan-item');
                const bahanValues = [];
                
                bahanItems.forEach(item => {
                    const select = item.querySelector('.bahan-select');
                    const qtyInput = item.querySelector('.bahan-qty');
                    
                    if (select && qtyInput && select.value && qtyInput.value) {
                        const selectedOption = select.options[select.selectedIndex];
                        if (selectedOption) {
                            const nama = selectedOption.textContent.trim();
                            const jumlah = qtyInput.value.trim();
                            bahanValues.push(`${nama}:${jumlah}`);
                        }
                    }
                });
                
                bahanInput.value = bahanValues.join(', ');
            } catch (error) {
                console.error('Error updating bahan input:', error);
            }
        }
        
        // Function to calculate harga modal
        function calculateHargaModal() {
            try {
                if (!hargaModalInput) return;
                
                let totalModal = 0;
                const bahanItems = document.querySelectorAll('.bahan-item');
                
                bahanItems.forEach(item => {
                    const select = item.querySelector('.bahan-select');
                    const qtyInput = item.querySelector('.bahan-qty');
                    
                    if (select && qtyInput && select.value && qtyInput.value) {
                        const selectedOption = select.options[select.selectedIndex];
                        if (selectedOption) {
                            const harga = parseFloat(selectedOption.dataset.harga || 0);
                            const jumlah = parseFloat(qtyInput.value || 0);
                            
                            if (!isNaN(harga) && !isNaN(jumlah)) {
                                totalModal += harga * jumlah;
                            }
                        }
                    }
                });
                
                // Update harga modal input
                hargaModalInput.value = totalModal.toFixed(0);
                // Trigger calculation of profit
                calculateKeuntungan();
            } catch (error) {
                console.error('Error calculating harga modal:', error);
            }
        }
        
        // Function to calculate keuntungan
        function calculateKeuntungan() {
            try {
                if (!hargaInput || !hargaModalInput || !keuntunganInput) return;
                
                const hargaJual = parseFloat(hargaInput.value || 0);
                const hargaModal = parseFloat(hargaModalInput.value || 0);
                // Calculate profit - ensure it's always positive
                const keuntungan = Math.abs(hargaJual - hargaModal);
                
                keuntunganInput.value = keuntungan.toFixed(0);
            } catch (error) {
                console.error('Error calculating keuntungan:', error);
            }
        }
        
        // Calculate keuntungan when harga jual changes
        if (hargaInput) {
            hargaInput.addEventListener('input', calculateKeuntungan);
        }
        
        // Initial calculations
        if (bahanContainer && bahanInput) {
            setTimeout(calculateHargaModal, 100); // Delay to ensure DOM is fully processed
        }

        // Special fix for hamburger menu on mobile in menu_minuman.php
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            // Ensure hamburger menu is visible with high z-index
            sidebarToggle.style.zIndex = "10000";
            sidebarToggle.style.display = "flex";
            sidebarToggle.style.visibility = "visible";
            sidebarToggle.style.opacity = "1";
            
            // Check if in mobile view
            if (window.innerWidth < 1024) {
                // Add special styling to make hamburger menu easy to click
                sidebarToggle.style.position = "fixed";
                sidebarToggle.style.top = "1rem";
                sidebarToggle.style.left = "1rem";
                sidebarToggle.style.padding = "0.5rem";
                sidebarToggle.style.backgroundColor = "white";
                sidebarToggle.style.borderRadius = "0.375rem";
                sidebarToggle.style.boxShadow = "0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)";
            }
        }
        
        // Also make sure the sidebar itself has high z-index
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.style.zIndex = "9999";
            
            // Ensure compatibility with new class names
            if (window.innerWidth < 1024 && !sidebar.classList.contains('open')) {
                sidebar.classList.add('-translate-x-full');
            }
        }
    } catch (error) {
        console.error('Global error in script:', error);
    }
});

// Konfirmasi hapus menu
function confirmDelete(id, name) {
    const modal = document.getElementById('deleteModal');
    const modalContent = document.getElementById('deleteModalContent');
    const menuToDelete = document.getElementById('menuToDelete');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    menuToDelete.textContent = name;
    confirmDeleteBtn.href = `menu_minuman.php?delete=${id}`;
    
    modal.classList.remove('hidden');
    
    // Add animation
    setTimeout(() => {
        modalContent.classList.remove('scale-90', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Event listener untuk tombol batal
    document.getElementById('cancelDeleteBtn').addEventListener('click', function() {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-90', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            document.getElementById('cancelDeleteBtn').click();
        }
    });
}

// Preview gambar sebelum upload
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Jika sudah ada preview, hapus dulu
            const existingPreview = document.querySelector('.preview-container');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            // Buat elemen preview baru
            const previewContainer = document.createElement('div');
            previewContainer.className = 'preview-container mt-2';
            
            const previewText = document.createElement('p');
            previewText.className = 'text-sm text-gray-600 mb-1';
            previewText.textContent = 'Preview:';
            
            const previewImg = document.createElement('img');
            previewImg.src = e.target.result;
            previewImg.className = 'w-32 h-32 object-cover rounded-md';
            previewImg.alt = 'Preview';
            
            previewContainer.appendChild(previewText);
            previewContainer.appendChild(previewImg);
            
            // Tambahkan ke dalam form setelah input file
            document.getElementById('foto').parentNode.appendChild(previewContainer);
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 