<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID laporan tidak ditemukan");
}

$id_laporan = $_GET['id'];

// Get report data
$query = "SELECT lk.* FROM laporan_keluar lk WHERE lk.id_laporan_keluar = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_laporan);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();

if (!$report) {
    die("Laporan tidak ditemukan");
}

// Get report details
$query = "SELECT lkd.*, bk.qty_keluar, bk.tanggal_keluar, b.nama_barang, b.kode_barang, b.satuan
          FROM laporan_keluar_detail lkd
          JOIN barang_keluar bk ON lkd.id_keluar = bk.id_keluar
          JOIN barang b ON bk.id_barang = b.id_barang
          WHERE lkd.id_laporan = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_laporan);
$stmt->execute();
$result = $stmt->get_result();

$details = [];
while ($row = $result->fetch_assoc()) {
    $details[] = $row;
}
$stmt->close();

// Get company info
$query = "SELECT * FROM data_toko LIMIT 1";
$result = mysqli_query($conn, $query);
$company = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Barang Keluar #<?= $id_laporan ?></title>
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
                <h1 class="text-2xl font-bold"><?= $company['nama_toko'] ?? 'Sistem Inventory' ?></h1>
                <p class="text-gray-600"><?= $company['alamat'] ?? '' ?></p>
                <p class="text-gray-600"><?= $company['kontak'] ?? '' ?></p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold">Laporan Barang Keluar</h2>
                <p class="text-gray-600">No: #<?= $id_laporan ?></p>
                <p class="text-gray-600">Tanggal: <?= date('d/m/Y', strtotime($report['tanggal_laporan'])) ?></p>
            </div>
        </div>
        
        <!-- Content -->
        <div class="mb-6">
            <table class="w-full border">
            <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 text-left">No</th>
                        <th class="px-4 py-2 text-left">Tanggal Keluar</th>
                        <th class="px-4 py-2 text-left">Kode</th>
                        <th class="px-4 py-2 text-left">Nama Barang</th>
                        <th class="px-4 py-2 text-left">Jumlah</th>
                        <th class="px-4 py-2 text-left">Satuan</th>
                </tr>
            </thead>
            <tbody>
                    <?php if (!empty($details)): ?>
                        <?php foreach ($details as $index => $detail): ?>
                            <tr>
                                <td class="px-4 py-2 border"><?= $index + 1 ?></td>
                                <td class="px-4 py-2 border"><?= date('d/m/Y', strtotime($detail['tanggal_keluar'])) ?></td>
                                <td class="px-4 py-2 border"><?= $detail['kode_barang'] ?? '-' ?></td>
                                <td class="px-4 py-2 border"><?= $detail['nama_barang'] ?></td>
                                <td class="px-4 py-2 border"><?= $detail['qty_keluar'] ?></td>
                                <td class="px-4 py-2 border"><?= $detail['satuan'] ?></td>
                </tr>
                        <?php endforeach; ?>
                <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-2 text-center">Tidak ada data detail</td>
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