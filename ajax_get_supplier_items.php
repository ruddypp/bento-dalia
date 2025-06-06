<?php
// Turn off error output to prevent it from corrupting JSON
error_reporting(0);
ini_set('display_errors', 0);

// Buffer output to prevent any unwanted output before JSON
ob_start();

require_once 'config/database.php';
require_once 'config/functions.php';

// Log function for debugging
function log_debug($message, $data = null) {
    $log_file = 'ajax_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $log_message .= ": " . json_encode($data);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

log_debug("AJAX request started", $_GET);

// Check if user is logged in via session
session_start();
if (!isset($_SESSION['user_id'])) {
    // Clear any output that might have been generated
    ob_end_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Check if supplier ID is provided
if (!isset($_GET['supplier_id']) || empty($_GET['supplier_id'])) {
    // Clear any output that might have been generated
    ob_end_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Supplier ID is required'
    ]);
    exit();
}

$supplier_id = (int)$_GET['supplier_id'];
log_debug("Processing supplier ID", $supplier_id);

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Get supplier details
    $supplier_query = "SELECT * FROM supplier WHERE id_supplier = ?";
    $supplier_stmt = mysqli_prepare($conn, $supplier_query);
    mysqli_stmt_bind_param($supplier_stmt, "i", $supplier_id);
    mysqli_stmt_execute($supplier_stmt);
    $supplier_result = mysqli_stmt_get_result($supplier_stmt);
    
    if (mysqli_num_rows($supplier_result) == 0) {
        throw new Exception("Supplier not found");
    }

    $supplier = mysqli_fetch_assoc($supplier_result);
    mysqli_stmt_close($supplier_stmt);
    
    // Parse bahan_baku and satuan from the supplier
    $bahan_baku_array = explode(',', $supplier['bahan_baku']);
    $satuan_array = explode(',', $supplier['satuan']);

    $items = [];
    
    // Get existing items from the barang table for this supplier
    $barang_query = "SELECT id_barang, nama_barang, satuan, harga FROM barang WHERE id_supplier = ?";
    $barang_stmt = mysqli_prepare($conn, $barang_query);
    mysqli_stmt_bind_param($barang_stmt, "i", $supplier_id);
    mysqli_stmt_execute($barang_stmt);
    $barang_result = mysqli_stmt_get_result($barang_stmt);
    
    // Add existing items to the list
    while ($barang = mysqli_fetch_assoc($barang_result)) {
        $items[] = [
            'id_barang' => $barang['id_barang'],
            'nama_barang' => $barang['nama_barang'],
            'satuan' => $barang['satuan'],
            'harga' => $barang['harga']
        ];
        }

    // Add items from supplier's bahan_baku field that don't exist in barang table
    for ($i = 0; $i < count($bahan_baku_array); $i++) {
        $bahan = trim($bahan_baku_array[$i]);
        $satuan = isset($satuan_array[$i]) ? trim($satuan_array[$i]) : 'pcs';
                
        if (empty($bahan)) {
            continue;
        }
        
        // Check if this item already exists in the items array
        $exists = false;
        foreach ($items as $item) {
            if (strtolower($item['nama_barang']) == strtolower($bahan)) {
                $exists = true;
                break;
            }
        }
        
        // If not exists, add as a new item
        if (!$exists) {
            $items[] = [
                'id_barang' => 0, // 0 indicates a new item that needs to be created
                'nama_barang' => $bahan,
                            'satuan' => $satuan,
                'harga' => 0 // Default price
            ];
        }
    }

    // Return the items as JSON
    $response = [
        'success' => true,
        'items' => $items,
        'supplier' => [
            'id_supplier' => $supplier['id_supplier'],
            'nama_supplier' => $supplier['nama_supplier'],
            'alamat' => $supplier['alamat'],
            'kontak' => $supplier['kontak']
        ]
    ];

    log_debug("Sending response", $response);
    
    // Clear any output that might have been generated
    ob_end_clean();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    log_debug("Error occurred", $e->getMessage());
    
    // Clear any output that might have been generated
    ob_end_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // End transaction
    mysqli_commit($conn);
}
?> 