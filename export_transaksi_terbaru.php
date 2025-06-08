<?php
// Turn off error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to prevent any unwanted output
ob_start();

require_once 'config/database.php';
require_once 'config/functions.php';
checkLogin();

// For PDF and Excel export
require_once 'vendor/autoload.php';

// Ensure all previous output is cleared
if (ob_get_length()) ob_end_clean();
ob_start();

// Get parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf'; // Default to PDF if not specified

// Format currency to Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Function to format date
function formatTanggal($date) {
    return date('d M Y', strtotime($date));
}

// Initialize variables
$transactions = [];
$total_penjualan = 0;
$total_keuntungan = 0;
$total_transaksi = 0;

// Get title
$title = 'Laporan Transaksi Terbaru';

// Fetch transaction data
try {
    // Recent transactions query
    $query_transactions = "SELECT p.*, 
                        COUNT(pd.id_penjualan_detail) as total_items,
                        u.nama_lengkap as nama_kasir
                    FROM penjualan p 
                    LEFT JOIN penjualan_detail pd ON p.id_penjualan = pd.id_penjualan 
                    LEFT JOIN users u ON p.id_user = u.id_user
                    WHERE DATE(p.tanggal_penjualan) BETWEEN ? AND ? 
                    GROUP BY p.id_penjualan 
                    ORDER BY p.tanggal_penjualan DESC";

    $stmt = $conn->prepare($query_transactions);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
        
        // Calculate totals
        $total_penjualan += $row['total_harga'];
        $total_keuntungan += $row['keuntungan'];
        $total_transaksi++;
    }

    // Check which format to export
    if ($format === 'excel') {
        // Excel export using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Inventory System')
            ->setLastModifiedBy('Administrator')
            ->setTitle($title)
            ->setSubject('Laporan Transaksi Terbaru')
            ->setDescription('Laporan Transaksi Terbaru dari ' . formatTanggal($start_date) . ' sampai ' . formatTanggal($end_date));
        
        // Set header styles
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Set title
        $sheet->setCellValue('A1', 'BENTO KOPI');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $sheet->setCellValue('A2', $title);
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setSize(14)->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $sheet->setCellValue('A3', 'Periode: ' . formatTanggal($start_date) . ' - ' . formatTanggal($end_date));
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Add some space
        $sheet->setCellValue('A5', 'Ringkasan');
        $sheet->mergeCells('A5:H5');
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $sheet->getStyle('A5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F0');
        
        // Summary headers
        $sheet->setCellValue('A6', 'Total Penjualan');
        $sheet->setCellValue('C6', 'Total Keuntungan');
        $sheet->setCellValue('E6', 'Jumlah Transaksi');
        $sheet->getStyle('A6:E6')->getFont()->setBold(true);
        
        // Summary values
        $sheet->setCellValue('A7', formatRupiah($total_penjualan));
        $sheet->setCellValue('C7', formatRupiah($total_keuntungan));
        $sheet->setCellValue('E7', $total_transaksi);
        
        // Add some space
        $sheet->setCellValue('A9', 'Detail Transaksi');
        $sheet->mergeCells('A9:H9');
        $sheet->getStyle('A9')->getFont()->setBold(true);
        $sheet->getStyle('A9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F0');
        
        // Headers
        $sheet->setCellValue('A10', 'No');
        $sheet->setCellValue('B10', 'No. Invoice');
        $sheet->setCellValue('C10', 'Tanggal');
        $sheet->setCellValue('D10', 'Pelanggan');
        $sheet->setCellValue('E10', 'Kasir');
        $sheet->setCellValue('F10', 'Items');
        $sheet->setCellValue('G10', 'Total');
        $sheet->setCellValue('H10', 'Keuntungan');
        $sheet->getStyle('A10:H10')->applyFromArray($headerStyle);
        
        // Data rows
        $row = 11;
        $no = 1;
        foreach ($transactions as $trx) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $trx['no_invoice']);
            $sheet->setCellValue('C' . $row, date('d M Y H:i', strtotime($trx['tanggal_penjualan'])));
            $sheet->setCellValue('D' . $row, !empty($trx['nama_pelanggan']) ? $trx['nama_pelanggan'] : '-');
            $sheet->setCellValue('E' . $row, !empty($trx['nama_kasir']) ? $trx['nama_kasir'] : '-');
            $sheet->setCellValue('F' . $row, $trx['total_items'] . ' item');
            $sheet->setCellValue('G' . $row, formatRupiah($trx['total_harga']));
            $sheet->setCellValue('H' . $row, formatRupiah($trx['keuntungan']));
            
            // Apply borders to the row
            $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Alternate row colors
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5F5');
            }
            
            $row++;
        }
        
        // Auto size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Set borders for all cells
        $sheet->getStyle('A10:H' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'transaksi_terbaru_' . $start_date . '_' . $end_date . '.xlsx';
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit();
        
    } else {
        // PDF export using TCPDF
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Inventory System');
        $pdf->SetAuthor('Administrator');
        $pdf->SetTitle($title);
        $pdf->SetSubject('Laporan Transaksi Terbaru');
        $pdf->SetKeywords('Laporan, Transaksi, PDF, Export');

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
        $summary_col_width = 90;
        $pdf->SetLineWidth(0.2); // Thicker lines for better visibility
        
        // First row - labels with better styling
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell($summary_col_width, 8, 'Total Penjualan', 1, 0, 'L', true);
        $pdf->Cell($summary_col_width, 8, 'Total Keuntungan', 1, 0, 'L', true);
        $pdf->Cell($summary_col_width, 8, 'Jumlah Transaksi', 1, 1, 'L', true);
        
        // Second row - values with better styling
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell($summary_col_width, 8, formatRupiah($total_penjualan), 1, 0, 'R');
        $pdf->Cell($summary_col_width, 8, formatRupiah($total_keuntungan), 1, 0, 'R');
        $pdf->Cell($summary_col_width, 8, $total_transaksi, 1, 1, 'R');
        
        $pdf->Ln(10);
        
        // Main data table with improved styling
        $pdf->SetFillColor(220, 230, 240);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Detail Transaksi', 0, 1, 'L', true);
        
        $pdf->Ln(3);
        
        // Table header for transactions - better styling
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        
        // Column widths adjusted for better fit without the Aksi column
        $pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'No. Invoice', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Tanggal', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Pelanggan', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Kasir', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Items', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Total', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Keuntungan', 1, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 9);
        
        // Data rows with alternating background
        if (empty($transactions)) {
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->Cell(290, 10, 'Tidak ada data transaksi untuk periode yang dipilih', 1, 1, 'C');
        } else {
            $no = 1;
            $row_count = 0;
            foreach ($transactions as $trx) {
                // Alternating row colors for better readability
                $row_fill = ($row_count % 2 == 0) ? false : true;
                if ($row_fill) $pdf->SetFillColor(245, 245, 245);
                
                $pdf->Cell(10, 7, $no++, 1, 0, 'C', $row_fill);
                $pdf->Cell(45, 7, $trx['no_invoice'], 1, 0, 'L', $row_fill);
                $pdf->Cell(40, 7, date('d M Y H:i', strtotime($trx['tanggal_penjualan'])), 1, 0, 'L', $row_fill);
                $pdf->Cell(50, 7, !empty($trx['nama_pelanggan']) ? $trx['nama_pelanggan'] : '-', 1, 0, 'L', $row_fill);
                $pdf->Cell(40, 7, !empty($trx['nama_kasir']) ? $trx['nama_kasir'] : '-', 1, 0, 'L', $row_fill);
                $pdf->Cell(25, 7, $trx['total_items'] . ' item', 1, 0, 'C', $row_fill);
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
        $filename = 'transaksi_terbaru_' . $start_date . '_' . $end_date . '.pdf';
        $pdf->Output($filename, 'D');
        exit();
    }
    
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
    echo "<h2>Terjadi Kesalahan Saat Export</h2>";
    echo "<p>Sistem tidak dapat menghasilkan file. Pesan error: " . $e->getMessage() . "</p>";
    echo "</div>";
    echo "<a href='laporan_penjualan.php' class='btn'>Kembali ke Laporan</a>";
    echo "</body></html>";
}

// End output buffering and clean it
if (ob_get_length()) ob_end_clean();

// Close the database connection
mysqli_close($conn);