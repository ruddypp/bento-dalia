<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID penerimaan tidak diberikan");
}

$id_penerimaan = (int)$_GET['id'];

// Get penerimaan details
$query = "SELECT p.*, s.nama_supplier, u.nama_lengkap as nama_pengguna 
          FROM penerimaan p 
          JOIN supplier s ON p.id_supplier = s.id_supplier 
          JOIN users u ON p.id_user = u.id_user 
          WHERE p.id_penerimaan = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Data penerimaan tidak ditemukan");
}

$penerimaan = mysqli_fetch_assoc($result);

// Get retur details
$query_retur = "SELECT rb.*, u.nama_lengkap as nama_pengguna 
               FROM retur_barang rb 
               JOIN users u ON rb.id_user = u.id_user 
               WHERE rb.id_penerimaan = ?";
$stmt_retur = mysqli_prepare($conn, $query_retur);
mysqli_stmt_bind_param($stmt_retur, "i", $id_penerimaan);
mysqli_stmt_execute($stmt_retur);
$result_retur = mysqli_stmt_get_result($stmt_retur);
$retur = mysqli_fetch_assoc($result_retur);

// Get detail barang yang diretur
$query_detail = "SELECT dt.*, b.nama_barang, b.satuan 
                FROM detail_terima dt
                JOIN barang b ON dt.id_barang = b.id_barang
                WHERE dt.id_penerimaan = ?";
$stmt_detail = mysqli_prepare($conn, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_penerimaan);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

// Get barang yang masuk ke stok (approved) dari proses retur ini
$query_approved = "SELECT bb.*, b.nama_barang, b.satuan 
                  FROM bahan_baku bb 
                  JOIN barang b ON bb.id_barang = b.id_barang 
                  WHERE bb.status = 'approved' AND bb.id_user = ? AND DATE(bb.tanggal_input) = ?";
$stmt_approved = mysqli_prepare($conn, $query_approved);
mysqli_stmt_bind_param($stmt_approved, "is", $retur['id_user'], date('Y-m-d', strtotime($retur['tanggal_retur'])));
mysqli_stmt_execute($stmt_approved);
$result_approved = mysqli_stmt_get_result($stmt_approved);

// Calculate total
$total_qty_retur = 0;
$total_qty_approved = 0;

// Reset pointer
mysqli_data_seek($result_detail, 0);
while ($detail = mysqli_fetch_assoc($result_detail)) {
    $total_qty_retur += $detail['jumlah_diterima'];
}

while ($approved = mysqli_fetch_assoc($result_approved)) {
    $total_qty_approved += $approved['qty'];
}

// Format tanggal
function formatTanggal($tanggal) {
    return date('d F Y', strtotime($tanggal));
}
?>

<div class="p-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <h4 class="text-lg font-semibold mb-2">Informasi Retur</h4>
            <table class="w-full">
                <tr>
                    <td class="py-1 font-medium">Tanggal Retur</td>
                    <td class="py-1">: <?= formatTanggal($retur['tanggal_retur']) ?></td>
                </tr>
                <tr>
                    <td class="py-1 font-medium">Supplier</td>
                    <td class="py-1">: <?= $penerimaan['nama_supplier'] ?></td>
                </tr>
                <tr>
                    <td class="py-1 font-medium">Petugas</td>
                    <td class="py-1">: <?= $retur['nama_pengguna'] ?></td>
                </tr>
            </table>
        </div>
        
        <div>
            <h4 class="text-lg font-semibold mb-2">Ringkasan</h4>
            <table class="w-full">
                <tr>
                    <td class="py-1 font-medium">Total Barang Diretur</td>
                    <td class="py-1">: <?= $total_qty_retur ?> item</td>
                </tr>
                <tr>
                    <td class="py-1 font-medium">Total Barang Masuk Stok</td>
                    <td class="py-1">: <?= $total_qty_approved ?> item</td>
                </tr>
                <tr>
                    <td class="py-1 font-medium">Alasan Retur</td>
                    <td class="py-1">: <?= $retur['alasan_retur'] ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <h4 class="text-lg font-semibold mb-2">Detail Barang Diretur</h4>
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-2 px-3 text-left">No</th>
                    <th class="py-2 px-3 text-left">Nama Barang</th>
                    <th class="py-2 px-3 text-left">Jumlah</th>
                    <th class="py-2 px-3 text-left">Satuan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                mysqli_data_seek($result_detail, 0);
                if (mysqli_num_rows($result_detail) > 0): 
                    while ($detail = mysqli_fetch_assoc($result_detail)):
                ?>
                <tr class="border-t border-gray-200">
                    <td class="py-2 px-3"><?= $no++ ?></td>
                    <td class="py-2 px-3"><?= $detail['nama_barang'] ?></td>
                    <td class="py-2 px-3"><?= $detail['jumlah_diterima'] ?></td>
                    <td class="py-2 px-3"><?= $detail['satuan'] ?></td>
                </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                <tr class="border-t border-gray-200">
                    <td colspan="4" class="py-2 px-3 text-center">Tidak ada detail barang</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (mysqli_num_rows($result_approved) > 0): ?>
    <h4 class="text-lg font-semibold mb-2">Barang Yang Masuk Stok</h4>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-2 px-3 text-left">No</th>
                    <th class="py-2 px-3 text-left">Nama Barang</th>
                    <th class="py-2 px-3 text-left">Jumlah</th>
                    <th class="py-2 px-3 text-left">Satuan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                mysqli_data_seek($result_approved, 0);
                while ($approved = mysqli_fetch_assoc($result_approved)):
                ?>
                <tr class="border-t border-gray-200">
                    <td class="py-2 px-3"><?= $no++ ?></td>
                    <td class="py-2 px-3"><?= $approved['nama_barang'] ?></td>
                    <td class="py-2 px-3"><?= $approved['qty'] ?></td>
                    <td class="py-2 px-3"><?= $approved['satuan'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div> 