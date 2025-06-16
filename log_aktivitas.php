<?php
$pageTitle = "Log Aktivitas";
require_once 'includes/header.php';
checkLogin();

// Verifikasi akses - hanya admin
if ($_SESSION['user_role'] != 'admin') {
    setAlert("error", "Anda tidak memiliki akses ke halaman ini!");
    header("Location: index.php");
    exit();
}

// Check for session messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Delete log entries
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Delete all logs
    if (isset($_POST['delete_all_logs'])) {
        try {
            $query = "TRUNCATE TABLE log_aktivitas";
            if (mysqli_query($conn, $query)) {
                // Log this activity (will be the only entry after truncate)
                logActivity($_SESSION['user_id'], "Menghapus seluruh log aktivitas");
                
                $_SESSION['success_message'] = "Seluruh log aktivitas berhasil dihapus";
            } else {
                $_SESSION['error_message'] = "Gagal menghapus seluruh log: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
        
        header("Location: log_aktivitas.php");
        exit();
    }
    // Delete logs older than specified days
    else if (isset($_POST['clear_logs'])) {
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
            $_SESSION['success_message'] = "Berhasil menghapus $affected_rows entri log!";
    } else {
            $_SESSION['error_message'] = "Gagal menghapus log: " . mysqli_error($conn);
    }
    
    header("Location: log_aktivitas.php");
    exit();
    }
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

// Check if users query was successful
if (!$users) {
    $error_message = "Error fetching users: " . mysqli_error($conn);
    $users = null; // Set to null so we can check later
}
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
    <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 flex justify-between items-center">
        <div>
            <i class="fas fa-check-circle mr-2"></i> <?= $success_message ?>
        </div>
        <button class="text-green-700 hover:text-green-900" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 flex justify-between items-center">
        <div>
            <i class="fas fa-exclamation-circle mr-2"></i> <?= $error_message ?>
        </div>
        <button class="text-red-700 hover:text-red-900" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>
    
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-history text-blue-500 mr-2"></i> Log Aktivitas
        </h2>
        
        <div class="flex gap-2">
            <button type="button" class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                onclick="showModal('clearLogModal')">
                <i class="fas fa-calendar-times mr-2"></i> Hapus Log Lama
            </button>
            
            <button type="button" class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-md transition-all" 
                    onclick="showModal('deleteAllModal')">
                <i class="fas fa-trash-alt mr-2"></i> Hapus Semua Log
        </button>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                        <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="user_id">
                    Filter Pengguna
                </label>
                <select id="user_id" name="user_id" class="shadow-sm border border-gray-300 rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full">
                    <option value="0">Semua Pengguna</option>
                    <?php 
                    // Reset pointer to first row and ensure it's valid
                    if ($users && mysqli_num_rows($users) > 0) {
                        mysqli_data_seek($users, 0);
                        
                        while ($user = mysqli_fetch_assoc($users)): ?>
                        <option value="<?= $user['id_user'] ?>" <?= ($user_filter == $user['id_user']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['nama_lengkap']) ?> (<?= htmlspecialchars($user['username']) ?>)
                        </option>
                        <?php endwhile;
                    } else { ?>
                        <option disabled>Tidak ada data pengguna</option>
                    <?php } ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="date">
                    Filter Tanggal
                </label>
                <input type="date" id="date" name="date" value="<?= $date_filter ?>" 
                       class="shadow-sm border border-gray-300 rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full">
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
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">ID</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">Waktu</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">Pengguna</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b">Aktivitas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (mysqli_num_rows($logs) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-sm"><?= $log['id_log'] ?></td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <span class="font-medium"><?= date('d/m/Y', strtotime($log['waktu'])) ?></span>
                            <span class="text-gray-500"><?= date('H:i:s', strtotime($log['waktu'])) ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($log['nama_lengkap']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($log['username']) ?></div>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-800"><?= htmlspecialchars($log['aktivitas']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="py-6 px-4 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-info-circle text-gray-400 text-2xl mb-1"></i>
                                <p>Tidak ada data log yang ditemukan</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-6 border-t border-gray-200 pt-4 flex flex-col sm:flex-row justify-between items-center">
        <div class="mb-4 sm:mb-0 text-sm text-gray-600">
            Menampilkan <?= mysqli_num_rows($logs) ?> dari <?= $total_logs ?> entri log
        </div>
        
    <?php if ($total_pages > 1): ?>
        <nav class="relative z-0 inline-flex shadow-sm -space-x-px" aria-label="Pagination">
            <!-- Previous Page -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?><?= $user_filter ? '&user_id='.$user_filter : '' ?><?= $date_filter ? '&date='.$date_filter : '' ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </a>
            <?php else: ?>
            <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </span>
                <?php endif; ?>
                
            <!-- Page Numbers -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
            // Always show first page
            if ($start_page > 1): 
            ?>
            <a href="?page=1<?= $user_filter ? '&user_id='.$user_filter : '' ?><?= $date_filter ? '&date='.$date_filter : '' ?>" 
               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                1
            </a>
            <?php 
            // Add ellipsis if needed
            if ($start_page > 2): ?>
            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                ...
            </span>
            <?php endif; ?>
            <?php endif; ?>
            
            <!-- Page range -->
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?= $i ?><?= $user_filter ? '&user_id='.$user_filter : '' ?><?= $date_filter ? '&date='.$date_filter : '' ?>" 
               class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?= $i == $page ? 'bg-blue-50 text-blue-600 z-10 border-blue-500' : 'bg-white text-gray-700 hover:bg-gray-50' ?> text-sm font-medium">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
            <!-- Show last page if needed -->
            <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                ...
            </span>
            <?php endif; ?>
            <a href="?page=<?= $total_pages ?><?= $user_filter ? '&user_id='.$user_filter : '' ?><?= $date_filter ? '&date='.$date_filter : '' ?>" 
               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                <?= $total_pages ?>
            </a>
            <?php endif; ?>
            
            <!-- Next Page -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?><?= $user_filter ? '&user_id='.$user_filter : '' ?><?= $date_filter ? '&date='.$date_filter : '' ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </a>
            <?php else: ?>
            <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </span>
                <?php endif; ?>
        </nav>
    <?php endif; ?>
    </div>
</div>

<!-- Clear Log Modal -->
<div id="clearLogModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Hapus Log Aktivitas Lama</h3>
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
                            <option value="7">7 Hari</option>
                            <option value="30" selected>30 Hari</option>
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
                                    <strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan. Log aktivitas yang lebih lama dari periode yang dipilih akan dihapus secara permanen.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('clearLogModal')">
                        Batal
                    </button>
                    <button type="submit" name="clear_logs" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        <i class="fas fa-calendar-times mr-1"></i> Hapus Log Lama
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete All Logs Modal -->
<div id="deleteAllModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Hapus Semua Log Aktivitas</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('deleteAllModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="" class="mt-4">
                <div>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    <strong>PERINGATAN:</strong> Tindakan ini akan menghapus <strong>SELURUH</strong> log aktivitas dari sistem dan tidak dapat dibatalkan.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gray-50 rounded">
                        <p class="text-sm text-gray-700 mb-2">
                            <i class="fas fa-info-circle mr-1"></i> Sebelum menghapus seluruh data:
                        </p>
                        <ul class="list-disc list-inside text-sm text-gray-700 ml-2 space-y-1">
                            <li>Pastikan Anda benar-benar membutuhkan penghapusan ini</li>
                            <li>Pertimbangkan untuk mengekspor/backup data terlebih dahulu</li>
                            <li>Penghapusan ini akan menyisakan satu log aktivitas baru yang mencatat penghapusan ini</li>
                        </ul>
                    </div>
                    
                    <!-- Confirmation checkbox -->
                    <div class="mt-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="confirmDelete" class="form-checkbox h-4 w-4 text-red-600 transition duration-150 ease-in-out" required>
                            <span class="ml-2 text-sm text-gray-700">Saya memahami bahwa tindakan ini tidak dapat dibatalkan</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('deleteAllModal')">
                        Batal
                    </button>
                    <button type="submit" name="delete_all_logs" id="deleteAllBtn" class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" disabled>
                        <i class="fas fa-trash-alt mr-1"></i> Hapus Semua Log
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
    
    // Initialize page functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle delete all button based on confirmation checkbox
        document.getElementById('confirmDelete').addEventListener('change', function() {
            document.getElementById('deleteAllBtn').disabled = !this.checked;
        });
        
        // Make the alerts dismissible
        const alertCloseButtons = document.querySelectorAll('.alert-close');
        alertCloseButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?> 