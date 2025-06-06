<?php
$pageTitle = "Cetak Data Bahan Baku";
require_once 'config/database.php';
require_once 'config/functions.php';

// Check login status
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Get filter values from URL parameters
$filter_periode = isset($_GET['periode']) ? (int)$_GET['periode'] : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Sanitize the filter_status if it's not empty
if (!empty($filter_status)) {
    $filter_status = mysqli_real_escape_string($conn, $filter_status);
}

// Build query based on filters
$query = "SELECT bb.*, b.nama_barang, b.satuan 
          FROM bahan_baku bb 
          JOIN barang b ON bb.id_barang = b.id_barang";

$where_clauses = [];

if ($filter_periode > 0) {
    $where_clauses[] = "bb.periode = $filter_periode";
}

if ($filter_status) {
    $where_clauses[] = "bb.status = '$filter_status'";
} else {
    $where_clauses[] = "bb.status != 'retur'"; // Default: exclude retur items
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY bb.tanggal_input DESC";

$bahan_baku_list = mysqli_query($conn, $query);

// Calculate total per periode
$total_per_periode = [];
$periode_query = "SELECT periode, SUM(total) as total_periode FROM bahan_baku WHERE status != 'retur' GROUP BY periode";
$periode_result = mysqli_query($conn, $periode_query);

while ($row = mysqli_fetch_assoc($periode_result)) {
    $total_per_periode[$row['periode']] = $row['total_periode'];
}

// Get current date and time
$tanggal_cetak = date('d-m-Y H:i:s');

// Get filter text for display
$filter_text = "Semua Data";
if ($filter_periode > 0) {
    $filter_text = "Periode " . $filter_periode;
}
if ($filter_status) {
    $filter_text .= ($filter_periode > 0 ? ", Status: " : "Status: ") . ucfirst($filter_status);
}
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
        .status-pending {
            background-color: #FFF9C4;
        }
        .status-approved {
            background-color: #C8E6C9;
        }
        .status-retur {
            background-color: #FFCDD2;
        }
        .filter-form {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .filter-form select, .filter-form button {
            padding: 5px;
            margin-right: 10px;
        }
        @media print {
            .print-button, .filter-form {
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
    
    <div class="filter-form">
        <form method="GET" action="">
            <label for="periode">Filter Periode:</label>
            <select id="periode" name="periode">
                <option value="0">Semua Periode</option>
                <option value="1" <?= $filter_periode == 1 ? 'selected' : '' ?>>Periode 1</option>
                <option value="2" <?= $filter_periode == 2 ? 'selected' : '' ?>>Periode 2</option>
                <option value="3" <?= $filter_periode == 3 ? 'selected' : '' ?>>Periode 3</option>
                <option value="4" <?= $filter_periode == 4 ? 'selected' : '' ?>>Periode 4</option>
            </select>
            
            <label for="status">Filter Status:</label>
            <select id="status" name="status">
                <option value="">Semua Status (Kecuali Retur)</option>
                <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $filter_status == 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="retur" <?= $filter_status == 'retur' ? 'selected' : '' ?>>Retur</option>
            </select>
            
            <button type="submit">Filter</button>
            <a href="print_bahan_baku.php" style="text-decoration: none; color: #333;">Reset</a>
        </form>
    </div>
    
    <div class="header">
        <h1>DATA BAHAN BAKU</h1>
        <p>Tanggal Cetak: <?= $tanggal_cetak ?></p>
        <p>Filter: <?= $filter_text ?></p>
    </div>
    
    <div class="summary">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="summary-item">
            <strong>Total Periode <?= $i ?>:</strong> <?= isset($total_per_periode[$i]) ? formatRupiah($total_per_periode[$i]) : formatRupiah(0) ?>
        </div>
        <?php endfor; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Periode</th>
                <th>Harga Satuan</th>
                <th>Total</th>
                <th>Lokasi</th>
                <th>Tanggal Input</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $grand_total = 0;
            while ($item = mysqli_fetch_assoc($bahan_baku_list)): 
                $grand_total += $item['total'];
                
                // Determine row class based on status
                $rowClass = "";
                switch($item['status']) {
                    case 'pending':
                        $rowClass = "status-pending";
                        break;
                    case 'approved':
                        $rowClass = "status-approved";
                        break;
                    case 'retur':
                        $rowClass = "status-retur";
                        break;
                }
            ?>
            <tr class="<?= $rowClass ?>">
                <td><?= $no++ ?></td>
                <td><?= $item['nama_barang'] ?></td>
                <td><?= $item['qty'] ?></td>
                <td><?= $item['satuan'] ?></td>
                <td>Periode <?= $item['periode'] ?></td>
                <td><?= formatRupiah($item['harga_satuan']) ?></td>
                <td><?= formatRupiah($item['total']) ?></td>
                <td><?= $item['lokasi'] ?></td>
                <td><?= date('d M Y H:i', strtotime($item['tanggal_input'])) ?></td>
                <td><?= ucfirst($item['status']) ?></td>
            </tr>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($bahan_baku_list) == 0): ?>
            <tr>
                <td colspan="10" style="text-align: center;">Tidak ada data bahan baku</td>
            </tr>
            <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: right;"><strong>Grand Total:</strong></td>
                <td><strong><?= formatRupiah($grand_total) ?></strong></td>
                <td colspan="3"></td>
            </tr>
            <?php endif; ?>
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