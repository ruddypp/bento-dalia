<?php
$pageTitle = "Laporan Penjualan";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php';

// Check for success or error messages in session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Clear the session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Process reset action if requested by admin
if (isset($_POST['reset_laporan']) && $_SESSION['user_role'] === 'admin') {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete all data from penjualan_detail and penjualan_bahan tables first (foreign key constraints)
        $conn->query("DELETE FROM penjualan_bahan");
        $conn->query("DELETE FROM penjualan_detail");
        
        // Delete all data from penjualan table
        $conn->query("DELETE FROM penjualan");
        
        // Truncate the laporan_penjualan table
        $conn->query("TRUNCATE TABLE laporan_penjualan");
        
        // Commit the transaction
        $conn->commit();
        
        // Log the action
        logActivity($_SESSION['user_id'], "Reset seluruh data laporan penjualan, menu terlaris, dan transaksi");
        
        // Use PRG pattern (Post-Redirect-Get) to prevent form resubmission on refresh
        $_SESSION['success_message'] = "Seluruh data laporan penjualan, menu terlaris, dan transaksi berhasil dihapus.";
        header("Location: laporan_penjualan.php");
        exit;
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        
        // Set error message for display
        $_SESSION['error_message'] = "Gagal menghapus data: " . $e->getMessage();
        header("Location: laporan_penjualan.php");
        exit;
    }
}

// Format currency to Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Initialize variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'daily';

// Pagination settings for all three sections
$records_per_page_reports = isset($_GET['records_per_page_reports']) ? (int)$_GET['records_per_page_reports'] : 10;
$records_per_page_top = isset($_GET['records_per_page_top']) ? (int)$_GET['records_per_page_top'] : 10;
$records_per_page_trx = isset($_GET['records_per_page_trx']) ? (int)$_GET['records_per_page_trx'] : 10;

$page_reports = isset($_GET['page_reports']) ? (int)$_GET['page_reports'] : 1;
$page_top = isset($_GET['page_top']) ? (int)$_GET['page_top'] : 1;
$page_trx = isset($_GET['page_trx']) ? (int)$_GET['page_trx'] : 1;

$offset_reports = ($page_reports - 1) * $records_per_page_reports;
$offset_top = ($page_top - 1) * $records_per_page_top;
$offset_trx = ($page_trx - 1) * $records_per_page_trx;

// Records per page options for dropdowns
$records_per_page_options = [10, 25, 50, 100];

// Get sales reports based on filter
$reports = [];
$total_penjualan = 0;
$total_modal = 0;
$total_keuntungan = 0;
$total_transaksi = 0;

// Count total records for pagination - Reports
$count_query = "";
if ($filter_type === 'daily') {
    $count_query = "SELECT COUNT(*) as total FROM laporan_penjualan WHERE tanggal BETWEEN ? AND ?";
} else {
    $count_query = "SELECT COUNT(DISTINCT DATE_FORMAT(tanggal, '%Y-%m')) as total FROM laporan_penjualan WHERE tanggal BETWEEN ? AND ?";
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("ss", $start_date, $end_date);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records_reports = $count_row['total'];
$total_pages_reports = ceil($total_records_reports / $records_per_page_reports);

// Get reports with pagination
if ($filter_type === 'daily') {
    // Daily report query
    $query = "SELECT * FROM laporan_penjualan 
              WHERE tanggal BETWEEN ? AND ? 
              ORDER BY tanggal DESC
              LIMIT ?, ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $start_date, $end_date, $offset_reports, $records_per_page_reports);
} else {
    // Monthly report query
    $query = "SELECT 
                DATE_FORMAT(tanggal, '%Y-%m') AS bulan, 
                SUM(total_penjualan) AS total_penjualan,
                SUM(total_modal) AS total_modal,
                SUM(total_keuntungan) AS total_keuntungan,
                SUM(jumlah_transaksi) AS jumlah_transaksi
              FROM laporan_penjualan 
              WHERE tanggal BETWEEN ? AND ?
              GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
              ORDER BY bulan DESC
              LIMIT ?, ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $start_date, $end_date, $offset_reports, $records_per_page_reports);
}

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

