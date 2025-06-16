<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access";
    exit();
}

// Check if ID is provided
if (isset($_GET['id'])) {
    $id_penerimaan = (int)$_GET['id'];
    
    // Get penerimaan details
    $query = "SELECT p.*, s.nama_supplier, u.nama_pengguna 
              FROM penerimaan p 
              JOIN supplier s ON p.id_supplier = s.id_supplier 
              JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
              WHERE p.id_penerimaan = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $penerimaan = mysqli_fetch_assoc($result);
    
    if (!$penerimaan) {
        echo '<div class="text-center text-red-500">Data penerimaan tidak ditemukan!</div>';
        exit;
    }
    
    // Get detail items
    $query = "SELECT dt.*, b.nama_barang, b.satuan 
              FROM detail_terima dt 
              JOIN barang b ON dt.id_barang = b.id_barang 
              WHERE dt.id_penerimaan = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_penerimaan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Output the detail
    ?>
    <div class="bg-gray-50 p-4 mb-4 rounded-lg border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">No. Penerimaan:</span> <?= $penerimaan['id_penerimaan'] ?></p>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">Tanggal Penerimaan:</span> <?= date('d/m/Y', strtotime($penerimaan['tanggal_terima'])) ?></p>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">Status:</span> 
                    <?php if ($penerimaan['status_penerimaan'] == 'diterima'): ?>
                        <span class="text-green-600">Diterima</span>
                    <?php else: ?>
                        <span class="text-red-600">Diretur</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">Supplier:</span> <?= $penerimaan['nama_supplier'] ?></p>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">Petugas:</span> <?= $penerimaan['nama_pengguna'] ?></p>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">No</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">Nama Barang</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">Jumlah</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">Kualitas</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">Expired</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php 
                $no = 1;
                while ($item = mysqli_fetch_assoc($result)): 
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $no++ ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $item['nama_barang'] ?></td>
                    <td class="py-2 px-3 text-sm"><?= $item['jumlah_diterima'] ?> <span class="text-xs text-gray-500"><?= $item['satuan'] ?></span></td>
                    <td class="py-2 px-3 text-sm capitalize"><?= $item['kualitas'] ?></td>
                    <td class="py-2 px-3 text-sm">
                        <?= $item['tanggal_expired'] ? date('d/m/Y', strtotime($item['tanggal_expired'])) : '-' ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if (mysqli_num_rows($result) == 0): ?>
                <tr>
                    <td colspan="5" class="py-4 text-center text-sm text-gray-500">Tidak ada data barang</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
} else {
    echo '<div class="text-center text-red-500">ID Penerimaan tidak valid!</div>';
}
?> 