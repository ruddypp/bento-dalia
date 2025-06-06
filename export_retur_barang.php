<?php
// Turn off error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to prevent any unwanted output
ob_start();

require_once 'config/database.php';
require_once 'config/functions.php';
checkLogin();

// For Excel export
require_once 'vendor/autoload.php'; // Make sure you have required libraries installed via Composer
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Ensure all previous output is cleared
ob_end_clean();
ob_start();

// Get export format from URL
$format = isset($_GET['format']) ? sanitize($_GET['format']) : 'pdf';
$week = isset($_GET['week']) ? sanitize($_GET['week']) : '';

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Parse the week string (format: YYYY-Www) if provided
$week_condition = '';
$week_condition_bb = '';
if (!empty($week) && preg_match('/^(\d{4})-W(\d{2})$/', $week, $matches)) {
    $year = $matches[1];
    $weekNumber = $matches[2];
    
    // Calculate the start and end dates of the week
    $dto = new DateTime();
    $dto->setISODate($year, $weekNumber);
    $start_date = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $end_date = $dto->format('Y-m-d');
    
    $week_condition = " AND DATE(rb.tanggal_retur) BETWEEN '$start_date' AND '$end_date'";
    $week_condition_bb = " AND DATE(bb.tanggal_input) BETWEEN '$start_date' AND '$end_date'";
}

// Since the retur_barang table is empty, let's just use the bahan_baku table
$query = "SELECT NULL as id_retur, bb.id_barang, bb.jumlah_retur as qty_retur, 
           bb.tanggal_input as tanggal_retur, bb.catatan_retur as alasan_retur,
           bb.harga_satuan, bb.total, bb.periode, bb.id_bahan_baku, bb.jumlah_masuk,
           b.nama_barang, b.satuan, u.nama_lengkap as nama_pengguna
           FROM bahan_baku bb 
           JOIN barang b ON bb.id_barang = b.id_barang 
           LEFT JOIN users u ON bb.id_user = u.id_user
           LEFT JOIN pesanan_barang pb ON bb.id_pesanan = pb.id_pesanan
           WHERE bb.status = 'retur'
           $week_condition_bb
           ORDER BY bb.tanggal_input DESC";

// Execute the query
$result = mysqli_query($conn, $query);

// Check if there's data
if (!$result) {
    echo "Query error: " . mysqli_error($conn);
    exit;
}

if (mysqli_num_rows($result) == 0) {
    echo "Tidak ada data retur barang yang ditemukan. Silakan tambahkan data retur terlebih dahulu.";
    exit;
}

