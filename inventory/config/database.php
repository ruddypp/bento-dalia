<?php
// Konfigurasi database
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'inventori_db';

// Buat koneksi
$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8");
?> 