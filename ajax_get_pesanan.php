<?php
// Buffer all output to prevent any unexpected output
ob_start();

// Set error reporting off for production
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in via session
session_start();
if (!isset($_SESSION['user_id'])) {
    // Clear any buffered output
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Clear any buffered output
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID pesanan tidak ditemukan'
    ]);
    exit();
}

$id_pesanan = (int)$_GET['id'];

// Get pesanan header
$query = "SELECT pb.*, s.nama_supplier, s.kontak as supplier_kontak, s.alamat as supplier_alamat, u.nama_lengkap as nama_user 
          FROM pesanan_barang pb 
          LEFT JOIN supplier s ON pb.id_supplier = s.id_supplier 
          LEFT JOIN users u ON pb.id_user = u.id_user 
          WHERE pb.id_pesanan = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

if (!$pesanan) {
    echo json_encode([
        'success' => false,
        'message' => 'Pesanan tidak ditemukan'
    ]);
    exit();
}

// Get pesanan details
$detail_query = "SELECT pd.*, b.nama_barang, b.satuan 
                FROM pesanan_detail pd 
                LEFT JOIN barang b ON pd.id_barang = b.id_barang 
                WHERE pd.id_pesanan = ?";
$detail_stmt = mysqli_prepare($conn, $detail_query);
mysqli_stmt_bind_param($detail_stmt, "i", $id_pesanan);
mysqli_stmt_execute($detail_stmt);
$detail_result = mysqli_stmt_get_result($detail_stmt);

$details = [];
$total_items = 0;
$total_nilai = 0;

while ($detail = mysqli_fetch_assoc($detail_result)) {
    // Format the values for display
    $detail['harga_satuan_formatted'] = formatRupiah($detail['harga_satuan']);
    $detail['total_formatted'] = formatRupiah($detail['total']);
    $detail['periode_text'] = 'Periode ' . $detail['periode'];
    
    $details[] = $detail;
    $total_items += $detail['qty'];
    $total_nilai += $detail['total'];
}

// Check if any bahan_baku entries are linked to this pesanan
// First try with id_pesanan column if it exists
$linked_bahan = [];

try {
    $bahan_query = "SELECT bb.*, b.nama_barang, b.satuan 
                   FROM bahan_baku bb 
                   JOIN barang b ON bb.id_barang = b.id_barang 
                   WHERE bb.id_pesanan = ? 
                   ORDER BY bb.tanggal_input DESC";
    $bahan_stmt = mysqli_prepare($conn, $bahan_query);
    mysqli_stmt_bind_param($bahan_stmt, "i", $id_pesanan);
    mysqli_stmt_execute($bahan_stmt);
    $bahan_result = mysqli_stmt_get_result($bahan_stmt);
    
    while ($bahan = mysqli_fetch_assoc($bahan_result)) {
        // Format the values for display
        $bahan['harga_satuan_formatted'] = formatRupiah($bahan['harga_satuan']);
        $bahan['total_formatted'] = formatRupiah($bahan['total']);
        $bahan['tanggal_formatted'] = date('d/m/Y H:i', strtotime($bahan['tanggal_input']));
        
        // Make sure qty_retur is available for JavaScript
        if ($bahan['status'] === 'retur') {
            // Use jumlah_retur if available, otherwise use qty
            if (isset($bahan['jumlah_retur']) && !empty($bahan['jumlah_retur'])) {
                $bahan['qty_retur'] = $bahan['jumlah_retur'];
            } else {
                $bahan['qty_retur'] = $bahan['qty'];
            }
        }
        
        $linked_bahan[] = $bahan;
    }
} catch (Exception $e) {
    // If there's an error, log it but continue
    error_log("Error fetching linked bahan_baku: " . $e->getMessage());
}