// Count total records for pagination - Top Items
$count_query_top = "SELECT COUNT(DISTINCT m.id_menu) as total
                    FROM penjualan p
                    JOIN penjualan_detail pd ON p.id_penjualan = pd.id_penjualan
                    JOIN menu m ON pd.id_menu = m.id_menu
                    WHERE DATE(p.tanggal_penjualan) BETWEEN ? AND ?";
$count_stmt_top = $conn->prepare($count_query_top);
$count_stmt_top->bind_param("ss", $start_date, $end_date);
$count_stmt_top->execute();
$count_result_top = $count_stmt_top->get_result();
$count_row_top = $count_result_top->fetch_assoc();
$total_records_top = $count_row_top['total'];
$total_pages_top = ceil($total_records_top / $records_per_page_top);

// Get top selling menu items with pagination
$top_items = [];
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
              LIMIT ?, ?";

$stmt_top = $conn->prepare($query_top);
$stmt_top->bind_param("ssii", $start_date, $end_date, $offset_top, $records_per_page_top);
$stmt_top->execute();
$result_top = $stmt_top->get_result();

while ($row = $result_top->fetch_assoc()) {
    $top_items[] = $row;
}

// Count total records for pagination - Transactions
$count_query_trx = "SELECT COUNT(*) as total
                    FROM penjualan p 
                    WHERE DATE(p.tanggal_penjualan) BETWEEN ? AND ?";
$count_stmt_trx = $conn->prepare($count_query_trx);
$count_stmt_trx->bind_param("ss", $start_date, $end_date);
$count_stmt_trx->execute();
$count_result_trx = $count_stmt_trx->get_result();
$count_row_trx = $count_result_trx->fetch_assoc();
$total_records_trx = $count_row_trx['total'];
$total_pages_trx = ceil($total_records_trx / $records_per_page_trx);

// Get recent transactions with pagination
$transactions = [];
$query_transactions = "SELECT p.*, COUNT(pd.id_penjualan_detail) as total_items 
                      FROM penjualan p 
                      LEFT JOIN penjualan_detail pd ON p.id_penjualan = pd.id_penjualan 
                      WHERE DATE(p.tanggal_penjualan) BETWEEN ? AND ? 
                      GROUP BY p.id_penjualan 
                      ORDER BY p.tanggal_penjualan DESC 
                      LIMIT ?, ?";

$stmt_transactions = $conn->prepare($query_transactions);
$stmt_transactions->bind_param("ssii", $start_date, $end_date, $offset_trx, $records_per_page_trx);
$stmt_transactions->execute();
$result_transactions = $stmt_transactions->get_result();

while ($row = $result_transactions->fetch_assoc()) {
    $transactions[] = $row;
}
?>

