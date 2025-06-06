<?php
// Koneksi ke database
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Periksa apakah tabel retur_barang ada
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'retur_barang'");
if (mysqli_num_rows($table_check) > 0) {
    echo "Tabel retur_barang ditemukan.\n\n";
    
    // Periksa struktur tabel
    echo "Struktur Tabel:\n";
    $structure = mysqli_query($conn, "DESCRIBE retur_barang");
    while ($row = mysqli_fetch_assoc($structure)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']} - {$row['Extra']}\n";
    }
    
    // Periksa data dalam tabel
    echo "\nData dalam Tabel:\n";
    $data = mysqli_query($conn, "SELECT * FROM retur_barang");
    if (mysqli_num_rows($data) > 0) {
        while ($row = mysqli_fetch_assoc($data)) {
            echo "ID: {$row['id_retur']}, ID Barang: {$row['id_barang']}, Qty: {$row['qty_retur']}, Tanggal: {$row['tanggal_retur']}\n";
            echo "Alasan: " . (empty($row['alasan_retur']) ? 'NULL' : $row['alasan_retur']) . "\n";
            echo "User: {$row['id_user']}, Supplier: " . (empty($row['supplier']) ? 'NULL' : $row['supplier']) . "\n";
            echo "Harga: {$row['harga_satuan']}, Total: {$row['total']}, Periode: {$row['periode']}\n";
            echo "ID Pesanan: " . (empty($row['id_pesanan']) ? 'NULL' : $row['id_pesanan']) . "\n\n";
        }
    } else {
        echo "Tidak ada data di tabel retur_barang\n";
    }
} else {
    echo "Tabel retur_barang tidak ditemukan!\n";
}

// Periksa data di bahan_baku dengan status retur
echo "\nData Bahan Baku dengan Status Retur:\n";
$retur_data = mysqli_query($conn, "SELECT bb.*, b.nama_barang FROM bahan_baku bb JOIN barang b ON bb.id_barang = b.id_barang WHERE bb.status = 'retur'");

if (mysqli_num_rows($retur_data) > 0) {
    while ($row = mysqli_fetch_assoc($retur_data)) {
        echo "ID: {$row['id_bahan_baku']}, ID Barang: {$row['id_barang']}, Nama: {$row['nama_barang']}, Qty: {$row['qty']}, Status: {$row['status']}\n";
        echo "Jumlah Retur: " . (isset($row['jumlah_retur']) ? $row['jumlah_retur'] : 'NULL') . ", Jumlah Masuk: " . (isset($row['jumlah_masuk']) ? $row['jumlah_masuk'] : 'NULL') . "\n";
        echo "Catatan Retur: " . (isset($row['catatan_retur']) ? $row['catatan_retur'] : 'NULL') . "\n";
        echo "ID Pesanan: " . (isset($row['id_pesanan']) && $row['id_pesanan'] ? $row['id_pesanan'] : 'NULL') . "\n\n";
    }
} else {
    echo "Tidak ada data bahan_baku dengan status retur.\n";
}

mysqli_close($conn);
?> 