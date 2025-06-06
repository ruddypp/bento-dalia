<?php
// Koneksi ke database langsung
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Periksa tabel pesanan_barang
echo "=== Memeriksa tabel pesanan_barang ===\n";
$query = "DESCRIBE pesanan_barang";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Default']} - {$row['Extra']}\n";
}

// 2. Periksa tabel laporan_masuk
echo "\n=== Memeriksa tabel laporan_masuk ===\n";
$query = "DESCRIBE laporan_masuk";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Default']} - {$row['Extra']}\n";
}

// 3. Periksa tabel retur_barang
echo "\n=== Memeriksa tabel retur_barang ===\n";
$query = "DESCRIBE retur_barang";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Default']} - {$row['Extra']}\n";
    }
} else {
    echo "Tabel retur_barang tidak ditemukan\n";
}

// 4. Periksa kode di retur_barang.php yang mungkin menyebabkan error
echo "\n=== Memeriksa kode di retur_barang.php ===\n";
$file_content = file_get_contents('retur_barang.php');
if ($file_content) {
    // Cari bagian kode yang mungkin menyebabkan error
    if (preg_match('/INSERT INTO.*retur_barang.*VALUES/s', $file_content, $matches)) {
        echo "Query insert retur_barang ditemukan:\n";
        echo substr($matches[0], 0, 200) . "...\n";
    } else {
        echo "Query insert retur_barang tidak ditemukan\n";
    }
    
    // Cari binding parameter yang mungkin salah
    if (preg_match('/mysqli_stmt_bind_param.*retur_barang/s', $file_content, $matches)) {
        echo "\nBinding parameter retur_barang ditemukan:\n";
        echo substr($matches[0], 0, 200) . "...\n";
    } else {
        echo "\nBinding parameter retur_barang tidak ditemukan\n";
    }
} else {
    echo "File retur_barang.php tidak ditemukan\n";
}

// 5. Periksa kode di bahan_baku.php yang terkait dengan retur
echo "\n=== Memeriksa kode retur di bahan_baku.php ===\n";
$file_content = file_get_contents('bahan_baku.php');
if ($file_content) {
    // Cari bagian kode yang terkait dengan retur
    if (preg_match('/retur_bahan_baku.*status/s', $file_content, $matches)) {
        echo "Kode retur ditemukan:\n";
        echo substr($matches[0], 0, 200) . "...\n";
    } else {
        echo "Kode retur tidak ditemukan\n";
    }
}

// 6. Coba update status di pesanan_barang
echo "\n=== Mencoba update status di pesanan_barang ===\n";
$query = "SELECT id_pesanan FROM pesanan_barang LIMIT 1";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $id_pesanan = $row['id_pesanan'];
    
    // Coba update dengan status yang valid
    $query = "UPDATE pesanan_barang SET status = 'pending' WHERE id_pesanan = $id_pesanan";
    if (mysqli_query($conn, $query)) {
        echo "Berhasil update status pesanan_barang ke 'pending'\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
    
    $query = "UPDATE pesanan_barang SET status = 'processed' WHERE id_pesanan = $id_pesanan";
    if (mysqli_query($conn, $query)) {
        echo "Berhasil update status pesanan_barang ke 'processed'\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
    
    // Coba update dengan status yang tidak valid
    $query = "UPDATE pesanan_barang SET status = 'selesai' WHERE id_pesanan = $id_pesanan";
    if (mysqli_query($conn, $query)) {
        echo "Berhasil update status pesanan_barang ke 'selesai'\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Tidak ada data di tabel pesanan_barang\n";
}

// 7. Periksa apakah tabel retur_barang memiliki kolom id_pesanan
echo "\n=== Memeriksa kolom id_pesanan di tabel retur_barang ===\n";
$query = "SHOW COLUMNS FROM retur_barang LIKE 'id_pesanan'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    echo "Kolom id_pesanan ditemukan di tabel retur_barang\n";
} else {
    echo "Kolom id_pesanan tidak ditemukan di tabel retur_barang\n";
    
    // Tambahkan kolom id_pesanan jika belum ada
    $query = "ALTER TABLE retur_barang ADD COLUMN id_pesanan INT DEFAULT NULL";
    if (mysqli_query($conn, $query)) {
        echo "Berhasil menambahkan kolom id_pesanan ke tabel retur_barang\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}

// Tutup koneksi
mysqli_close($conn);

echo "\n=== Selesai ===\n";
?> 