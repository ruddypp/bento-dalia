<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'supplier' => null
];

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['message'] = 'ID supplier tidak diberikan';
    echo json_encode($response);
    exit;
}

// Get supplier ID
$id_supplier = (int) $_GET['id'];

// Get supplier data
$query = "SELECT * FROM supplier WHERE id_supplier = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_supplier);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $supplier = mysqli_fetch_assoc($result);
    
    // Set response data
    $response['success'] = true;
    $response['supplier'] = $supplier;
} else {
    $response['message'] = 'Supplier tidak ditemukan';
}

mysqli_stmt_close($stmt);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit; 