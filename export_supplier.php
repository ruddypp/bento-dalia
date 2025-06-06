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

// Build query
$query = "SELECT * FROM supplier ORDER BY nama_supplier ASC";

// Execute the query
$result = mysqli_query($conn, $query);

// Check if there's data
if (!$result) {
    echo "Query error: " . mysqli_error($conn);
    exit;
}

if (mysqli_num_rows($result) == 0) {
    echo "Tidak ada data supplier yang ditemukan";
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
        $pdf->SetTitle('Data Supplier');
        $pdf->SetSubject('Supplier Export');
        $pdf->SetKeywords('Supplier, PDF, Export');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');

        // Set margins lebih kecil untuk memberikan ruang lebih besar pada tabel
        $pdf->SetMargins(15, 15, 15);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 15);

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
        $pdf->Cell(0, 10, 'Data Supplier', 0, 1, 'C');
        
        $pdf->Ln(5);

        // Gunakan HTML untuk membuat tabel yang lebih rapi
        $html = '<style>
            table {
                border-collapse: collapse;
                width: 100%;
                margin-bottom: 10px;
            }
            th, td {
                border: 1px solid #000000;
                padding: 6px;
                vertical-align: top;
            }
            th {
                background-color: #E6E6E6;
                font-weight: bold;
                text-align: center;
            }
        </style>
        <table cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th width="8%" align="center">No</th>
                    <th width="20%" align="center">Nama Supplier</th>
                    <th width="25%" align="center">Alamat</th>
                    <th width="15%" align="center">Kontak</th>
                    <th width="32%" align="center">Bahan Baku</th>
                </tr>
            </thead>
            <tbody>';
        
        $no = 1;
        mysqli_data_seek($result, 0); // Reset result pointer to beginning
        
        while ($row = mysqli_fetch_assoc($result)) {
            // Format bahan baku dan satuan untuk tampilan
            $bahan_array = explode(',', $row['bahan_baku']);
            $satuan_array = explode(',', $row['satuan']);
            $bahan_items = [];
            
            for ($i = 0; $i < count($bahan_array); $i++) {
                $bahan = trim($bahan_array[$i]);
                $satuan = isset($satuan_array[$i]) ? trim($satuan_array[$i]) : '';
                if (!empty($bahan)) {
                    $bahan_items[] = htmlspecialchars($bahan . ' (' . $satuan . ')');
                }
            }
            
            // Untuk HTML, gunakan <br> untuk setiap item
            $bahan_display = implode('<br>', $bahan_items);
            
                         $html .= '<tr>
                <td style="text-align: center;">' . $no++ . '</td>
                <td style="font-weight: medium;">' . htmlspecialchars($row['nama_supplier']) . '</td>
                <td>' . nl2br(htmlspecialchars($row['alamat'])) . '</td>
                <td>' . htmlspecialchars($row['kontak']) . '</td>
                <td>' . $bahan_display . '</td>
            </tr>';
        }
        
        $html .= '</tbody>';
        
        // Tambahkan footer tabel dengan jumlah supplier
        $html .= '<tfoot>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold; background-color: #f2f2f2;">Total Supplier:</td>
                <td style="font-weight: bold; background-color: #f2f2f2;">' . ($no - 1) . '</td>
            </tr>
        </tfoot>';
        
        $html .= '</table>';
        
        // Set font
        $pdf->SetFont('helvetica', '', 9);
        
        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
            
        // Check if we need to add a page after a long entry
        if ($pdf->GetY() > $pdf->getPageHeight() - 20) {
            $pdf->AddPage();
        }

        // Close and output PDF document
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="supplier_export.pdf"');
        $pdf->Output('supplier_export.pdf', 'D');
        exit();
    } else if ($format === 'excel') {
        // Export to Excel
        
        // Clear any previous output to avoid Excel corruption
        if (ob_get_length()) ob_end_clean();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set the title
        $sheet->setCellValue('A1', 'DATA SUPPLIER');
        
        // Make the title span multiple columns
        $spreadsheet->getActiveSheet()->mergeCells('A1:E1');
        
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
        
        // Header row starting position
        $row = 3;
        
        // Set headers
        $sheet->setCellValue('A'.$row, 'No');
        $sheet->setCellValue('B'.$row, 'Nama Supplier');
        $sheet->setCellValue('C'.$row, 'Alamat');
        $sheet->setCellValue('D'.$row, 'Kontak');
        $sheet->setCellValue('E'.$row, 'Bahan Baku');
        
        // Style the header dengan border yang lebih tebal
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E6E6E6',
                ],
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A'.$row.':E'.$row)->applyFromArray($headerStyle);
        
        // Add data
        $no = 1;
        $row++;
        
        mysqli_data_seek($result, 0); // Reset result pointer to beginning
        
        while ($data = mysqli_fetch_assoc($result)) {
            // Format bahan baku dan satuan untuk tampilan
            $bahan_array = explode(',', $data['bahan_baku']);
            $satuan_array = explode(',', $data['satuan']);
            $bahan_items = [];
            
            for ($i = 0; $i < count($bahan_array); $i++) {
                $bahan = trim($bahan_array[$i]);
                $satuan = isset($satuan_array[$i]) ? trim($satuan_array[$i]) : '';
                if (!empty($bahan)) {
                    $bahan_items[] = $bahan . ' (' . $satuan . ')';
                }
            }
            
            // Untuk Excel, gunakan baris baru (Alt+Enter) untuk setiap item
            $bahan_display = implode("\n", $bahan_items);
            
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $data['nama_supplier']);
            $sheet->setCellValue('C'.$row, $data['alamat']);
            $sheet->setCellValue('D'.$row, $data['kontak']);
            $sheet->setCellValue('E'.$row, $bahan_display);
            
            // Aktifkan wrap text untuk kolom bahan baku
            $sheet->getStyle('E'.$row)->getAlignment()->setWrapText(true);
            
            // Hitung jumlah baris untuk menentukan tinggi sel
            $num_lines = count($bahan_items);
            
            // Set row height berdasarkan jumlah baris bahan baku (minimum 15, tambah 15 untuk setiap baris tambahan)
            $row_height = max(15, 15 * $num_lines);
            $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight($row_height);
            
            $row++;
        }
        
        // Style all data cells dengan border yang lebih jelas
        $dataCellStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ];
        
        // Style untuk kolom nomor (center alignment)
        $sheet->getStyle('A3:A'.($row-1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style untuk nama supplier (bold)
        $sheet->getStyle('B3:B'.($row-1))->getFont()->setBold(true);
        
        $spreadsheet->getActiveSheet()->getStyle('A3:E'.($row-1))->applyFromArray($dataCellStyle);
        
        // Tambahkan baris total
        $sheet->setCellValue('A'.$row, 'Total Supplier:');
        $sheet->setCellValue('E'.$row, $no - 1);
        
        // Merge cells untuk label total
        $spreadsheet->getActiveSheet()->mergeCells('A'.$row.':D'.$row);
        
        // Style untuk baris total
        $totalStyle = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F2F2F2',
                ],
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A'.$row.':E'.$row)->applyFromArray($totalStyle);
        
        // Auto-size columns
        foreach (range('A', 'E') as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Set content type headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="supplier_export.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Create the writer and save the file
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(false);
        
        // Save directly to output
        $writer->save('php://output');
        exit();
    } else {
        // Invalid format
        echo "Format ekspor tidak valid";
    }
} catch (Exception $e) {
    // Log the error
    error_log("Export error: " . $e->getMessage());
    
    // Display user-friendly message
    echo "Terjadi kesalahan saat ekspor: " . $e->getMessage();
}

// End output buffering and clean it
if (ob_get_length()) ob_end_clean();

// Close the database connection
mysqli_close($conn); 