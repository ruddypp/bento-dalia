<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
// Session is already started in required files, so we don't need to start it again
if (!isset($_SESSION['user_id'])) {
    echo '<div style="color: red; padding: 20px;">You must be logged in to print reports.</div>';
    exit;
}

// Get parameters
$date = isset($_GET['date']) ? $_GET['date'] : '';
$periode = isset($_GET['periode']) ? (int)$_GET['periode'] : 0;
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;

// Validate parameters
if (empty($date) || $periode <= 0 || $supplier_id <= 0) {
    echo '<div style="color: red; padding: 20px;">Invalid parameters provided.</div>';
    exit;
}

// Get supplier information
$supplier_query = "SELECT * FROM supplier WHERE id_supplier = ?";
$supplier_stmt = $conn->prepare($supplier_query);

if (!$supplier_stmt) {
    echo '<div style="color: red; padding: 20px;">Database error: ' . $conn->error . '</div>';
    exit;
}

$supplier_stmt->bind_param("i", $supplier_id);
$supplier_stmt->execute();
$supplier_result = $supplier_stmt->get_result();
$supplier = $supplier_result->fetch_assoc();

if (!$supplier) {
    echo '<div style="color: red; padding: 20px;">Supplier not found.</div>';
    exit;
}

// Get all items for this supplier on this date
$items_query = "SELECT 
                b.nama_barang,
                b.satuan,
                bm.qty_masuk,
                bm.harga_satuan,
                CASE WHEN bm.lokasi = '' OR bm.lokasi IS NULL THEN '-' ELSE bm.lokasi END as lokasi,
                bm.tanggal_masuk,
                lm.status
              FROM 
                barang_masuk bm
              LEFT JOIN 
                barang b ON bm.id_barang = b.id_barang
              LEFT JOIN 
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN 
                laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              ORDER BY 
                b.nama_barang ASC";
                
$items_stmt = $conn->prepare($items_query);

if (!$items_stmt) {
    echo '<div style="color: red; padding: 20px;">Database error: ' . $conn->error . '</div>';
    exit;
}

$items_stmt->bind_param("sii", $date, $periode, $supplier_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Get summary information
$summary_query = "SELECT 
                    COUNT(DISTINCT bm.id_masuk) as total_transaksi,
                    SUM(bm.qty_masuk) as total_qty,
                    COUNT(DISTINCT bm.id_barang) as total_jenis_barang,
                    SUM(bm.qty_masuk * bm.harga_satuan) as total_nilai
                  FROM 
                    barang_masuk bm
                  WHERE 
                    DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?";
                    
$summary_stmt = $conn->prepare($summary_query);

if (!$summary_stmt) {
    echo '<div style="color: red; padding: 20px;">Database error: ' . $conn->error . '</div>';
    exit;
}

$summary_stmt->bind_param("sii", $date, $periode, $supplier_id);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();
$summary = $summary_result->fetch_assoc();

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

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
    <title>Laporan Barang Masuk - <?= date('d/m/Y', strtotime($date)) ?> - <?= htmlspecialchars($supplier['nama_supplier']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12pt;
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
        
        .info-section {
            margin-bottom: 20px;
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
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .footer {
            margin-top: 30px;
        }
        
        .signature {
            float: right;
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            margin-top: 80px;
            border-top: 1px solid #000;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
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
            <h2>Laporan Barang Masuk</h2>
            <p>Tanggal: <?= date('d F Y', strtotime($date)) ?> - Periode <?= $periode ?></p>
            <p>Supplier: <?= htmlspecialchars($supplier['nama_supplier']) ?></p>
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
                <div class="info-item">
                    <span class="info-label">Total Barang:</span>
                    <span><?= $summary['total_jenis_barang'] ?> jenis</span>
                </div>
            </div>
            <div class="info-right">
                <div class="info-item">
                    <span class="info-label">Supplier:</span>
                    <span><?= htmlspecialchars($supplier['nama_supplier']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kontak:</span>
                    <span><?= htmlspecialchars($supplier['kontak'] ?? '-') ?></span>
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
                
                if ($items_result->num_rows > 0) {
                    while ($item = $items_result->fetch_assoc()) {
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
        
        <div class="footer">
            <div class="signature">
                <p>Dicetak oleh:</p>
                <div class="signature-line"></div>
                <p><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                <p><?= date('d/m/Y H:i:s') ?></p>
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