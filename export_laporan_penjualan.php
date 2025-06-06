<?php
// Turn off error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to prevent any unwanted output
ob_start();

require_once 'config/database.php';
require_once 'config/functions.php';
checkLogin();

// For PDF export
require_once 'vendor/autoload.php';

// Ensure all previous output is cleared
if (ob_get_length()) ob_end_clean();
ob_start();

// Get parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'daily';
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily_report';

// Format currency to Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Function to format date
function formatTanggal($date) {
    return date('d M Y', strtotime($date));
}

// Initialize variables
$reports = [];
$total_penjualan = 0;
$total_modal = 0;
$total_keuntungan = 0;
$total_transaksi = 0;

// Get title based on report type
$title = '';
switch($report_type) {
    case 'daily_report':
        $title = 'Laporan ' . ($filter_type === 'daily' ? 'Harian' : 'Bulanan') . ' Penjualan';
        break;
    case 'top_items':
        $title = 'Laporan Menu Terlaris';
        break;
    case 'recent_transactions':
        $title = 'Laporan Transaksi Terbaru';
        break;
}

// Fetch appropriate data based on report type
try {
    if ($report_type === 'daily_report') {
        // Daily or Monthly report query
        if ($filter_type === 'daily') {
            $query = "SELECT * FROM laporan_penjualan 
                    WHERE tanggal BETWEEN ? AND ? 
                    ORDER BY tanggal DESC";
        } else {
            $query = "SELECT 
                        DATE_FORMAT(tanggal, '%Y-%m') AS bulan, 
                        SUM(total_penjualan) AS total_penjualan,
                        SUM(total_modal) AS total_modal,
                        SUM(total_keuntungan) AS total_keuntungan,
                        SUM(jumlah_transaksi) AS jumlah_transaksi
                    FROM laporan_penjualan 
                    WHERE tanggal BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
                    ORDER BY bulan DESC";
        }

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
            
            // Calculate totals
            $total_penjualan += $row['total_penjualan'];
            $total_modal += $row['total_modal'];
            $total_keuntungan += $row['total_keuntungan'];
            $total_transaksi += $row['jumlah_transaksi'];
        }
    } elseif ($report_type === 'top_items') {
        // Top selling menu items
        $query_top = "SELECT m.id_menu, m.nama_menu, m.kategori, 
                    SUM(pd.jumlah) AS total_terjual, 
                    SUM(pd.subtotal) AS total_penjualan,
                    SUM(pd.subtotal_modal) AS total_modal,
                    SUM(pd.subtotal - pd.subtotal_modal) AS total_keuntungan
                FROM penjualan p
                JOIN penjualan_detail pd ON p.id_penjualan = pd.id_penjualan
                JOIN menu m ON pd.id_menu = m.id_menu
                WHERE DATE(p.tanggal_penjualan) BETWEEN ? AND ?
                GROUP BY m.id_menu
                ORDER BY total_terjual DESC
                LIMIT 20";

        $stmt = $conn->prepare($query_top);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
            
            // Calculate totals
            $total_penjualan += $row['total_penjualan'];
            $total_modal += $row['total_modal'];
            $total_keuntungan += $row['total_keuntungan'];
            $total_transaksi += $row['total_terjual'];
        }
    } elseif ($report_type === 'recent_transactions') {
        // Recent transactions
        $query_transactions = "SELECT p.*, 
                            COUNT(pd.id_penjualan_detail) as total_items,
                            u.nama as nama_kasir
                        FROM penjualan p 
                        LEFT JOIN penjualan_detail pd ON p.id_penjualan = pd.id_penjualan 
                        LEFT JOIN users u ON p.id_user = u.id_user
                        WHERE DATE(p.tanggal_penjualan) BETWEEN ? AND ? 
                        GROUP BY p.id_penjualan 
                        ORDER BY p.tanggal_penjualan DESC 
                        LIMIT 50";

        $stmt = $conn->prepare($query_transactions);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
            
            // Calculate totals
            $total_penjualan += $row['total_harga'];
            $total_keuntungan += $row['keuntungan'];
            $total_transaksi++;
        }
    }

    // Initialize TCPDF with higher quality settings
    $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Inventory System');
    $pdf->SetAuthor('Administrator');
    $pdf->SetTitle($title);
    $pdf->SetSubject('Laporan Penjualan');
    $pdf->SetKeywords('Laporan, Penjualan, PDF, Export');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set higher quality settings
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->SetMargins(15, 15, 15);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Better font settings
    $pdf->SetFont('helvetica', '', 11);

    // Add a page
    $pdf->AddPage();
    
    // Store name with better formatting
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'BENTO KOPI', 0, 1, 'C');
    
    // Report title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    
    // Period with better formatting
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 8, 'Periode: ' . formatTanggal($start_date) . ' - ' . formatTanggal($end_date), 0, 1, 'C');
    
    // Add some space
    $pdf->Ln(8);
    
    // Summary Box with better styling
    $pdf->SetFillColor(220, 230, 240); // Soft blue
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Ringkasan', 0, 1, 'L', true);
    
    $pdf->Ln(3);
    
    // Summary table with better design
    $summary_col_width = 67;
    $pdf->SetLineWidth(0.2); // Thicker lines for better visibility
    
    // First row - labels with better styling
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell($summary_col_width, 8, 'Total Penjualan', 1, 0, 'L', true);
    $pdf->Cell($summary_col_width, 8, 'Total Modal', 1, 0, 'L', true);
    $pdf->Cell($summary_col_width, 8, 'Total Keuntungan', 1, 0, 'L', true);
    $pdf->Cell($summary_col_width, 8, 'Jumlah Transaksi', 1, 1, 'L', true);
    
    // Second row - values with better styling
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($summary_col_width, 8, formatRupiah($total_penjualan), 1, 0, 'R');
    $pdf->Cell($summary_col_width, 8, formatRupiah($total_modal), 1, 0, 'R');
    $pdf->Cell($summary_col_width, 8, formatRupiah($total_keuntungan), 1, 0, 'R');
    $pdf->Cell($summary_col_width, 8, $total_transaksi, 1, 1, 'R');
    
    $pdf->Ln(10);
    
    // Main data table with improved styling
    $pdf->SetFillColor(220, 230, 240);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Detail Data', 0, 1, 'L', true);
    
    $pdf->Ln(3);
    
    if ($report_type === 'daily_report') {
        // Table header for daily/monthly report - better styling
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        
        $pdf->Cell(40, 8, ($filter_type === 'daily' ? 'Tanggal' : 'Bulan'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Transaksi', 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'Penjualan', 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'Modal', 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'Keuntungan', 1, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 10);
        
        // Data rows with alternating background
        $row_count = 0;
        foreach ($reports as $report) {
            // Alternating row colors for better readability
            $row_fill = ($row_count % 2 == 0) ? false : true;
            if ($row_fill) $pdf->SetFillColor(245, 245, 245);
            
            $date_display = '';
            if ($filter_type === 'daily') {
                $date_display = formatTanggal($report['tanggal']);
            } else {
                $date_display = date('M Y', strtotime($report['bulan'] . '-01'));
            }
            
            $pdf->Cell(40, 7, $date_display, 1, 0, 'L', $row_fill);
            $pdf->Cell(30, 7, $report['jumlah_transaksi'], 1, 0, 'C', $row_fill);
            $pdf->Cell(70, 7, formatRupiah($report['total_penjualan']), 1, 0, 'R', $row_fill);
            $pdf->Cell(70, 7, formatRupiah($report['total_modal']), 1, 0, 'R', $row_fill);
            $pdf->Cell(70, 7, formatRupiah($report['total_keuntungan']), 1, 1, 'R', $row_fill);
            
            $row_count++;
        }
    } elseif ($report_type === 'top_items') {
        // Table header for top items - better styling
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        
        $pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'Menu', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Kategori', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Terjual', 1, 0, 'C', true);
        $pdf->Cell(60, 8, 'Penjualan', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Modal', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Keuntungan', 1, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 10);
        
        // Data rows with alternating background
        $no = 1;
        $row_count = 0;
        foreach ($reports as $item) {
            // Alternating row colors for better readability
            $row_fill = ($row_count % 2 == 0) ? false : true;
            if ($row_fill) $pdf->SetFillColor(245, 245, 245);
            
            $pdf->Cell(10, 7, $no++, 1, 0, 'C', $row_fill);
            $pdf->Cell(70, 7, $item['nama_menu'], 1, 0, 'L', $row_fill);
            $pdf->Cell(30, 7, ucfirst($item['kategori']), 1, 0, 'C', $row_fill);
            $pdf->Cell(30, 7, $item['total_terjual'], 1, 0, 'C', $row_fill);
            $pdf->Cell(60, 7, formatRupiah($item['total_penjualan']), 1, 0, 'R', $row_fill);
            $pdf->Cell(40, 7, formatRupiah($item['total_modal']), 1, 0, 'R', $row_fill);
            $pdf->Cell(40, 7, formatRupiah($item['total_keuntungan']), 1, 1, 'R', $row_fill);
            
            $row_count++;
        }
    } elseif ($report_type === 'recent_transactions') {
        // Table header for transactions - better styling
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        
        $pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'No. Invoice', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Tanggal', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Pelanggan', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Kasir', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Items', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Total', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Keuntungan', 1, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 9);
        
        // Data rows with alternating background
        $no = 1;
        $row_count = 0;
        foreach ($reports as $trx) {
            // Alternating row colors for better readability
            $row_fill = ($row_count % 2 == 0) ? false : true;
            if ($row_fill) $pdf->SetFillColor(245, 245, 245);
            
            $pdf->Cell(10, 7, $no++, 1, 0, 'C', $row_fill);
            $pdf->Cell(40, 7, $trx['no_invoice'], 1, 0, 'L', $row_fill);
            $pdf->Cell(35, 7, date('d M Y H:i', strtotime($trx['tanggal_penjualan'])), 1, 0, 'L', $row_fill);
            $pdf->Cell(50, 7, !empty($trx['nama_pelanggan']) ? $trx['nama_pelanggan'] : '-', 1, 0, 'L', $row_fill);
            $pdf->Cell(35, 7, !empty($trx['nama_kasir']) ? $trx['nama_kasir'] : '-', 1, 0, 'L', $row_fill);
            $pdf->Cell(20, 7, $trx['total_items'] . ' item', 1, 0, 'C', $row_fill);
            $pdf->Cell(40, 7, formatRupiah($trx['total_harga']), 1, 0, 'R', $row_fill);
            $pdf->Cell(40, 7, formatRupiah($trx['keuntungan']), 1, 1, 'R', $row_fill);
            
            $row_count++;
        }
    }

    // Footer with date and page number - better styling
    $pdf->SetY(-20);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Dicetak pada: ' . date('d/m/Y H:i:s'), 0, 0, 'L');
    $pdf->Cell(0, 10, 'Halaman ' . $pdf->getAliasNumPage() . ' dari ' . $pdf->getAliasNbPages(), 0, 0, 'R');

    // Output the PDF with a more descriptive filename
    $filename = 'laporan_' . $report_type . '_' . $start_date . '_' . $end_date . '.pdf';
    $pdf->Output($filename, 'D');
    exit();
    
} catch (Exception $e) {
    // Log the error
    error_log("Export error: " . $e->getMessage());
    
    // Display user-friendly message
    echo "<html><head><title>Export Error</title>";
    echo "<style>body { font-family: Arial, sans-serif; line-height: 1.6; margin: 40px; }";
    echo ".error-box { border: 1px solid #f8d7da; background-color: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin-bottom: 20px; }";
    echo ".btn { display: inline-block; padding: 8px 16px; background-color: #4a5568; color: white; text-decoration: none; border-radius: 4px; margin-top: 15px; }";
    echo "</style></head><body>";
    echo "<div class='error-box'>";
    echo "<h2>Terjadi Kesalahan Saat Export PDF</h2>";
    echo "<p>Sistem tidak dapat menghasilkan file PDF. Pesan error: " . $e->getMessage() . "</p>";
    echo "</div>";
    echo "<a href='laporan_penjualan.php' class='btn'>Kembali ke Laporan</a>";
    echo "</body></html>";
}

// End output buffering and clean it
if (ob_get_length()) ob_end_clean();

// Close the database connection
mysqli_close($conn); 