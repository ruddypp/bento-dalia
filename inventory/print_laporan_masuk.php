<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div style="color: red; text-align: center; margin-top: 50px;">Error: ID tidak ditemukan</div>';
    exit;
}

$id_laporan = $_GET['id'];

// Get store info for header
$store_info = getStoreInfo();

// Get the main report data
$query = "SELECT lm.*, u.nama_pengguna
          FROM laporan_masuk lm
          LEFT JOIN pengguna u ON lm.created_by = u.id_pengguna
          WHERE lm.id_laporan_masuk = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo '<div style="color: red; text-align: center; margin-top: 50px;">Error: ' . $conn->error . '</div>';
    exit;
}

$stmt->bind_param("i", $id_laporan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div style="color: red; text-align: center; margin-top: 50px;">Error: Laporan tidak ditemukan</div>';
    exit;
}

$laporan = $result->fetch_assoc();

// Get all the detail items
$query_detail = "SELECT bm.*, b.nama_barang, b.satuan, s.nama_supplier 
                FROM laporan_masuk_detail lmd
                JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
                JOIN barang b ON bm.id_barang = b.id_barang
                JOIN supplier s ON bm.id_supplier = s.id_supplier
                WHERE lmd.id_laporan = ?";

$stmt_detail = $conn->prepare($query_detail);
if (!$stmt_detail) {
    echo '<div style="color: red; text-align: center; margin-top: 50px;">Error: ' . $conn->error . '</div>';
    exit;
}

$stmt_detail->bind_param("i", $id_laporan);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

$details = [];
while ($row = $result_detail->fetch_assoc()) {
    $details[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Barang Masuk #<?= $id_laporan ?></title>
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
            margin: 20px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .info-item label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
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
        
        <div class="report-title">LAPORAN BARANG MASUK</div>
        
        <div class="info-grid">
            <div class="info-item">
                <label>No. Laporan:</label>
                <span><?= $laporan['id_laporan_masuk'] ?></span>
            </div>
            <div class="info-item">
                <label>Tanggal Laporan:</label>
                <span><?= date('d F Y', strtotime($laporan['tanggal_laporan'])) ?></span>
            </div>
            <div class="info-item">
                <label>Dibuat Oleh:</label>
                <span><?= $laporan['nama_pengguna'] ?? 'N/A' ?></span>
            </div>
            <div class="info-item">
                <label>Tanggal Dibuat:</label>
                <span><?= $laporan['created_at'] ? date('d F Y H:i', strtotime($laporan['created_at'])) : 'N/A' ?></span>
            </div>
            <div class="info-item">
                <label>Status:</label>
                <span><?= ucfirst($laporan['status'] ?? 'Pending') ?></span>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Supplier</th>
                    <th>Tanggal Masuk</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($details)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada detail barang</td>
                </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($details as $detail): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($detail['nama_barang']) ?></td>
                        <td><?= htmlspecialchars($detail['qty_masuk']) ?></td>
                        <td><?= htmlspecialchars($detail['satuan']) ?></td>
                        <td><?= htmlspecialchars($detail['nama_supplier']) ?></td>
                        <td><?= date('d F Y', strtotime($detail['tanggal_masuk'])) ?></td>
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