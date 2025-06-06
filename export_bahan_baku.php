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
$filter_periode = isset($_GET['periode']) ? (int)$_GET['periode'] : 0;

// Build query
$query = "SELECT bb.*, b.nama_barang, b.satuan 
          FROM bahan_baku bb 
          JOIN barang b ON bb.id_barang = b.id_barang
          LEFT JOIN pesanan_barang pb ON bb.id_pesanan = pb.id_pesanan";

// Add filter conditions if periode is selected
if ($filter_periode > 0) {
    $query .= " WHERE bb.periode = $filter_periode AND (pb.status != 'dibatalkan' OR pb.status IS NULL)";
} else {
    $query .= " WHERE (pb.status != 'dibatalkan' OR pb.status IS NULL)";
}

// Add ordering
$query .= " ORDER BY bb.tanggal_input DESC";

// Execute the query
$result = mysqli_query($conn, $query);

// Check if there's data
if (!$result || mysqli_num_rows($result) == 0) {
    echo "No data found";
    exit;
}

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
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
        $pdf->SetTitle('Data Bahan Baku');
        $pdf->SetSubject('Bahan Baku Export');
        $pdf->SetKeywords('Bahan Baku, PDF, Export');

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
        $pdf->Cell(0, 10, 'Data Bahan Baku', 0, 1, 'C');
        
        if ($filter_periode > 0) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'Periode: ' . $filter_periode, 0, 1, 'C');
        }
        
        $pdf->Ln(5);

        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        
        // Table header cells
        $pdf->Cell(8, 7, 'No', 1, 0, 'C', 1);
        $pdf->Cell(60, 7, 'Nama Barang', 1, 0, 'C', 1);
        $pdf->Cell(15, 7, 'Qty', 1, 0, 'C', 1);
        $pdf->Cell(20, 7, 'Satuan', 1, 0, 'C', 1);
        $pdf->Cell(20, 7, 'Periode', 1, 0, 'C', 1);
        $pdf->Cell(35, 7, 'Harga Satuan', 1, 0, 'C', 1);
        $pdf->Cell(35, 7, 'Total', 1, 0, 'C', 1);
        $pdf->Cell(25, 7, 'Lokasi', 1, 0, 'C', 1);
        $pdf->Cell(35, 7, 'Tanggal Input', 1, 0, 'C', 1);
        $pdf->Cell(20, 7, 'Status', 1, 1, 'C', 1);

        // Table data
        $pdf->SetFont('helvetica', '', 8);
        $no = 1;
        
        mysqli_data_seek($result, 0); // Reset result pointer to beginning
        
        while ($row = mysqli_fetch_assoc($result)) {
            $pdf->Cell(8, 6, $no++, 1, 0, 'C');
            $pdf->Cell(60, 6, $row['nama_barang'], 1, 0, 'L');
            
            // For retur items, show the non-returned quantity (jumlah_masuk)
            if ($row['status'] == 'retur' && $row['jumlah_masuk'] > 0) {
                $pdf->Cell(15, 6, $row['jumlah_masuk'] . ' (R:' . $row['jumlah_retur'] . ')', 1, 0, 'C');
            } else {
                $pdf->Cell(15, 6, $row['qty'], 1, 0, 'C');
            }
            
            $pdf->Cell(20, 6, $row['satuan'], 1, 0, 'C');
            $pdf->Cell(20, 6, 'Periode ' . $row['periode'], 1, 0, 'C');
            $pdf->Cell(35, 6, formatRupiah($row['harga_satuan']), 1, 0, 'R');
            $pdf->Cell(35, 6, formatRupiah($row['total']), 1, 0, 'R');
            $pdf->Cell(25, 6, $row['lokasi'], 1, 0, 'C');
            $pdf->Cell(35, 6, date('d M Y H:i', strtotime($row['tanggal_input'])), 1, 0, 'C');
            $pdf->Cell(20, 6, ucfirst($row['status']), 1, 1, 'C');
        }

        // Calculate total
        mysqli_data_seek($result, 0); // Reset result pointer to beginning
        $grand_total = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $grand_total += $row['total'];
        }
        
        // Output total row
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(173, 7, 'Total', 1, 0, 'R', 1);
        $pdf->Cell(35, 7, formatRupiah($grand_total), 1, 0, 'R', 1);
        $pdf->Cell(80, 7, '', 1, 1, 'C', 1);

        // Close and output PDF document - using 'I' instead of 'D' for inline viewing to troubleshoot
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="bahan_baku_export.pdf"');
        $pdf->Output('bahan_baku_export.pdf', 'D');
        exit();
    } else if ($format === 'excel') {
        // Export to Excel - PhpSpreadsheet classes are already loaded above
        
        // Clear any previous output to avoid Excel corruption
        if (ob_get_length()) ob_end_clean();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set the title
        $sheet->setCellValue('A1', 'DATA BAHAN BAKU');
        if ($filter_periode > 0) {
            $sheet->setCellValue('A2', 'Periode: ' . $filter_periode);
        }
        
        // Make the title span multiple columns
        $spreadsheet->getActiveSheet()->mergeCells('A1:J1');
        if ($filter_periode > 0) {
            $spreadsheet->getActiveSheet()->mergeCells('A2:J2');
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
        if ($filter_periode > 0) {
            $spreadsheet->getActiveSheet()->getStyle('A2')->applyFromArray($titleStyle);
        }
        
        // Header row starting position
        $row = $filter_periode > 0 ? 4 : 3;
        
        // Set headers
        $sheet->setCellValue('A'.$row, 'No');
        $sheet->setCellValue('B'.$row, 'Nama Barang');
        $sheet->setCellValue('C'.$row, 'Qty');
        $sheet->setCellValue('D'.$row, 'Satuan');
        $sheet->setCellValue('E'.$row, 'Periode');
        $sheet->setCellValue('F'.$row, 'Harga Satuan');
        $sheet->setCellValue('G'.$row, 'Total');
        $sheet->setCellValue('H'.$row, 'Lokasi');
        $sheet->setCellValue('I'.$row, 'Tanggal Input');
        $sheet->setCellValue('J'.$row, 'Status');
        
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
        
        $spreadsheet->getActiveSheet()->getStyle('A'.$row.':J'.$row)->applyFromArray($headerStyle);
        
        // Add data
        $no = 1;
        $row++;
        
        mysqli_data_seek($result, 0); // Reset result pointer to beginning
        
        while ($data = mysqli_fetch_assoc($result)) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $data['nama_barang']);
            
            // For retur items, show the non-returned quantity (jumlah_masuk)
            if ($data['status'] == 'retur' && $data['jumlah_masuk'] > 0) {
                $sheet->setCellValue('C'.$row, $data['jumlah_masuk'] . ' (Retur: ' . $data['jumlah_retur'] . ')');
            } else {
                $sheet->setCellValue('C'.$row, $data['qty']);
            }
            
            $sheet->setCellValue('D'.$row, $data['satuan']);
            $sheet->setCellValue('E'.$row, 'Periode ' . $data['periode']);
            $sheet->setCellValue('F'.$row, $data['harga_satuan']);
            $sheet->setCellValue('G'.$row, $data['total']);
            $sheet->setCellValue('H'.$row, $data['lokasi']);
            $sheet->setCellValue('I'.$row, date('d M Y H:i', strtotime($data['tanggal_input'])));
            $sheet->setCellValue('J'.$row, ucfirst($data['status']));
            $row++;
        }
        
        // Calculate and add total row
        mysqli_data_seek($result, 0); // Reset result pointer to beginning
        $grand_total = 0;
        while ($data = mysqli_fetch_assoc($result)) {
            $grand_total += $data['total'];
        }
        
        $sheet->setCellValue('A'.$row, 'Total');
        $sheet->setCellValue('G'.$row, $grand_total);
        
        // Merge cells for "Total" label
        $spreadsheet->getActiveSheet()->mergeCells('A'.$row.':F'.$row);
        
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
        
        $spreadsheet->getActiveSheet()->getStyle('A'.$row.':J'.$row)->applyFromArray($totalStyle);
        
        // Format currency cells
        $currencyFormat = '#,##0';
        $sheet->getStyle('F4:G'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
        
        // Auto-size columns
        foreach (range('A', 'J') as $column) {
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
        
        $spreadsheet->getActiveSheet()->getStyle('A'.($filter_periode > 0 ? 4 : 3).':J'.$row)->applyFromArray($dataCellStyle);
        
        // Set content type headers with proper formatting
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="bahan_baku_export.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Create the writer and save the file
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(false); // This can help with compatibility
        
        // Save directly to output
        $writer->save('php://output');
        exit(); // Stop execution immediately after saving
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