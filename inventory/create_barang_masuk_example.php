<?php
require_once 'config/database.php';
require_once 'config/functions.php';
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_barang_masuk'])) {
    $id_barang = $_POST['id_barang'] ?? '';
    $qty_masuk = $_POST['qty_masuk'] ?? '';
    $tanggal_masuk = $_POST['tanggal_masuk'] ?? date('Y-m-d');
    $id_supplier = $_POST['id_supplier'] ?? '';
    
    // Validasi input
    if (empty($id_barang)) {
        $error = "Barang harus dipilih!";
    } elseif (empty($qty_masuk) || !is_numeric($qty_masuk) || $qty_masuk <= 0) {
        $error = "Jumlah harus berupa angka positif!";
    } elseif (empty($id_supplier)) {
        $error = "Supplier harus dipilih!";
    } else {
        // Simpan data barang masuk
        $user_id = $_SESSION['user_id'];
        
        $query = "INSERT INTO barang_masuk (id_barang, qty_masuk, tanggal_masuk, id_supplier, id_user) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            $error = "Error: " . $conn->error;
        } else {
            $stmt->bind_param("idsii", $id_barang, $qty_masuk, $tanggal_masuk, $id_supplier, $user_id);
            
            if ($stmt->execute()) {
                // Update stok barang
                updateStock($id_barang, $qty_masuk, 'in');
                
                // Log aktivitas
                logActivity($user_id, "Menambahkan barang masuk: $qty_masuk unit");
                
                $message = "Barang masuk berhasil ditambahkan!";
                
                // Redirect ke halaman barang masuk
                header("Location: barang_masuk.php?success=1");
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}

// Ambil data barang untuk dropdown
$query_barang = "SELECT id_barang, nama_barang FROM barang ORDER BY nama_barang";
$result_barang = $conn->query($query_barang);

// Ambil data supplier untuk dropdown
$query_supplier = "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier";
$result_supplier = $conn->query($query_supplier);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang Masuk</title>
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/font-awesome.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container px-6 mx-auto">
        <h2 class="text-2xl font-semibold text-gray-700 my-4">
            <i class="fas fa-box-open mr-2"></i> Tambah Barang Masuk
        </h2>
        
        <div class="flex justify-between items-center mb-4">
            <nav class="text-black" aria-label="Breadcrumb">
                <ol class="list-none p-0 inline-flex">
                    <li class="flex items-center">
                        <a href="index.php" class="text-gray-500 hover:text-blue-600">Dashboard</a>
                        <svg class="fill-current w-3 h-3 mx-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                            <path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                        </svg>
                    </li>
                    <li class="flex items-center">
                        <a href="barang_masuk.php" class="text-gray-500 hover:text-blue-600">Barang Masuk</a>
                        <svg class="fill-current w-3 h-3 mx-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                            <path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                        </svg>
                    </li>
                    <li>
                        <span class="text-gray-700">Tambah Barang Masuk</span>
                    </li>
                </ol>
            </nav>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gray-50 py-3 px-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700">
                    Form Tambah Barang Masuk
                </h3>
            </div>
            
            <div class="p-4">
                <?php if (!empty($error)): ?>
                <div class="bg-red-100 border-red-500 text-red-700 border-l-4 p-4 mb-4 rounded-md">
                    <div class="flex items-center">
                        <div class="py-1">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                        </div>
                        <div>
                            <p class="font-medium"><?= $error ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($message)): ?>
                <div class="bg-green-100 border-green-500 text-green-700 border-l-4 p-4 mb-4 rounded-md">
                    <div class="flex items-center">
                        <div class="py-1">
                            <i class="fas fa-check-circle mr-2"></i>
                        </div>
                        <div>
                            <p class="font-medium"><?= $message ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label for="id_barang" class="block text-gray-700 text-sm font-medium mb-2">Nama Barang</label>
                            <select id="id_barang" name="id_barang" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">-- Pilih Barang --</option>
                                <?php while ($row = $result_barang->fetch_assoc()): ?>
                                <option value="<?= $row['id_barang'] ?>"><?= htmlspecialchars($row['nama_barang']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="qty_masuk" class="block text-gray-700 text-sm font-medium mb-2">Jumlah</label>
                            <input type="number" id="qty_masuk" name="qty_masuk" min="1" step="0.01" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="tanggal_masuk" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Masuk</label>
                            <input type="date" id="tanggal_masuk" name="tanggal_masuk" value="<?= date('Y-m-d') ?>" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="id_supplier" class="block text-gray-700 text-sm font-medium mb-2">Supplier</label>
                            <select id="id_supplier" name="id_supplier" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">-- Pilih Supplier --</option>
                                <?php while ($row = $result_supplier->fetch_assoc()): ?>
                                <option value="<?= $row['id_supplier'] ?>"><?= htmlspecialchars($row['nama_supplier']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <a href="barang_masuk.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-md transition duration-200 mr-2">
                            <i class="fas fa-times mr-2"></i> Batal
                        </a>
                        
                        <button type="submit" name="tambah_barang_masuk" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow-md transition duration-200">
                            <i class="fas fa-save mr-2"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 