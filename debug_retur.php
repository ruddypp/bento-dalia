<?php
// Koneksi ke database langsung
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Periksa data di tabel retur_barang
echo "=== Data di tabel retur_barang ===\n\n";
$query = "SELECT * FROM retur_barang";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID Retur: {$row['id_retur']}, ID Barang: {$row['id_barang']}, Qty Retur: {$row['qty_retur']}, Tanggal: {$row['tanggal_retur']}\n";
        echo "Alasan: {$row['alasan_retur']}, Supplier: " . ($row['supplier'] ? $row['supplier'] : 'NULL') . "\n";
        echo "Harga Satuan: {$row['harga_satuan']}, Total: {$row['total']}, Periode: {$row['periode']}\n";
        echo "ID Pesanan: " . (isset($row['id_pesanan']) && $row['id_pesanan'] ? $row['id_pesanan'] : 'NULL') . "\n\n";
    }
} else {
    echo "Tidak ada data di tabel retur_barang\n\n";
}

// Periksa data di tabel bahan_baku dengan status retur
echo "=== Data di tabel bahan_baku dengan status retur ===\n\n";
$query = "SELECT * FROM bahan_baku WHERE status = 'retur'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID Bahan Baku: {$row['id_bahan_baku']}, ID Barang: {$row['id_barang']}, Qty: {$row['qty']}, Status: {$row['status']}\n";
        echo "Jumlah Retur: " . (isset($row['jumlah_retur']) ? $row['jumlah_retur'] : 'NULL') . ", Jumlah Masuk: " . (isset($row['jumlah_masuk']) ? $row['jumlah_masuk'] : 'NULL') . "\n";
        echo "Catatan Retur: " . (isset($row['catatan_retur']) ? $row['catatan_retur'] : 'NULL') . "\n";
        echo "ID Pesanan: " . (isset($row['id_pesanan']) && $row['id_pesanan'] ? $row['id_pesanan'] : 'NULL') . "\n\n";
    }
} else {
    echo "Tidak ada data di tabel bahan_baku dengan status retur\n\n";
}

// Simulasi proses retur
echo "=== Simulasi proses retur bahan_baku ===\n\n";

// 1. Ambil data bahan_baku untuk diretur (gunakan ID 10 sebagai contoh)
$id_bahan_baku = 10; // Ganti dengan ID yang sesuai
echo "1. Mengambil data bahan_baku dengan ID $id_bahan_baku...\n";
$query = "SELECT * FROM bahan_baku WHERE id_bahan_baku = $id_bahan_baku";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $bahan_baku = mysqli_fetch_assoc($result);
    echo "Data ditemukan: ID Barang = {$bahan_baku['id_barang']}, Qty = {$bahan_baku['qty']}, Status = {$bahan_baku['status']}\n";
    
    // 2. Simulasi retur sebagian
    $qty_retur = floor($bahan_baku['qty'] / 2); // Retur setengah dari qty
    $remaining_qty = $bahan_baku['qty'] - $qty_retur;
    $harga_satuan = $bahan_baku['harga_satuan'];
    $total_retur = $qty_retur * $harga_satuan;
    $remaining_total = $remaining_qty * $harga_satuan;
    
    echo "\n2. Simulasi retur sebagian (qty_retur = $qty_retur, remaining_qty = $remaining_qty)...\n";
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 3. Insert data ke retur_barang
        echo "\n3. Insert data ke retur_barang...\n";
        $retur_query = "INSERT INTO retur_barang (id_barang, qty_retur, tanggal_retur, alasan_retur, id_user, supplier, harga_satuan, total, periode, id_pesanan) 
                        VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
        $retur_stmt = mysqli_prepare($conn, $retur_query);
        
        if (!$retur_stmt) {
            throw new Exception("Error preparing retur statement: " . mysqli_error($conn));
        }
        
        $id_user = 1; // Gunakan ID user 1 untuk simulasi
        $alasan_retur = "Simulasi retur untuk debug";
        $supplier_value = ''; // String kosong untuk supplier
        
        mysqli_stmt_bind_param($retur_stmt, "iisisidii", 
                              $bahan_baku['id_barang'], 
                              $qty_retur, 
                              $alasan_retur,
                              $id_user,
                              $supplier_value,
                              $harga_satuan,
                              $total_retur,
                              $bahan_baku['periode'],
                              $bahan_baku['id_pesanan']);
        
        if (!mysqli_stmt_execute($retur_stmt)) {
            throw new Exception("Error executing retur statement: " . mysqli_stmt_error($retur_stmt));
        }
        
        $id_retur = mysqli_insert_id($conn);
        echo "Berhasil insert data ke retur_barang dengan ID: $id_retur\n";
        mysqli_stmt_close($retur_stmt);
        
        // 4. Insert data sisa ke bahan_baku baru
        echo "\n4. Insert data sisa ke bahan_baku baru...\n";
        $remaining_query = "INSERT INTO bahan_baku (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input, id_pesanan) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)";
        $remaining_stmt = mysqli_prepare($conn, $remaining_query);
        
        if (!$remaining_stmt) {
            throw new Exception("Error preparing statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($remaining_stmt, "iiiddsii", 
                              $bahan_baku['id_barang'], 
                              $remaining_qty, 
                              $bahan_baku['periode'],
                              $harga_satuan,
                              $remaining_total,
                              $bahan_baku['lokasi'],
                              $id_user,
                              $bahan_baku['id_pesanan']);
        
        if (!mysqli_stmt_execute($remaining_stmt)) {
            throw new Exception("Error executing statement: " . mysqli_stmt_error($remaining_stmt));
        }
        
        $new_bahan_baku_id = mysqli_insert_id($conn);
        echo "Berhasil insert data sisa dengan ID baru: $new_bahan_baku_id\n";
        mysqli_stmt_close($remaining_stmt);
        
        // 5. Update bahan_baku asli menjadi status retur
        echo "\n5. Update bahan_baku asli menjadi status retur...\n";
        $update_query = "UPDATE bahan_baku SET status = 'retur', qty = ?, total = ?, jumlah_retur = ?, jumlah_masuk = ?, catatan_retur = ? WHERE id_bahan_baku = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        
        if (!$update_stmt) {
            throw new Exception("Error preparing update statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($update_stmt, "idiisi", $qty_retur, $total_retur, $qty_retur, $remaining_qty, $alasan_retur, $id_bahan_baku);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Error executing update statement: " . mysqli_stmt_error($update_stmt));
        }
        
        echo "Berhasil update bahan_baku menjadi status retur\n";
        mysqli_stmt_close($update_stmt);
        
        // 6. Rollback transaction (untuk simulasi)
        echo "\n6. Rollback transaction (untuk simulasi)...\n";
        mysqli_rollback($conn);
        echo "Berhasil rollback transaction\n";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Data bahan_baku dengan ID $id_bahan_baku tidak ditemukan\n";
}

// Tutup koneksi
mysqli_close($conn);

echo "\n=== Selesai ===\n";
?> 