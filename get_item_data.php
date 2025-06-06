<?php
// Pastikan tidak ada output sebelum header
require_once 'includes/config.php';

// Untuk debugging, tulis ke file log
$log_file = 'item_data_debug.log';
file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Request received\n", FILE_APPEND);

// Disable error reporting untuk produksi
error_reporting(0);
ini_set('display_errors', 0);

// Set header JSON
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Error: ID tidak diberikan\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'ID tidak diberikan']);
    exit;
}

$id = (int)$_GET['id'];
file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "ID: $id\n", FILE_APPEND);

// Get item data
$query = "SELECT * FROM barang WHERE id_barang = ?";
file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Query: $query\n", FILE_APPEND);

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    $error = mysqli_error($conn);
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Prepare error: $error\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $error]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);

if (!mysqli_stmt_execute($stmt)) {
    $error = mysqli_stmt_error($stmt);
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Execute error: $error\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $error]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);

if ($item = mysqli_fetch_assoc($result)) {
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Item found: " . json_encode($item) . "\n", FILE_APPEND);
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Item not found for ID: $id\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
}

mysqli_stmt_close($stmt);
?> 