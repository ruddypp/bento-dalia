<?php
// Koneksi ke database langsung
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Periksa definisi kolom status di laporan_masuk
$query = "SHOW COLUMNS FROM laporan_masuk LIKE 'status'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

echo "Status column definition in laporan_masuk: " . $row['Type'] . "\n\n";

// Periksa nilai yang digunakan di laporan_masuk
$query = "SELECT DISTINCT status FROM laporan_masuk";
$result = mysqli_query($conn, $query);

echo "Values used in laporan_masuk.status:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- '" . $row['status'] . "'\n";
}

// Periksa file laporan_masuk.php untuk penggunaan status
echo "\nChecking for status values in laporan_masuk.php...\n";
if (file_exists('laporan_masuk.php')) {
    $file_content = file_get_contents('laporan_masuk.php');
    preg_match_all("/status\s*=\s*['\"](.*?)['\"]/", $file_content, $matches);

    echo "Status values used in laporan_masuk.php:\n";
    foreach ($matches[1] as $match) {
        echo "- '" . $match . "'\n";
    }
} else {
    echo "laporan_masuk.php file not found.\n";
}
?> 