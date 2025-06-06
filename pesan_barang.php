<?php
$pageTitle = "Pesan Barang";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php';
checkLogin();

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_pesanan'])) {
        // Tambah pesanan baru
        $id_supplier = (int)$_POST['id_supplier'];
        $tanggal_pesan = sanitize($_POST['tanggal_pesan']);
        $catatan = sanitize($_POST['catatan']);
        $status = 'pending'; // Always start with pending status
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Validate supplier
            $supplier_query = "SELECT id_supplier, nama_supplier FROM supplier WHERE id_supplier = ?";
            $supplier_stmt = mysqli_prepare($conn, $supplier_query);
            mysqli_stmt_bind_param($supplier_stmt, "i", $id_supplier);
            mysqli_stmt_execute($supplier_stmt);
            $supplier_result = mysqli_stmt_get_result($supplier_stmt);
            
            if (mysqli_num_rows($supplier_result) == 0) {
                throw new Exception("Supplier tidak valid");
            }
            
            $supplier = mysqli_fetch_assoc($supplier_result);
            mysqli_stmt_close($supplier_stmt);
            
            // Validate items
            if (!isset($_POST['barang']) || !is_array($_POST['barang']) || count($_POST['barang']) == 0) {
                throw new Exception("Tidak ada item yang dipilih");
            }
            
            // Insert into pesanan_barang table
            $query = "INSERT INTO pesanan_barang (id_supplier, tanggal_pesan, id_user, catatan, status) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            $user_id = $_SESSION['user_id'];
            mysqli_stmt_bind_param($stmt, "isiss", $id_supplier, $tanggal_pesan, $user_id, $catatan, $status);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Gagal menambahkan pesanan: " . mysqli_stmt_error($stmt));
            }
            
            $id_pesanan = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Insert pesanan items
            $total_pesanan = 0;
            $item_count = 0;
            
            if (isset($_POST['barang']) && is_array($_POST['barang'])) {
                foreach ($_POST['barang'] as $key => $id_barang) {
                    // Skip empty items
                    if (empty($_POST['qty'][$key]) || (!$id_barang && !isset($_POST['new_item_name'][$key]))) {
                        continue;
                    }
                    
                    $qty = (int)$_POST['qty'][$key];
                    if ($qty <= 0) {
                        continue; // Skip items with zero or negative quantity
                    }
                    
                    $periode = (int)$_POST['periode'][$key];
                    $harga_satuan = (float)$_POST['harga_satuan'][$key];
                    $lokasi = sanitize($_POST['lokasi'][$key]);
                    
                    // Check if this is a new item (id_barang = 0)
                    if ($id_barang == 0 && isset($_POST['new_item_name'][$key]) && !empty($_POST['new_item_name'][$key])) {
                        $new_item_name = sanitize($_POST['new_item_name'][$key]);
                        $new_item_satuan = sanitize($_POST['new_item_satuan'][$key] ?? 'pcs');
                        
                        // Create new item in barang table
                        $new_item_query = "INSERT INTO barang (nama_barang, satuan, jenis, stok, harga, id_supplier) 
                                          VALUES (?, ?, 'bahan baku', 0, ?, ?)";
                        $new_item_stmt = mysqli_prepare($conn, $new_item_query);
                        mysqli_stmt_bind_param($new_item_stmt, "ssdi", $new_item_name, $new_item_satuan, $harga_satuan, $id_supplier);
                        
                        if (!mysqli_stmt_execute($new_item_stmt)) {
                            throw new Exception("Gagal menambahkan barang baru: " . mysqli_stmt_error($new_item_stmt));
                        }
                        
                        $id_barang = mysqli_insert_id($conn);
                        mysqli_stmt_close($new_item_stmt);
                        
                        // Log activity
                        logActivity($user_id, "Menambahkan barang baru dari pesanan: $new_item_name");
                    }
                    
                    // Calculate total
                    $total = $qty * $harga_satuan;
                    $total_pesanan += $total;
                    $item_count++;
                    
                    // Insert into pesanan_detail
                    $detail_query = "INSERT INTO pesanan_detail (id_pesanan, id_barang, qty, periode, harga_satuan, total, lokasi) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $detail_stmt = mysqli_prepare($conn, $detail_query);
                    mysqli_stmt_bind_param($detail_stmt, "iiiddds", $id_pesanan, $id_barang, $qty, $periode, $harga_satuan, $total, $lokasi);
                    
                    if (!mysqli_stmt_execute($detail_stmt)) {
                        throw new Exception("Gagal menambahkan detail pesanan: " . mysqli_stmt_error($detail_stmt));
                    }
                    
                    mysqli_stmt_close($detail_stmt);
                    
                    // Insert into bahan_baku with pending status
                    $bahan_query = "INSERT INTO bahan_baku 
                                  (id_barang, qty, periode, harga_satuan, total, lokasi, id_user, status, tanggal_input, id_pesanan) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)";
                    $bahan_stmt = mysqli_prepare($conn, $bahan_query);
                    
                    mysqli_stmt_bind_param(
                        $bahan_stmt, 
                        "iiiddsii", 
                        $id_barang, 
                        $qty, 
                        $periode, 
                        $harga_satuan, 
                        $total, 
                        $lokasi, 
                        $user_id,
                        $id_pesanan
                    );
                    
                    if (!mysqli_stmt_execute($bahan_stmt)) {
                        throw new Exception("Gagal menambahkan bahan baku: " . mysqli_stmt_error($bahan_stmt));
                    }
                    
                    mysqli_stmt_close($bahan_stmt);
                    
                    // Log activity for each item
                    logActivity($user_id, "Menambahkan bahan baku dari pesanan: id_barang #{$id_barang}, qty: {$qty}, periode: {$periode}");
                }
            }
            
            // Check if any items were added
            if ($item_count == 0) {
                throw new Exception("Tidak ada item yang valid dalam pesanan");
            }
            
            // Log activity
            logActivity($user_id, "Membuat pesanan baru ke supplier: {$supplier['nama_supplier']} dengan total " . formatRupiah($total_pesanan));
            
            // Commit transaction
            mysqli_commit($conn);
            
            setAlert("success", "Pesanan berhasil dibuat dan bahan baku telah ditambahkan dengan status pending!");
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            setAlert("error", $e->getMessage());
        }
        
        // Redirect to refresh page
        header("Location: pesan_barang.php");
        exit();
    }
    elseif (isset($_POST['cancel_pesanan'])) {
        // Cancel pesanan
        $id_pesanan = (int)$_POST['id_pesanan'];
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Get pesanan details
            $query = "SELECT pb.*, s.nama_supplier 
                      FROM pesanan_barang pb 
                      LEFT JOIN supplier s ON pb.id_supplier = s.id_supplier 
                      WHERE pb.id_pesanan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $pesanan = mysqli_fetch_assoc($result);
            
            if (!$pesanan) {
                throw new Exception("Pesanan tidak ditemukan");
            }
            
            // Check if status is pending (only pending orders can be canceled)
            if ($pesanan['status'] != 'pending') {
                throw new Exception("Hanya pesanan dengan status pending yang dapat dibatalkan");
            }
            
            // Update pesanan status to canceled
            $update_query = "UPDATE pesanan_barang SET status = 'dibatalkan' WHERE id_pesanan = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "i", $id_pesanan);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Gagal membatalkan pesanan: " . mysqli_stmt_error($update_stmt));
            }
            
            mysqli_stmt_close($update_stmt);
            
            // Check if any bahan_baku entries are linked to this pesanan and update their status to cancelled
            $bahan_query = "SELECT id_bahan_baku FROM bahan_baku WHERE id_pesanan = ?";
            $bahan_stmt = mysqli_prepare($conn, $bahan_query);
            mysqli_stmt_bind_param($bahan_stmt, "i", $id_pesanan);
            mysqli_stmt_execute($bahan_stmt);
            $bahan_result = mysqli_stmt_get_result($bahan_stmt);
            
            while ($bahan = mysqli_fetch_assoc($bahan_result)) {
                // Update bahan_baku status to dibatalkan instead of retur
                $update_bahan_query = "UPDATE bahan_baku SET status = 'dibatalkan', catatan_retur = 'Pesanan dibatalkan oleh admin' WHERE id_bahan_baku = ?";
                $update_bahan_stmt = mysqli_prepare($conn, $update_bahan_query);
                mysqli_stmt_bind_param($update_bahan_stmt, "i", $bahan['id_bahan_baku']);
                mysqli_stmt_execute($update_bahan_stmt);
                mysqli_stmt_close($update_bahan_stmt);
            }
            
            // Log activity
            $user_id = $_SESSION['user_id'];
            logActivity($user_id, "Membatalkan pesanan #$id_pesanan dari supplier: {$pesanan['nama_supplier']}");
            
            // Commit transaction
            mysqli_commit($conn);
            
            setAlert("success", "Pesanan berhasil dibatalkan!");
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            setAlert("error", $e->getMessage());
        }
        
        // Redirect to refresh page
        header("Location: pesan_barang.php");
        exit();
    }
}

