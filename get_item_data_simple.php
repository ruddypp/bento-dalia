<?php
// Koneksi database sederhana
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventori_db3";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

header('Content-Type: application/json');

// Ambil ID dari parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

// Query sederhana
$sql = "SELECT * FROM barang WHERE id_barang = $id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $item = $result->fetch_assoc();
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
}

$conn->close();
?> 