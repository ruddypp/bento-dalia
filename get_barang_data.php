<?php
// Koneksi database langsung
$host = "localhost";
$user = "root";
$pass = "";
$db = "inventori_db3";

// Buat koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Periksa koneksi
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . mysqli_connect_error()
    ]);
    exit;
}

// Set header untuk JSON
header('Content-Type: application/json');

// Ambil ID dari parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID tidak diberikan'
    ]);
    exit;
}

$id = (int)$_GET['id'];

// Query sederhana
$query = "SELECT * FROM barang WHERE id_barang = $id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $item = mysqli_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'item' => $item
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Barang tidak ditemukan'
    ]);
}

// Tutup koneksi
mysqli_close($conn);
?> 