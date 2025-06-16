<?php
$pageTitle = "Dashboard";
require_once 'includes/header.php';
checkLogin();

// Mendapatkan statistik untuk dashboard
// Total barang
$query = "SELECT COUNT(*) as total FROM barang";
$result = mysqli_query($conn, $query);
$totalItems = mysqli_fetch_assoc($result)['total'];

// Total supplier
$query = "SELECT COUNT(*) as total FROM supplier";
$result = mysqli_query($conn, $query);
$totalSuppliers = mysqli_fetch_assoc($result)['total'];

// Penerimaan bulan ini
$query = "SELECT COUNT(*) as total FROM penerimaan 
          WHERE MONTH(tanggal_terima) = MONTH(CURRENT_DATE()) 
          AND YEAR(tanggal_terima) = YEAR(CURRENT_DATE())";
$result = mysqli_query($conn, $query);
$currentMonthReceipts = mysqli_fetch_assoc($result)['total'];

// Pengeluaran bulan ini
$query = "SELECT COUNT(*) as total FROM pengeluaran 
          WHERE MONTH(tanggal_keluar) = MONTH(CURRENT_DATE()) 
          AND YEAR(tanggal_keluar) = YEAR(CURRENT_DATE())";
$result = mysqli_query($conn, $query);
$currentMonthOutgoings = mysqli_fetch_assoc($result)['total'];

// Barang dengan stok di bawah stok minimum
$query = "SELECT COUNT(*) as total FROM barang WHERE stok <= stok_minimum";
$result = mysqli_query($conn, $query);
$lowStockItems = mysqli_fetch_assoc($result)['total'];

// Barang dengan stok teratas
$query = "SELECT * FROM barang ORDER BY stok DESC LIMIT 5";
$topStockItems = mysqli_query($conn, $query);

// Barang dengan stok terendah
$query = "SELECT * FROM barang WHERE stok > 0 ORDER BY stok ASC LIMIT 5";
$lowestStockItems = mysqli_query($conn, $query);

// Log aktivitas terbaru
$query = "SELECT l.*, p.nama_pengguna 
          FROM log_aktivitas l 
          JOIN pengguna p ON l.id_pengguna = p.id_pengguna 
          ORDER BY l.waktu DESC LIMIT 5";
$recentActivities = mysqli_query($conn, $query);

// Penerimaan terbaru
$query = "SELECT p.*, s.nama_supplier, u.nama_pengguna 
          FROM penerimaan p 
          JOIN supplier s ON p.id_supplier = s.id_supplier 
          JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
          ORDER BY p.tanggal_terima DESC LIMIT 5";
$recentReceipts = mysqli_query($conn, $query);

// Pengeluaran terbaru
$query = "SELECT p.*, u.nama_pengguna 
          FROM pengeluaran p 
          JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
          ORDER BY p.tanggal_keluar DESC LIMIT 5";
$recentOutgoings = mysqli_query($conn, $query);
?>

