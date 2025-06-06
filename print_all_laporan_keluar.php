<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Get date range filter if provided
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Get all reports within date range
$query = "SELECT lk.*, COUNT(lkd.id_detail_keluar) as item_count 
          FROM laporan_keluar lk
          LEFT JOIN laporan_keluar_detail lkd ON lk.id_laporan_keluar = lkd.id_laporan
          WHERE lk.tanggal_laporan BETWEEN ? AND ?
          GROUP BY lk.id_laporan_keluar
          ORDER BY lk.tanggal_laporan DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();

// Get company info
$query = "SELECT * FROM pengaturan LIMIT 1";
$result = mysqli_query($conn, $query);
$company = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Semua Laporan Barang Keluar</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @media print {
            @page { margin: 0; }
            body { margin: 1cm; }
            .no-print { display: none; }
            .print-only { display: block; }
        }
        .print-only { display: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white p-8 shadow-md">
        <div class="no-print mb-4">
            <form method="GET" action="" class="flex flex-wrap items-center gap-2 mb-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Dari Tanggal</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div class="self-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Filter
                    </button>
                </div>
            </form>
            
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                <i class="fas fa-print mr-2"></i> Cetak
            </button>
            <button onclick="window.close()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded ml-2">
                Tutup
            </button>
        </div>
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b-2 border-gray-300 pb-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold"><?= $company['nama_perusahaan'] ?? 'Sistem Inventory' ?></h1>
                <p class="text-gray-600"><?= $company['alamat'] ?? '' ?></p>
                <p class="text-gray-600"><?= $company['telepon'] ?? '' ?></p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold">Laporan Barang Keluar</h2>
                <p class="text-gray-600">Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
            </div>
        </div>
        
        <!-- Content -->
        <div class="mb-6">
            <table class="w-full border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 text-left">No</th>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-left">ID Laporan</th>
                        <th class="px-4 py-2 text-left">Jumlah Item</th>
                        <th class="px-4 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reports)): ?>
            <?php foreach ($reports as $index => $report): ?>
                            <tr>
                                <td class="px-4 py-2 border"><?= $index + 1 ?></td>
                                <td class="px-4 py-2 border"><?= date('d/m/Y', strtotime($report['tanggal_laporan'])) ?></td>
                                <td class="px-4 py-2 border">#<?= $report['id_laporan_keluar'] ?></td>
                                <td class="px-4 py-2 border"><?= $report['item_count'] ?> item</td>
                                <td class="px-4 py-2 border">
                                    <?= formatStatus($report['item_count']) ?>
                                </td>
                            </tr>
                            
                            <?php
                            // Get details for this report
                            $detailQuery = "SELECT b.nama_barang, bk.qty_keluar, b.satuan, bk.tanggal_keluar
                                           FROM laporan_keluar_detail lkd
                                           JOIN barang_keluar bk ON lkd.id_keluar = bk.id_keluar
                                           JOIN barang b ON bk.id_barang = b.id_barang
                                           WHERE lkd.id_laporan = ?";
                            $stmt = $conn->prepare($detailQuery);
                            $stmt->bind_param("i", $report['id_laporan_keluar']);
                            $stmt->execute();
                            $detailResult = $stmt->get_result();
                            
                            if ($detailResult->num_rows > 0):
                            ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-2 border bg-gray-50">
                                        <table class="w-full">
                    <thead>
                        <tr>
                                                    <th class="px-2 py-1 text-left text-xs">Nama Barang</th>
                                                    <th class="px-2 py-1 text-left text-xs">Jumlah</th>
                                                    <th class="px-2 py-1 text-left text-xs">Satuan</th>
                                                    <th class="px-2 py-1 text-left text-xs">Tanggal Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                                                <?php while ($detail = $detailResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td class="px-2 py-1 text-xs"><?= $detail['nama_barang'] ?></td>
                                                        <td class="px-2 py-1 text-xs"><?= $detail['qty_keluar'] ?></td>
                                                        <td class="px-2 py-1 text-xs"><?= $detail['satuan'] ?></td>
                                                        <td class="px-2 py-1 text-xs"><?= date('d/m/Y', strtotime($detail['tanggal_keluar'])) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </td>
                        </tr>
                            <?php 
                            endif;
                            $stmt->close();
                            ?>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-center">Tidak ada data laporan</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        <!-- Footer -->
        <div class="mt-8 text-right">
            <p class="mb-4">Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
            <div class="mt-8">
                <p>Mengetahui,</p>
                <div class="h-16"></div>
                <p class="font-bold">(________________)</p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            // Uncomment to automatically print
            // window.print();
        }
    </script>
</body>
</html> 

<?php
/**
 * Format the status based on the number of items in the report
 */
function formatStatus($detailCount) {
    return $detailCount > 0 ? 'Lengkap' : 'Belum Lengkap';
}
?> 