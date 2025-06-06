<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID bahan baku tidak diberikan");
}

$id = (int)$_GET['id'];

// Get bahan baku details
$query = "SELECT bb.*, b.nama_barang, b.satuan 
          FROM bahan_baku bb 
          JOIN barang b ON bb.id_barang = b.id_barang 
          WHERE bb.id_bahan_baku = ? AND bb.status = 'retur'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Data retur bahan baku tidak ditemukan atau status bukan retur");
}

$bahan_baku = mysqli_fetch_assoc($result);

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
    <title>Laporan Retur Bahan Baku</title>
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
            <h2>LAPORAN RETUR BAHAN BAKU</h2>
        </div>
        
        <div class="info">
            <div class="info-row">
                <div class="info-label">No. Dokumen</div>
                <div class="info-value">: RTR-<?= sprintf('%04d', $bahan_baku['id_bahan_baku']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal</div>
                <div class="info-value">: <?= formatTanggal($bahan_baku['tanggal_input']) ?></div>
            </div>
        </div>
        
        <table>
            <tr>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Total</th>
            </tr>
            <tr>
                <td><?= $bahan_baku['nama_barang'] ?></td>
                <td><?= $bahan_baku['qty'] ?></td>
                <td><?= $bahan_baku['satuan'] ?></td>
                <td><?= formatRupiah($bahan_baku['harga_satuan']) ?></td>
                <td><?= formatRupiah($bahan_baku['total']) ?></td>
            </tr>
        </table>
        
        <div class="info">
            <div class="info-row">
                <div class="info-label">Status</div>
                <div class="info-value">: <?= ucfirst($bahan_baku['status']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Lokasi</div>
                <div class="info-value">: <?= $bahan_baku['lokasi'] ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Periode</div>
                <div class="info-value">: Periode <?= $bahan_baku['periode'] ?></div>
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