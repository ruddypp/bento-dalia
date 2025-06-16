<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Function to get first detail item for a laporan
function getFirstLaporanMasukDetail($conn, $id_laporan) {
    try {
        // From check_tables.php we see we need to join with barang_masuk to get item details
        $query = "SELECT bm.*, b.nama_barang, b.satuan, s.nama_supplier 
                FROM laporan_masuk_detail lmd
                JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
                JOIN barang b ON bm.id_barang = b.id_barang
                JOIN supplier s ON bm.id_supplier = s.id_supplier
                WHERE lmd.id_laporan = ? 
                ORDER BY lmd.id_detail LIMIT 1";
                
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in getFirstLaporanMasukDetail: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $id_laporan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $detail = $result->fetch_assoc();
            $stmt->close();
            return $detail;
        }
        
        $stmt->close();
        return null;
    } catch (Exception $e) {
        error_log("Error in getFirstLaporanMasukDetail: " . $e->getMessage());
        return null;
    }
}

// Get store info for header
$store_info = getStoreInfo();

// Get all laporan masuk
$query = "SELECT lm.*, u.nama_pengguna
          FROM laporan_masuk lm
          LEFT JOIN pengguna u ON lm.created_by = u.id_pengguna
          ORDER BY lm.tanggal_laporan DESC";

$result = $conn->query($query);
$laporan_list = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $laporan_list[] = $row;
    }
}

// Title based on date range
$title = "SEMUA LAPORAN BARANG MASUK";
$subtitle = "Per Tanggal " . date('d F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Semua Laporan Barang Masuk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12pt;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
        }
        .header p {
            margin: 5px 0;
        }
        .report-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 20px 0 5px;
        }
        .report-subtitle {
            text-align: center;
            font-size: 12pt;
            margin: 0 0 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .signature {
            margin-top: 80px;
        }
        @media print {
            body {
                font-size: 12pt;
            }
            .no-print {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($store_info['nama_toko'] ?? 'Sistem Inventori') ?></h1>
            <p><?= htmlspecialchars($store_info['alamat'] ?? '') ?></p>
            <p>Telp: <?= htmlspecialchars($store_info['kontak'] ?? '') ?></p>
        </div>
        
        <div class="report-title"><?= $title ?></div>
        <div class="report-subtitle"><?= $subtitle ?></div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Laporan</th>
                    <th>Tanggal Laporan</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Supplier</th>
                    <th>Dibuat Oleh</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_list)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada laporan</td>
                </tr>
                <?php else: ?>
                    <?php 
                    $no = 1; 
                    foreach($laporan_list as $laporan): 
                        // Get first item detail for display
                        $firstItem = getFirstLaporanMasukDetail($conn, $laporan['id_laporan_masuk']);
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $laporan['id_laporan_masuk'] ?></td>
                        <td><?= date('d F Y', strtotime($laporan['tanggal_laporan'])) ?></td>
                        <td><?= $firstItem ? htmlspecialchars($firstItem['nama_barang']) : '-' ?></td>
                        <td><?= $firstItem ? htmlspecialchars($firstItem['qty_masuk']) : '-' ?></td>
                        <td><?= $firstItem ? htmlspecialchars($firstItem['nama_supplier']) : '-' ?></td>
                        <td><?= htmlspecialchars($laporan['nama_pengguna'] ?? 'N/A') ?></td>
                        <td><?= ucfirst(htmlspecialchars($laporan['status'] ?? 'pending')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <div>
                <p>
                    <?= date('d F Y') ?><br>
                    Mengetahui,
                </p>
                <div class="signature">
                    <p>(_________________________)</p>
                    <p>Manajer</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto print when loaded
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html> 