<?php
$pageTitle = "Edit Laporan Barang Masuk";
require_once 'includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'ID Laporan tidak valid'
    ];
    header('Location: laporan_masuk.php');
    exit;
}

$id_laporan = $_GET['id'];
$laporan_data = getLaporanMasukDetail($conn, $id_laporan);

// Check if laporan exists
if (empty($laporan_data['header'])) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Laporan tidak ditemukan'
    ];
    header('Location: laporan_masuk.php');
    exit;
}

// Handle form submission
if (isset($_POST['update_laporan'])) {
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $items = isset($_POST['id_masuk']) ? $_POST['id_masuk'] : [];
    
    if (empty($items)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Pilih minimal satu data barang masuk'
        ];
    } else {
        // Delete old detail records
        $delete_details = "DELETE FROM laporan_masuk_detail WHERE id_laporan = ?";
        $stmt_delete = mysqli_prepare($conn, $delete_details);
        mysqli_stmt_bind_param($stmt_delete, "i", $id_laporan);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        
        // Update laporan date
        $update_laporan = "UPDATE laporan_masuk SET tanggal_laporan = ? WHERE id_laporan_masuk = ?";
        $stmt_update = mysqli_prepare($conn, $update_laporan);
        mysqli_stmt_bind_param($stmt_update, "si", $tanggal_laporan, $id_laporan);
        $result = mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        // Insert new details
        foreach ($items as $id_masuk) {
            $query_detail = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk) VALUES (?, ?)";
            $stmt_detail = mysqli_prepare($conn, $query_detail);
            mysqli_stmt_bind_param($stmt_detail, "ii", $id_laporan, $id_masuk);
            mysqli_stmt_execute($stmt_detail);
            mysqli_stmt_close($stmt_detail);
        }
        
        logActivity($_SESSION['user_id'], "Mengupdate laporan barang masuk #$id_laporan");
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Laporan barang masuk berhasil diupdate'
        ];
        header('Location: laporan_masuk.php');
        exit;
    }
}

// Get the selected item IDs from the database
$selected_items = [];
$query_selected = "SELECT id_masuk FROM laporan_masuk_detail WHERE id_laporan = ?";
$stmt_selected = mysqli_prepare($conn, $query_selected);
mysqli_stmt_bind_param($stmt_selected, "i", $id_laporan);
mysqli_stmt_execute($stmt_selected);
$result_selected = mysqli_stmt_get_result($stmt_selected);

while ($row = mysqli_fetch_assoc($result_selected)) {
    $selected_items[] = $row['id_masuk'];
}
mysqli_stmt_close($stmt_selected);

// Handle filter date range
$dari_tanggal = isset($_GET['dari']) ? $_GET['dari'] . ' 00:00:00' : date('Y-m-d', strtotime('-7 days')) . ' 00:00:00';
$sampai_tanggal = isset($_GET['sampai']) ? $_GET['sampai'] . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';

// Get barang masuk data in date range
$barang_masuk_list = getBarangMasukInDateRange($conn, $dari_tanggal, $sampai_tanggal);
?>