<!-- Dashboard Content -->
<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <!-- Total Barang -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-all">
        <div class="flex items-center">
            <div class="rounded-full bg-blue-50 p-3 mr-4">
                <i class="fas fa-boxes text-blue-500 text-lg"></i>
            </div>
            <div>
                <h3 class="text-gray-500 text-xs font-medium uppercase tracking-wider">Stock Barang</h3>
                <p class="text-xl font-bold text-gray-800 mt-1"><?= $totalItems ?></p>
            </div>
        </div>
    </div>
    
    <!-- Total Supplier -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-all">
        <div class="flex items-center">
            <div class="rounded-full bg-green-50 p-3 mr-4">
                <i class="fas fa-truck text-green-500 text-lg"></i>
            </div>
            <div>
                <h3 class="text-gray-500 text-xs font-medium uppercase tracking-wider">Total Supplier</h3>
                <p class="text-xl font-bold text-gray-800 mt-1"><?= $totalSuppliers ?></p>
            </div>
        </div>
    </div>
    
    <!-- Stok Rendah -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-all">
        <div class="flex items-center">
            <div class="rounded-full bg-red-50 p-3 mr-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
            </div>
            <div>
                <h3 class="text-gray-500 text-xs font-medium uppercase tracking-wider">Stok Rendah</h3>
                <p class="text-xl font-bold text-gray-800 mt-1"><?= $lowStockItems ?></p>
            </div>
        </div>
    </div>
    
    <!-- Transaksi Bulan Ini -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-all">
        <div class="flex items-center">
            <div class="rounded-full bg-purple-50 p-3 mr-4">
                <i class="fas fa-exchange-alt text-purple-500 text-lg"></i>
            </div>
            <div>
                <h3 class="text-gray-500 text-xs font-medium uppercase tracking-wider">Transaksi Bulan Ini</h3>
                <p class="text-xl font-bold text-gray-800 mt-1"><?= $currentMonthReceipts + $currentMonthOutgoings ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-6">
    <!-- Barang Stok Teratas -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-base font-medium text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-line text-blue-500 mr-2"></i> Barang Stok Teratas
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Nama Barang</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Stok</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Jenis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while ($item = mysqli_fetch_assoc($topStockItems)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 text-sm"><?= $item['nama_barang'] ?></td>
                        <td class="py-2 px-3 text-sm font-medium"><?= $item['stok'] ?> <span class="text-xs text-gray-500"><?= $item['satuan'] ?></span></td>
                        <td class="py-2 px-3 text-sm text-gray-600"><?= $item['jenis'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Barang Stok Terendah (Tidak Kosong) -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-base font-medium text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-red-500 mr-2"></i> Barang Stok Terendah
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Nama Barang</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Stok</th>
                        <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Stok Minimum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while ($item = mysqli_fetch_assoc($lowestStockItems)): ?>
                    <tr class="<?= $item['stok'] <= $item['stok_minimum'] ? 'bg-red-50' : '' ?> hover:bg-gray-50">
                        <td class="py-2 px-3 text-sm"><?= $item['nama_barang'] ?></td>
                        <td class="py-2 px-3 text-sm font-medium <?= $item['stok'] <= $item['stok_minimum'] ? 'text-red-600' : '' ?>">
                            <?= $item['stok'] ?> <span class="text-xs text-gray-500"><?= $item['satuan'] ?></span>
                        </td>
                        <td class="py-2 px-3 text-sm text-gray-600"><?= $item['stok_minimum'] ?> <span class="text-xs text-gray-500"><?= $item['satuan'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <!-- Aktivitas Terbaru -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-base font-medium text-gray-800 mb-4 flex items-center">
            <i class="fas fa-history text-blue-500 mr-2"></i> Aktivitas Terbaru
        </h3>
        <div class="space-y-3">
            <?php while ($activity = mysqli_fetch_assoc($recentActivities)): ?>
            <div class="flex items-start border-b border-gray-100 pb-3">
                <div class="rounded-full bg-blue-50 p-2 mr-3 flex-shrink-0">
                    <i class="fas fa-user-clock text-blue-500 text-xs"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800"><?= $activity['nama_pengguna'] ?></p>
                    <p class="text-xs text-gray-600 mt-0.5"><?= $activity['aktivitas'] ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?= date('d/m/Y H:i', strtotime($activity['waktu'])) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Penerimaan Terbaru -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-base font-medium text-gray-800 mb-4 flex items-center">
            <i class="fas fa-dolly text-green-500 mr-2"></i> Penerimaan Terbaru
        </h3>
        <div class="space-y-3">
            <?php while ($receipt = mysqli_fetch_assoc($recentReceipts)): ?>
            <div class="flex items-start border-b border-gray-100 pb-3">
                <div class="rounded-full bg-green-50 p-2 mr-3 flex-shrink-0">
                    <i class="fas fa-truck-loading text-green-500 text-xs"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800">Dari: <?= $receipt['nama_supplier'] ?></p>
                    <p class="text-xs text-gray-600 mt-0.5">Oleh: <?= $receipt['nama_pengguna'] ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?= date('d/m/Y', strtotime($receipt['tanggal_terima'])) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Pengeluaran Terbaru -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-base font-medium text-gray-800 mb-4 flex items-center">
            <i class="fas fa-shipping-fast text-red-500 mr-2"></i> Pengeluaran Terbaru
        </h3>
        <div class="space-y-3">
            <?php while ($outgoing = mysqli_fetch_assoc($recentOutgoings)): ?>
            <div class="flex items-start border-b border-gray-100 pb-3">
                <div class="rounded-full bg-red-50 p-2 mr-3 flex-shrink-0">
                    <i class="fas fa-arrow-right text-red-500 text-xs"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800">Keperluan: <?= mb_strimwidth($outgoing['keperluan'], 0, 30, "...") ?></p>
                    <p class="text-xs text-gray-600 mt-0.5">Oleh: <?= $outgoing['nama_pengguna'] ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?= date('d/m/Y', strtotime($outgoing['tanggal_keluar'])) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 