<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
// Session is already started in required files, so we don't need to start it again
if (!isset($_SESSION['user_id'])) {
    echo '<div style="color: red; padding: 20px;">You must be logged in to print reports.</div>';
    exit;
}

// Function to get all days with laporan masuk
function getAllDaysWithLaporan($conn) {
    $query = "SELECT 
              DATE(bm.tanggal_masuk) as tanggal,
              bm.periode,
              s.id_supplier,
              s.nama_supplier,
              COUNT(DISTINCT bm.id_masuk) as entry_count,
              MAX(lm.status) as status
              FROM barang_masuk bm
              JOIN laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              JOIN laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              JOIN barang b ON bm.id_barang = b.id_barang
              JOIN supplier s ON bm.id_supplier = s.id_supplier
              GROUP BY DATE(bm.tanggal_masuk), bm.periode, s.id_supplier
              ORDER BY DATE(bm.tanggal_masuk) DESC, s.nama_supplier ASC";
    
    $result = mysqli_query($conn, $query);
    
    $days = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $days[] = $row;
    }
    
    return $days;
}

// Function to get daily supplier summary
function getDailySupplierSummary($conn, $date, $periode, $supplier_id) {
    $query = "SELECT 
                COUNT(DISTINCT bm.id_masuk) as total_transaksi,
                SUM(bm.qty_masuk) as total_qty,
                COUNT(DISTINCT bm.id_barang) as total_jenis_barang,
                SUM(bm.qty_masuk * bm.harga_satuan) as total_nilai
              FROM 
                barang_masuk bm
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?";
                
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [
            'total_transaksi' => 0,
            'total_qty' => 0,
            'total_jenis_barang' => 0,
            'total_nilai' => 0
        ];
    }
    
    $stmt->bind_param("sii", $date, $periode, $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return [
            'total_transaksi' => 0,
            'total_qty' => 0,
            'total_jenis_barang' => 0,
            'total_nilai' => 0
        ];
    }
}

// Function to get all supplier items received on a specific day
function getDailySupplierItems($conn, $date, $periode, $supplier_id) {
    $query = "SELECT 
                b.nama_barang,
                b.satuan,
                bm.qty_masuk,
                bm.harga_satuan,
                CASE WHEN bm.lokasi = '' OR bm.lokasi IS NULL THEN '-' ELSE bm.lokasi END as lokasi,
                bm.tanggal_masuk
              FROM 
                barang_masuk bm
              LEFT JOIN 
                barang b ON bm.id_barang = b.id_barang
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              ORDER BY 
                b.nama_barang ASC";
                
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("sii", $date, $periode, $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Get all days with reports
$all_days = getAllDaysWithLaporan($conn);

// Get company name from settings (if table exists)
$company_name = "Inventory System";
$company_address = "";

// Check if settings table exists
$check_settings_table = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if ($check_settings_table && mysqli_num_rows($check_settings_table) > 0) {
    // Settings table exists, proceed with queries
    $settings_query = "SELECT * FROM settings WHERE setting_key = 'company_name' LIMIT 1";
    $settings_result = mysqli_query($conn, $settings_query);
    if ($settings_result && mysqli_num_rows($settings_result) > 0) {
        $setting = mysqli_fetch_assoc($settings_result);
        $company_name = $setting['setting_value'];
    }
    
    // Get company address from settings
    $address_query = "SELECT * FROM settings WHERE setting_key = 'company_address' LIMIT 1";
    $address_result = mysqli_query($conn, $address_query);
    if ($address_result && mysqli_num_rows($address_result) > 0) {
        $setting = mysqli_fetch_assoc($address_result);
        $company_address = $setting['setting_value'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Laporan Barang Masuk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 11pt;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18pt;
        }
        
        .header p {
            margin: 5px 0;
        }
        
        .report-header {
            margin-top: 30px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #333;
        }
        
        .info-section {
            margin-bottom: 15px;
            width: 100%;
            display: flex;
        }
        
        .info-left, .info-right {
            width: 50%;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 10pt;
            text-align: center;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 10mm;
            }
            
            button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($company_name) ?></h1>
            <?php if (!empty($company_address)): ?>
            <p><?= htmlspecialchars($company_address) ?></p>
            <?php endif; ?>
            <h2>Laporan Semua Barang Masuk</h2>
            <p>Dicetak pada: <?= date('d F Y H:i:s') ?></p>
        </div>
        
        <?php 
        $currentDate = '';
        $totalAllReports = 0;
        
        foreach ($all_days as $index => $day): 
            $date = $day['tanggal'];
            $periode = $day['periode'];
            $supplier_id = $day['id_supplier'];
            $supplier_name = $day['nama_supplier'];
            
            // Get summary and items
            $summary = getDailySupplierSummary($conn, $date, $periode, $supplier_id);
            $items = getDailySupplierItems($conn, $date, $periode, $supplier_id);
            
            // Add to total across all reports
            $totalAllReports += $summary['total_nilai'];
            
            // Check if we need a page break
            if ($index > 0 && $currentDate != $date) {
                echo '<div class="page-break"></div>';
            }
            
            // Update current date
            $currentDate = $date;
        ?>
        
        <div class="report-header">
            <h3>Tanggal: <?= date('d F Y', strtotime($date)) ?> - Periode <?= $periode ?> - <?= htmlspecialchars($supplier_name) ?></h3>
        </div>
        
        <div class="info-section">
            <div class="info-left">
                <div class="info-item">
                    <span class="info-label">Tanggal:</span>
                    <span><?= date('d F Y', strtotime($date)) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Periode:</span>
                    <span><?= $periode ?></span>
                </div>
            </div>
            <div class="info-right">
                <div class="info-item">
                    <span class="info-label">Supplier:</span>
                    <span><?= htmlspecialchars($supplier_name) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Nilai:</span>
                    <span><?= formatRupiah($summary['total_nilai']) ?></span>
                </div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Qty</th>
                    <th>Satuan</th>
                    <th>Harga Satuan</th>
                    <th>Total</th>
                    <th>Lokasi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $totalNilai = 0;
                
                if (count($items) > 0) {
                    foreach ($items as $item) {
                        $total = $item['qty_masuk'] * $item['harga_satuan'];
                        $totalNilai += $total;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                    <td><?= $item['qty_masuk'] ?></td>
                    <td><?= htmlspecialchars($item['satuan']) ?></td>
                    <td><?= formatRupiah($item['harga_satuan']) ?></td>
                    <td><?= formatRupiah($total) ?></td>
                    <td><?= ucfirst($item['lokasi']) ?></td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data barang</td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong><?= formatRupiah($totalNilai) ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
        <?php endforeach; ?>
        
        <?php if (count($all_days) === 0): ?>
        <div style="text-align: center; margin-top: 50px;">
            <p>Tidak ada data laporan yang tersedia.</p>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Total Nilai Semua Laporan: <strong><?= formatRupiah($totalAllReports) ?></strong></p>
            <p>Dicetak oleh: <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?> | <?= date('d/m/Y H:i:s') ?></p>
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