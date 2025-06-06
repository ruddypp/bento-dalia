<?php
// Koneksi ke database langsung
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "=== Test 1: Direct INSERT with 'pending' ===\n";
// Coba insert ke tabel bahan_baku dengan status 'pending'
$query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input) 
          VALUES (1, 5, 1, 5000.00, 25000.00, 'kitchen', 22, 'pending', NOW())";

if (mysqli_query($conn, $query)) {
    echo "Insert berhasil dengan status 'pending'\n";
    $id = mysqli_insert_id($conn);
    echo "ID bahan_baku baru: $id\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

echo "\n=== Test 2: UPDATE to 'approved' ===\n";
// Coba update status bahan_baku yang baru saja dibuat
if (isset($id)) {
    $query = "UPDATE bahan_baku SET status = 'approved' WHERE id_bahan_baku = $id";
    
    if (mysqli_query($conn, $query)) {
        echo "Update ke 'approved' berhasil\n";
    } else {
        echo "Error update: " . mysqli_error($conn) . "\n";
    }
}

echo "\n=== Test 3: INSERT with prepared statement ===\n";
// Coba insert ke tabel bahan_baku dengan status 'pending' menggunakan prepared statement
$query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
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
    
    mysqli_stmt_bind_param($stmt, "iiiddsss", $id_barang, $qty, $periode, $harga_satuan, $total, $lokasi, $id_user, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Insert berhasil dengan prepared statement\n";
        $id2 = mysqli_insert_id($conn);
        echo "ID bahan_baku baru: $id2\n";
    } else {
        echo "Error prepared statement: " . mysqli_stmt_error($stmt) . "\n";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn) . "\n";
}

echo "\n=== Test 4: INSERT with id_pesanan ===\n";
// Coba insert dengan id_pesanan
$query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input, id_pesanan) 
          VALUES (1, 5, 1, 5000.00, 25000.00, 'kitchen', 22, 'pending', NOW(), 1)";

if (mysqli_query($conn, $query)) {
    echo "Insert berhasil dengan id_pesanan\n";
    $id3 = mysqli_insert_id($conn);
    echo "ID bahan_baku baru: $id3\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// Cleanup - hapus semua bahan_baku yang baru dibuat
echo "\n=== Cleanup ===\n";
if (isset($id)) {
    $query = "DELETE FROM bahan_baku WHERE id_bahan_baku = $id";
    if (mysqli_query($conn, $query)) {
        echo "Deleted ID: $id\n";
    }
}

if (isset($id2)) {
    $query = "DELETE FROM bahan_baku WHERE id_bahan_baku = $id2";
    if (mysqli_query($conn, $query)) {
        echo "Deleted ID: $id2\n";
    }
}

if (isset($id3)) {
    $query = "DELETE FROM bahan_baku WHERE id_bahan_baku = $id3";
    if (mysqli_query($conn, $query)) {
        echo "Deleted ID: $id3\n";
    }
}

// Tutup koneksi
mysqli_close($conn);
?> 