<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Function to format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID laporan tidak ditemukan";
    exit;
}

$id_laporan = (int)$_GET['id'];

try {
    // Get laporan header
    $query = "SELECT lm.*, DATE_FORMAT(lm.tanggal_laporan, '%d %M %Y') as formatted_date 
              FROM laporan_masuk lm
              WHERE lm.id_laporan_masuk = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_laporan);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo "Laporan tidak ditemukan";
        exit;
    }
    
    $laporan = $result->fetch_assoc();
    $stmt->close();
    
    // Get laporan details
    $detail_query = "SELECT lmd.*, bm.qty_masuk, bm.tanggal_masuk, bm.harga_satuan, bm.periode,
                    b.nama_barang, b.satuan, s.nama_supplier
                    FROM laporan_masuk_detail lmd
                    JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
                    JOIN barang b ON bm.id_barang = b.id_barang
                    LEFT JOIN supplier s ON bm.id_supplier = s.id_supplier
                    WHERE lmd.id_laporan = ?
                    ORDER BY bm.tanggal_masuk DESC";
    
    $detail_stmt = $conn->prepare($detail_query);
    if (!$detail_stmt) {
        throw new Exception("Error preparing detail query: " . $conn->error);
    }
    
    $detail_stmt->bind_param("i", $id_laporan);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();
    
    $details = [];
    while ($row = $detail_result->fetch_assoc()) {
        $details[] = $row;
    }
    $detail_stmt->close();
    
    // Output the details in HTML format
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-600">Nomor Laporan</p>
            <p class="font-semibold">#<?= $laporan['id_laporan_masuk'] ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Tanggal Laporan</p>
            <p class="font-semibold"><?= $laporan['formatted_date'] ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Status</p>
            <p class="font-semibold">
                <?php
                $statusClass = 'bg-yellow-100 text-yellow-800'; // Default for pending
                $statusText = $laporan['status'] ?? 'pending';
                
                if ($statusText == 'approved') {
                    $statusClass = 'bg-green-100 text-green-800';
                } else if ($statusText == 'rejected') {
                    $statusClass = 'bg-red-100 text-red-800';
                }
                ?>
                <span class="<?= $statusClass ?> text-xs font-medium px-2.5 py-0.5 rounded-full">
                    <?= ucfirst($statusText) ?>
                </span>
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Dibuat Pada</p>
            <p class="font-semibold"><?= $laporan['created_at'] ? date('d M Y H:i', strtotime($laporan['created_at'])) : '-' ?></p>
        </div>
    </div>
    
    <div class="border-t border-gray-200 pt-4">
        <h4 class="font-medium text-lg mb-3">Detail Barang</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-50 text-gray-600 text-sm">
                    <tr>
                        <th class="py-2 px-3 text-left">No</th>
                        <th class="py-2 px-3 text-left">Nama Barang</th>
                        <th class="py-2 px-3 text-left">Jumlah</th>
                        <th class="py-2 px-3 text-left">Satuan</th>
                        <th class="py-2 px-3 text-left">Harga Satuan</th>
                        <th class="py-2 px-3 text-left">Total</th>
                        <th class="py-2 px-3 text-left">Supplier</th>
                        <th class="py-2 px-3 text-left">Periode</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600">
                    <?php $no = 1; $grand_total = 0; ?>
                    <?php foreach ($details as $item): 
                        $total = $item['qty_masuk'] * $item['harga_satuan'];
                        $grand_total += $total;
                    ?>
                    <tr class="border-b">
                        <td class="py-2 px-3"><?= $no++ ?></td>
                        <td class="py-2 px-3"><?= htmlspecialchars($item['nama_barang']) ?></td>
                        <td class="py-2 px-3"><?= $item['qty_masuk'] ?></td>
                        <td class="py-2 px-3"><?= htmlspecialchars($item['satuan']) ?></td>
                        <td class="py-2 px-3"><?= formatRupiah($item['harga_satuan']) ?></td>
                        <td class="py-2 px-3"><?= formatRupiah($total) ?></td>
                        <td class="py-2 px-3"><?= htmlspecialchars($item['nama_supplier'] ?? '-') ?></td>
                        <td class="py-2 px-3">Periode <?= $item['periode'] ?? '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($details)): ?>
                    <tr>
                        <td colspan="8" class="py-3 px-3 text-center">Tidak ada detail barang</td>
                    </tr>
                    <?php else: ?>
                    <tr class="bg-gray-50 font-medium">
                        <td colspan="5" class="py-2 px-3 text-right">Total:</td>
                        <td class="py-2 px-3"><?= formatRupiah($grand_total) ?></td>
                        <td colspan="2"></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 