// If no linked bahan found, try with text search fallback (legacy method)
if (empty($linked_bahan)) {
    try {
        $legacy_query = "SELECT bb.*, b.nama_barang, b.satuan 
                       FROM bahan_baku bb 
                       JOIN barang b ON bb.id_barang = b.id_barang 
                       WHERE bb.catatan_retur LIKE ? OR bb.catatan_retur LIKE ?
                       ORDER BY bb.tanggal_input DESC";
        $search_term1 = "%Dari pesanan #{$id_pesanan}%";
        $search_term2 = "%Order #{$id_pesanan}%";
        $legacy_stmt = mysqli_prepare($conn, $legacy_query);
        mysqli_stmt_bind_param($legacy_stmt, "ss", $search_term1, $search_term2);
        mysqli_stmt_execute($legacy_stmt);
        $legacy_result = mysqli_stmt_get_result($legacy_stmt);
        
        while ($bahan = mysqli_fetch_assoc($legacy_result)) {
            // Format the values for display
            $bahan['harga_satuan_formatted'] = formatRupiah($bahan['harga_satuan']);
            $bahan['total_formatted'] = formatRupiah($bahan['total']);
            $bahan['tanggal_formatted'] = date('d/m/Y H:i', strtotime($bahan['tanggal_input']));
            
            // Make sure qty_retur is available for JavaScript
            if ($bahan['status'] === 'retur') {
                // Use jumlah_retur if available, otherwise use qty
                if (isset($bahan['jumlah_retur']) && !empty($bahan['jumlah_retur'])) {
                    $bahan['qty_retur'] = $bahan['jumlah_retur'];
                } else {
                    $bahan['qty_retur'] = $bahan['qty'];
                }
            }
            
            $linked_bahan[] = $bahan;
        }
    } catch (Exception $e) {
        // If there's an error, log it but continue
        error_log("Error fetching linked bahan_baku with text search: " . $e->getMessage());
    }
}

// Check if any bahan_baku entries are linked to this pesanan and have status 'retur'
$has_retur = false;
foreach ($linked_bahan as $bahan) {
    if ($bahan['status'] === 'retur') {
        $has_retur = true;
        break;
    }
}

// Format the response
$status_text = '';
switch ($pesanan['status']) {
    case 'pending':
        $status_text = 'Menunggu';
        break;
    case 'selesai':
        $status_text = 'Selesai';
        break;
    case 'dibatalkan':
        $status_text = 'Dibatalkan';
        break;
    default:
        $status_text = $pesanan['status'];
        break;
}

$response = [
    'success' => true,
    'pesanan' => [
        'id_pesanan' => $pesanan['id_pesanan'],
        'tanggal_pesan' => date('d/m/Y', strtotime($pesanan['tanggal_pesan'])),
        'status' => $pesanan['status'],
        'status_text' => $status_text,
        'has_retur' => $has_retur,
        'catatan' => $pesanan['catatan'],
        'created_at' => date('d/m/Y H:i', strtotime($pesanan['created_at'])),
        'supplier' => [
            'id_supplier' => $pesanan['id_supplier'],
            'nama_supplier' => $pesanan['nama_supplier'],
            'kontak' => $pesanan['supplier_kontak'],
            'alamat' => $pesanan['supplier_alamat']
        ],
        'user' => [
            'id_user' => $pesanan['id_user'],
            'nama_user' => $pesanan['nama_user']
        ],
        'total_items' => $total_items,
        'total_nilai' => $total_nilai,
        'total_nilai_formatted' => formatRupiah($total_nilai),
        'details' => $details,
        'linked_bahan' => $linked_bahan,
        'can_process' => false,
        'can_cancel' => ($pesanan['status'] == 'pending')
    ]
];

// Add HTML output for the details
$html_output = '';

// Header information
$html_output .= '<div class="bg-gray-50 p-4 rounded-lg mb-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="text-lg font-medium text-gray-800 mb-2">Informasi Pesanan</h4>
            <p class="text-sm text-gray-600"><span class="font-medium">No Pesanan:</span> #' . $pesanan['id_pesanan'] . '</p>
            <p class="text-sm text-gray-600"><span class="font-medium">Tanggal Pesan:</span> ' . date('d/m/Y', strtotime($pesanan['tanggal_pesan'])) . '</p>
            <p class="text-sm text-gray-600"><span class="font-medium">Status:</span>';

// Determine status class based on status
$statusClass = '';
switch($pesanan['status']) {
    case 'pending':
        $statusClass = 'bg-yellow-100 text-yellow-800';
        break;
    case 'selesai':
        // Check if there are any returns
        if ($has_retur) {
            $statusClass = 'bg-red-100 text-red-800';
        } else {
            $statusClass = 'bg-green-100 text-green-800';
        }
        break;
    default:
        $statusClass = 'bg-red-100 text-red-800';
        break;
}

$html_output .= ' <span class="px-2 py-1 text-xs rounded-full ' . $statusClass . '">' . $response['pesanan']['status_text'] . '</span>
            </p>
            <p class="text-sm text-gray-600"><span class="font-medium">Dibuat oleh:</span> ' . $pesanan['nama_user'] . '</p>
        </div>
        <div>
            <h4 class="text-lg font-medium text-gray-800 mb-2">Informasi Supplier</h4>
            <p class="text-sm text-gray-600"><span class="font-medium">Nama:</span> ' . $pesanan['nama_supplier'] . '</p>
            <p class="text-sm text-gray-600"><span class="font-medium">Kontak:</span> ' . ($pesanan['supplier_kontak'] ?: '-') . '</p>
            <p class="text-sm text-gray-600"><span class="font-medium">Alamat:</span> ' . ($pesanan['supplier_alamat'] ?: '-') . '</p>
        </div>
    </div>';

