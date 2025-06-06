<?php
// Koneksi ke database
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Pemeriksaan Tabel Retur Barang</h1>";

// 1. Periksa apakah tabel retur_barang ada
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'retur_barang'");
if (mysqli_num_rows($table_check) > 0) {
    echo "<p style='color:green'>Tabel retur_barang ditemukan.</p>";
    
    // 2. Periksa struktur tabel
    echo "<h2>Struktur Tabel:</h2>";
    $structure = mysqli_query($conn, "DESCRIBE retur_barang");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($structure)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Periksa data dalam tabel
    echo "<h2>Data dalam Tabel:</h2>";
    $data = mysqli_query($conn, "SELECT * FROM retur_barang");
    if (mysqli_num_rows($data) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        $first_row = mysqli_fetch_assoc($data);
        foreach ($first_row as $key => $value) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        // Reset pointer
        mysqli_data_seek($data, 0);
        
        while ($row = mysqli_fetch_assoc($data)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value === null ? "NULL" : htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>Tidak ada data dalam tabel retur_barang.</p>";
    }
    
} else {
    echo "<p style='color:red'>Tabel retur_barang tidak ditemukan!</p>";
    
    // Tampilkan semua tabel yang ada
    echo "<h2>Tabel yang ada di database:</h2>";
    $tables = mysqli_query($conn, "SHOW TABLES");
    echo "<ul>";
    while ($row = mysqli_fetch_row($tables)) {
        echo "<li>{$row[0]}</li>";
    }
    echo "</ul>";
}

// 4. Periksa data di bahan_baku dengan status retur
echo "<h2>Data Bahan Baku dengan Status Retur:</h2>";
$retur_data = mysqli_query($conn, "SELECT bb.*, b.nama_barang FROM bahan_baku bb JOIN barang b ON bb.id_barang = b.id_barang WHERE bb.status = 'retur'");

if (mysqli_num_rows($retur_data) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>ID Barang</th><th>Nama Barang</th><th>Qty</th><th>Jumlah Retur</th><th>Jumlah Masuk</th><th>Status</th><th>Catatan Retur</th><th>Tanggal Input</th></tr>";
    
    while ($row = mysqli_fetch_assoc($retur_data)) {
        echo "<tr>";
        echo "<td>{$row['id_bahan_baku']}</td>";
        echo "<td>{$row['id_barang']}</td>";
        echo "<td>{$row['nama_barang']}</td>";
        echo "<td>{$row['qty']}</td>";
        echo "<td>" . (isset($row['jumlah_retur']) ? $row['jumlah_retur'] : 'NULL') . "</td>";
        echo "<td>" . (isset($row['jumlah_masuk']) ? $row['jumlah_masuk'] : 'NULL') . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>" . (isset($row['catatan_retur']) ? $row['catatan_retur'] : 'NULL') . "</td>";
        echo "<td>{$row['tanggal_input']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Tidak ada data bahan_baku dengan status retur.</p>";
}

// 5. Periksa query yang digunakan untuk menampilkan data retur
echo "<h2>Query untuk Menampilkan Data Retur:</h2>";
$query = "SELECT bb.*, 
          b.nama_barang, b.satuan,
          u.nama_lengkap as nama_pengguna,
          pb.id_pesanan,
          pb.status as pesanan_status,
          rb.id_retur, rb.tanggal_retur, rb.alasan_retur as rb_alasan_retur, rb.supplier
          FROM bahan_baku bb 
          JOIN barang b ON bb.id_barang = b.id_barang 
          LEFT JOIN users u ON bb.id_user = u.id_user
          LEFT JOIN pesanan_barang pb ON bb.id_pesanan = pb.id_pesanan
          LEFT JOIN retur_barang rb ON bb.id_barang = rb.id_barang AND DATE(bb.tanggal_input) = DATE(rb.tanggal_retur)
          WHERE bb.status = 'retur'
          ORDER BY bb.tanggal_input DESC";

echo "<pre>" . htmlspecialchars($query) . "</pre>";

// Eksekusi query untuk melihat hasilnya
$result = mysqli_query($conn, $query);
if ($result) {
    echo "<p>Jumlah hasil query: " . mysqli_num_rows($result) . "</p>";
    
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID Bahan Baku</th><th>Nama Barang</th><th>Status</th><th>ID Retur</th><th>Tanggal Retur</th><th>Alasan Retur</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['id_bahan_baku']}</td>";
            echo "<td>{$row['nama_barang']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>" . (isset($row['id_retur']) ? $row['id_retur'] : 'NULL') . "</td>";
            echo "<td>" . (isset($row['tanggal_retur']) ? $row['tanggal_retur'] : 'NULL') . "</td>";
            echo "<td>" . (isset($row['rb_alasan_retur']) ? $row['rb_alasan_retur'] : (isset($row['catatan_retur']) ? $row['catatan_retur'] : 'NULL')) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

mysqli_close($conn);
?> 