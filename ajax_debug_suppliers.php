<?php
// Include database connection
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get all suppliers
    $supplier_query = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
    $supplier_result = mysqli_query($conn, $supplier_query);
    
    if (!$supplier_result) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Error querying suppliers: ' . mysqli_error($conn),
            'query' => $supplier_query
        ]);
        exit;
    }
    
    $suppliers = [];
    while ($supplier = mysqli_fetch_assoc($supplier_result)) {
        $suppliers[] = $supplier;
    }
    
    // Check if supplier table exists
    $table_exists_query = "SHOW TABLES LIKE 'supplier'";
    $table_exists_result = mysqli_query($conn, $table_exists_query);
    $supplier_table_exists = mysqli_num_rows($table_exists_result) > 0;
    
    // Get table structure
    $table_structure = [];
    if ($supplier_table_exists) {
        $structure_query = "DESCRIBE supplier";
        $structure_result = mysqli_query($conn, $structure_query);
        while ($column = mysqli_fetch_assoc($structure_result)) {
            $table_structure[] = $column;
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'suppliers' => $suppliers,
        'count' => count($suppliers),
        'table_exists' => $supplier_table_exists,
        'table_structure' => $table_structure
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 