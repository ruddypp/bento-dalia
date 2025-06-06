<?php
$pageTitle = "Data Toko";
require_once 'includes/header.php';
checkLogin();

// Verifikasi akses - hanya admin
if ($_SESSION['user_role'] != 'admin') {
    setAlert("error", "Anda tidak memiliki akses ke halaman ini!");
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_store'])) {
    $nama_toko = sanitize($_POST['nama_toko']);
    $alamat = sanitize($_POST['alamat']);
    $kontak = sanitize($_POST['kontak']);
    $email = sanitize($_POST['email']);
    $website = sanitize($_POST['website']);
    $deskripsi = sanitize($_POST['deskripsi']);
    
    // Check if store data exists
    $check_query = "SELECT COUNT(*) as total FROM data_toko";
    $check_result = mysqli_query($conn, $check_query);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['total'] > 0) {
        // Update existing data
        $query = "UPDATE data_toko SET 
                 nama_toko = ?, 
                 alamat = ?, 
                 kontak = ?, 
                 email = ?, 
                 website = ?, 
                 deskripsi = ? 
                 WHERE id_toko = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssss", $nama_toko, $alamat, $kontak, $email, $website, $deskripsi);
    } else {
        // Insert new data
        $query = "INSERT INTO data_toko (nama_toko, alamat, kontak, email, website, deskripsi) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssss", $nama_toko, $alamat, $kontak, $email, $website, $deskripsi);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        logActivity($_SESSION['user_id'], "Memperbarui data toko");
        setAlert("success", "Data toko berhasil diperbarui!");
    } else {
        setAlert("error", "Gagal memperbarui data toko: " . mysqli_error($conn));
    }
    
    // Upload logo if provided
    if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
        $target_dir = "assets/img/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $target_file = $target_dir . "store_logo." . $file_extension;
        
        // Check file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_extension, $allowed_types)) {
            // Check file size (max 2MB)
            if ($_FILES['logo']['size'] <= 2000000) {
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                    // Update logo path in database
                    $logo_path = $target_file;
                    $query = "UPDATE data_toko SET logo = ? WHERE id_toko = 1";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "s", $logo_path);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        logActivity($_SESSION['user_id'], "Mengupload logo toko baru");
                        setAlert("success", "Logo toko berhasil diperbarui!");
                    } else {
                        setAlert("error", "Gagal memperbarui path logo: " . mysqli_error($conn));
                    }
                } else {
                    setAlert("error", "Gagal mengupload file logo.");
                }
            } else {
                setAlert("error", "Ukuran file logo terlalu besar. Maksimal 2MB.");
            }
        } else {
            setAlert("error", "Format file logo tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.");
        }
    }
    
    header("Location: data_toko.php");
    exit();
}

// Get store data
$query = "SELECT * FROM data_toko LIMIT 1";
$result = mysqli_query($conn, $query);
$store_data = mysqli_fetch_assoc($result);

// If no data exists, initialize with empty values
if (!$store_data) {
    $store_data = [
        'nama_toko' => '',
        'alamat' => '',
        'kontak' => '',
        'email' => '',
        'website' => '',
        'deskripsi' => '',
        'logo' => ''
    ];
}
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-store text-blue-500 mr-2"></i> Manajemen Data Toko
        </h2>
    </div>
    
    <form method="POST" action="" enctype="multipart/form-data" class="mt-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Store Profile Section -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="text-md font-medium text-gray-800 mb-3 pb-2 border-b border-gray-200">Profil Toko</h3>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="nama_toko">
                        Nama Toko <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nama_toko" name="nama_toko" required 
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           value="<?= $store_data['nama_toko'] ?>" placeholder="Masukkan nama toko">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="alamat">
                        Alamat <span class="text-red-500">*</span>
                    </label>
                    <textarea id="alamat" name="alamat" required rows="3" 
                              class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Masukkan alamat lengkap"><?= $store_data['alamat'] ?></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="kontak">
                        Nomor Telepon <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="kontak" name="kontak" required 
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           value="<?= $store_data['kontak'] ?>" placeholder="Contoh: 021-1234567">
                </div>
            </div>
            
            <!-- Additional Information Section -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="text-md font-medium text-gray-800 mb-3 pb-2 border-b border-gray-200">Informasi Tambahan</h3>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                        Email
                    </label>
                    <input type="email" id="email" name="email" 
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           value="<?= $store_data['email'] ?? '' ?>" placeholder="Contoh: info@tokosaya.com">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="website">
                        Website
                    </label>
                    <input type="url" id="website" name="website" 
                           class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           value="<?= $store_data['website'] ?? '' ?>" placeholder="Contoh: https://www.tokosaya.com">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="deskripsi">
                        Deskripsi Toko
                    </label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" 
                              class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Deskripsi singkat tentang toko"><?= $store_data['deskripsi'] ?? '' ?></textarea>
                </div>
            </div>
            
            <!-- Logo Upload Section -->
            <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="text-md font-medium text-gray-800 mb-3 pb-2 border-b border-gray-200">Logo Toko</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="logo">
                            Upload Logo Baru
                        </label>
                        <input type="file" id="logo" name="logo" accept="image/*" 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, JPEG, PNG, GIF. Maks. ukuran: 2MB</p>
                    </div>
                    
                    <div class="flex justify-center items-center">
                        <?php if (!empty($store_data['logo']) && file_exists($store_data['logo'])): ?>
                            <div class="text-center">
                                <img src="<?= $store_data['logo'] ?>" alt="Logo Toko" class="max-h-28 mx-auto border p-2 rounded shadow-sm">
                                <p class="text-xs text-gray-500 mt-1">Logo saat ini</p>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-gray-500">
                                <div class="border border-dashed rounded p-8 flex items-center justify-center">
                                    <i class="fas fa-store text-3xl"></i>
                                </div>
                                <p class="text-xs mt-1">Belum ada logo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-5 flex justify-end">
            <button type="submit" name="update_store" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                <i class="fas fa-save mr-2"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<!-- Adding missing fields to data_toko table if it doesn't have the required structure -->
<?php
// Check if email, website, deskripsi, and logo columns exist in data_toko table
$result = mysqli_query($conn, "SHOW COLUMNS FROM data_toko LIKE 'email'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "ALTER TABLE data_toko ADD email VARCHAR(100) NULL");
}

$result = mysqli_query($conn, "SHOW COLUMNS FROM data_toko LIKE 'website'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "ALTER TABLE data_toko ADD website VARCHAR(100) NULL");
}

$result = mysqli_query($conn, "SHOW COLUMNS FROM data_toko LIKE 'deskripsi'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "ALTER TABLE data_toko ADD deskripsi TEXT NULL");
}

$result = mysqli_query($conn, "SHOW COLUMNS FROM data_toko LIKE 'logo'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "ALTER TABLE data_toko ADD logo VARCHAR(255) NULL");
}
?>

<?php require_once 'includes/footer.php'; ?> 