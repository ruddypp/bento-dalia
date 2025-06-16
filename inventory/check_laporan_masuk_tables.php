<?php
// Include database connection
require_once 'config/database.php';

echo "<h1>Checking Laporan Masuk Tables Structure</h1>";

// Check laporan_masuk table
echo "<h2>laporan_masuk table structure:</h2>";
$result = $conn->query("SHOW CREATE TABLE laporan_masuk");
if ($result && $row = $result->fetch_assoc()) {
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
} else {
    echo "Table does not exist or error: " . $conn->error;
}

// Check laporan_masuk_detail table
echo "<h2>laporan_masuk_detail table structure:</h2>";
$result = $conn->query("SHOW CREATE TABLE laporan_masuk_detail");
if ($result && $row = $result->fetch_assoc()) {
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
} else {
    echo "Table does not exist or error: " . $conn->error;
}

// Check if there are any records in the tables
echo "<h2>Records in laporan_masuk:</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk");
if ($result && $row = $result->fetch_assoc()) {
    echo "Total records: " . $row['count'];
} else {
    echo "Error counting records: " . $conn->error;
}

echo "<h2>Records in laporan_masuk_detail:</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk_detail");
if ($result && $row = $result->fetch_assoc()) {
    echo "Total records: " . $row['count'];
} else {
    echo "Error counting records: " . $conn->error;
}

echo "<p><a href='laporan_masuk.php'>Back to Laporan Masuk</a></p>";
?> 