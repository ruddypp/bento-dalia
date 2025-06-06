<?php
$pageTitle = "Cetak Laporan Barang Lost";
require_once 'includes/header.php';
checkLogin();

// Periksa apakah tabel lost_barang sudah ada
$table_exists = false;
$check_table_query = "SHOW TABLES LIKE 'lost_barang'";
$check_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_result) > 0) {
    $table_exists = true;
}

// Jika tabel tidak ada, tampilkan pesan untuk menginstal
if (!$table_exists) {
    setAlert("warning", "Tabel lost_barang belum ada. Silahkan jalankan install_lost_barang.php terlebih dahulu.");
    // Tampilkan konten alternatif
    ?>
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-print text-blue-500 mr-2"></i> Cetak Laporan Barang Lost
            </h2>
        </div>
        
        <div class="p-5 text-center">
            <p class="text-gray-600 mb-4">Modul Barang Lost belum terinstal. Silahkan jalankan installer terlebih dahulu.</p>
            <div class="flex justify-center space-x-4">
                <a href="install_lost_barang.php" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all">
                    <i class="fas fa-download mr-2"></i> Install Modul Barang Lost
                </a>
                <a href="barang_lost.php" class="bg-gray-500 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-md transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </div>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}

// Default filter values
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : date('Y-m-d', strtotime('-30 days'));
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : date('Y-m-d');
$id_barang = isset($_GET['id_barang']) ? (int)$_GET['id_barang'] : 0;
$alasan = isset($_GET['alasan']) ? $_GET['alasan'] : '';

// Get all items for filter
$query = "SELECT * FROM barang ORDER BY nama_barang ASC";
$items = mysqli_query($conn, $query);

// Get all alasan options for filter
$query = "SELECT DISTINCT alasan FROM lost_barang ORDER BY alasan ASC";
$alasan_options = mysqli_query($conn, $query);

// Build query with filters
$query = "SELECT l.*, b.nama_barang, b.satuan, u.nama_lengkap 
          FROM lost_barang l
          JOIN barang b ON l.id_barang = b.id_barang
          LEFT JOIN users u ON l.dibuat_oleh = u.id_user
          WHERE DATE(l.created_at) BETWEEN ? AND ?";

$params = [$dari_tanggal, $sampai_tanggal];
$types = "ss";

if ($id_barang > 0) {
    $query .= " AND l.id_barang = ?";
    $params[] = $id_barang;
    $types .= "i";
}

if (!empty($alasan)) {
    $query .= " AND l.alasan = ?";
    $params[] = $alasan;
    $types .= "s";
}

$query .= " ORDER BY l.created_at DESC";

// Prepare and execute query
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count total items and calculate total loss
$total_items = 0;
$total_loss = 0;
$lost_items = [];

while ($item = mysqli_fetch_assoc($result)) {
    // Debug: periksa struktur data item
    // echo "<pre>"; print_r($item); echo "</pre>";
    
    // Pastikan nama_lengkap ada, jika tidak set ke N/A
    if (!isset($item['nama_lengkap']) || $item['nama_lengkap'] === null) {
        $item['nama_lengkap'] = 'N/A';
    }
    
    $lost_items[] = $item;
    $total_items++;
    $total_loss += $item['jumlah'];
}
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-print text-blue-500 mr-2"></i> Cetak Laporan Barang Lost
        </h2>
        
        <div class="flex space-x-2">
            <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                    onclick="window.location.href='barang_lost.php'">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </button>
            
            <button type="button" class="bg-green-500 hover:bg-green-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                    onclick="printReport()">
                <i class="fas fa-print mr-2"></i> Cetak Laporan
            </button>
            
            <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                    onclick="generatePDF()">
                <i class="fas fa-file-pdf mr-2"></i> Download PDF
            </button>
        </div>
    </div>
    
    <!-- Filter Form -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="dari_tanggal">
                    Dari Tanggal
                </label>
                <input type="date" id="dari_tanggal" name="dari_tanggal" value="<?= $dari_tanggal ?>" 
                       class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="sampai_tanggal">
                    Sampai Tanggal
                </label>
                <input type="date" id="sampai_tanggal" name="sampai_tanggal" value="<?= $sampai_tanggal ?>" 
                       class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_barang">
                    Barang
                </label>
                <select id="id_barang" name="id_barang" 
                        class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="0">Semua Barang</option>
                    <?php mysqli_data_seek($items, 0); ?>
                    <?php while ($item = mysqli_fetch_assoc($items)): ?>
                        <option value="<?= $item['id_barang'] ?>" <?= $id_barang == $item['id_barang'] ? 'selected' : '' ?>>
                            <?= $item['nama_barang'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="alasan">
                    Alasan
                </label>
                <select id="alasan" name="alasan" 
                        class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Semua Alasan</option>
                    <?php while ($alasan_item = mysqli_fetch_assoc($alasan_options)): ?>
                        <option value="<?= $alasan_item['alasan'] ?>" <?= $alasan == $alasan_item['alasan'] ? 'selected' : '' ?>>
                            <?= $alasan_item['alasan'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
            </div>
        </form>
    </div>
    
    <!-- Report Preview -->
    <div id="report-container">
        <div class="text-center mb-6 report-header">
            <h1 class="text-xl font-bold"><?= $store_info['nama_toko'] ?? 'Sistem Inventori' ?></h1>
            <p class="text-gray-600"><?= $store_info['alamat'] ?? '' ?></p>
            <h2 class="text-lg font-semibold mt-4">Laporan Barang Lost</h2>
            <p class="text-gray-600">Periode: <?= date('d/m/Y', strtotime($dari_tanggal)) ?> - <?= date('d/m/Y', strtotime($sampai_tanggal)) ?></p>
        </div>
        
        <div class="mb-4 report-summary">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-600">Total Item Lost:</p>
                    <p class="text-lg font-bold"><?= $total_items ?> item</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-600">Dicetak oleh:</p>
                    <p class="text-lg font-bold"><?= $_SESSION['nama_lengkap'] ?? 'Administrator' ?></p>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200">
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Nama Barang</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Jumlah</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Alasan</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Dibuat Oleh</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Bukti Foto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (count($lost_items) > 0): ?>
                        <?php $no = 1; foreach ($lost_items as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-3 text-sm"><?= $no++ ?></td>
                            <td class="py-2 px-3 text-sm"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                            <td class="py-2 px-3 text-sm font-medium"><?= $item['nama_barang'] ?></td>
                            <td class="py-2 px-3 text-sm text-gray-600">
                                <?= $item['jumlah'] ?> <span class="text-xs text-gray-500"><?= $item['satuan'] ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm"><?= $item['alasan'] ?></td>
                            <td class="py-2 px-3 text-sm text-gray-600"><?= $item['nama_lengkap'] ?></td>
                            <td class="py-2 px-3 text-sm">
                                <?php if (!empty($item['foto_bukti'])): ?>
                                    <span class="text-blue-500">Ada</span>
                                <?php else: ?>
                                    <span class="text-gray-400">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-500">Tidak ada data yang ditemukan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 text-right report-footer">
            <p class="text-sm text-gray-600">Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
</div>

<!-- Include jsPDF library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
// Function to print the report
function printReport() {
    // Hide elements that should not be printed
    const originalContents = document.body.innerHTML;
    
    // Only print the report container
    const printContents = document.getElementById('report-container').innerHTML;
    
    document.body.innerHTML = `
        <style>
            @media print {
                body { font-family: Arial, sans-serif; }
                .report-header { margin-bottom: 20px; }
                .report-summary { margin-bottom: 15px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                th { background-color: #f2f2f2; }
                .report-footer { margin-top: 20px; }
            }
        </style>
        <div>${printContents}</div>
    `;
    
    window.print();
    
    // Restore original content
    document.body.innerHTML = originalContents;
}

// Function to generate PDF
function generatePDF() {
    // Create PDF instance
    window.jsPDF = window.jspdf.jsPDF;
    
    const doc = new jsPDF();
    
    // Add title
    doc.setFontSize(16);
    doc.text("<?= $store_info['nama_toko'] ?? 'Sistem Inventori' ?>", 105, 15, { align: 'center' });
    
    doc.setFontSize(12);
    doc.text("Laporan Barang Lost", 105, 25, { align: 'center' });
    doc.text("Periode: <?= date('d/m/Y', strtotime($dari_tanggal)) ?> - <?= date('d/m/Y', strtotime($sampai_tanggal)) ?>", 105, 32, { align: 'center' });
    
    // Add summary
    doc.setFontSize(10);
    doc.text(`Total Item Lost: <?= $total_items ?> item`, 14, 45);
    doc.text(`Dicetak oleh: <?= $_SESSION['nama_lengkap'] ?? 'Administrator' ?>`, 14, 52);
    
    // Create table
    const tableColumn = ["No", "Tanggal", "Nama Barang", "Jumlah", "Alasan", "Dibuat Oleh", "Bukti"];
    const tableRows = [];
    
    <?php $no = 1; foreach ($lost_items as $item): ?>
        tableRows.push([
            <?= $no++ ?>, 
            "<?= date('d/m/Y', strtotime($item['created_at'])) ?>",
            "<?= $item['nama_barang'] ?>",
            "<?= $item['jumlah'] ?> <?= $item['satuan'] ?>",
            "<?= $item['alasan'] ?>",
            "<?= $item['nama_lengkap'] ?>",
            "<?= !empty($item['foto_bukti']) ? 'Ada' : 'Tidak ada' ?>"
        ]);
    <?php endforeach; ?>
    
    // Add table to PDF
    doc.autoTable({
        head: [tableColumn],
        body: tableRows,
        startY: 60,
        theme: 'grid',
        styles: {
            fontSize: 8
        },
        headStyles: {
            fillColor: [66, 135, 245]
        }
    });
    
    // Add footer
    const finalY = doc.lastAutoTable.finalY + 10;
    doc.text(`Dicetak pada: <?= date('d/m/Y H:i:s') ?>`, 195, finalY, { align: 'right' });
    
    // Save PDF
    doc.save('Laporan_Barang_Lost_<?= date('Ymd') ?>.pdf');
}
</script>

<?php require_once 'includes/footer.php'; ?> 