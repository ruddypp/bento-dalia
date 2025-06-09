<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Special permission handling for different roles
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'headproduksi' || $_SESSION['user_role'] === 'purchasing') {
        // Full access for headproduksi and purchasing
        $permission = 'full';
        $VIEW_ONLY = false;
    } elseif ($_SESSION['user_role'] === 'crew') {
        // View-only access for crew, but allow detail viewing
        $permission = 'view';
        $VIEW_ONLY = true;
    } else {
        // For other roles, use regular permission check
        require_once 'role_permission_check.php';
    }
} else {
    // For users not logged in or without role
    require_once 'role_permission_check.php';
}

// Pastikan parameter id tersedia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="text-red-500 p-4">ID tidak valid</div>';
    exit;
}

$id_lost = (int)$_GET['id'];

// Query untuk mendapatkan detail
$query = "SELECT 
            l.*,
            b.nama_barang,
            b.satuan,
            u.username as dibuat_oleh_user
          FROM 
            lost_barang l
          JOIN 
            barang b ON l.id_barang = b.id_barang
          LEFT JOIN 
            users u ON l.dibuat_oleh = u.id_user
          WHERE 
            l.id_lost = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_lost);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo '<div class="text-red-500 p-4">Data tidak ditemukan</div>';
    exit;
}

$item = mysqli_fetch_assoc($result);

// Format tanggal
$tanggal_input = isset($item['created_at']) && !empty($item['created_at']) 
    ? date('d M Y H:i', strtotime($item['created_at'])) 
    : 'Tidak ada data';

// Path foto
$foto_path = '';
if (!empty($item['foto_bukti'])) {
    $foto_path = 'uploads/lost/' . $item['foto_bukti'];
}
?>

<div class="p-4">
    <h3 class="text-lg font-semibold mb-4">Detail Barang Lost</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <table class="w-full">
                <tr>
                    <td class="py-2 font-medium text-gray-600">Nama Barang</td>
                    <td class="py-2"><?= htmlspecialchars($item['nama_barang']) ?></td>
                </tr>
                <tr>
                    <td class="py-2 font-medium text-gray-600">Jumlah</td>
                    <td class="py-2"><?= number_format($item['jumlah'], 2) ?> <?= htmlspecialchars($item['satuan']) ?></td>
                </tr>
                <tr>
                    <td class="py-2 font-medium text-gray-600">Tanggal Input</td>
                    <td class="py-2"><?= $tanggal_input ?></td>
                </tr>
                <tr>
                    <td class="py-2 font-medium text-gray-600">Dibuat Oleh</td>
                    <td class="py-2"><?= htmlspecialchars($item['dibuat_oleh_user'] ?? 'Unknown') ?></td>
                </tr>
            </table>
        </div>
        
        <div>
            <p class="font-medium text-gray-600 mb-2">Alasan:</p>
            <p class="bg-gray-100 p-3 rounded-md"><?= nl2br(htmlspecialchars($item['alasan'])) ?></p>
        </div>
    </div>
    
    <?php if (!empty($foto_path) && file_exists($foto_path)): ?>
    <div class="mt-4">
        <p class="font-medium text-gray-600 mb-2">Foto Bukti:</p>
        <div class="mt-2">
            <img src="<?= $foto_path ?>" alt="Foto Bukti" class="max-w-full h-auto rounded-lg shadow-sm">
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mt-6 text-right">
        <button onclick="closeModal('detailModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-all">
            Tutup
        </button>
    </div>
</div> 