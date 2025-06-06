<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="text-center text-red-500">ID retur tidak valid</div>';
    exit;
}

$id = (int)$_GET['id'];

// Get retur details
$query = "SELECT rb.*, p.tanggal_terima, s.nama_supplier, u.nama_lengkap as nama_pengguna 
          FROM retur_barang rb 
          LEFT JOIN penerimaan p ON rb.id_penerimaan = p.id_penerimaan 
          LEFT JOIN supplier s ON p.id_supplier = s.id_supplier 
          LEFT JOIN users u ON rb.id_user = u.id_user 
          WHERE rb.id_retur = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo '<div class="text-center text-red-500">Data retur tidak ditemukan</div>';
    exit;
}

$retur = mysqli_fetch_assoc($result);

// Get barang name for penerimaan returns
$nama_barang = '-';
$satuan = '-';
$jumlah_retur = $retur['jumlah_retur'] ?: 0;

if ($retur['id_penerimaan']) {
    // Get detail barang yang diretur
    $query_detail = "SELECT dt.*, b.nama_barang, b.satuan 
                    FROM detail_terima dt
                    JOIN barang b ON dt.id_barang = b.id_barang
                    WHERE dt.id_penerimaan = ? LIMIT 1";
    $stmt_detail = mysqli_prepare($conn, $query_detail);
    mysqli_stmt_bind_param($stmt_detail, "i", $retur['id_penerimaan']);
    mysqli_stmt_execute($stmt_detail);
    $result_detail = mysqli_stmt_get_result($stmt_detail);
    
    if ($detail = mysqli_fetch_assoc($result_detail)) {
        $nama_barang = $detail['nama_barang'];
        $satuan = $detail['satuan'];
    }
    
    // Get jumlah retur if not set
    if (!$jumlah_retur) {
        $qty_query = "SELECT SUM(jumlah_diterima) as total_qty FROM detail_terima WHERE id_penerimaan = ?";
        $qty_stmt = mysqli_prepare($conn, $qty_query);
        mysqli_stmt_bind_param($qty_stmt, "i", $retur['id_penerimaan']);
        mysqli_stmt_execute($qty_stmt);
        $qty_result = mysqli_stmt_get_result($qty_stmt);
        $qty_data = mysqli_fetch_assoc($qty_result);
        $jumlah_retur = $qty_data['total_qty'] ?? 0;
        mysqli_stmt_close($qty_stmt);
    }
} elseif ($retur['id_bahan_baku']) {
    // Get bahan baku details
    $query_bahan = "SELECT bb.*, b.nama_barang, b.satuan 
                   FROM bahan_baku bb 
                   JOIN barang b ON bb.id_barang = b.id_barang 
                   WHERE bb.id_bahan_baku = ?";
    $stmt_bahan = mysqli_prepare($conn, $query_bahan);
    mysqli_stmt_bind_param($stmt_bahan, "i", $retur['id_bahan_baku']);
    mysqli_stmt_execute($stmt_bahan);
    $result_bahan = mysqli_stmt_get_result($stmt_bahan);
    
    if ($bahan = mysqli_fetch_assoc($result_bahan)) {
        $nama_barang = $bahan['nama_barang'];
        $satuan = $bahan['satuan'];
    }
}

// Format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Format tanggal
function formatTanggal($tanggal) {
    return date('d F Y', strtotime($tanggal));
}
?>

<div class="bg-white p-4 rounded-lg">
    <div class="border-b pb-3 mb-4">
        <h3 class="text-lg font-medium text-gray-900">Detail Retur Barang</h3>
        <p class="text-sm text-gray-500">ID Retur: <?= $retur['id_retur'] ?></p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <p class="text-sm text-gray-500 mb-1">Tanggal Retur</p>
            <p class="font-medium"><?= $retur['tanggal_retur'] ? formatTanggal($retur['tanggal_retur']) : '-' ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500 mb-1">Nama Bahan Baku</p>
            <p class="font-medium"><?= $nama_barang ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500 mb-1">Supplier</p>
            <p class="font-medium"><?= $retur['nama_supplier'] ?: $retur['supplier'] ?: '-' ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500 mb-1">Jumlah Retur</p>
            <p class="font-medium"><?= $jumlah_retur ?> <?= $satuan ?></p>
        </div>
        
        <div class="md:col-span-2">
            <p class="text-sm text-gray-500 mb-1">Alasan Retur</p>
            <p class="font-medium"><?= $retur['alasan_retur'] ?: '-' ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500 mb-1">Petugas</p>
            <p class="font-medium"><?= $retur['nama_pengguna'] ?: '-' ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500 mb-1">Tanggal Input</p>
            <p class="font-medium"><?= $retur['tanggal_retur'] ? formatTanggal($retur['tanggal_retur']) : '-' ?></p>
        </div>
    </div>
    
    <?php if ($retur['id_penerimaan']): ?>
    <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <h4 class="font-medium mb-2">Detail Penerimaan</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 mb-1">ID Penerimaan</p>
                <p class="font-medium"><?= $retur['id_penerimaan'] ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500 mb-1">Tanggal Terima</p>
                <p class="font-medium"><?= $retur['tanggal_terima'] ? formatTanggal($retur['tanggal_terima']) : '-' ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($retur['id_bahan_baku']): ?>
    <div class="bg-yellow-50 p-4 rounded-lg mb-4">
        <h4 class="font-medium mb-2">Detail Bahan Baku</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 mb-1">ID Bahan Baku</p>
                <p class="font-medium"><?= $retur['id_bahan_baku'] ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500 mb-1">Status</p>
                <p class="font-medium">
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Retur Bahan Baku
                    </span>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div> 