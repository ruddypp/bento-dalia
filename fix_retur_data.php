<?php
// Koneksi ke database
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Perbaikan Data Retur</h1>";

// Mulai transaction
mysqli_begin_transaction($conn);

try {
    // 1. Periksa data di tabel bahan_baku dengan status retur
    echo "<h2>1. Memeriksa data bahan_baku dengan status retur</h2>";
    $query = "SELECT bb.*, b.nama_barang FROM bahan_baku bb JOIN barang b ON bb.id_barang = b.id_barang WHERE bb.status = 'retur'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p>Ditemukan " . mysqli_num_rows($result) . " data bahan_baku dengan status retur.</p>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<p>ID: {$row['id_bahan_baku']}, Barang: {$row['nama_barang']}, Qty: {$row['qty']}</p>";
            
            // 2. Periksa apakah data ini sudah ada di tabel retur_barang
            $check_query = "SELECT * FROM retur_barang WHERE id_barang = ? AND DATE(tanggal_retur) = DATE(?)";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "is", $row['id_barang'], $row['tanggal_input']);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                echo "<p style='color:green'>- Sudah ada data retur di tabel retur_barang</p>";
            } else {
                echo "<p style='color:red'>- Belum ada data retur di tabel retur_barang</p>";
                
                // 3. Insert data ke tabel retur_barang
                $qty_retur = $row['jumlah_retur'] > 0 ? $row['jumlah_retur'] : $row['qty'];
                $harga_satuan = $row['harga_satuan'];
                $total_retur = $qty_retur * $harga_satuan;
                $id_user = $row['id_user'];
                $catatan_retur = $row['catatan_retur'] ? $row['catatan_retur'] : 'Auto-generated from fix script';
                $id_pesanan = $row['id_pesanan'] ? $row['id_pesanan'] : 0;
                
                $insert_query = "INSERT INTO retur_barang (id_barang, qty_retur, tanggal_retur, alasan_retur, id_user, supplier, harga_satuan, total, periode, id_pesanan) 
                                VALUES (?, ?, ?, ?, ?, '', ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "iissiidii", 
                                     $row['id_barang'], 
                                     $qty_retur, 
                                     $row['tanggal_input'],
                                     $catatan_retur,
                                     $id_user,
                                     $harga_satuan,
                                     $total_retur,
                                     $row['periode'],
                                     $id_pesanan);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $id_retur = mysqli_insert_id($conn);
                    echo "<p style='color:green'>- Berhasil insert data retur dengan ID: $id_retur</p>";
                } else {
                    echo "<p style='color:red'>- Gagal insert data retur: " . mysqli_stmt_error($insert_stmt) . "</p>";
                }
                mysqli_stmt_close($insert_stmt);
            }
            
            mysqli_stmt_close($check_stmt);
        }
    } else {
        echo "<p>Tidak ada data bahan_baku dengan status retur.</p>";
    }
    
    // 4. Periksa data di tabel retur_barang yang tidak terhubung dengan bahan_baku
    echo "<h2>2. Memeriksa data retur_barang yang tidak terhubung dengan bahan_baku</h2>";
    $query = "SELECT rb.*, b.nama_barang 
              FROM retur_barang rb 
              JOIN barang b ON rb.id_barang = b.id_barang 
              LEFT JOIN bahan_baku bb ON rb.id_barang = bb.id_barang AND bb.status = 'retur' AND DATE(rb.tanggal_retur) = DATE(bb.tanggal_input)
              WHERE bb.id_bahan_baku IS NULL";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p>Ditemukan " . mysqli_num_rows($result) . " data retur_barang yang tidak terhubung dengan bahan_baku.</p>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<p>ID Retur: {$row['id_retur']}, Barang: {$row['nama_barang']}, Qty: {$row['qty_retur']}, Tanggal: {$row['tanggal_retur']}</p>";
        }
    } else {
        echo "<p>Semua data retur_barang terhubung dengan bahan_baku.</p>";
    }
    
    // Commit transaction
    mysqli_commit($conn);
    echo "<h2 style='color:green'>Proses perbaikan data selesai!</h2>";
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
}

mysqli_close($conn);
?> 