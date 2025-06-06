<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/database.php';

// Check if the table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'retur_barang'");
if (mysqli_num_rows($tableCheck) == 0) {
    echo "Table 'retur_barang' does not exist!";
    exit;
}

// Count records
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM retur_barang");
if (!$result) {
    echo "Query error: " . mysqli_error($conn);
    exit;
}

$row = mysqli_fetch_assoc($result);
echo "Total records in retur_barang: " . $row['total'] . "<br>";

// Get sample data
if ($row['total'] > 0) {
    $sampleData = mysqli_query($conn, "SELECT rb.*, b.nama_barang 
                                      FROM retur_barang rb
                                      JOIN barang b ON rb.id_barang = b.id_barang
                                      LIMIT 5");
    
    echo "<h3>Sample data:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Barang</th><th>Qty</th><th>Tanggal</th><th>Alasan</th></tr>";
    
    while ($data = mysqli_fetch_assoc($sampleData)) {
        echo "<tr>";
        echo "<td>" . $data['id_retur'] . "</td>";
        echo "<td>" . $data['nama_barang'] . "</td>";
        echo "<td>" . $data['qty_retur'] . "</td>";
        echo "<td>" . $data['tanggal_retur'] . "</td>";
        echo "<td>" . $data['alasan_retur'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Show table structure
$structure = mysqli_query($conn, "DESCRIBE retur_barang");
echo "<h3>Table structure:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($field = mysqli_fetch_assoc($structure)) {
    echo "<tr>";
    echo "<td>" . $field['Field'] . "</td>";
    echo "<td>" . $field['Type'] . "</td>";
    echo "<td>" . $field['Null'] . "</td>";
    echo "<td>" . $field['Key'] . "</td>";
    echo "<td>" . ($field['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $field['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>"; 