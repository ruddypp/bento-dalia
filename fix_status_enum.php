<?php
// Koneksi ke database langsung
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "=== Memeriksa dan memperbaiki masalah status enum ===\n\n";

// 1. Periksa tabel bahan_baku
echo "1. Memeriksa tabel bahan_baku...\n";
$query = "DESCRIBE bahan_baku";
$result = mysqli_query($conn, $query);
$bahan_baku_status_type = "";
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Field'] == 'status') {
        $bahan_baku_status_type = $row['Type'];
        echo "Kolom status: {$row['Type']} - {$row['Null']} - {$row['Default']}\n";
    }
}

// Jika status masih enum, ubah menjadi VARCHAR
if (strpos($bahan_baku_status_type, 'enum') !== false) {
    echo "Mengubah tipe data status di bahan_baku menjadi VARCHAR(50)...\n";
    $query = "ALTER TABLE bahan_baku MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'";
    if (mysqli_query($conn, $query)) {
        echo "Berhasil mengubah tipe data status di bahan_baku menjadi VARCHAR(50)\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}

// 2. Periksa tabel pesanan_barang
echo "\n2. Memeriksa tabel pesanan_barang...\n";
$query = "DESCRIBE pesanan_barang";
$result = mysqli_query($conn, $query);
$pesanan_status_type = "";
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Field'] == 'status') {
        $pesanan_status_type = $row['Type'];
        echo "Kolom status: {$row['Type']} - {$row['Null']} - {$row['Default']}\n";
    }
}

// 3. Periksa nilai status yang digunakan di pesanan_barang
echo "\n3. Nilai status yang digunakan di pesanan_barang...\n";
$query = "SELECT DISTINCT status FROM pesanan_barang";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "- '{$row['status']}'\n";
}

// 4. Ubah nilai 'selesai' menjadi 'approved' di pesanan_barang jika ada
echo "\n4. Mengubah nilai 'selesai' menjadi 'approved' di pesanan_barang...\n";
$query = "UPDATE pesanan_barang SET status = 'approved' WHERE status = 'selesai'";
if (mysqli_query($conn, $query)) {
    $affected_rows = mysqli_affected_rows($conn);
    echo "Berhasil mengubah $affected_rows baris dengan status 'selesai' menjadi 'approved'\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// 5. Ubah nilai 'diproses' menjadi 'processed' di pesanan_barang jika ada
echo "\n5. Mengubah nilai 'diproses' menjadi 'processed' di pesanan_barang...\n";
$query = "UPDATE pesanan_barang SET status = 'processed' WHERE status = 'diproses'";
if (mysqli_query($conn, $query)) {
    $affected_rows = mysqli_affected_rows($conn);
    echo "Berhasil mengubah $affected_rows baris dengan status 'diproses' menjadi 'processed'\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// 6. Ubah nilai 'dibatalkan' menjadi 'canceled' di pesanan_barang jika ada
echo "\n6. Mengubah nilai 'dibatalkan' menjadi 'canceled' di pesanan_barang...\n";
$query = "UPDATE pesanan_barang SET status = 'canceled' WHERE status = 'dibatalkan'";
if (mysqli_query($conn, $query)) {
    $affected_rows = mysqli_affected_rows($conn);
    echo "Berhasil mengubah $affected_rows baris dengan status 'dibatalkan' menjadi 'canceled'\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// 7. Periksa apakah tabel retur_barang memiliki kolom id_pesanan
echo "\n7. Memeriksa kolom id_pesanan di tabel retur_barang...\n";
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

// 8. Periksa apakah ada nilai status yang tidak valid di bahan_baku
echo "\n8. Memeriksa nilai status yang tidak valid di bahan_baku...\n";
$query = "SELECT id_bahan_baku, status FROM bahan_baku WHERE status NOT IN ('pending', 'approved', 'retur')";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    echo "Ditemukan " . mysqli_num_rows($result) . " baris dengan status tidak valid:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- ID: {$row['id_bahan_baku']}, Status: '{$row['status']}'\n";
        
        // Ubah status yang tidak valid menjadi 'pending'
        $update_query = "UPDATE bahan_baku SET status = 'pending' WHERE id_bahan_baku = {$row['id_bahan_baku']}";
        if (mysqli_query($conn, $update_query)) {
            echo "  Berhasil mengubah status menjadi 'pending'\n";
        } else {
            echo "  Error: " . mysqli_error($conn) . "\n";
        }
    }
} else {
    echo "Tidak ditemukan nilai status yang tidak valid di bahan_baku\n";
}

// Tutup koneksi
mysqli_close($conn);

echo "\n=== Selesai ===\n";
echo "Semua perbaikan telah dilakukan. Silakan coba kembali proses retur barang.\n";
?> 