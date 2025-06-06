<?php
$pageTitle = "Cetak Data Barang";
require_once 'config/database.php';
require_once 'config/functions.php';

// Check login status
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Format rupiah function
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Get all items with supplier info
$query = "SELECT b.*, s.nama_supplier 
          FROM barang b 
          LEFT JOIN supplier s ON b.id_supplier = s.id_supplier
          ORDER BY b.nama_barang ASC";
$result = mysqli_query($conn, $query);

// Calculate total inventory value
$total_inventory_value = 0;
$total_items = 0;

// Store items in an array
$all_items = [];
while ($item = mysqli_fetch_assoc($result)) {
    $all_items[] = $item;
    $total_inventory_value += $item['stok'] * $item['harga'];
    $total_items += $item['stok'];
}

// Get current date and time
$tanggal_cetak = date('d-m-Y H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .summary {
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .summary-item {
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .print-button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .low-stock {
            background-color: #FFECB3;
        }
        .out-of-stock {
            background-color: #FFCDD2;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                size: landscape;
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
    
    <div class="header">
        <h1>DATA INVENTARIS BARANG</h1>
        <p>Tanggal Cetak: <?= $tanggal_cetak ?></p>
    </div>
    
    <div class="summary">
        <div class="summary-item"><strong>Total Jenis Barang:</strong> <?= count($all_items) ?></div>
        <div class="summary-item"><strong>Total Quantity Barang:</strong> <?= $total_items ?> items</div>
        <div class="summary-item"><strong>Total Nilai Inventaris:</strong> <?= formatRupiah($total_inventory_value) ?></div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Jenis</th>
                <th>Satuan</th>
                <th>Stok</th>
                <th>Stok Min</th>
                <th>Harga</th>
                <th>Total Nilai</th>
                <th>Supplier</th>
                <th>Lokasi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($all_items as $row): 
                $rowClass = "";
                if ($row['stok'] <= 0) {
                    $rowClass = "out-of-stock";
                } elseif ($row['stok'] <= $row['stok_minimum']) {
                    $rowClass = "low-stock";
                }
                $total_nilai = $row['stok'] * $row['harga'];
            ?>
            <tr class="<?= $rowClass ?>">
                <td><?= $no++ ?></td>
                <td><?= $row['nama_barang'] ?></td>
                <td><?= $row['jenis'] ?></td>
                <td><?= $row['satuan'] ?></td>
                <td><?= $row['stok'] ?></td>
                <td><?= $row['stok_minimum'] ?></td>
                <td><?= formatRupiah($row['harga']) ?></td>
                <td><?= formatRupiah($total_nilai) ?></td>
                <td><?= $row['nama_supplier'] ?? '-' ?></td>
                <td><?= ucfirst($row['lokasi'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Dicetak oleh: <?= $_SESSION['username'] ?? 'Admin' ?></p>
        <p><?= date('d F Y') ?></p>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            // Uncomment the line below to automatically print when page loads
            // window.print();
        };
    </script>
</body>
</html> 