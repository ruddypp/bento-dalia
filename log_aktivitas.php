<?php
$pageTitle = "Log Aktivitas";
require_once 'includes/header.php';
checkLogin();

// Verifikasi akses - hanya admin & manajer
if ($_SESSION['user_role'] != 'administrator' && $_SESSION['user_role'] != 'manajer') {
    setAlert("error", "Anda tidak memiliki akses ke halaman ini!");
    header("Location: index.php");
    exit();
}

// Delete log entries
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_logs'])) {
    $days = (int)$_POST['days'];
    
    // Hitung tanggal
    $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));
    
    // Delete logs older than specified days
    $query = "DELETE FROM log_aktivitas WHERE waktu < ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $date_limit);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        logActivity($_SESSION['user_id'], "Menghapus log aktivitas yang lebih lama dari $days hari ($affected_rows entri)");
        setAlert("success", "Berhasil menghapus $affected_rows entri log!");
    } else {
        setAlert("error", "Gagal menghapus log: " . mysqli_error($conn));
    }
    
    header("Location: log_aktivitas.php");
    exit();
}

// Get all logs with pagination
$limit = 100; // Tampilkan 100 log per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter by user if specified
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';

// Build query with filters
$where_clause = '';
$params = [];
$types = '';

if ($user_filter > 0) {
    $where_clause .= " WHERE l.id_user = ?";
    $params[] = $user_filter;
    $types .= "i";
}

if (!empty($date_filter)) {
    $date_start = $date_filter . ' 00:00:00';
    $date_end = $date_filter . ' 23:59:59';
    
    if (empty($where_clause)) {
        $where_clause .= " WHERE l.waktu BETWEEN ? AND ?";
    } else {
        $where_clause .= " AND l.waktu BETWEEN ? AND ?";
    }
    
    $params[] = $date_start;
    $params[] = $date_end;
    $types .= "ss";
}

// Get total logs count
$count_query = "SELECT COUNT(*) as total FROM log_aktivitas l" . $where_clause;
$stmt = mysqli_prepare($conn, $count_query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_logs = $count_row['total'];
$total_pages = ceil($total_logs / $limit);

// Get logs data
$query = "SELECT l.*, u.nama_lengkap, u.username 
          FROM log_aktivitas l 
          JOIN users u ON l.id_user = u.id_user" . $where_clause . "
          ORDER BY l.waktu DESC 
          LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    $params[] = $offset;
    $params[] = $limit;
    $types .= "ii";
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $offset, $limit);
}

mysqli_stmt_execute($stmt);
$logs = mysqli_stmt_get_result($stmt);

// Get all users for filter
$users_query = "SELECT id_user, nama_lengkap, username FROM users ORDER BY nama_lengkap ASC";
$users = mysqli_query($conn, $users_query);
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-history text-blue-500 mr-2"></i> Log Aktivitas
        </h2>
        
        <button type="button" class="bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                onclick="showModal('clearLogModal')">
            <i class="fas fa-trash-alt mr-2"></i> Bersihkan Log
        </button>
    </div>
    
    <!-- Filter Section -->
    <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="user_id">
                    Filter Pengguna
                </label>
                <select id="user_id" name="user_id" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="0">Semua Pengguna</option>
                    <?php mysqli_data_seek($users, 0); ?>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <option value="<?= $user['id_user'] ?>" <?= ($user_filter == $user['id_user']) ? 'selected' : '' ?>>
                        <?= $user['nama_lengkap'] ?> (<?= $user['username'] ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="date">
                    Filter Tanggal
                </label>
                <input type="date" id="date" name="date" value="<?= $date_filter ?>" 
                       class="shadow-sm border border-gray-300 rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                
                <a href="log_aktivitas.php" class="bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">
                    <i class="fas fa-times mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Log Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Waktu</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Pengguna</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aktivitas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (mysqli_num_rows($logs) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 text-sm"><?= $log['id_log'] ?></td>
                        <td class="py-2 px-3 text-sm text-gray-600"><?= date('d/m/Y H:i:s', strtotime($log['waktu'])) ?></td>
                        <td class="py-2 px-3 text-sm font-medium"><?= $log['nama_lengkap'] ?> <span class="text-xs text-gray-500">(<?= $log['username'] ?>)</span></td>
                        <td class="py-2 px-3 text-sm"><?= $log['aktivitas'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="py-4 px-3 text-center text-sm text-gray-500">Tidak ada data log yang ditemukan</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-4 flex justify-center">
        <nav>
            <ul class="flex space-x-1">
                <?php if ($page > 1): ?>
                <li>
                    <a href="?page=<?= $page-1 ?><?= $user_filter ? '&user_id='.$user_filter : '' ?><?= $date_filter ? '&date='.$date_filter : '' ?>" 
                       class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">
                        &laquo;
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <li>
                    <a href="?page=<?= $i ?><?= $user_filter ? '&user_id='.$user_filter : '' ?><?= $date_filter ? '&date='.$date_filter : '' ?>" 
                       class="px-3 py-1 text-sm <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> rounded-md">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li>
                    <a href="?page=<?= $page+1 ?><?= $user_filter ? '&user_id='.$user_filter : '' ?><?= $date_filter ? '&date='.$date_filter : '' ?>" 
                       class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">
                        &raquo;
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    
    <div class="mt-4 text-xs text-gray-500 text-center">
        Menampilkan <?= mysqli_num_rows($logs) ?> dari <?= $total_logs ?> entri log
    </div>
</div>

<!-- Clear Log Modal -->
<div id="clearLogModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Bersihkan Log Aktivitas</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('clearLogModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="" class="mt-4">
                <div>
                    <p class="text-sm text-gray-600 mb-4">
                        Hapus log aktivitas yang lebih lama dari jangka waktu tertentu.
                    </p>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="days">
                            Hapus Log Lebih Lama Dari:
                        </label>
                        <select id="days" name="days" required
                              class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="30">30 Hari</option>
                            <option value="60">60 Hari</option>
                            <option value="90">90 Hari</option>
                            <option value="180">6 Bulan</option>
                            <option value="365">1 Tahun</option>
                        </select>
                    </div>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan. Semua log aktivitas yang lebih lama dari periode yang dipilih akan dihapus secara permanen.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('clearLogModal')">
                        Batal
                    </button>
                    <button type="submit" name="clear_logs" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Hapus Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(modalId).classList.add('modal-entering');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    // Initialize select2 if needed
    $(document).ready(function() {
        $('#user_id').select2({
            placeholder: "Pilih Pengguna",
            width: '100%'
        });
        
        $('#days').select2({
            dropdownParent: $('#clearLogModal'),
            minimumResultsForSearch: -1, // hide search box
            width: '100%'
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?> 