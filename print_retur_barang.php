<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID retur tidak diberikan");
}

$id = (int)$_GET['id'];

// Get retur details
$query = "SELECT rb.*, b.nama_barang, b.satuan, u.nama_lengkap as nama_pengguna 
          FROM retur_barang rb 
          JOIN bahan_baku b ON rb.id_bahan_baku = b.id_bahan_baku 
          JOIN users u ON rb.id_user = u.id_user 
          WHERE rb.id_retur = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Data retur tidak ditemukan");
}

$retur = mysqli_fetch_assoc($result);

// Get detail barang yang diretur
$query_detail = "SELECT dt.*, b.nama_barang, b.satuan 
                FROM detail_terima dt
                JOIN barang b ON dt.id_barang = b.id_barang
                WHERE dt.id_penerimaan = ?";
$stmt_detail = mysqli_prepare($conn, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $retur['id_penerimaan']);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

// Get toko info
$query_toko = "SELECT * FROM data_toko WHERE id_toko = 1";
$result_toko = mysqli_query($conn, $query_toko);
$toko = mysqli_fetch_assoc($result_toko);

// Format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Format tanggal
function formatTanggal($tanggal) {
    return date('d F Y', strtotime($tanggal));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Retur Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        .info {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            width: 150px;
            font-weight: bold;
        }
        .info-value {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
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
            margin-top: 60px;
        }
        .print-button {
            text-align: center;
            margin-top: 20px;
        }
        .print-button button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
            }
            .container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= $toko['nama_toko'] ?? 'Inventory System' ?></h1>
            <p><?= $toko['alamat'] ?? 'Alamat Toko' ?></p>
            <p><?= $toko['kontak'] ?? 'Kontak Toko' ?></p>
            <h2>LAPORAN RETUR BARANG</h2>
        </div>
        
        <div class="info">
            <div class="info-row">
                <div class="info-label">No. Dokumen</div>
                <div class="info-value">: RTR-<?= sprintf('%04d', $retur['id_retur']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal Retur</div>
                <div class="info-value">: <?= formatTanggal($retur['tanggal_retur']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Supplier</div>
                <div class="info-value">: <?= $retur['nama_supplier'] ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Petugas</div>
                <div class="info-value">: <?= $retur['nama_pengguna'] ?></div>
            </div>
        </div>
        
        <h3>Alasan Retur</h3>
        <div class="mb-4 p-3 bg-gray-100 rounded-lg">
            <?= nl2br($retur['alasan_retur']) ?>
        </div>
        
        <h3>Detail Barang</h3>
        <table>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Jumlah Diretur</th>
                <th>Satuan</th>
            </tr>
            <?php 
            $no = 1;
            $total_qty_retur = 0;
            if (mysqli_num_rows($result_detail) > 0): 
                while ($detail = mysqli_fetch_assoc($result_detail)):
                    $total_qty_retur += $detail['jumlah_diterima'];
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $detail['nama_barang'] ?></td>
                <td><?= $detail['jumlah_diterima'] ?></td>
                <td><?= $detail['satuan'] ?></td>
            </tr>
            <?php 
                endwhile; 
            else:
            ?>
            <tr>
                <td colspan="4" class="text-center">Tidak ada detail barang</td>
            </tr>
            <?php endif; ?>
        </table>
        
        <div class="info">
            <div class="info-row">
                <div class="info-label">Total Barang Diretur</div>
                <div class="info-value">: <?= $total_qty_retur ?> item</div>
            </div>
        </div>
        
        <div class="footer">
            <p>Dicetak pada: <?= date('d F Y H:i:s') ?></p>
            
            <div class="signature">
                <p>Penanggung Jawab</p>
                <br><br><br>
                <p>(_________________________)</p>
            </div>
        </div>
        
        <div class="print-button">
            <button onclick="window.print()">Cetak Laporan</button>
        </div>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            // Uncomment line below to automatically print
            // window.print();
        };
    </script>
</body>
</html> 