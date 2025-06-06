<?php
require_once 'includes/koneksi.php';

// Periksa struktur tabel penerimaan
$result = mysqli_query($conn, "DESCRIBE penerimaan");
echo "<h3>Struktur Tabel Penerimaan:</h3>";
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
    echo "\n";
}
echo "</pre>";

// Periksa juga tabel stok_opname
$result = mysqli_query($conn, "DESCRIBE stok_opname");
echo "<h3>Struktur Tabel Stok Opname:</h3>";
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
    echo "\n";
}
echo "</pre>";

// Periksa juga tabel log_aktivitas
$result = mysqli_query($conn, "DESCRIBE log_aktivitas");
echo "<h3>Struktur Tabel Log Aktivitas:</h3>";
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
    echo "\n";
}
echo "</pre>";
?> 