// Display alerts
displayAlert();

// Get all pesanan
$query = "SELECT pb.*, s.nama_supplier, u.nama_lengkap as nama_user 
          FROM pesanan_barang pb 
          LEFT JOIN supplier s ON pb.id_supplier = s.id_supplier 
          LEFT JOIN users u ON pb.id_user = u.id_user 
          ORDER BY pb.tanggal_pesan DESC";
$pesanan_list = mysqli_query($conn, $query);

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($records_per_page, [10, 25, 50, 100])) {
    $records_per_page = 10; // Default to 10 if invalid value
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Ensure page is at least 1
$offset = ($page - 1) * $records_per_page;

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM pesanan_barang";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-shopping-cart text-blue-500 mr-2"></i> Daftar Pesanan Barang
        </h2>
        
        <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" onclick="showModal('addPesananModal')">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Pesanan
        </button>
        <?php endif; ?>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white pesanan-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No</th>
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal Pesan</th>
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Supplier</th>
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Jumlah Item</th>
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Total Nilai</th>
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Catatan</th>
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Dibuat Oleh</th>
                    <th class="py-2 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                // Get all pesanan with supplier and user info
                $query = "SELECT pb.*, s.nama_supplier, u.nama_lengkap as nama_user,
                         (SELECT COUNT(*) FROM pesanan_detail WHERE id_pesanan = pb.id_pesanan) as jumlah_item,
                         (SELECT SUM(total) FROM pesanan_detail WHERE id_pesanan = pb.id_pesanan) as total_nilai,
                         (SELECT COUNT(*) FROM bahan_baku WHERE id_pesanan = pb.id_pesanan AND status = 'retur') as has_retur
                         FROM pesanan_barang pb
                         LEFT JOIN supplier s ON pb.id_supplier = s.id_supplier
                         LEFT JOIN users u ON pb.id_user = u.id_user
                         ORDER BY pb.tanggal_pesan DESC
                         LIMIT $records_per_page OFFSET $offset";
                $result = mysqli_query($conn, $query);
                
                $no = 1 + $offset;
                while ($pesanan = mysqli_fetch_assoc($result)):
                    // Format status display
                    $status_class = '';
                    $status_text = $pesanan['status'];
                    
                    switch ($pesanan['status']) {
                        case 'pending':
                            $status_class = 'bg-yellow-100 text-yellow-800';
                            $status_text = 'Menunggu';
                            break;
                        case 'selesai':
                            // Check if there are any returns
                            if ($pesanan['has_retur'] > 0) {
                                $status_class = 'bg-red-100 text-red-800';
                            } else {
                                $status_class = 'bg-green-100 text-green-800';
                            }
                            $status_text = 'Selesai';
                            break;
                        case 'dibatalkan':
                            $status_class = 'bg-red-100 text-red-800';
                            $status_text = 'Dibatalkan';
                            break;
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4"><?= $no++ ?></td>
                    <td class="py-2 px-4"><?= date('d/m/Y', strtotime($pesanan['tanggal_pesan'])) ?></td>
                    <td class="py-2 px-4"><?= $pesanan['nama_supplier'] ?></td>
                    <td class="py-2 px-4"><?= $pesanan['jumlah_item'] ?> item</td>
                    <td class="py-2 px-4"><?= formatRupiah($pesanan['total_nilai']) ?></td>
                    <td class="py-2 px-4">
                        <?php if (!empty($pesanan['catatan'])): ?>
                            <span class="tooltip" data-tooltip="<?= htmlspecialchars($pesanan['catatan']) ?>">
                                <i class="fas fa-sticky-note text-gray-500"></i>
                                <?= mb_strlen($pesanan['catatan']) > 20 ? mb_substr(htmlspecialchars($pesanan['catatan']), 0, 20) . '...' : htmlspecialchars($pesanan['catatan']) ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="py-2 px-4">
                        <span class="px-2 py-1 text-xs rounded-full <?= $status_class ?>"><?= $status_text ?></span>
                    </td>
                    <td class="py-2 px-4"><?= $pesanan['nama_user'] ?? '-' ?></td>
                    <td class="py-2 px-4">
                        <div class="flex space-x-2">
                            <button type="button" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md" onclick="viewPesanan(<?= $pesanan['id_pesanan'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            
                            <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
                            <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md" onclick="showEditModal(<?= $pesanan['id_pesanan'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <button type="button" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md" onclick="confirmDelete(<?= $pesanan['id_pesanan'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if (mysqli_num_rows($result) == 0): ?>
                <tr>
                    <td colspan="8" class="py-4 text-center text-gray-500">Belum ada pesanan barang</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination Controls -->
        <?php if($total_pages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&per_page=<?= $records_per_page ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Previous</span>
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php else: ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                    <span class="sr-only">Previous</span>
                    <i class="fas fa-chevron-left"></i>
                </span>
                <?php endif; ?>
                
                <?php
                // Calculate range of page numbers to display
                $range = 2; // Display 2 pages before and after current page
                $start_page = max(1, $page - $range);
                $end_page = min($total_pages, $page + $range);
                
                // Always show first page
                if($start_page > 1) {
                    echo '<a href="?page=1&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                    if($start_page > 2) {
                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                    }
                }
                
                // Display page links
                for($i = $start_page; $i <= $end_page; $i++) {
                    if($i == $page) {
                        echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">'.$i.'</span>';
                    } else {
                        echo '<a href="?page='.$i.'&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$i.'</a>';
                    }
                }
                
                // Always show last page
                if($end_page < $total_pages) {
                    if($end_page < $total_pages - 1) {
                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                    }
                    echo '<a href="?page='.$total_pages.'&per_page='.$records_per_page.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$total_pages.'</a>';
                }
                ?>
                
                <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&per_page=<?= $records_per_page ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                    <span class="sr-only">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </span>
                <?php endif; ?>
            </nav>
        </div>
        
        <div class="mt-2 text-sm text-gray-500 text-center">
            Menampilkan <?= min(($page-1)*$records_per_page+1, $total_records) ?> - <?= min($page*$records_per_page, $total_records) ?> dari <?= $total_records ?> data
        </div>
        <?php endif; ?>
        
        <!-- Records Per Page Selector -->
        <div class="mt-4 flex justify-end">
            <div class="flex items-center">
                <span class="text-sm text-gray-700 mr-2">Tampilkan:</span>
                <select id="per_page_selector" class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="changePerPage(this.value)">
                    <option value="10" <?= $records_per_page == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $records_per_page == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $records_per_page == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $records_per_page == 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span class="text-sm text-gray-700 ml-2">data per halaman</span>
            </div>
        </div>
    </div>
</div>

<!-- Add Pesanan Modal -->
<div id="addPesananModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Buat Pesanan Baru</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addPesananModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="pesananForm">
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="id_supplier" class="block text-gray-700 text-sm font-semibold mb-2">Supplier</label>
                        <select id="id_supplier" name="id_supplier" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="loadSupplierItems()">
                            <option value="">-- Pilih Supplier --</option>
                            <?php
                            // Get all suppliers
                            $supplier_query = "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier ASC";
                            $supplier_result = mysqli_query($conn, $supplier_query);
                            
                            while ($supplier = mysqli_fetch_assoc($supplier_result)) {
                                echo "<option value='{$supplier['id_supplier']}'>{$supplier['nama_supplier']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="tanggal_pesan" class="block text-gray-700 text-sm font-semibold mb-2">Tanggal Pesan</label>
                        <input type="date" id="tanggal_pesan" name="tanggal_pesan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="catatan" class="block text-gray-700 text-sm font-semibold mb-2">Catatan</label>
                    <textarea id="catatan" name="catatan" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                </div>
                
                <div class="mt-6">
                    <h4 class="text-md font-medium text-gray-800 mb-2">Detail Item Pesanan</h4>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barang</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pesanan-items-body">
                                <tr class="border-b pesanan-item">
                                    <td class="py-2 px-2">
                                        <select name="barang[]" class="barang-select shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="">-- Pilih Barang --</option>
                                            <!-- Items will be loaded via AJAX based on selected supplier -->
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" name="qty[]" class="qty-input shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" min="1" value="1" required>
                                    </td>
                                    <td class="py-2 px-2">
                                        <select name="periode[]" class="periode-select shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="1">Periode 1</option>
                                            <option value="2">Periode 2</option>
                                            <option value="3">Periode 3</option>
                                            <option value="4">Periode 4</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" name="harga_satuan[]" class="harga-input shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" min="0" step="100" required>
                                    </td>
                                    <td class="py-2 px-2">
                                        <select name="lokasi[]" class="lokasi-select shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="kitchen">Kitchen</option>
                                            <option value="bar">Bar</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <span class="total-display">Rp 0</span>
                                    </td>
                                    <td class="py-2 px-2">
                                        <button type="button" class="text-red-500 hover:text-red-700 remove-item-btn" onclick="removeItem(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-2">
                        <button type="button" class="text-blue-500 hover:text-blue-700 text-sm" onclick="addPesananItem()">
                            <i class="fas fa-plus-circle mr-1"></i> Tambah Item
                        </button>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2" onclick="closeModal('addPesananModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_pesanan" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Pesanan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Pesanan Modal -->
<div id="viewPesananModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Detail Pesanan</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('viewPesananModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="pesanan-detail-content" class="mt-4">
                <!-- Content will be loaded via AJAX -->
                <div class="flex justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('viewPesananModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Pesanan Modal -->
<div id="cancelPesananModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-times text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Batalkan Pesanan</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Apakah Anda yakin ingin membatalkan pesanan ini? Pesanan akan ditandai sebagai dibatalkan dan akan tetap muncul di halaman Bahan Baku dengan status Retur.</p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="cancel_id_pesanan" name="id_pesanan">
                
                <div class="items-center px-4 py-3">
                    <button type="submit" name="cancel_pesanan" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Ya, Batalkan Pesanan
                    </button>
                    <button type="button" onclick="closeModal('cancelPesananModal')" class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show modal function
    function showModal(modalId) {
        console.log("Opening modal:", modalId);
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error("Modal not found:", modalId);
            return;
        }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }
    
    // Close modal function
    function closeModal(modalId) {
        console.log("Closing modal:", modalId);
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error("Modal not found:", modalId);
            return;
        }
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    // Load supplier items function
    function loadSupplierItems() {
        const supplierId = document.getElementById('id_supplier').value;
        const itemSelects = document.querySelectorAll('.barang-select');
        
        if (!supplierId) {
            // Clear all item selects if no supplier selected
            itemSelects.forEach(select => {
                select.innerHTML = '<option value="">-- Pilih Barang --</option>';
            });
            return;
        }
        
        // Show loading indicator
        itemSelects.forEach(select => {
            select.innerHTML = '<option value="">Loading...</option>';
            select.disabled = true;
        });
        
        console.log("Fetching supplier items for supplier ID:", supplierId);
        
        // Fetch supplier items via AJAX
        fetch('ajax_get_supplier_items.php?supplier_id=' + supplierId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log("Received data:", data);
                
                if (data.success) {
                    const items = data.items || [];
                    console.log("Items count:", items.length);
                    
                    let optionsHtml = '';
                    
                    if (items.length > 0) {
                        optionsHtml = items.map(item => {
                            if (item.id_barang > 0) {
                                return `<option value="${item.id_barang}" 
                                         data-satuan="${item.satuan || ''}" 
                                         data-harga="${item.harga || 0}">
                                    ${item.nama_barang} (${item.satuan || 'pcs'})
                                 </option>`;
                            } else {
                                // For new items (id_barang = 0), include the name and satuan as data attributes
                                return `<option value="0" 
                                         data-satuan="${item.satuan || ''}" 
                                         data-harga="${item.harga || 0}"
                                         data-new-item="true"
                                         data-name="${item.nama_barang}">
                                    ${item.nama_barang} (${item.satuan || 'pcs'}) - Baru
                                 </option>`;
                            }
                        }).join('');
                    } else {
                        console.log("No items found for supplier");
                        // Add option to create new item
                        optionsHtml = `<option value="0" data-new-item="true">+ Tambah Item Baru</option>`;
                    }
                    
                    const defaultOption = '<option value="">-- Pilih Barang --</option>';
                    
                    // Update all item selects
                    itemSelects.forEach(select => {
                        select.innerHTML = defaultOption + optionsHtml;
                        select.disabled = false;
                        
                        // Add change event to populate harga and handle new items
                        select.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            if (!selectedOption || selectedOption.value === '') return;
                            
                            const row = this.closest('.pesanan-item');
                            
                            // Remove any existing hidden fields for new items
                            const existingFields = row.querySelectorAll('.new-item-field');
                            existingFields.forEach(field => field.remove());
                            
                            if (selectedOption.value === "0" && selectedOption.dataset.newItem === "true") {
                                // This is a new item, check if it's a predefined new item or "Add New Item" option
                                if (selectedOption.dataset.name) {
                                    // Predefined new item
                                const nameField = document.createElement('input');
                                nameField.type = 'hidden';
                                nameField.name = 'new_item_name[]';
                                nameField.value = selectedOption.dataset.name;
                                nameField.className = 'new-item-field';
                                row.appendChild(nameField);
                                
                                const satuanField = document.createElement('input');
                                satuanField.type = 'hidden';
                                satuanField.name = 'new_item_satuan[]';
                                satuanField.value = selectedOption.dataset.satuan || 'pcs';
                                satuanField.className = 'new-item-field';
                                row.appendChild(satuanField);
                                    
                                    // Set the harga if available
                                    if (selectedOption.dataset.harga) {
                                        row.querySelector('.harga-input').value = selectedOption.dataset.harga;
                                    }
                                } else {
                                    // "Add New Item" option - show modal or inline form to add new item
                                    showNewItemForm(row);
                                    return;
                                }
                            }
                            
                            if (selectedOption.dataset.harga) {
                                row.querySelector('.harga-input').value = selectedOption.dataset.harga;
                                calculateTotal(row);
                            }
                        });
                    });
                } else {
                    // Show error
                    console.error('Error loading supplier items:', data.message);
                    itemSelects.forEach(select => {
                        select.innerHTML = '<option value="">Error: ' + (data.message || 'Unknown error') + '</option>';
                        select.disabled = false;
                    });
                }
            })
            .catch(error => {
                // Show error
                console.error('Error fetching supplier items:', error);
                itemSelects.forEach(select => {
                    select.innerHTML = '<option value="">Error: ' + error.message + '</option>';
                    select.disabled = false;
                });
            });
    }
    
    // Function to show new item form
    function showNewItemForm(row) {
        // Get the select element
        const select = row.querySelector('.barang-select');
        
        // Create inline form for new item
        const formHtml = `
            <div class="new-item-form mt-2 p-2 border rounded bg-gray-50">
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nama Barang</label>
                    <input type="text" class="new-item-name shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm" required>
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Satuan</label>
                    <input type="text" class="new-item-satuan shadow-sm border border-gray-300 rounded w-full py-1 px-2 text-gray-700 text-sm" value="pcs" required>
                </div>
                <div class="flex space-x-2">
                    <button type="button" class="save-new-item bg-blue-500 hover:bg-blue-700 text-white text-xs py-1 px-2 rounded">Simpan</button>
                    <button type="button" class="cancel-new-item bg-gray-500 hover:bg-gray-700 text-white text-xs py-1 px-2 rounded">Batal</button>
                </div>
            </div>
        `;
        
        // Create a container for the form
        const formContainer = document.createElement('div');
        formContainer.className = 'new-item-form-container';
        formContainer.innerHTML = formHtml;
        
        // Insert after the select
        select.parentNode.insertBefore(formContainer, select.nextSibling);
        
        // Add event listeners
        const saveBtn = formContainer.querySelector('.save-new-item');
        const cancelBtn = formContainer.querySelector('.cancel-new-item');
        const nameInput = formContainer.querySelector('.new-item-name');
        const satuanInput = formContainer.querySelector('.new-item-satuan');
        
        saveBtn.addEventListener('click', function() {
            const name = nameInput.value.trim();
            const satuan = satuanInput.value.trim();
            
            if (!name) {
                alert('Nama barang tidak boleh kosong');
                return;
            }
            
            if (!satuan) {
                alert('Satuan tidak boleh kosong');
                return;
            }
            
            // Create a new option for this item
            const newOption = document.createElement('option');
            newOption.value = "0";
            newOption.dataset.newItem = "true";
            newOption.dataset.name = name;
            newOption.dataset.satuan = satuan;
            newOption.textContent = `${name} (${satuan}) - Baru`;
            
            // Add to select and select it
            select.appendChild(newOption);
            select.value = "0";
            
            // Create hidden fields
            const nameField = document.createElement('input');
            nameField.type = 'hidden';
            nameField.name = 'new_item_name[]';
            nameField.value = name;
            nameField.className = 'new-item-field';
            row.appendChild(nameField);
            
            const satuanField = document.createElement('input');
            satuanField.type = 'hidden';
            satuanField.name = 'new_item_satuan[]';
            satuanField.value = satuan;
            satuanField.className = 'new-item-field';
            row.appendChild(satuanField);
            
            // Remove the form
            formContainer.remove();
        });
        
        cancelBtn.addEventListener('click', function() {
            // Reset select to default
            select.selectedIndex = 0;
            
            // Remove the form
            formContainer.remove();
            });
    }
    
    // Add pesanan item function
    function addPesananItem() {
        const tbody = document.getElementById('pesanan-items-body');
        const template = document.querySelector('.pesanan-item').cloneNode(true);
        
        // Reset values
        template.querySelector('.barang-select').selectedIndex = 0;
        template.querySelector('.qty-input').value = 1;
        template.querySelector('.periode-select').selectedIndex = 0;
        template.querySelector('.harga-input').value = '';
        template.querySelector('.lokasi-select').selectedIndex = 0;
        template.querySelector('.total-display').textContent = 'Rp 0';
        
        // Remove any existing hidden fields for new items
        const existingFields = template.querySelectorAll('.new-item-field');
        existingFields.forEach(field => field.remove());
        
        // Add event listeners
        setupItemEvents(template);
        
        // Append to table
        tbody.appendChild(template);
        
        // If supplier is already selected, load items for this new row
        const supplierId = document.getElementById('id_supplier').value;
        if (supplierId) {
            const select = template.querySelector('.barang-select');
            select.innerHTML = '<option value="">Loading...</option>';
            select.disabled = true;
            
            fetch('ajax_get_supplier_items.php?supplier_id=' + supplierId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const items = data.items;
                        let optionsHtml = items.map(item => {
                            if (item.id_barang > 0) {
                                return `<option value="${item.id_barang}" 
                                         data-satuan="${item.satuan}" 
                                         data-harga="${item.harga}">
                                    ${item.nama_barang} (${item.satuan})
                                 </option>`;
                            } else {
                                // For new items (id_barang = 0), include the name and satuan as data attributes
                                return `<option value="0" 
                                         data-satuan="${item.satuan}" 
                                         data-harga="${item.harga}"
                                         data-new-item="true"
                                         data-name="${item.nama_barang}">
                                    ${item.nama_barang} (${item.satuan}) - Baru
                                 </option>`;
                            }
                        }).join('');
                        
                        select.innerHTML = '<option value="">-- Pilih Barang --</option>' + optionsHtml;
                        select.disabled = false;
                        
                        // Add change event to populate harga and handle new items
                        select.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const row = this.closest('.pesanan-item');
                            
                            // Remove any existing hidden fields for new items
                            const existingFields = row.querySelectorAll('.new-item-field');
                            existingFields.forEach(field => field.remove());
                            
                            if (selectedOption.value === "0" && selectedOption.dataset.newItem === "true") {
                                // This is a new item, add hidden fields for name and satuan
                                const nameField = document.createElement('input');
                                nameField.type = 'hidden';
                                nameField.name = 'new_item_name[]';
                                nameField.value = selectedOption.dataset.name;
                                nameField.className = 'new-item-field';
                                row.appendChild(nameField);
                                
                                const satuanField = document.createElement('input');
                                satuanField.type = 'hidden';
                                satuanField.name = 'new_item_satuan[]';
                                satuanField.value = selectedOption.dataset.satuan;
                                satuanField.className = 'new-item-field';
                                row.appendChild(satuanField);
                            }
                            
                            if (selectedOption.dataset.harga) {
                                row.querySelector('.harga-input').value = selectedOption.dataset.harga;
                                calculateTotal(row);
                            }
                        });
                    }
                })
                .catch(error => {
                    select.innerHTML = '<option value="">Error loading items</option>';
                    select.disabled = false;
                });
        }
    }
    
    // Remove pesanan item function
    function removeItem(button) {
        const tbody = document.getElementById('pesanan-items-body');
        const row = button.closest('.pesanan-item');
        
        // Don't remove if it's the last row
        if (tbody.querySelectorAll('.pesanan-item').length > 1) {
            tbody.removeChild(row);
        } else {
            // Reset values instead of removing
            row.querySelector('.barang-select').selectedIndex = 0;
            row.querySelector('.qty-input').value = 1;
            row.querySelector('.periode-select').selectedIndex = 0;
            row.querySelector('.harga-input').value = '';
            row.querySelector('.lokasi-select').selectedIndex = 0;
            row.querySelector('.total-display').textContent = 'Rp 0';
        }
    }
    
    // Calculate total function
    function calculateTotal(row) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const harga = parseFloat(row.querySelector('.harga-input').value) || 0;
        const total = qty * harga;
        
        row.querySelector('.total-display').textContent = formatRupiah(total);
    }
    
    // Setup events for item row
    function setupItemEvents(row) {
        const qtyInput = row.querySelector('.qty-input');
        const hargaInput = row.querySelector('.harga-input');
        const barangSelect = row.querySelector('.barang-select');
        
        qtyInput.addEventListener('input', function() {
            calculateTotal(row);
        });
        
        hargaInput.addEventListener('input', function() {
            calculateTotal(row);
        });
        
        barangSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            // Remove any existing hidden fields for new items
            const existingFields = row.querySelectorAll('.new-item-field');
            existingFields.forEach(field => field.remove());
            
            if (selectedOption.value === "0" && selectedOption.dataset.newItem === "true") {
                // This is a new item, add hidden fields for name and satuan
                const nameField = document.createElement('input');
                nameField.type = 'hidden';
                nameField.name = 'new_item_name[]';
                nameField.value = selectedOption.dataset.name;
                nameField.className = 'new-item-field';
                row.appendChild(nameField);
                
                const satuanField = document.createElement('input');
                satuanField.type = 'hidden';
                satuanField.name = 'new_item_satuan[]';
                satuanField.value = selectedOption.dataset.satuan;
                satuanField.className = 'new-item-field';
                row.appendChild(satuanField);
            }
            
            if (selectedOption.dataset.harga) {
                hargaInput.value = selectedOption.dataset.harga;
                calculateTotal(row);
            }
        });
    }
    
    // Format rupiah for client-side
    function formatRupiah(angka) {
        return "Rp " + parseFloat(angka).toLocaleString('id-ID');
    }
    
    // View pesanan function
    function viewPesanan(id) {
        // Show modal with loading indicator
        showModal('viewPesananModal');
        
        // Get pesanan details via AJAX
        const detailContent = document.getElementById('pesanan-detail-content');
        detailContent.innerHTML = `
            <div class="flex justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
        `;
        
        // Fetch data from AJAX endpoint
        fetch('ajax_get_pesanan.php?id=' + id)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const pesanan = data.pesanan;
                    const details = pesanan.details || [];
                    
                    // Build the HTML for pesanan details
                    let html = `
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-800 mb-2">Informasi Pesanan</h4>
                                    <p class="text-sm text-gray-600"><span class="font-medium">No Pesanan:</span> #${pesanan.id_pesanan}</p>
                                    <p class="text-sm text-gray-600"><span class="font-medium">Tanggal Pesan:</span> ${pesanan.tanggal_pesan}</p>
                                    <p class="text-sm text-gray-600"><span class="font-medium">Status:</span> 
                                        <span class="px-2 py-1 text-xs rounded-full ${
                                            pesanan.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            pesanan.status === 'selesai' ? 
                                                (pesanan.has_retur ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800') :
                                            'bg-red-100 text-red-800'
                                        }">${pesanan.status_text}</span>
                                    </p>
                                    <p class="text-sm text-gray-600"><span class="font-medium">Dibuat oleh:</span> ${pesanan.user.nama_user || '-'}</p>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-gray-800 mb-2">Informasi Supplier</h4>
                                    <p class="text-sm text-gray-600"><span class="font-medium">Nama:</span> ${pesanan.supplier.nama_supplier}</p>
                                    <p class="text-sm text-gray-600"><span class="font-medium">Kontak:</span> ${pesanan.supplier.kontak || '-'}</p>
                                    <p class="text-sm text-gray-600"><span class="font-medium">Alamat:</span> ${pesanan.supplier.alamat || '-'}</p>
                                </div>
                            </div>
                            ${pesanan.catatan ? `
                            <div class="mt-4">
                                <h4 class="text-md font-medium text-gray-800 mb-1">Catatan:</h4>
                                <p class="text-sm text-gray-600 bg-white p-2 rounded border">${pesanan.catatan}</p>
                            </div>
                            ` : ''}
                        </div>`;

                    // Only show details table if there are items
                    if (details && details.length > 0) {
                        html += `
                            <div class="mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-2">Detail Item Pesanan</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full bg-white border">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Barang</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;

                        // Add rows for each detail item
                        details.forEach((item, index) => {
                            html += `
                                <tr class="border-b">
                                    <td class="py-2 px-2 text-sm">${index + 1}</td>
                                    <td class="py-2 px-2 text-sm">${item.nama_barang}</td>
                                    <td class="py-2 px-2 text-sm">${item.qty}</td>
                                    <td class="py-2 px-2 text-sm">${item.satuan}</td>
                                    <td class="py-2 px-2 text-sm">Periode ${item.periode}</td>
                                    <td class="py-2 px-2 text-sm">${item.harga_satuan_formatted}</td>
                                    <td class="py-2 px-2 text-sm">${item.lokasi}</td>
                                    <td class="py-2 px-2 text-sm font-medium">${item.total_formatted}</td>
                                </tr>`;
                        });

                        // Add total row
                        html += `
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="7" class="py-2 px-2 text-sm font-medium text-right">Total:</td>
                                        <td class="py-2 px-2 text-sm font-bold">${pesanan.total_nilai_formatted}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>`;
                    } else {
                        html += `
                            <div class="mb-4 p-4 bg-yellow-50 text-yellow-700 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Tidak ada detail item untuk pesanan ini.
                            </div>`;
                    }

                    // Show linked bahan_baku if any
                    if (pesanan.linked_bahan && pesanan.linked_bahan.length > 0) {
                        html += `
                            <div class="mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-2">Bahan Baku Terkait</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full bg-white border">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Barang</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Input</th>
                                                <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;

                        // Add rows for each linked bahan_baku
                        pesanan.linked_bahan.forEach((item, index) => {
                            const statusClass = 
                                item.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                item.status === 'approved' ? 'bg-green-100 text-green-800' : 
                                'bg-red-100 text-red-800';
                            
                            html += `
                                <tr class="border-b">
                                    <td class="py-2 px-2 text-sm">${index + 1}</td>
                                    <td class="py-2 px-2 text-sm">${item.nama_barang}</td>
                                    <td class="py-2 px-2 text-sm">${item.qty} ${item.satuan}</td>
                                    <td class="py-2 px-2 text-sm">Periode ${item.periode}</td>
                                    <td class="py-2 px-2 text-sm">${item.tanggal_formatted}</td>
                                    <td class="py-2 px-2 text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full ${statusClass}">
                                            ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                                        </span>
                                    </td>
                                </tr>`;
                        });

                        html += `
                                </tbody>
                            </table>
                        </div>
                    </div>`;
                    } else {
                        html += `
                            <div class="mb-4 p-4 bg-yellow-50 text-yellow-700 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Tidak ada bahan baku terkait untuk pesanan ini.
                            </div>`;
                    }

                    // Add action buttons based on status
                    if (pesanan.can_cancel) {
                        html += `<div class="flex justify-end space-x-2">`;
                        
                        html += `
                            <button type="button" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="cancelPesanan(${pesanan.id_pesanan})">
                                <i class="fas fa-times mr-1"></i> Batalkan Pesanan
                            </button>`;
                        
                        html += `</div>`;
                    }
                    
                    detailContent.innerHTML = html;
                } else {
                    // Show error message from API
                    detailContent.innerHTML = `
                        <div class="p-4 bg-red-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Gagal memuat detail pesanan</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>${data.message || 'Terjadi kesalahan saat memuat data pesanan.'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                }
            })
            .catch(error => {
                console.error('Error fetching pesanan details:', error);
                // Show error with more details
                detailContent.innerHTML = `
                    <div class="p-4 bg-red-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Gagal memuat detail pesanan</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>${error.message || 'Terjadi kesalahan saat memuat data pesanan.'}</p>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
    }
    
    // Cancel pesanan function
    function cancelPesanan(id) {
        document.getElementById('cancel_id_pesanan').value = id;
        showModal('cancelPesananModal');
    }
    
    function changePerPage(perPage) {
        window.location.href = "?page=1&per_page=" + perPage;
    }
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Set up event listeners for the first pesanan item row
        const firstRow = document.querySelector('.pesanan-item');
        if (firstRow) {
            setupItemEvents(firstRow);
        }
        
        console.log("DOM fully loaded and event listeners initialized");
    });
    
    // Add event listener to qty_retur input
    document.addEventListener('DOMContentLoaded', function() {
        const qtyReturInput = document.getElementById('qty_retur');
        if (qtyReturInput) {
            qtyReturInput.addEventListener('input', calculateJumlahMasuk);
        }
        
        // Initialize tooltips
        initTooltips();
    });
    
    // Initialize tooltips
    function initTooltips() {
        const tooltips = document.querySelectorAll('.tooltip');
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', function() {
                const tooltipText = this.getAttribute('data-tooltip');
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'tooltip-text';
                tooltipEl.textContent = tooltipText;
                document.body.appendChild(tooltipEl);
                
                const rect = this.getBoundingClientRect();
                tooltipEl.style.left = rect.left + window.scrollX + 'px';
                tooltipEl.style.top = rect.bottom + window.scrollY + 5 + 'px';
            });
            
            tooltip.addEventListener('mouseleave', function() {
                const tooltipEl = document.querySelector('.tooltip-text');
                if (tooltipEl) {
                    tooltipEl.remove();
                }
            });
        });
    }
</script>

<style>
    .tooltip {
        position: relative;
        cursor: pointer;
        display: inline-block;
    }
    
    .tooltip-text {
        position: absolute;
        z-index: 1000;
        background-color: #333;
        color: #fff;
        padding: 8px 12px;
        border-radius: 6px;
        max-width: 300px;
        word-wrap: break-word;
        font-size: 14px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .tooltip-text::after {
        content: "";
        position: absolute;
        bottom: 100%;
        left: 20px;
        border-width: 5px;
        border-style: solid;
        border-color: transparent transparent #333 transparent;
    }
</style>

<?php require_once 'includes/footer.php'; ?>