<div class="container px-6 mx-auto">
    <h2 class="text-2xl font-semibold text-gray-700 mb-4">
        <i class="fas fa-edit mr-2"></i> Edit Laporan Barang Masuk
    </h2>
    
    <div class="flex justify-between items-center mb-4">
        <nav class="text-black" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="index.php" class="text-gray-500 hover:text-blue-600">Dashboard</a>
                    <svg class="fill-current w-3 h-3 mx-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                        <path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                    </svg>
                </li>
                <li class="flex items-center">
                    <a href="laporan_masuk.php" class="text-gray-500 hover:text-blue-600">Laporan Barang Masuk</a>
                    <svg class="fill-current w-3 h-3 mx-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                        <path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                    </svg>
                </li>
                <li>
                    <span class="text-gray-700">Edit Laporan</span>
                </li>
            </ol>
        </nav>
    </div>
    
    <?php if (isset($_SESSION['alert'])): ?>
    <div class="<?= $_SESSION['alert']['type'] == 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700' ?> border-l-4 p-4 mb-4 rounded-md">
        <div class="flex items-center">
            <div class="py-1">
                <i class="<?= $_SESSION['alert']['type'] == 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle' ?> mr-2"></i>
            </div>
            <div>
                <p class="font-medium"><?= $_SESSION['alert']['message'] ?></p>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['alert']); endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-gray-50 py-3 px-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700">
                Form Edit Laporan
            </h3>
        </div>
        
        <div class="p-4">
            <form action="" method="post">
                <div class="mb-4">
                    <label for="tanggal_laporan" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Laporan</label>
                    <input type="date" id="tanggal_laporan" name="tanggal_laporan" value="<?= $laporan_data['header']['tanggal_laporan'] ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-700">Filter Data</label>
                        <div class="flex gap-2">
                            <input type="date" id="dari_tanggal" value="<?= date('Y-m-d', strtotime($dari_tanggal)) ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                            <span class="self-center">s/d</span>
                            <input type="date" id="sampai_tanggal" value="<?= date('Y-m-d', strtotime($sampai_tanggal)) ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                            <button type="button" id="btn-filter" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow-md transition duration-200">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 mb-4">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="check-all" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                    </div>
                                </th>
                                <th class="px-4 py-2">No</th>
                                <th class="px-4 py-2">Kode Barang</th>
                                <th class="px-4 py-2">Nama Barang</th>
                                <th class="px-4 py-2">Supplier</th>
                                <th class="px-4 py-2">Jumlah</th>
                                <th class="px-4 py-2">Tanggal Masuk</th>
                                <th class="px-4 py-2">Penerima</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($barang_masuk_list)): ?>
                            <tr class="bg-white border-b">
                                <td colspan="8" class="px-4 py-2 text-center">Tidak ada data barang masuk dalam rentang tanggal ini</td>
                            </tr>
                            <?php else: ?>
                            
                            <?php 
                            $no = 1;
                            foreach($barang_masuk_list as $barang): 
                                $is_selected = in_array($barang['id_masuk'], $selected_items);
                            ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-4 py-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="id_masuk[]" value="<?= $barang['id_masuk'] ?>" <?= $is_selected ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 item-check">
                                    </div>
                                </td>
                                <td class="px-4 py-2"><?= $no++ ?></td>
                                <td class="px-4 py-2"><?= $barang['kode_barang'] ?></td>
                                <td class="px-4 py-2"><?= $barang['nama_barang'] ?></td>
                                <td class="px-4 py-2"><?= $barang['nama_supplier'] ?></td>
                                <td class="px-4 py-2"><?= $barang['qty_masuk'] ?></td>
                                <td class="px-4 py-2"><?= date('d/m/Y H:i', strtotime($barang['tanggal_masuk'])) ?></td>
                                <td class="px-4 py-2"><?= $barang['nama_pengguna'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="flex justify-end gap-2">
                    <a href="laporan_masuk.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                    <button type="submit" name="update_laporan" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow-md transition duration-200">
                        <i class="fas fa-save mr-2"></i> Update Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check all functionality
    const checkAll = document.getElementById('check-all');
    const itemChecks = document.querySelectorAll('.item-check');
    
    checkAll.addEventListener('change', function() {
        itemChecks.forEach(check => {
            check.checked = checkAll.checked;
        });
    });
    
    // Filter button
    const btnFilter = document.getElementById('btn-filter');
    btnFilter.addEventListener('click', function() {
        const dariTanggal = document.getElementById('dari_tanggal').value;
        const sampaiTanggal = document.getElementById('sampai_tanggal').value;
        
        if (dariTanggal && sampaiTanggal) {
            window.location.href = `edit_laporan_masuk.php?id=<?= $id_laporan ?>&dari=${dariTanggal}&sampai=${sampaiTanggal}`;
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 