try {
    // Handle export based on format
    if ($format === 'pdf') {
        // Export to PDF
        // Clear any previous output to avoid PDF corruption
        if (ob_get_length()) ob_end_clean();
        
        // Initialize TCPDF with landscape orientation
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Inventory System');
        $pdf->SetAuthor('Administrator');
        $pdf->SetTitle('Data Retur Barang');
        $pdf->SetSubject('Retur Barang Export');
        $pdf->SetKeywords('Retur Barang, PDF, Export');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');

        // Set margins
        $pdf->SetMargins(10, 10, 10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 10);

        // Set image scale factor
        $pdf->setImageScale(1.25);

        // Disable compression for troubleshooting
        $pdf->setCompression(false);

        // Set font - make sure this font exists in TCPDF fonts directory
        $pdf->SetFont('helvetica', '', 10);

        // Add a page
        $pdf->AddPage();

        // Set content - use basic fonts to avoid issues
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Data Retur Barang', 0, 1, 'C');
        
        if (!empty($week)) {
            // Parse the week string (format: YYYY-Www)
            if (preg_match('/^(\d{4})-W(\d{2})$/', $week, $matches)) {
                $year = $matches[1];
                $weekNumber = $matches[2];
                
                // Calculate the start and end dates of the week
                $dto = new DateTime();
                $dto->setISODate($year, $weekNumber);
                $start_date = $dto->format('d M Y');
                $dto->modify('+6 days');
                $end_date = $dto->format('d M Y');
                
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, "Periode: $start_date - $end_date (Minggu $weekNumber, $year)", 0, 1, 'C');
            }
        }
        
        $pdf->Ln(5);

        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        
        // Table header cells
        $pdf->Cell(8, 7, 'No', 1, 0, 'C', 1);
        $pdf->Cell(45, 7, 'Nama Barang', 1, 0, 'C', 1);
        $pdf->Cell(30, 7, 'Qty Retur', 1, 0, 'C', 1);
        $pdf->Cell(15, 7, 'Satuan', 1, 0, 'C', 1);
        $pdf->Cell(35, 7, 'Harga Satuan', 1, 0, 'C', 1);
        $pdf->Cell(35, 7, 'Total', 1, 0, 'C', 1);
        $pdf->Cell(20, 7, 'Periode', 1, 0, 'C', 1);
        $pdf->Cell(35, 7, 'Tanggal Retur', 1, 0, 'C', 1);
        $pdf->Cell(45, 7, 'Alasan Retur', 1, 1, 'C', 1);

        // Table data
        $pdf->SetFont('helvetica', '', 8);
        $no = 1;
        $grand_total = 0;
        
        mysqli_data_seek($result, 0); // Reset result pointer to beginning
        
        while ($row = mysqli_fetch_assoc($result)) {
            $pdf->Cell(8, 6, $no++, 1, 0, 'C');
            $pdf->Cell(45, 6, $row['nama_barang'], 1, 0, 'L');
            
            // For items with jumlah_masuk > 0, show both returned and accepted quantities
            if (!empty($row['jumlah_masuk']) && $row['jumlah_masuk'] > 0) {
                $pdf->Cell(30, 6, $row['qty_retur'] . ' (Masuk: ' . $row['jumlah_masuk'] . ')', 1, 0, 'C');
            } else {
                $pdf->Cell(30, 6, $row['qty_retur'], 1, 0, 'C');
            }
            
            $pdf->Cell(15, 6, $row['satuan'], 1, 0, 'C');
            $pdf->Cell(35, 6, formatRupiah($row['harga_satuan']), 1, 0, 'R');
            $pdf->Cell(35, 6, formatRupiah($row['total']), 1, 0, 'R');
            $pdf->Cell(20, 6, 'Periode ' . $row['periode'], 1, 0, 'C');
            $pdf->Cell(35, 6, date('d M Y H:i', strtotime($row['tanggal_retur'])), 1, 0, 'C');
            $pdf->Cell(45, 6, $row['alasan_retur'], 1, 1, 'L');
            
            $grand_total += $row['total'];
        }

        // Output total row
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(133, 7, 'Total', 1, 0, 'R', 1);
        $pdf->Cell(35, 7, formatRupiah($grand_total), 1, 0, 'R', 1);
        $pdf->Cell(100, 7, '', 1, 1, 'C', 1);

        // Close and output PDF document
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="retur_barang_export.pdf"');
        $pdf->Output('retur_barang_export.pdf', 'D');
        exit();
    } else if ($format === 'excel') {
        // Export to Excel
        
        // Clear any previous output to avoid Excel corruption
        if (ob_get_length()) ob_end_clean();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set the title
        $sheet->setCellValue('A1', 'DATA RETUR BARANG');
        
        if (!empty($week)) {
            // Parse the week string (format: YYYY-Www)
            if (preg_match('/^(\d{4})-W(\d{2})$/', $week, $matches)) {
                $year = $matches[1];
                $weekNumber = $matches[2];
                
                // Calculate the start and end dates of the week
                $dto = new DateTime();
                $dto->setISODate($year, $weekNumber);
                $start_date = $dto->format('d M Y');
                $dto->modify('+6 days');
                $end_date = $dto->format('d M Y');
                
                $sheet->setCellValue('A2', "Periode: $start_date - $end_date (Minggu $weekNumber, $year)");
            }
        }
        
        // Make the title span multiple columns
        $spreadsheet->getActiveSheet()->mergeCells('A1:I1');
        if (!empty($week)) {
            $spreadsheet->getActiveSheet()->mergeCells('A2:I2');
        }
        
        // Style the title
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        if (!empty($week)) {
            $spreadsheet->getActiveSheet()->getStyle('A2')->applyFromArray($titleStyle);
        }
        
        // Header row starting position
        $row = !empty($week) ? 4 : 3;
        
        // Set headers
        $sheet->setCellValue('A'.$row, 'No');
        $sheet->setCellValue('B'.$row, 'Nama Barang');
        $sheet->setCellValue('C'.$row, 'Qty Retur');
        $sheet->setCellValue('D'.$row, 'Satuan');
        $sheet->setCellValue('E'.$row, 'Harga Satuan');
        $sheet->setCellValue('F'.$row, 'Total');
        $sheet->setCellValue('G'.$row, 'Periode');
        $sheet->setCellValue('H'.$row, 'Tanggal Retur');
        $sheet->setCellValue('I'.$row, 'Alasan Retur');
        
        // Style the header
        $headerStyle = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E9E9E9',
                ],
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A'.$row.':I'.$row)->applyFromArray($headerStyle);
        
        // Add data
        $no = 1;
        $row++;
        $grand_total = 0;
        
        mysqli_data_seek($result, 0); // Reset result pointer to beginning
        
        while ($data = mysqli_fetch_assoc($result)) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $data['nama_barang']);
            
            // For items with jumlah_masuk > 0, show both returned and accepted quantities
            if (!empty($data['jumlah_masuk']) && $data['jumlah_masuk'] > 0) {
                $sheet->setCellValue('C'.$row, $data['qty_retur'] . ' (Masuk: ' . $data['jumlah_masuk'] . ')');
            } else {
                $sheet->setCellValue('C'.$row, $data['qty_retur']);
            }
            
            $sheet->setCellValue('D'.$row, $data['satuan']);
            $sheet->setCellValue('E'.$row, $data['harga_satuan']);
            $sheet->setCellValue('F'.$row, $data['total']);
            $sheet->setCellValue('G'.$row, 'Periode ' . $data['periode']);
            $sheet->setCellValue('H'.$row, date('d M Y H:i', strtotime($data['tanggal_retur'])));
            $sheet->setCellValue('I'.$row, $data['alasan_retur']);
            
            $grand_total += $data['total'];
            $row++;
        }
        
        // Add total row
        $sheet->setCellValue('A'.$row, 'Total');
        $sheet->setCellValue('F'.$row, $grand_total);
        
        // Merge cells for "Total" label
        $spreadsheet->getActiveSheet()->mergeCells('A'.$row.':E'.$row);
        
        // Style the total row
        $totalStyle = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E9E9E9',
                ],
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A'.$row.':I'.$row)->applyFromArray($totalStyle);
        
        // Format currency cells
        $currencyFormat = '#,##0';
        $sheet->getStyle('E4:F'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
        
        // Auto-size columns
        foreach (range('A', 'I') as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Style all data cells
        $dataCellStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A'.(!empty($week) ? 4 : 3).':I'.$row)->applyFromArray($dataCellStyle);
        
        // Set content type headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="retur_barang_export.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Create the writer and save the file
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(false);
        
        // Save directly to output
        $writer->save('php://output');
        exit();
    } else {
        // Invalid format
        echo "Invalid export format";
    }
} catch (Exception $e) {
    // Log the error
    error_log("Export error: " . $e->getMessage());
    
    // Display user-friendly message
    echo "An error occurred during export: " . $e->getMessage();
}

// End output buffering and clean it
if (ob_get_length()) ob_end_clean();

// Close the database connection
mysqli_close($conn); 