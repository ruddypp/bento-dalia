<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'ID not provided']);
    exit();
}

$id_barang = (int)$_GET['id'];

// Get barang detail
$query = "SELECT * FROM barang WHERE id_barang = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_barang);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Item not found']);
}
?> 