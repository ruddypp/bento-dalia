<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="text-center text-red-500">ID tidak valid</div>';
    exit;
}

$id = (int)$_GET['id'];

// Get bahan baku details with return information
$query = "SELECT bb.*, 
          b.nama_barang, b.satuan, 
          u.nama_lengkap as nama_pengguna
          FROM bahan_baku bb
          JOIN barang b ON bb.id_barang = b.id_barang
          LEFT JOIN users u ON bb.id_user = u.id_user
          WHERE bb.id_bahan_baku = ? AND bb.status = 'retur'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$bahan_baku = mysqli_fetch_assoc($result);

if ($bahan_baku) {
    // Format dates
    $tanggal_retur = $bahan_baku['tanggal_input'] ? date('d/m/Y', strtotime($bahan_baku['tanggal_input'])) : '-';
    
    // Calculate values
    $jumlah_total = $bahan_baku['qty'];
    $jumlah_retur = $bahan_baku['jumlah_retur'] ?? 0;
    $jumlah_masuk = $bahan_baku['jumlah_masuk'] ?? 0;
    
    // If jumlah_masuk is 0 but jumlah_retur is set, calculate jumlah_masuk
    if ($jumlah_masuk == 0 && $jumlah_retur > 0) {
        $jumlah_masuk = $jumlah_total - $jumlah_retur;
    }
    
    $harga_satuan = $bahan_baku['harga_satuan'];
    $total_nilai = $jumlah_total * $harga_satuan;
    $nilai_retur = $jumlah_retur * $harga_satuan;
    $nilai_masuk = $jumlah_masuk * $harga_satuan;
    
    // Output the detail
    ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <div class="flex items-start">
            <div class="text-yellow-600 mr-3">
                <i class="fas fa-info-circle text-xl"></i>
            </div>
            <div>
                <h4 class="text-yellow-800 font-medium mb-1">Informasi Retur Bahan Baku</h4>
                <p class="text-sm text-yellow-700">
                    Detail retur bahan baku yang telah diproses.
                </p>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h4 class="text-gray-700 font-medium mb-3 border-b pb-2">Informasi Bahan Baku</h4>
            <div class="space-y-2">
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Nama Barang</div>
                    <div class="text-sm font-medium"><?= $bahan_baku['nama_barang'] ?? '-' ?></div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Jumlah Total</div>
                    <div class="text-sm font-medium"><?= $jumlah_total ?> <?= $bahan_baku['satuan'] ?? 'pack' ?></div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Periode</div>
                    <div class="text-sm font-medium">Periode <?= $bahan_baku['periode'] ?? '1' ?></div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Harga Satuan</div>
                    <div class="text-sm font-medium">Rp <?= number_format($harga_satuan, 0, ',', '.') ?></div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="text-sm font-medium">Rp <?= number_format($total_nilai, 0, ',', '.') ?></div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Lokasi</div>
                    <div class="text-sm font-medium"><?= $bahan_baku['lokasi'] ?? 'bar' ?></div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Tanggal Input</div>
                    <div class="text-sm font-medium"><?= date('d F Y H:i', strtotime($bahan_baku['tanggal_input'])) ?></div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Status</div>
                    <div class="text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Retur
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Input Oleh</div>
                    <div class="text-sm font-medium"><?= $bahan_baku['nama_pengguna'] ?: 'Admin' ?></div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h4 class="text-gray-700 font-medium mb-3 border-b pb-2">Informasi Retur</h4>
            <div class="space-y-2">
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Tanggal Retur</div>
                    <div class="text-sm font-medium"><?= $tanggal_retur ?></div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Jumlah Diretur</div>
                    <div class="text-sm font-medium text-red-600">
                        <?= $jumlah_retur ?> <?= $bahan_baku['satuan'] ?? 'pack' ?>
                    </div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Jumlah Masuk Stok</div>
                    <div class="text-sm font-medium text-green-600">
                        <?= $jumlah_masuk ?> <?= $bahan_baku['satuan'] ?? 'pack' ?>
                    </div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="text-sm text-gray-500">Alasan Retur</div>
                    <div class="text-sm">
                        <?php if (!empty($bahan_baku['catatan_retur'])): ?>
                            <?= htmlspecialchars($bahan_baku['catatan_retur']) ?>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <h4 class="text-gray-700 font-medium mb-3 border-b pb-2">Ringkasan Biaya</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 p-3 rounded-lg">
                <div class="text-sm text-gray-500">Total Nilai Awal</div>
                <div class="text-lg font-semibold">Rp <?= number_format($total_nilai, 0, ',', '.') ?></div>
                <div class="text-xs text-gray-500 mt-1"><?= $jumlah_total ?> <?= $bahan_baku['satuan'] ?? 'pack' ?> × Rp <?= number_format($harga_satuan, 0, ',', '.') ?></div>
            </div>
            
            <div class="bg-red-50 p-3 rounded-lg">
                <div class="text-sm text-red-500">Nilai Diretur</div>
                <div class="text-lg font-semibold text-red-600">
                    Rp <?= number_format($nilai_retur, 0, ',', '.') ?>
                </div>
                <div class="text-xs text-red-500 mt-1"><?= $jumlah_retur ?> <?= $bahan_baku['satuan'] ?? 'pack' ?> × Rp <?= number_format($harga_satuan, 0, ',', '.') ?></div>
            </div>
            
            <div class="bg-green-50 p-3 rounded-lg">
                <div class="text-sm text-green-500">Nilai Masuk Stok</div>
                <div class="text-lg font-semibold text-green-600">
                    Rp <?= number_format($nilai_masuk, 0, ',', '.') ?>
                </div>
                <div class="text-xs text-green-500 mt-1"><?= $jumlah_masuk ?> <?= $bahan_baku['satuan'] ?? 'pack' ?> × Rp <?= number_format($harga_satuan, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    
    <?php
} else {
    echo '<div class="text-center text-red-500">Data bahan baku tidak ditemukan atau bukan berstatus retur</div>';
}

mysqli_stmt_close($stmt);
?> 