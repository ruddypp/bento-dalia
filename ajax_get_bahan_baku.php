<?php
require_once 'config/database.php';
require_once 'config/functions.php';

header('Content-Type: application/json');

try {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID tidak valid');
    }
    
    $id = (int)$_GET['id'];
    
    // Get bahan baku details
    $query = "SELECT bb.*, b.nama_barang, b.satuan, u.nama_lengkap as nama_pengguna
              FROM bahan_baku bb 
              JOIN barang b ON bb.id_barang = b.id_barang 
              LEFT JOIN users u ON bb.id_user = u.id_user
              WHERE bb.id_bahan_baku = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute statement failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) > 0) {
        $bahan_baku = mysqli_fetch_assoc($result);
        
        // Format dates for better display
        if (isset($bahan_baku['tanggal_input'])) {
            $bahan_baku['tanggal_input_formatted'] = date('d/m/Y H:i', strtotime($bahan_baku['tanggal_input']));
        }
        
        // Add additional calculated fields
        $bahan_baku['jumlah_total'] = $bahan_baku['qty'];
        $harga_satuan = $bahan_baku['harga_satuan'];
        $total_qty = $bahan_baku['qty'];
        
        // Calculate values for all status types
        $bahan_baku['nilai_total'] = $total_qty * $harga_satuan;
        
        // Calculate values for retur status
        if ($bahan_baku['status'] == 'retur') {
            $jumlah_retur = $bahan_baku['jumlah_retur'] ?? 0;
            $jumlah_masuk = $bahan_baku['jumlah_masuk'] ?? 0;
            
            // Ensure jumlah_masuk is properly set
            if ($jumlah_masuk == 0 && $jumlah_retur > 0) {
                $jumlah_masuk = $total_qty - $jumlah_retur;
                $bahan_baku['jumlah_masuk'] = $jumlah_masuk;
            }
            
            $bahan_baku['nilai_retur'] = $jumlah_retur * $harga_satuan;
            $bahan_baku['nilai_masuk'] = $jumlah_masuk * $harga_satuan;
        } else if ($bahan_baku['status'] == 'approved') {
            // For approved items, all qty goes to stock
            $bahan_baku['jumlah_masuk'] = $total_qty;
            $bahan_baku['nilai_masuk'] = $total_qty * $harga_satuan;
        } else if ($bahan_baku['status'] == 'pending') {
            // For pending items, nothing goes to stock yet
            $bahan_baku['jumlah_masuk'] = 0;
            $bahan_baku['nilai_masuk'] = 0;
        }
        
        // Get suppliers list
        $suppliers = [];
        $supplier_query = "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier";
        $supplier_result = mysqli_query($conn, $supplier_query);
        
        if (!$supplier_result) {
            throw new Exception("Supplier query failed: " . mysqli_error($conn));
        }
        
        while ($supplier = mysqli_fetch_assoc($supplier_result)) {
            $suppliers[] = $supplier;
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'bahan_baku' => $bahan_baku,
            'suppliers' => $suppliers,
            'debug' => [
                'id' => $id,
                'status' => $bahan_baku['status'],
                'has_jumlah_retur' => isset($bahan_baku['jumlah_retur']),
                'has_jumlah_masuk' => isset($bahan_baku['jumlah_masuk']),
                'has_catatan_retur' => isset($bahan_baku['catatan_retur']),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Bahan baku tidak ditemukan');
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'query' => $query ?? 'No query',
        'id' => $id ?? 'No ID',
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}

mysqli_close($conn); 