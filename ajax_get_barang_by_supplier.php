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

// Get supplier ID from request
$id_supplier = isset($_GET['id_supplier']) ? (int)$_GET['id_supplier'] : 0;

// Validate supplier ID
if ($id_supplier <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid supplier ID']);
    exit;
}

try {
    // Check if barang_supplier table exists
    $table_exists_query = "SHOW TABLES LIKE 'barang_supplier'";
    $table_exists_result = mysqli_query($conn, $table_exists_query);
    $barang_supplier_exists = mysqli_num_rows($table_exists_result) > 0;
    
    // Create barang_supplier table if it doesn't exist
    if (!$barang_supplier_exists) {
        $create_table_query = "CREATE TABLE barang_supplier (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_barang INT NOT NULL,
            id_supplier INT NOT NULL,
            FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
            FOREIGN KEY (id_supplier) REFERENCES supplier(id_supplier)
        )";
        mysqli_query($conn, $create_table_query);
    }
    
    // Get supplier name
    $supplier_query = "SELECT nama_supplier FROM supplier WHERE id_supplier = ?";
    $supplier_stmt = mysqli_prepare($conn, $supplier_query);
    mysqli_stmt_bind_param($supplier_stmt, "i", $id_supplier);
    mysqli_stmt_execute($supplier_stmt);
    $supplier_result = mysqli_stmt_get_result($supplier_stmt);
    $supplier = mysqli_fetch_assoc($supplier_result);
    
    if (!$supplier) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        exit;
    }
    
    // Check if the id_supplier column exists in the barang table
    $check_column_query = "SHOW COLUMNS FROM barang LIKE 'id_supplier'";
    $check_column_result = mysqli_query($conn, $check_column_query);
    $has_supplier_column = mysqli_num_rows($check_column_result) > 0;
    
    if ($has_supplier_column) {
        $barang_query = "SELECT b.id_barang, b.nama_barang, b.satuan, b.stok, s.nama_supplier 
                        FROM barang b 
                        JOIN supplier s ON b.id_supplier = s.id_supplier
                        WHERE b.id_supplier = ?
                        ORDER BY b.nama_barang ASC";
        $barang_stmt = mysqli_prepare($conn, $barang_query);
        mysqli_stmt_bind_param($barang_stmt, "i", $id_supplier);
    } else {
        // Fallback if id_supplier column doesn't exist - try to use another relationship
        $barang_query = "SELECT b.id_barang, b.nama_barang, b.satuan, b.stok, s.nama_supplier 
                        FROM barang b 
                        JOIN barang_supplier bs ON b.id_barang = bs.id_barang
                        JOIN supplier s ON bs.id_supplier = s.id_supplier
                        WHERE s.id_supplier = ?
                        ORDER BY b.nama_barang ASC";
        $barang_stmt = mysqli_prepare($conn, $barang_query);
        mysqli_stmt_bind_param($barang_stmt, "i", $id_supplier);
    }
    
    mysqli_stmt_execute($barang_stmt);
    $barang_result = mysqli_stmt_get_result($barang_stmt);
    
    $barang_list = [];
    while ($barang = mysqli_fetch_assoc($barang_result)) {
        $barang_list[] = $barang;
    }
    
    // If no barang found with the supplier relationship, try to get all barang
    if (empty($barang_list)) {
        $all_barang_query = "SELECT b.id_barang, b.nama_barang, b.satuan, b.stok, ? as nama_supplier 
                            FROM barang b 
                            ORDER BY b.nama_barang ASC
                            LIMIT 20";
        $all_barang_stmt = mysqli_prepare($conn, $all_barang_query);
        mysqli_stmt_bind_param($all_barang_stmt, "s", $supplier['nama_supplier']);
        mysqli_stmt_execute($all_barang_stmt);
        $all_barang_result = mysqli_stmt_get_result($all_barang_stmt);
        
        while ($barang = mysqli_fetch_assoc($all_barang_result)) {
            $barang_list[] = $barang;
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'supplier_name' => $supplier['nama_supplier'],
        'barang' => $barang_list,
        'debug' => [
            'id_supplier' => $id_supplier,
            'supplier_query' => $supplier_query,
            'barang_count' => count($barang_list),
            'has_supplier_column' => $has_supplier_column,
            'query_used' => $has_supplier_column ? 'direct_supplier' : 'barang_supplier_relation',
            'fallback_used' => empty($barang_list) ? true : false,
            'barang_supplier_exists' => $barang_supplier_exists
        ]
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 