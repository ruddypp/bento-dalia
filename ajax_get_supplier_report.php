<?php
// Start the session to access $_SESSION
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
// require_once 'role_permission_check.php'; // Removed this line as it blocks crew users from seeing details

// Pastikan parameter ada
if (!isset($_GET['date']) || !isset($_GET['periode']) || !isset($_GET['supplier_id'])) {
    echo "<div class='text-red-500 p-4'>Parameter tidak lengkap</div>";
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-red-500 p-4'>Silakan login terlebih dahulu</div>";
    exit;
}

$date = sanitize($_GET['date']);
$periode = (int)$_GET['periode'];
$supplier_id = (int)$_GET['supplier_id'];

// Fungsi untuk mendapatkan ringkasan harian supplier
function getDailySupplierSummary($conn, $date, $periode, $supplier_id) {
    $query = "SELECT 
                DATE(bm.tanggal_masuk) as tanggal,
                COUNT(DISTINCT bm.id_masuk) as total_transaksi,
                SUM(bm.qty_masuk) as total_qty,
                COUNT(DISTINCT bm.id_barang) as total_jenis_barang,
                s.nama_supplier,
                bm.periode,
                COALESCE(lm.status, 'Pending') as status,
                lm.id_laporan_masuk
              FROM 
                barang_masuk bm
              JOIN 
                supplier s ON bm.id_supplier = s.id_supplier
              LEFT JOIN 
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN 
                laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              GROUP BY 
                DATE(bm.tanggal_masuk), bm.periode, s.id_supplier";
                
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sii", $date, $periode, $supplier_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } else {
        return [
            'total_transaksi' => 0,
            'total_qty' => 0,
            'total_jenis_barang' => 0,
            'nama_supplier' => 'Unknown',
            'status' => 'Pending'
        ];
    }
}

// Fungsi untuk mendapatkan item supplier harian
function getDailySupplierItems($conn, $date, $periode, $supplier_id) {
    $query = "SELECT 
                b.nama_barang,
                b.satuan,
                SUM(bm.qty_masuk) as total_qty,
                AVG(bm.harga_satuan) as avg_harga,
                CASE WHEN bm.lokasi = '' OR bm.lokasi IS NULL THEN '-' ELSE bm.lokasi END as lokasi,
                s.nama_supplier,
                lm.status
              FROM 
                barang_masuk bm
              JOIN 
                barang b ON bm.id_barang = b.id_barang
              JOIN 
                supplier s ON bm.id_supplier = s.id_supplier
              LEFT JOIN
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN
                laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              GROUP BY 
                b.nama_barang, b.satuan, bm.lokasi, s.nama_supplier, lm.status";
                
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sii", $date, $periode, $supplier_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    return $items;
}

// Ambil data ringkasan dan item
$summary = getDailySupplierSummary($conn, $date, $periode, $supplier_id);
$items = getDailySupplierItems($conn, $date, $periode, $supplier_id);

// Render tampilan detail
?>
<div class="p-4">
    <!-- Bagian Header -->
    <div class="mb-4 border-b pb-3">
        <h3 class="text-lg font-semibold"><?= htmlspecialchars($summary['nama_supplier']) ?></h3>
        <div class="flex flex-wrap gap-4 text-sm text-gray-600 mt-2">
            <div>
                <span class="font-medium">Tanggal:</span> <?= date('d M Y', strtotime($date)) ?>
            </div>
            <div>
                <span class="font-medium">Periode:</span> <?= $periode ?>
            </div>
            <div>
                <span class="font-medium">Status:</span> 
                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                    <?= $summary['status'] == 'Approved' ? 'bg-green-100 text-green-800' : 
                       ($summary['status'] == 'Rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                    <?= $summary['status'] ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Ringkasan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-blue-800 text-sm font-medium">Total Transaksi</div>
            <div class="text-2xl font-bold"><?= number_format($summary['total_transaksi']) ?></div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-green-800 text-sm font-medium">Total Kuantitas</div>
            <div class="text-2xl font-bold"><?= number_format($summary['total_qty']) ?></div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
            <div class="text-purple-800 text-sm font-medium">Jenis Barang</div>
            <div class="text-2xl font-bold"><?= number_format($summary['total_jenis_barang']) ?></div>
        </div>
    </div>
    
    <!-- Tabel Item -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kuantitas</th>
                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Rata-Rata</th>
                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="py-3 px-4 text-center text-gray-500">Tidak ada data</td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                <tr class="<?= $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' ?>">
                    <td class="py-2 px-4 border-b"><?= $index + 1 ?></td>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($item['nama_barang']) ?></td>
                    <td class="py-2 px-4 border-b"><?= number_format($item['total_qty']) ?></td>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($item['satuan']) ?></td>
                    <td class="py-2 px-4 border-b">Rp <?= number_format($item['avg_harga'], 0, ',', '.') ?></td>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($item['lokasi']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Tombol Print -->
    <div class="mt-6 flex justify-end">
        <button onclick="printReport('<?= $date ?>', <?= $periode ?>, <?= $supplier_id ?>)" 
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
            <i class="fas fa-print mr-2"></i> Cetak Laporan
        </button>
    </div>
</div> 