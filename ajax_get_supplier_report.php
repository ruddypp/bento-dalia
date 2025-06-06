<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
// Session is already started in required files, so we don't need to start it again
if (!isset($_SESSION['user_id'])) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded">You must be logged in to view this content.</div>';
    exit;
}

// Get parameters
$date = isset($_GET['date']) ? $_GET['date'] : '';
$periode = isset($_GET['periode']) ? (int)$_GET['periode'] : 0;
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;

// Validate parameters
if (empty($date) || $periode <= 0 || $supplier_id <= 0) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded">Invalid parameters provided.</div>';
    exit;
}

// Get supplier information
$supplier_query = "SELECT * FROM supplier WHERE id_supplier = ?";
$supplier_stmt = $conn->prepare($supplier_query);

if (!$supplier_stmt) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded">Database error: ' . $conn->error . '</div>';
    exit;
}

$supplier_stmt->bind_param("i", $supplier_id);
$supplier_stmt->execute();
$supplier_result = $supplier_stmt->get_result();
$supplier = $supplier_result->fetch_assoc();

if (!$supplier) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded">Supplier not found.</div>';
    exit;
}

// Get all items for this supplier on this date
$items_query = "SELECT 
                b.nama_barang,
                b.satuan,
                bm.qty_masuk,
                bm.harga_satuan,
                CASE WHEN bm.lokasi = '' OR bm.lokasi IS NULL THEN '-' ELSE bm.lokasi END as lokasi,
                bm.tanggal_masuk,
                lm.status
              FROM 
                barang_masuk bm
              LEFT JOIN 
                barang b ON bm.id_barang = b.id_barang
              LEFT JOIN 
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN 
                laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              ORDER BY 
                b.nama_barang ASC";
                
$items_stmt = $conn->prepare($items_query);

if (!$items_stmt) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded">Database error: ' . $conn->error . '</div>';
    exit;
}

$items_stmt->bind_param("sii", $date, $periode, $supplier_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Get summary information
$summary_query = "SELECT 
                    COUNT(DISTINCT bm.id_masuk) as total_transaksi,
                    SUM(bm.qty_masuk) as total_qty,
                    COUNT(DISTINCT bm.id_barang) as total_jenis_barang,
                    SUM(bm.qty_masuk * bm.harga_satuan) as total_nilai
                  FROM 
                    barang_masuk bm
                  WHERE 
                    DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?";
                    
$summary_stmt = $conn->prepare($summary_query);

if (!$summary_stmt) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded">Database error: ' . $conn->error . '</div>';
    exit;
}

$summary_stmt->bind_param("sii", $date, $periode, $supplier_id);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();
$summary = $summary_result->fetch_assoc();

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Start output
?>

<div class="space-y-4">
    <!-- Header Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="text-lg font-medium text-gray-800">Informasi Laporan</h4>
            <p class="text-sm text-gray-600"><span class="font-medium">Tanggal:</span> <?= date('d F Y', strtotime($date)) ?></p>
            <p class="text-sm text-gray-600"><span class="font-medium">Periode:</span> <?= $periode ?></p>
            <p class="text-sm text-gray-600"><span class="font-medium">Total Jenis Barang:</span> <?= $summary['total_jenis_barang'] ?></p>
            <p class="text-sm text-gray-600"><span class="font-medium">Total Qty:</span> <?= $summary['total_qty'] ?></p>
            <p class="text-sm text-gray-600"><span class="font-medium">Total Nilai:</span> <?= formatRupiah($summary['total_nilai']) ?></p>
        </div>
        <div>
            <h4 class="text-lg font-medium text-gray-800">Informasi Supplier</h4>
            <p class="text-sm text-gray-600"><span class="font-medium">Nama Supplier:</span> <?= htmlspecialchars($supplier['nama_supplier']) ?></p>
            <p class="text-sm text-gray-600"><span class="font-medium">Kontak:</span> <?= htmlspecialchars($supplier['kontak'] ?? '-') ?></p>
            <p class="text-sm text-gray-600"><span class="font-medium">Alamat:</span> <?= htmlspecialchars($supplier['alamat'] ?? '-') ?></p>
        </div>
    </div>

    <!-- Items Table -->
    <div>
        <h4 class="text-lg font-medium text-gray-800 mb-2">Detail Barang</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Barang</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $totalNilai = 0;
                    
                    if ($items_result->num_rows > 0) {
                        while ($item = $items_result->fetch_assoc()) {
                            $total = $item['qty_masuk'] * $item['harga_satuan'];
                            $totalNilai += $total;
                    ?>
                    <tr class="border-b">
                        <td class="py-2 px-3 text-sm"><?= $no++ ?></td>
                        <td class="py-2 px-3 text-sm"><?= htmlspecialchars($item['nama_barang']) ?></td>
                        <td class="py-2 px-3 text-sm"><?= $item['qty_masuk'] ?></td>
                        <td class="py-2 px-3 text-sm"><?= htmlspecialchars($item['satuan']) ?></td>
                        <td class="py-2 px-3 text-sm"><?= formatRupiah($item['harga_satuan']) ?></td>
                        <td class="py-2 px-3 text-sm"><?= formatRupiah($total) ?></td>
                        <td class="py-2 px-3 text-sm"><?= ucfirst($item['lokasi']) ?></td>
                    </tr>
                    <?php 
                        }
                    } else {
                    ?>
                    <tr class="border-b">
                        <td colspan="7" class="py-4 px-3 text-center text-gray-500">Tidak ada data barang</td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="5" class="py-2 px-3 text-right text-sm font-medium">Total:</td>
                        <td class="py-2 px-3 text-sm font-bold"><?= formatRupiah($totalNilai) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div> 