<?php
// Koneksi ke database langsung
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Periksa tabel bahan_baku
echo "=== Memeriksa tabel bahan_baku ===\n";
$query = "DESCRIBE bahan_baku";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Field'] == 'status') {
        echo "Kolom status: {$row['Type']} - {$row['Null']} - {$row['Default']} - {$row['Extra']}\n";
    }
}

// 2. Periksa pesanan_barang
echo "\n=== Memeriksa tabel pesanan_barang ===\n";
$query = "DESCRIBE pesanan_barang";
$result = mysqli_query($conn, $query);
$pesanan_barang_exists = mysqli_num_rows($result) > 0;

if ($pesanan_barang_exists) {
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['Field'] == 'status') {
            echo "Kolom status: {$row['Type']} - {$row['Null']} - {$row['Default']} - {$row['Extra']}\n";
        }
    }
} else {
    echo "Tabel pesanan_barang tidak ditemukan\n";
}

// 3. Periksa nilai status yang digunakan
echo "\n=== Nilai status yang digunakan di tabel bahan_baku ===\n";
$query = "SELECT DISTINCT status FROM bahan_baku";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "- '{$row['status']}'\n";
}

// 4. Coba update kolom status secara langsung
echo "\n=== Mencoba update status di bahan_baku ===\n";
$query = "UPDATE bahan_baku SET status = 'pending' WHERE id_bahan_baku = 1";
if (mysqli_query($conn, $query)) {
    echo "Berhasil update status ke 'pending'\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

$query = "UPDATE bahan_baku SET status = 'approved' WHERE id_bahan_baku = 1";
if (mysqli_query($conn, $query)) {
    echo "Berhasil update status ke 'approved'\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

$query = "UPDATE bahan_baku SET status = 'retur' WHERE id_bahan_baku = 1";
if (mysqli_query($conn, $query)) {
    echo "Berhasil update status ke 'retur'\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// 5. Coba insert dengan status yang berbeda
echo "\n=== Mencoba insert dengan status yang berbeda ===\n";
$query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input) 
          VALUES (1, 5, 1, 5000.00, 25000.00, 'kitchen', 22, 'pending', NOW())";
if (mysqli_query($conn, $query)) {
    echo "Berhasil insert dengan status 'pending'\n";
    $id1 = mysqli_insert_id($conn);
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

$query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input) 
          VALUES (1, 5, 1, 5000.00, 25000.00, 'kitchen', 22, 'approved', NOW())";
if (mysqli_query($conn, $query)) {
    echo "Berhasil insert dengan status 'approved'\n";
    $id2 = mysqli_insert_id($conn);
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

$query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input) 
          VALUES (1, 5, 1, 5000.00, 25000.00, 'kitchen', 22, 'retur', NOW())";
if (mysqli_query($conn, $query)) {
    echo "Berhasil insert dengan status 'retur'\n";
    $id3 = mysqli_insert_id($conn);
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// 6. Hapus data test
echo "\n=== Membersihkan data test ===\n";
if (isset($id1)) {
    $query = "DELETE FROM bahan_baku WHERE id_bahan_baku = $id1";
    mysqli_query($conn, $query);
    echo "Deleted id $id1\n";
}
if (isset($id2)) {
    $query = "DELETE FROM bahan_baku WHERE id_bahan_baku = $id2";
    mysqli_query($conn, $query);
    echo "Deleted id $id2\n";
}
if (isset($id3)) {
    $query = "DELETE FROM bahan_baku WHERE id_bahan_baku = $id3";
    mysqli_query($conn, $query);
    echo "Deleted id $id3\n";
}

// 7. Periksa laporan_masuk
echo "\n=== Memeriksa tabel laporan_masuk ===\n";
$query = "DESCRIBE laporan_masuk";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Field'] == 'status') {
        echo "Kolom status: {$row['Type']} - {$row['Null']} - {$row['Default']} - {$row['Extra']}\n";
    }
}

// 8. Periksa nilai status yang digunakan di laporan_masuk
echo "\n=== Nilai status yang digunakan di tabel laporan_masuk ===\n";
$query = "SELECT DISTINCT status FROM laporan_masuk";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "- '{$row['status']}'\n";
}

// 9. Coba insert dengan prepared statement
echo "\n=== Mencoba insert dengan prepared statement ===\n";
$query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input, id_pesanan) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    $id_barang = 1;
    $qty = 5;
    $periode = 1;
    $harga_satuan = 5000.00;
    $total = 25000.00;
    $lokasi = 'kitchen';
    $id_user = 22;
    $status = 'pending';
    $id_pesanan = null;
    
    mysqli_stmt_bind_param($stmt, "iiiddsssi", 
                          $id_barang, 
                          $qty, 
                          $periode,
                          $harga_satuan,
                          $total,
                          $lokasi,
                          $id_user,
                          $status,
                          $id_pesanan);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Insert berhasil dengan prepared statement\n";
        $id4 = mysqli_insert_id($conn);
        echo "ID bahan_baku baru: $id4\n";
        
        // Hapus data test
        $query = "DELETE FROM bahan_baku WHERE id_bahan_baku = $id4";
        mysqli_query($conn, $query);
        echo "Deleted id $id4\n";
    } else {
        echo "Error prepared statement: " . mysqli_stmt_error($stmt) . "\n";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn) . "\n";
}

// Tutup koneksi
mysqli_close($conn);

echo "\n=== Selesai ===\n";
echo "Silakan periksa hasil di atas untuk menemukan masalah\n";
?> 