<div class="ml-17 p-2">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-700 flex items-center">
            <i class="fas fa-chart-line mr-2"></i> Laporan Penjualan
        </h1>
        <div class="flex items-center mt-2 text-sm">
            <a href="dashboard.php" class="text-blue-500 hover:text-blue-700">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-600">Laporan Penjualan</span>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-lg font-semibold mb-4">Filter Laporan</h2>
        
        <form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="filter_type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Laporan</label>
                <select id="filter_type" name="filter_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="daily" <?= $filter_type === 'daily' ? 'selected' : '' ?>>Harian</option>
                    <option value="monthly" <?= $filter_type === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                </select>
            </div>
            
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                <input type="date" id="start_date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $start_date ?>">
            </div>
            
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <input type="date" id="end_date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $end_date ?>">
            </div>
            
            <div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </div>
            
            <div>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition duration-200 inline-block">
                    <i class="fas fa-sync-alt mr-1"></i> Reset Filter
                </a>
            </div>
            
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div>
                <button type="button" id="resetLaporanBtn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200">
                    <i class="fas fa-trash-alt mr-1"></i> Reset Semua Data Penjualan
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex justify-between items-center">
        <span><?= $success_message ?></span>
        <button class="text-green-700 hover:text-green-900 text-lg" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex justify-between items-center">
        <span><?= $error_message ?></span>
        <button class="text-red-700 hover:text-red-900 text-lg" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
    <?php endif; ?>
    
    <!-- Summary Section -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-lg font-semibold mb-4">Ringkasan <?= $filter_type === 'daily' ? 'Harian' : 'Bulanan' ?></h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                <div class="text-sm text-blue-600 mb-1">Total Penjualan</div>
                <div class="text-2xl font-bold"><?= formatRupiah($total_penjualan) ?></div>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                <div class="text-sm text-green-600 mb-1">Total Keuntungan</div>
                <div class="text-2xl font-bold"><?= formatRupiah($total_keuntungan) ?></div>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                <div class="text-sm text-yellow-600 mb-1">Total Modal</div>
                <div class="text-2xl font-bold"><?= formatRupiah($total_modal) ?></div>
            </div>
            
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                <div class="text-sm text-purple-600 mb-1">Jumlah Transaksi</div>
                <div class="text-2xl font-bold"><?= $total_transaksi ?></div>
            </div>
        </div>
    </div>
    
    <!-- Detail Reports -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Report Table -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Laporan <?= $filter_type === 'daily' ? 'Harian' : 'Bulanan' ?></h2>
                <a href="export_laporan_penjualan.php?report_type=daily_report&filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                   class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200 text-sm">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </a>
            </div>
            
            <?php if (empty($reports)): ?>
            <div class="text-center py-8 text-gray-500">
                <p>Tidak ada data laporan untuk periode yang dipilih</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-2"><?= $filter_type === 'daily' ? 'Tanggal' : 'Bulan' ?></th>
                            <th class="px-4 py-2">Transaksi</th>
                            <th class="px-4 py-2">Penjualan</th>
                            <th class="px-4 py-2">Modal</th>
                            <th class="px-4 py-2">Keuntungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium">
                                <?php 
                                if ($filter_type === 'daily') {
                                    echo date('d M Y', strtotime($report['tanggal']));
                                } else {
                                    echo date('M Y', strtotime($report['bulan'] . '-01'));
                                }
                                ?>
                            </td>
                            <td class="px-4 py-2"><?= $report['jumlah_transaksi'] ?></td>
                            <td class="px-4 py-2"><?= formatRupiah($report['total_penjualan']) ?></td>
                            <td class="px-4 py-2"><?= formatRupiah($report['total_modal']) ?></td>
                            <td class="px-4 py-2 text-green-600"><?= formatRupiah($report['total_keuntungan']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls for Reports -->
            <div class="flex flex-col md:flex-row justify-between items-center mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
                <div class="flex items-center mb-4 md:mb-0">
                    <span class="text-sm text-gray-700">
                        Menampilkan
                        <span class="font-medium"><?= min(($page_reports - 1) * $records_per_page_reports + 1, $total_records_reports) ?></span>
                        sampai
                        <span class="font-medium"><?= min($page_reports * $records_per_page_reports, $total_records_reports) ?></span>
                        dari
                        <span class="font-medium"><?= $total_records_reports ?></span>
                        data
                    </span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Records per page dropdown -->
                    <div class="flex items-center">
                        <label for="records_per_page_reports" class="mr-2 text-sm text-gray-600">Tampilkan:</label>
                        <select id="records_per_page_reports" name="records_per_page_reports" onchange="changeRecordsPerPage('reports', this.value)" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                            <?php foreach ($records_per_page_options as $option): ?>
                            <option value="<?= $option ?>" <?= $records_per_page_reports == $option ? 'selected' : '' ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Pagination buttons -->
                    <div class="flex items-center space-x-2">
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=1&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_reports <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= max($page_reports - 1, 1) ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_reports <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-left"></i>
                        </a>
                        
                        <?php
                        $start_page = max(1, min($page_reports - 2, $total_pages_reports - 4));
                        $end_page = min($total_pages_reports, max(5, $page_reports + 2));
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $i ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_reports == $i ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?> px-3 py-1 border rounded-md text-sm font-medium">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= min($page_reports + 1, $total_pages_reports) ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_reports >= $total_pages_reports ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $total_pages_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_reports >= $total_pages_reports ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Top Selling Items -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Menu Terlaris</h2>
                <a href="export_laporan_penjualan.php?report_type=top_items&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                   class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200 text-sm">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </a>
            </div>
            
            <?php if (empty($top_items)): ?>
            <div class="text-center py-8 text-gray-500">
                <p>Tidak ada data penjualan untuk periode yang dipilih</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">Menu</th>
                            <th class="px-4 py-2">Kategori</th>
                            <th class="px-4 py-2">Terjual</th>
                            <th class="px-4 py-2">Penjualan</th>
                            <th class="px-4 py-2">Keuntungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_items as $item): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium"><?= htmlspecialchars($item['nama_menu']) ?></td>
                            <td class="px-4 py-2"><?= ucfirst($item['kategori']) ?></td>
                            <td class="px-4 py-2"><?= $item['total_terjual'] ?></td>
                            <td class="px-4 py-2"><?= formatRupiah($item['total_penjualan']) ?></td>
                            <td class="px-4 py-2 text-green-600"><?= formatRupiah($item['total_keuntungan']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls for Top Items -->
            <div class="flex flex-col md:flex-row justify-between items-center mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
                <div class="flex items-center mb-4 md:mb-0">
                    <span class="text-sm text-gray-700">
                        Menampilkan
                        <span class="font-medium"><?= min(($page_top - 1) * $records_per_page_top + 1, $total_records_top) ?></span>
                        sampai
                        <span class="font-medium"><?= min($page_top * $records_per_page_top, $total_records_top) ?></span>
                        dari
                        <span class="font-medium"><?= $total_records_top ?></span>
                        data
                    </span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Records per page dropdown -->
                    <div class="flex items-center">
                        <label for="records_per_page_top" class="mr-2 text-sm text-gray-600">Tampilkan:</label>
                        <select id="records_per_page_top" name="records_per_page_top" onchange="changeRecordsPerPage('top', this.value)" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                            <?php foreach ($records_per_page_options as $option): ?>
                            <option value="<?= $option ?>" <?= $records_per_page_top == $option ? 'selected' : '' ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Pagination buttons -->
                    <div class="flex items-center space-x-2">
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=1&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_top <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= max($page_top - 1, 1) ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_top <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-left"></i>
                        </a>
                        
                        <?php
                        $start_page = max(1, min($page_top - 2, $total_pages_top - 4));
                        $end_page = min($total_pages_top, max(5, $page_top + 2));
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $i ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_top == $i ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?> px-3 py-1 border rounded-md text-sm font-medium">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= min($page_top + 1, $total_pages_top) ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_top >= $total_pages_top ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        
                        <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $total_pages_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $page_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                           class="<?= $page_top >= $total_pages_top ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Transaksi Terbaru</h2>
            <div class="relative inline-block text-left">
                <button id="exportDropdownButton" type="button" class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200 text-sm inline-flex items-center">
                    <i class="fas fa-file-export mr-1"></i> Export <i class="fas fa-chevron-down ml-1"></i>
                </button>
                <div id="exportDropdownMenu" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="exportDropdownButton">
                        <a href="export_transaksi_terbaru.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&format=pdf" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                            <i class="fas fa-file-pdf mr-2 text-red-500"></i> Export PDF
                        </a>
                        <a href="export_transaksi_terbaru.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&format=excel" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                            <i class="fas fa-file-excel mr-2 text-green-500"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($transactions)): ?>
        <div class="text-center py-8 text-gray-500">
            <p>Tidak ada transaksi untuk periode yang dipilih</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-2">No. Invoice</th>
                        <th class="px-4 py-2">Tanggal</th>
                        <th class="px-4 py-2">Pelanggan</th>
                        <th class="px-4 py-2">Jumlah Item</th>
                        <th class="px-4 py-2">Total</th>
                        <th class="px-4 py-2">Keuntungan</th>
                        <th class="px-4 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trx): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium"><?= htmlspecialchars($trx['no_invoice']) ?></td>
                        <td class="px-4 py-2"><?= date('d M Y H:i', strtotime($trx['tanggal_penjualan'])) ?></td>
                        <td class="px-4 py-2"><?= !empty($trx['nama_pelanggan']) ? htmlspecialchars($trx['nama_pelanggan']) : '<span class="text-gray-400">-</span>' ?></td>
                        <td class="px-4 py-2"><?= $trx['total_items'] ?> item</td>
                        <td class="px-4 py-2 font-medium"><?= formatRupiah($trx['total_harga']) ?></td>
                        <td class="px-4 py-2 text-green-600"><?= formatRupiah($trx['keuntungan']) ?></td>
                        <td class="px-4 py-2">
                            <a href="detail_penjualan.php?id=<?= $trx['id_penjualan'] ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls for Transactions -->
        <div class="flex flex-col md:flex-row justify-between items-center mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
            <div class="flex items-center mb-4 md:mb-0">
                <span class="text-sm text-gray-700">
                    Menampilkan
                    <span class="font-medium"><?= min(($page_trx - 1) * $records_per_page_trx + 1, $total_records_trx) ?></span>
                    sampai
                    <span class="font-medium"><?= min($page_trx * $records_per_page_trx, $total_records_trx) ?></span>
                    dari
                    <span class="font-medium"><?= $total_records_trx ?></span>
                    data
                </span>
            </div>
            
            <div class="flex items-center space-x-2">
                <!-- Records per page dropdown -->
                <div class="flex items-center">
                    <label for="records_per_page_trx" class="mr-2 text-sm text-gray-600">Tampilkan:</label>
                    <select id="records_per_page_trx" name="records_per_page_trx" onchange="changeRecordsPerPage('trx', this.value)" 
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                        <?php foreach ($records_per_page_options as $option): ?>
                        <option value="<?= $option ?>" <?= $records_per_page_trx == $option ? 'selected' : '' ?>><?= $option ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Pagination buttons -->
                <div class="flex items-center space-x-2">
                    <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=1&records_per_page_trx=<?= $records_per_page_trx ?>" 
                       class="<?= $page_trx <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    
                    <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= max($page_trx - 1, 1) ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                       class="<?= $page_trx <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    
                    <?php
                    $start_page = max(1, min($page_trx - 2, $total_pages_trx - 4));
                    $end_page = min($total_pages_trx, max(5, $page_trx + 2));
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $i ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                       class="<?= $page_trx == $i ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?> px-3 py-1 border rounded-md text-sm font-medium">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= min($page_trx + 1, $total_pages_trx) ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                       class="<?= $page_trx >= $total_pages_trx ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    
                    <a href="?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&page_reports=<?= $page_reports ?>&records_per_page_reports=<?= $records_per_page_reports ?>&page_top=<?= $page_top ?>&records_per_page_top=<?= $records_per_page_top ?>&page_trx=<?= $total_pages_trx ?>&records_per_page_trx=<?= $records_per_page_trx ?>" 
                       class="<?= $page_trx >= $total_pages_trx ? 'opacity-50 cursor-not-allowed' : '' ?> px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update date inputs based on filter type
    const filterType = document.getElementById('filter_type');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    filterType.addEventListener('change', function() {
        if (this.value === 'monthly') {
            // Set to first day of current month
            const date = new Date();
            const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
            startDate.value = firstDay.toISOString().split('T')[0];
        }
    });
    
    // Export dropdown toggle
    const exportDropdownButton = document.getElementById('exportDropdownButton');
    const exportDropdownMenu = document.getElementById('exportDropdownMenu');
    
    if (exportDropdownButton && exportDropdownMenu) {
        exportDropdownButton.addEventListener('click', function() {
            exportDropdownMenu.classList.toggle('hidden');
        });
        
        // Close the dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!exportDropdownButton.contains(event.target) && !exportDropdownMenu.contains(event.target)) {
                exportDropdownMenu.classList.add('hidden');
            }
        });
    }
    
    // Reset Laporan Button
    const resetLaporanBtn = document.getElementById('resetLaporanBtn');
    if (resetLaporanBtn) {
        resetLaporanBtn.addEventListener('click', function() {
            if (confirm('PERHATIAN: Tindakan ini akan menghapus SELURUH data laporan penjualan, menu terlaris, dan transaksi. Data tidak dapat dikembalikan. Yakin ingin melanjutkan?')) {
                // Create and submit a form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reset_laporan';
                input.value = '1';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});

// Function to change records per page
function changeRecordsPerPage(section, value) {
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Set the records per page parameter for the specific section
    urlParams.set(`records_per_page_${section}`, value);
    
    // Reset to page 1 for the section being changed
    urlParams.set(`page_${section}`, 1);
    
    // Redirect to the new URL
    window.location.href = `${window.location.pathname}?${urlParams.toString()}`;
}
</script>

<?php require_once 'includes/footer.php'; ?> 