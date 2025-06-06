<?php
// Set header untuk output plaintext
header('Content-Type: text/plain');

// Lokasi file error log PHP
$error_log_file = 'C:/xampp/php/logs/php_error_log'; // Sesuaikan dengan lokasi error log di server Anda

echo "=== Pemeriksaan Error Log PHP ===\n\n";

if (file_exists($error_log_file)) {
    // Baca 100 baris terakhir dari file error log
    $lines = [];
    $fp = fopen($error_log_file, 'r');
    
    // Cari posisi 100 baris dari akhir file
    $pos = -2;
    $count = 0;
    $line = '';
    
    while ($count < 100 && fseek($fp, $pos, SEEK_END) !== -1) {
        $char = fgetc($fp);
        if ($char === "\n") {
            $lines[] = $line;
            $line = '';
            $count++;
        } else {
            $line = $char . $line;
        }
        $pos--;
    }
    
    fclose($fp);
    
    // Tampilkan error log terkait proses retur
    echo "100 baris terakhir dari error log yang terkait dengan proses retur:\n\n";
    $found = false;
    
    foreach ($lines as $line) {
        if (strpos($line, 'retur') !== false || 
            strpos($line, 'Retur') !== false || 
            strpos($line, 'bahan_baku') !== false ||
            strpos($line, 'mysqli') !== false) {
            echo $line . "\n";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "Tidak ditemukan error yang terkait dengan proses retur.\n";
    }
} else {
    echo "File error log tidak ditemukan di: $error_log_file\n";
    echo "Silakan periksa lokasi file error log PHP di server Anda.\n";
}

// Cek error terbaru dari PHP
echo "\n=== Error PHP Terbaru ===\n\n";
$errors = error_get_last();
if ($errors) {
    print_r($errors);
} else {
    echo "Tidak ada error PHP terbaru.\n";
}

// Cek tabel retur_barang
echo "\n=== Data di Tabel retur_barang ===\n\n";
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if ($conn) {
    $query = "SELECT * FROM retur_barang ORDER BY id_retur DESC LIMIT 10";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "ID: {$row['id_retur']}, ID Barang: {$row['id_barang']}, Qty: {$row['qty_retur']}, Tanggal: {$row['tanggal_retur']}\n";
            echo "Alasan: " . (empty($row['alasan_retur']) ? 'NULL' : $row['alasan_retur']) . "\n";
            echo "User: {$row['id_user']}, Supplier: " . (empty($row['supplier']) ? 'NULL' : $row['supplier']) . "\n";
            echo "Harga: {$row['harga_satuan']}, Total: {$row['total']}, Periode: {$row['periode']}\n";
            echo "ID Pesanan: " . (empty($row['id_pesanan']) ? 'NULL' : $row['id_pesanan']) . "\n\n";
        }
    } else {
        echo "Tidak ada data di tabel retur_barang atau terjadi error: " . mysqli_error($conn) . "\n";
    }
    
    mysqli_close($conn);
} else {
    echo "Koneksi database gagal: " . mysqli_connect_error() . "\n";
}
?> 