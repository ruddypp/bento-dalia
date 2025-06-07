<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
// Session is already started in required files, so we don't need to start it again
if (!isset($_SESSION['user_id'])) {
    echo '<div style="color: red; padding: 20px;">You must be logged in to print reports.</div>';
    exit;
}

// Pastikan parameter ada
if (!isset($_GET['date']) || !isset($_GET['periode']) || !isset($_GET['supplier_id'])) {
    echo "Parameter tidak lengkap";
    exit;
}

$date = sanitize($_GET['date']);
$periode = (int)$_GET['periode'];
$supplier_id = (int)$_GET['supplier_id'];

// Fungsi untuk mendapatkan ringkasan harian supplier
function getDailySupplierSummary($conn, $date, $periode, $supplier_id) {
    $query = "SELECT 
                DATE(bm.tanggal_masuk) as tanggal,
                COUNT(DISTINCT bm.id_masuk) as total_transaksi,
                SUM(bm.qty_masuk) as total_qty,
                COUNT(DISTINCT bm.id_barang) as total_jenis_barang,
                s.nama_supplier,
                bm.periode,
                COALESCE(lm.status, 'Pending') as status,
                lm.id_laporan_masuk
              FROM 
                barang_masuk bm
              JOIN 
                supplier s ON bm.id_supplier = s.id_supplier
              LEFT JOIN 
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN 
                laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              GROUP BY 
                DATE(bm.tanggal_masuk), bm.periode, s.id_supplier";
                
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sii", $date, $periode, $supplier_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } else {
        return [
            'total_transaksi' => 0,
            'total_qty' => 0,
            'total_jenis_barang' => 0,
            'nama_supplier' => 'Unknown',
            'status' => 'Pending'
        ];
    }
}

// Fungsi untuk mendapatkan item supplier harian
function getDailySupplierItems($conn, $date, $periode, $supplier_id) {
    $query = "SELECT 
                b.nama_barang,
                b.satuan,
                SUM(bm.qty_masuk) as total_qty,
                AVG(bm.harga_satuan) as avg_harga,
                CASE WHEN bm.lokasi = '' OR bm.lokasi IS NULL THEN '-' ELSE bm.lokasi END as lokasi,
                s.nama_supplier,
                lm.status
              FROM 
                barang_masuk bm
              JOIN 
                barang b ON bm.id_barang = b.id_barang
              JOIN 
                supplier s ON bm.id_supplier = s.id_supplier
              LEFT JOIN
                laporan_masuk_detail lmd ON bm.id_masuk = lmd.id_masuk
              LEFT JOIN
                laporan_masuk lm ON lmd.id_laporan = lm.id_laporan_masuk
              WHERE 
                DATE(bm.tanggal_masuk) = ? AND bm.periode = ? AND bm.id_supplier = ?
              GROUP BY 
                b.nama_barang, b.satuan, bm.lokasi, s.nama_supplier, lm.status";
                
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sii", $date, $periode, $supplier_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    return $items;
}

// Ambil data
$summary = getDailySupplierSummary($conn, $date, $periode, $supplier_id);
$items = getDailySupplierItems($conn, $date, $periode, $supplier_id);

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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Barang Masuk - <?= htmlspecialchars($summary['nama_supplier']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        .info {
            margin-bottom: 20px;
            width: 100%;
        }
        .info table {
            width: 100%;
            border-collapse: collapse;
        }
        .info td {
            padding: 5px;
            vertical-align: top;
        }
        .summary {
            margin-bottom: 20px;
            width: 100%;
        }
        .summary-card {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            width: 30%;
            display: inline-block;
            margin-right: 2%;
        }
        .summary-card h3 {
            margin: 0;
            font-size: 14px;
            color: #555;
        }
        .summary-card p {
            margin: 5px 0 0;
            font-size: 18px;
            font-weight: bold;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th,
        table.items td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table.items th {
            background-color: #f2f2f2;
            font-size: 12px;
        }
        table.items tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
        }
        .status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .status-approved {
            background-color: #D1FAE5;
            color: #065F46;
        }
        .status-rejected {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
        @media print {
            body {
                padding: 0;
                font-size: 12px;
            }
            .no-print {
                display: none;
            }
            .summary-card {
                border: 1px solid #aaa;
            }
            table.items th {
                background-color: #eee !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            table.items tr:nth-child(even) {
                background-color: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN BARANG MASUK</h1>
        <p>Inventory Management System</p>
    </div>
    
    <div class="info">
        <table>
            <tr>
                <td width="150"><strong>Supplier</strong></td>
                <td>: <?= htmlspecialchars($summary['nama_supplier']) ?></td>
                <td width="150"><strong>Tanggal</strong></td>
                <td>: <?= date('d M Y', strtotime($date)) ?></td>
            </tr>
            <tr>
                <td><strong>Periode</strong></td>
                <td>: <?= $periode ?></td>
                <td><strong>Status</strong></td>
                <td>: 
                    <span class="status <?= $summary['status'] == 'Approved' ? 'status-approved' : 
                       ($summary['status'] == 'Rejected' ? 'status-rejected' : 'status-pending') ?>">
                        <?= $summary['status'] ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="summary">
        <div class="summary-card">
            <h3>Total Transaksi</h3>
            <p><?= number_format($summary['total_transaksi']) ?></p>
        </div>
        <div class="summary-card">
            <h3>Total Kuantitas</h3>
            <p><?= number_format($summary['total_qty']) ?></p>
        </div>
        <div class="summary-card">
            <h3>Jenis Barang</h3>
            <p><?= number_format($summary['total_jenis_barang']) ?></p>
        </div>
    </div>
    
    <table class="items">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="30%">Nama Barang</th>
                <th width="15%">Kuantitas</th>
                <th width="10%">Satuan</th>
                <th width="20%">Harga Rata-Rata</th>
                <th width="20%">Lokasi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
            <tr>
                <td colspan="6" style="text-align: center;">Tidak ada data</td>
            </tr>
            <?php else: ?>
            <?php foreach ($items as $index => $item): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                <td><?= number_format($item['total_qty']) ?></td>
                <td><?= htmlspecialchars($item['satuan']) ?></td>
                <td>Rp <?= number_format($item['avg_harga'], 0, ',', '.') ?></td>
                <td><?= htmlspecialchars($item['lokasi']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
    </div>
    
    <script>
        // Auto print when loaded
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html> 