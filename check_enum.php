<?php
// Koneksi ke database langsung
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Periksa definisi kolom status
$query = "SHOW COLUMNS FROM bahan_baku LIKE 'status'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

echo "Status column definition: " . $row['Type'] . "\n\n";

// Periksa nilai status di tabel laporan_masuk
$query = "SHOW COLUMNS FROM laporan_masuk LIKE 'status'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

echo "Status column in laporan_masuk: " . $row['Type'] . "\n\n";

// Periksa semua nilai status yang digunakan di tabel bahan_baku
$query = "SELECT DISTINCT status FROM bahan_baku";
$result = mysqli_query($conn, $query);

echo "Distinct status values in bahan_baku table:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['status'] . "\n";
}

// Periksa semua nilai status yang digunakan di tabel laporan_masuk
echo "\nDistinct status values in laporan_masuk table:\n";
$query = "SELECT DISTINCT status FROM laporan_masuk";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['status'] . "\n";
}

// Periksa file laporan_masuk.php untuk penggunaan status
echo "\nChecking for status values in laporan_masuk.php...\n";
if (file_exists('laporan_masuk.php')) {
    $file_content = file_get_contents('laporan_masuk.php');
    preg_match_all("/status\s*=\s*['\"](.*?)['\"]/", $file_content, $matches);

    echo "Status values used in laporan_masuk.php:\n";
    foreach ($matches[1] as $match) {
        echo "- " . $match . "\n";
    }
} else {
    echo "laporan_masuk.php file not found.\n";
}
?> 