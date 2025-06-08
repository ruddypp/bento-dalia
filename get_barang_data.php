<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID not provided']);
    exit();
}

$id_barang = (int)$_GET['id'];

// Get barang detail
$query = "SELECT * FROM barang WHERE id_barang = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_barang);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

header('Content-Type: application/json');
if ($result && mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'id_barang' => $id_barang] + $data);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
}
?> 