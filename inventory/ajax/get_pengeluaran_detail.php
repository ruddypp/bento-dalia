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
    $id_pengeluaran = (int)$_GET['id'];
    
    // Get pengeluaran details
    $query = "SELECT p.*, u.nama_pengguna 
              FROM pengeluaran p 
              JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
              WHERE p.id_pengeluaran = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_pengeluaran);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pengeluaran = mysqli_fetch_assoc($result);
    
    if (!$pengeluaran) {
        echo '<div class="text-center text-red-500">Data pengeluaran tidak ditemukan!</div>';
        exit;
    }
    
    // Get detail items
    $query = "SELECT d.*, b.nama_barang, b.satuan 
              FROM detail_keluar d 
              JOIN barang b ON d.id_barang = b.id_barang 
              WHERE d.id_pengeluaran = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_pengeluaran);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Output the detail
    ?>
    <div class="bg-gray-50 p-4 mb-4 rounded-lg border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">No. Pengeluaran:</span> <?= $pengeluaran['id_pengeluaran'] ?></p>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">Tanggal Pengeluaran:</span> <?= date('d/m/Y', strtotime($pengeluaran['tanggal_keluar'])) ?></p>
            </div>
            <div>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">Keperluan:</span> <?= $pengeluaran['keperluan'] ?></p>
                <p class="text-sm mb-1"><span class="font-medium text-gray-700">Petugas:</span> <?= $pengeluaran['nama_pengguna'] ?></p>
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
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php 
                $no = 1;
                $total_items = 0;
                while ($item = mysqli_fetch_assoc($result)): 
                    $total_items += $item['jumlah_keluar'];
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $no++ ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $item['nama_barang'] ?></td>
                    <td class="py-2 px-3 text-sm"><?= $item['jumlah_keluar'] ?> <span class="text-xs text-gray-500"><?= $item['satuan'] ?></span></td>
                </tr>
                <?php endwhile; ?>
                
                <?php if (mysqli_num_rows($result) == 0): ?>
                <tr>
                    <td colspan="3" class="py-4 text-center text-sm text-gray-500">Tidak ada data barang</td>
                </tr>
                <?php else: ?>
                <tr class="bg-gray-50">
                    <td class="py-2 px-3 text-sm font-medium" colspan="2">Total Item</td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $total_items ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
} else {
    echo '<div class="text-center text-red-500">ID Pengeluaran tidak valid!</div>';
}
?> 