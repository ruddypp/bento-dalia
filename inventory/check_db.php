<?php
require_once 'config/database.php';

// Check if barang_masuk table exists and show structure
$result = $conn->query("SHOW TABLES LIKE 'barang_masuk'");
if ($result->num_rows > 0) {
    echo "<h2>barang_masuk table exists</h2>";
    
    // Show structure
    $structure = $conn->query("DESCRIBE barang_masuk");
    echo "<h3>Structure:</h3><pre>";
    while ($row = $structure->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
    
    // Show count
    $count = $conn->query("SELECT COUNT(*) as count FROM barang_masuk");
    $count_row = $count->fetch_assoc();
    echo "<p>Total rows: " . $count_row['count'] . "</p>";
    
    // Show sample data
    $data = $conn->query("SELECT * FROM barang_masuk LIMIT 2");
    echo "<h3>Sample data:</h3><pre>";
    while ($row = $data->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "<h2>barang_masuk table does not exist</h2>";
}

// Check if laporan_masuk table exists
$result = $conn->query("SHOW TABLES LIKE 'laporan_masuk'");
if ($result->num_rows > 0) {
    echo "<h2>laporan_masuk table exists</h2>";
    
    // Show count
    $count = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk");
    $count_row = $count->fetch_assoc();
    echo "<p>Total rows: " . $count_row['count'] . "</p>";
}

// Check if laporan_masuk_detail table exists
$result = $conn->query("SHOW TABLES LIKE 'laporan_masuk_detail'");
if ($result->num_rows > 0) {
    echo "<h2>laporan_masuk_detail table exists</h2>";
    
    // Show structure
    $structure = $conn->query("DESCRIBE laporan_masuk_detail");
    echo "<h3>Structure:</h3><pre>";
    while ($row = $structure->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
    
    // Show count
    $count = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk_detail");
    $count_row = $count->fetch_assoc();
    echo "<p>Total rows: " . $count_row['count'] . "</p>";
} else {
    echo "<h2>laporan_masuk_detail table does not exist</h2>";
}
?> 