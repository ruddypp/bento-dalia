<?php
// Database connection
require_once 'config/database.php';

// Read SQL file
$sql_file = file_get_contents('database/lost_barang.sql');

// Split SQL commands
$commands = explode(';', $sql_file);

// Execute each command
$success = true;
$error_messages = [];
$success_messages = [];

// Periksa apakah tabel lost_barang sudah ada
$check_table_query = "SHOW TABLES LIKE 'lost_barang'";
$check_result = mysqli_query($conn, $check_table_query);
$table_exists = mysqli_num_rows($check_result) > 0;

if ($table_exists) {
    $success_messages[] = "Tabel lost_barang sudah ada sebelumnya.";
} else {
    foreach ($commands as $command) {
        $command = trim($command);
        if (!empty($command)) {
            if (!mysqli_query($conn, $command)) {
                $success = false;
                $error_messages[] = "Error executing: " . mysqli_error($conn) . " pada query: " . substr($command, 0, 100) . "...";
            } else {
                $success_messages[] = "Berhasil mengeksekusi query: " . substr($command, 0, 100) . "...";
            }
        }
    }
}

// Check if directory exists, if not create it
$upload_dir = 'uploads/lost';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        $success = false;
        $error_messages[] = "Gagal membuat direktori: " . $upload_dir;
    } else {
        $success_messages[] = "Berhasil membuat direktori: " . $upload_dir;
    }
} else {
    $success_messages[] = "Direktori " . $upload_dir . " sudah ada sebelumnya.";
}

// Check if jenis column exists in stok_opname
$check_column_query = "SHOW COLUMNS FROM stok_opname LIKE 'jenis'";
$check_column_result = mysqli_query($conn, $check_column_query);
$column_exists = mysqli_num_rows($check_column_result) > 0;

if ($column_exists) {
    $success_messages[] = "Kolom 'jenis' pada tabel stok_opname sudah ada sebelumnya.";
} else {
    $add_column_query = "ALTER TABLE `stok_opname` ADD COLUMN `jenis` enum('opname','kerugian') NOT NULL DEFAULT 'opname' AFTER `selisih`";
    if (!mysqli_query($conn, $add_column_query)) {
        $success = false;
        $error_messages[] = "Gagal menambahkan kolom 'jenis' pada tabel stok_opname: " . mysqli_error($conn);
    } else {
        $success_messages[] = "Berhasil menambahkan kolom 'jenis' pada tabel stok_opname.";
    }
}

// Output result
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Lost Barang Module</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-lg">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <?php if ($success): ?>
                    <i class="fas fa-check-circle text-green-500 mr-2"></i> Instalasi Berhasil
                <?php else: ?>
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i> Instalasi Gagal
                <?php endif; ?>
            </h1>
        </div>
        
        <div class="mb-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                    <p>Modul Barang Lost berhasil diinstal. Tabel dan struktur yang diperlukan telah dibuat.</p>
                </div>
                <div class="mb-4">
                    <h3 class="font-semibold mb-2">Detail Instalasi:</h3>
                    <ul class="list-disc pl-5 text-gray-600">
                        <?php foreach ($success_messages as $message): ?>
                            <li><?= $message ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4">
                    <p>Anda sekarang dapat menggunakan fitur Barang Lost.</p>
                </div>
            <?php else: ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    <p>Terjadi kesalahan saat menginstal modul Barang Lost:</p>
                </div>
                <ul class="list-disc pl-5 text-red-600 mb-4">
                    <?php foreach ($error_messages as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                    <p>Beberapa bagian mungkin berhasil diinstal:</p>
                </div>
                <ul class="list-disc pl-5 text-gray-600 mt-2">
                    <?php foreach ($success_messages as $message): ?>
                        <li><?= $message ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="flex justify-center space-x-4">
            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
                <i class="fas fa-home mr-2"></i> Dashboard
            </a>
            <a href="barang_lost.php" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                <i class="fas fa-exclamation-triangle mr-2"></i> Barang Lost
            </a>
        </div>
    </div>
</body>
</html> 