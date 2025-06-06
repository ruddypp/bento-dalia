<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/database.php';

// Count records in bahan_baku with status 'retur'
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM bahan_baku WHERE status='retur'");
if (!$result) {
    echo "Query error: " . mysqli_error($conn);
    exit;
}

$row = mysqli_fetch_assoc($result);
echo "Total retur records in bahan_baku: " . $row['total'] . "<br>";

// Get sample data if there are records
if ($row['total'] > 0) {
    $sampleData = mysqli_query($conn, "SELECT bb.*, b.nama_barang 
                                      FROM bahan_baku bb
                                      JOIN barang b ON bb.id_barang = b.id_barang
                                      WHERE bb.status='retur'
                                      LIMIT 5");
    
    echo "<h3>Sample data:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Barang</th><th>Jumlah Retur</th><th>Tanggal</th><th>Catatan Retur</th></tr>";
    
    while ($data = mysqli_fetch_assoc($sampleData)) {
        echo "<tr>";
        echo "<td>" . $data['id_bahan_baku'] . "</td>";
        echo "<td>" . $data['nama_barang'] . "</td>";
        echo "<td>" . $data['jumlah_retur'] . "</td>";
        echo "<td>" . $data['tanggal_input'] . "</td>";
        echo "<td>" . $data['catatan_retur'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
?> 