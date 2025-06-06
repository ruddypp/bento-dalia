<?php
$pageTitle = "Detail Penjualan";
require_once 'includes/header.php';

// Format currency to Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to penjualan page if no ID
    header("Location: laporan_penjualan.php");
    exit;
}

$id_penjualan = intval($_GET['id']);

// Get transaction details
$transaction = null;
$query = "SELECT * FROM penjualan WHERE id_penjualan = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_penjualan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $transaction = $result->fetch_assoc();
} else {
    // Transaction not found
    header("Location: laporan_penjualan.php");
    exit;
}

// Get transaction items
$items = [];
$query_items = "SELECT pd.*, m.nama_menu, m.kategori, m.bahan 
                FROM penjualan_detail pd 
                JOIN menu m ON pd.id_menu = m.id_menu 
                WHERE pd.id_penjualan = ?";
$stmt_items = $conn->prepare($query_items);
$stmt_items->bind_param("i", $id_penjualan);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

while ($row = $result_items->fetch_assoc()) {
    $items[] = $row;
}

// Get ingredients used
$ingredients = [];
$query_ingredients = "SELECT pb.*, b.nama_barang, b.satuan 
                     FROM penjualan_bahan pb 
                     JOIN barang b ON pb.id_barang = b.id_barang 
                     JOIN penjualan_detail pd ON pb.id_penjualan_detail = pd.id_penjualan_detail 
                     WHERE pd.id_penjualan = ?";
$stmt_ingredients = $conn->prepare($query_ingredients);
$stmt_ingredients->bind_param("i", $id_penjualan);
$stmt_ingredients->execute();
$result_ingredients = $stmt_ingredients->get_result();

while ($row = $result_ingredients->fetch_assoc()) {
    $ingredients[] = $row;
}

// Get cashier name
$cashier_name = "Unknown";
if (!empty($transaction['id_user'])) {
    $query_user = "SELECT nama_lengkap FROM users WHERE id_user = ?";
    $stmt_user = $conn->prepare($query_user);
    $stmt_user->bind_param("i", $transaction['id_user']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $cashier_name = $user['nama_lengkap'];
    }
}
?>

<div class="ml-17 p-2">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-700 flex items-center">
            <i class="fas fa-receipt mr-2"></i> Detail Transaksi
        </h1>
        <div class="flex items-center mt-2 text-sm">
            <a href="dashboard.php" class="text-blue-500 hover:text-blue-700">Dashboard</a>
            <span class="mx-2">></span>
            <a href="laporan_penjualan.php" class="text-blue-500 hover:text-blue-700">Laporan Penjualan</a>
            <span class="mx-2">></span>
            <span class="text-gray-600">Detail Transaksi</span>
        </div>
    </div>
    
    <!-- Transaction Header -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-semibold mb-2">Invoice #<?= htmlspecialchars($transaction['no_invoice']) ?></h2>
                <p class="text-gray-600"><?= date('d M Y H:i', strtotime($transaction['tanggal_penjualan'])) ?></p>
                
                <div class="mt-4">
                    <p class="text-sm">
                        <span class="font-medium">Kasir:</span> <?= htmlspecialchars($cashier_name) ?>
                    </p>
                    <?php if (!empty($transaction['nama_pelanggan'])): ?>
                    <p class="text-sm">
                        <span class="font-medium">Pelanggan:</span> <?= htmlspecialchars($transaction['nama_pelanggan']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-right">
                <div class="bg-blue-50 px-4 py-2 rounded-lg border border-blue-100 inline-block">
                    <p class="text-sm text-blue-600 mb-1">Total Pembayaran</p>
                    <p class="text-2xl font-bold"><?= formatRupiah($transaction['total_harga']) ?></p>
                </div>
                
                <div class="mt-2">
                    <p class="text-sm">
                        <span class="font-medium">Modal:</span> <?= formatRupiah($transaction['total_modal']) ?>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">Keuntungan:</span> <span class="text-green-600"><?= formatRupiah($transaction['keuntungan']) ?></span>
                    </p>
                </div>
            </div>
        </div>
        
        <?php if (!empty($transaction['catatan'])): ?>
        <div class="mt-4 p-3 bg-gray-50 rounded-md">
            <p class="text-sm font-medium mb-1">Catatan:</p>
            <p class="text-sm"><?= nl2br(htmlspecialchars($transaction['catatan'])) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="flex justify-end mt-4">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition duration-200">
                <i class="fas fa-print mr-1"></i> Cetak
            </button>
        </div>
    </div>
    
    <!-- Transaction Items -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-lg font-semibold mb-4">Item Pesanan</h2>
                
                <?php if (empty($items)): ?>
                <div class="text-center py-4 text-gray-500">
                    <p>Tidak ada item dalam transaksi ini</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-2">Menu</th>
                                <th class="px-4 py-2">Kategori</th>
                                <th class="px-4 py-2 text-center">Jumlah</th>
                                <th class="px-4 py-2 text-right">Harga Satuan</th>
                                <th class="px-4 py-2 text-right">Modal Satuan</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2 font-medium"><?= htmlspecialchars($item['nama_menu']) ?></td>
                                <td class="px-4 py-2"><?= ucfirst($item['kategori']) ?></td>
                                <td class="px-4 py-2 text-center"><?= $item['jumlah'] ?></td>
                                <td class="px-4 py-2 text-right"><?= formatRupiah($item['harga_satuan']) ?></td>
                                <td class="px-4 py-2 text-right"><?= formatRupiah($item['harga_modal_satuan']) ?></td>
                                <td class="px-4 py-2 text-right font-medium"><?= formatRupiah($item['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-4 py-2 text-right font-medium">Total</td>
                                <td class="px-4 py-2 text-right font-bold"><?= formatRupiah($transaction['total_harga']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold mb-4">Bahan Terpakai</h2>
                
                <?php if (empty($ingredients)): ?>
                <div class="text-center py-4 text-gray-500">
                    <p>Tidak ada data bahan terpakai</p>
                </div>
                <?php else: ?>
                <div class="overflow-y-auto max-h-80">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2">Bahan</th>
                                <th class="px-4 py-2 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingredients as $ingredient): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2"><?= htmlspecialchars($ingredient['nama_barang']) ?></td>
                                <td class="px-4 py-2 text-right"><?= $ingredient['jumlah'] ?> <?= $ingredient['satuan'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="mt-6">
        <a href="laporan_penjualan.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200 inline-block">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Laporan
        </a>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .ml-64 {
        margin-left: 0 !important;
    }
    .bg-white, .bg-white * {
        visibility: visible;
    }
    .bg-white {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    button {
        display: none !important;
    }
    a.px-4 {
        display: none !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 