// Add catatan if exists
if (!empty($pesanan['catatan'])) {
    $html_output .= '<div class="mt-4">
        <h4 class="text-md font-medium text-gray-800 mb-1">Catatan:</h4>
        <p class="text-sm text-gray-600 bg-white p-2 rounded border">' . $pesanan['catatan'] . '</p>
    </div>';
}

$html_output .= '</div>';

// Detail items table
$html_output .= '<div class="mb-4">
    <h4 class="text-lg font-medium text-gray-800 mb-2">Detail Item Pesanan</h4>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Barang</th>
                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody>';

// Add rows for each detail item
$no = 1;
foreach ($details as $item) {
    $html_output .= '<tr class="border-b">
        <td class="py-2 px-2 text-sm">' . $no++ . '</td>
        <td class="py-2 px-2 text-sm">' . $item['nama_barang'] . '</td>
        <td class="py-2 px-2 text-sm">' . $item['qty'] . '</td>
        <td class="py-2 px-2 text-sm">' . $item['satuan'] . '</td>
        <td class="py-2 px-2 text-sm">Periode ' . $item['periode'] . '</td>
        <td class="py-2 px-2 text-sm">' . $item['harga_satuan_formatted'] . '</td>
        <td class="py-2 px-2 text-sm">' . $item['lokasi'] . '</td>
        <td class="py-2 px-2 text-sm font-medium">' . $item['total_formatted'] . '</td>
    </tr>';
}

// Add total row
$html_output .= '</tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="7" class="py-2 px-2 text-sm font-medium text-right">Total:</td>
                    <td class="py-2 px-2 text-sm font-bold">' . formatRupiah($total_nilai) . '</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>';

// Show linked bahan_baku if any
if (count($linked_bahan) > 0) {
    $html_output .= '<div class="mb-4">
        <h4 class="text-lg font-medium text-gray-800 mb-2">Bahan Baku Terkait</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Barang</th>
                        <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Input</th>
                        <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody>';

    // Add rows for each linked bahan_baku
    $no = 1;
    foreach ($linked_bahan as $item) {
        // Determine status class based on status
        $statusClass = '';
        switch($item['status']) {
            case 'pending':
                $statusClass = 'bg-yellow-100 text-yellow-800';
                break;
            case 'approved':
                $statusClass = 'bg-green-100 text-green-800';
                break;
            default:
                $statusClass = 'bg-red-100 text-red-800';
                break;
        }
        
        // Format qty based on status - for approved and retur status
        $qty_display = $item['qty'];
        
        // Adjust the quantity display based on status
        if ($item['status'] == 'approved') {
            // For approved items, show the quantity that was accepted
            $qty_display = $item['qty'];
        } elseif ($item['status'] == 'retur') {
            // For returned items, show the quantity that was returned
            $qty_display = isset($item['jumlah_retur']) && !empty($item['jumlah_retur']) ? $item['jumlah_retur'] : $item['qty'];
        }
        
        $html_output .= '<tr class="border-b">
            <td class="py-2 px-2 text-sm">' . $no++ . '</td>
            <td class="py-2 px-2 text-sm">' . $item['nama_barang'] . '</td>
            <td class="py-2 px-2 text-sm">' . $qty_display . ' ' . $item['satuan'] . '</td>
            <td class="py-2 px-2 text-sm">Periode ' . $item['periode'] . '</td>
            <td class="py-2 px-2 text-sm">' . $item['tanggal_formatted'] . '</td>
            <td class="py-2 px-2 text-sm">
                <span class="px-2 py-1 text-xs rounded-full ' . $statusClass . '">
                    ' . ucfirst($item['status']) . '
                </span>
            </td>
        </tr>';
    }

    $html_output .= '</tbody>
            </table>
        </div>
    </div>';
}

// Add action buttons based on status
if ($pesanan['status'] == 'pending') {
    $html_output .= '<div class="flex justify-end space-x-2">';
    
    // Process button removed as processing happens in bahan_baku.php
    
    $html_output .= '<button type="button" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="cancelPesanan(' . $pesanan['id_pesanan'] . ')">
        <i class="fas fa-times mr-1"></i> Batalkan Pesanan
    </button>';
    
    $html_output .= '</div>';
}

$response['html'] = $html_output;

// Return JSON response
// Clear any buffered output
ob_end_clean();
header('Content-Type: application/json');
// Ensure UTF-8 encoding
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit();
?> 