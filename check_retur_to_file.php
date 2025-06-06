<?php
// Koneksi ke database
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    file_put_contents('retur_check_result.txt', "Connection failed: " . mysqli_connect_error());
    exit;
}

$output = "";

// Periksa apakah tabel retur_barang ada
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'retur_barang'");
if (mysqli_num_rows($table_check) > 0) {
    $output .= "Tabel retur_barang ditemukan.\n\n";
    
    // Periksa struktur tabel
    $output .= "Struktur Tabel:\n";
    $structure = mysqli_query($conn, "DESCRIBE retur_barang");
    while ($row = mysqli_fetch_assoc($structure)) {
        $output .= "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']} - {$row['Extra']}\n";
    }
    
    // Periksa data dalam tabel
    $output .= "\nData dalam Tabel:\n";
    $data = mysqli_query($conn, "SELECT * FROM retur_barang");
    if (mysqli_num_rows($data) > 0) {
        while ($row = mysqli_fetch_assoc($data)) {
            $output .= "ID: {$row['id_retur']}, ID Barang: {$row['id_barang']}, Qty: {$row['qty_retur']}, Tanggal: {$row['tanggal_retur']}\n";
            $output .= "Alasan: " . (empty($row['alasan_retur']) ? 'NULL' : $row['alasan_retur']) . "\n";
            $output .= "User: {$row['id_user']}, Supplier: " . (empty($row['supplier']) ? 'NULL' : $row['supplier']) . "\n";
            $output .= "Harga: {$row['harga_satuan']}, Total: {$row['total']}, Periode: {$row['periode']}\n";
            $output .= "ID Pesanan: " . (empty($row['id_pesanan']) ? 'NULL' : $row['id_pesanan']) . "\n\n";
        }
    } else {
        $output .= "Tidak ada data di tabel retur_barang\n";
    }
} else {
    $output .= "Tabel retur_barang tidak ditemukan!\n";
}

// Periksa data di bahan_baku dengan status retur
$output .= "\nData Bahan Baku dengan Status Retur:\n";
$retur_data = mysqli_query($conn, "SELECT bb.*, b.nama_barang FROM bahan_baku bb JOIN barang b ON bb.id_barang = b.id_barang WHERE bb.status = 'retur'");

if (mysqli_num_rows($retur_data) > 0) {
    while ($row = mysqli_fetch_assoc($retur_data)) {
        $output .= "ID: {$row['id_bahan_baku']}, ID Barang: {$row['id_barang']}, Nama: {$row['nama_barang']}, Qty: {$row['qty']}, Status: {$row['status']}\n";
        $output .= "Jumlah Retur: " . (isset($row['jumlah_retur']) ? $row['jumlah_retur'] : 'NULL') . ", Jumlah Masuk: " . (isset($row['jumlah_masuk']) ? $row['jumlah_masuk'] : 'NULL') . "\n";
        $output .= "Catatan Retur: " . (isset($row['catatan_retur']) ? $row['catatan_retur'] : 'NULL') . "\n";
        $output .= "ID Pesanan: " . (isset($row['id_pesanan']) && $row['id_pesanan'] ? $row['id_pesanan'] : 'NULL') . "\n\n";
    }
} else {
    $output .= "Tidak ada data bahan_baku dengan status retur.\n";
}

// Simpan output ke file
file_put_contents('retur_check_result.txt', $output);

echo "Pemeriksaan selesai. Hasil disimpan di file retur_check_result.txt";

mysqli_close($conn);
?> 