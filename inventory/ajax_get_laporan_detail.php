<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="text-red-500">Error: ID not provided</div>';
    exit;
}

$id_laporan = $_GET['id'];

// Get the main report data
$query = "SELECT lm.*, u.nama_pengguna
          FROM laporan_masuk lm
          LEFT JOIN pengguna u ON lm.created_by = u.id_pengguna
          WHERE lm.id_laporan_masuk = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo '<div class="text-red-500">Error: ' . $conn->error . '</div>';
    exit;
}

$stmt->bind_param("i", $id_laporan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="text-red-500">Error: Laporan tidak ditemukan</div>';
    exit;
}

$laporan = $result->fetch_assoc();

// Get all the detail items
$query_detail = "SELECT bm.*, b.nama_barang, b.satuan, s.nama_supplier 
                FROM laporan_masuk_detail lmd
                JOIN barang_masuk bm ON lmd.id_masuk = bm.id_masuk
                JOIN barang b ON bm.id_barang = b.id_barang
                JOIN supplier s ON bm.id_supplier = s.id_supplier
                WHERE lmd.id_laporan = ?";

$stmt_detail = $conn->prepare($query_detail);
if (!$stmt_detail) {
    echo '<div class="text-red-500">Error: ' . $conn->error . '</div>';
    exit;
}

$stmt_detail->bind_param("i", $id_laporan);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

$details = [];
while ($row = $result_detail->fetch_assoc()) {
    $details[] = $row;
}
?>

<div class="mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <p class="text-sm text-gray-600">Nomor Laporan</p>
            <p class="font-medium"><?= $laporan['id_laporan_masuk'] ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Tanggal Laporan</p>
            <p class="font-medium"><?= date('d F Y', strtotime($laporan['tanggal_laporan'])) ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Dibuat Oleh</p>
            <p class="font-medium"><?= $laporan['nama_pengguna'] ?? 'N/A' ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Dibuat Pada</p>
            <p class="font-medium"><?= $laporan['created_at'] ? date('d F Y H:i', strtotime($laporan['created_at'])) : 'N/A' ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Status</p>
            <p class="font-medium">
                <?php 
                $status = $laporan['status'] ?? 'pending';
                $statusClass = '';
                switch ($status) {
                    case 'pending':
                        $statusClass = 'text-yellow-600';
                        break;
                    case 'diproses':
                        $statusClass = 'text-blue-600';
                        break;
                    case 'selesai':
                        $statusClass = 'text-green-600';
                        break;
                    case 'dibatalkan':
                        $statusClass = 'text-red-600';
                        break;
                }
                ?>
                <span class="<?= $statusClass ?>"><?= ucfirst($status) ?></span>
            </p>
        </div>
    </div>
    
    <div class="mt-4">
        <h4 class="text-lg font-medium mb-2">Detail Barang</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-2">No</th>
                        <th class="px-4 py-2">Nama Barang</th>
                        <th class="px-4 py-2">Jumlah</th>
                        <th class="px-4 py-2">Satuan</th>
                        <th class="px-4 py-2">Supplier</th>
                        <th class="px-4 py-2">Tanggal Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($details)): ?>
                    <tr class="bg-white border-b">
                        <td colspan="6" class="px-4 py-2 text-center">Tidak ada detail barang</td>
                    </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($details as $detail): ?>
                        <tr class="bg-white border-b">
                            <td class="px-4 py-2"><?= $no++ ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($detail['nama_barang']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($detail['qty_masuk']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($detail['satuan']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($detail['nama_supplier']) ?></td>
                            <td class="px-4 py-2"><?= date('d F Y', strtotime($detail['tanggal_masuk'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> 