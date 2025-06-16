<?php
$pageTitle = "Manajemen Pengguna";
require_once 'includes/header.php';
checkLogin();

// Verifikasi akses - hanya admin & manajer
if ($_SESSION['user_role'] != 'administrator' && $_SESSION['user_role'] != 'manajer') {
    setAlert("error", "Anda tidak memiliki akses ke halaman ini!");
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $nama = sanitize($_POST['nama_pengguna']);
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $email = sanitize($_POST['email']);
        $id_aktor = (int)$_POST['id_aktor'];
        
        // Cek apakah username atau email sudah digunakan
        $query = "SELECT * FROM pengguna WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            setAlert("error", "Username atau email sudah digunakan!");
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $query = "INSERT INTO pengguna (nama_pengguna, username, password, email, id_aktor) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssi", $nama, $username, $hashed_password, $email, $id_aktor);
            
            if (mysqli_stmt_execute($stmt)) {
                logActivity($_SESSION['user_id'], "Menambahkan pengguna baru: $username");
                setAlert("success", "Pengguna berhasil ditambahkan!");
                header("Location: pengguna.php");
                exit();
            } else {
                setAlert("error", "Gagal menambahkan pengguna: " . mysqli_error($conn));
            }
        }
    }
    
    if (isset($_POST['edit_user'])) {
        $id_pengguna = (int)$_POST['id_pengguna'];
        $nama = sanitize($_POST['nama_pengguna']);
        $email = sanitize($_POST['email']);
        $id_aktor = (int)$_POST['id_aktor'];
        $password = $_POST['password'];
        
        if (!empty($password)) {
            // Update dengan password baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE pengguna SET nama_pengguna = ?, email = ?, id_aktor = ?, password = ? WHERE id_pengguna = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssisi", $nama, $email, $id_aktor, $hashed_password, $id_pengguna);
        } else {
            // Update tanpa mengubah password
            $query = "UPDATE pengguna SET nama_pengguna = ?, email = ?, id_aktor = ? WHERE id_pengguna = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssii", $nama, $email, $id_aktor, $id_pengguna);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($_SESSION['user_id'], "Mengubah data pengguna ID: $id_pengguna");
            setAlert("success", "Data pengguna berhasil diperbarui!");
            header("Location: pengguna.php");
            exit();
        } else {
            setAlert("error", "Gagal memperbarui data pengguna: " . mysqli_error($conn));
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $id_pengguna = (int)$_POST['id_pengguna'];
        
        // Tidak bisa menghapus diri sendiri
        if ($id_pengguna == $_SESSION['user_id']) {
            setAlert("error", "Anda tidak dapat menghapus akun yang sedang digunakan!");
            header("Location: pengguna.php");
            exit();
        }
        
        // Cek apakah ada aktivitas terkait pengguna
        $query = "SELECT COUNT(*) as total FROM log_aktivitas WHERE id_pengguna = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_pengguna);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['total'] > 0) {
            setAlert("error", "Tidak dapat menghapus pengguna yang memiliki aktivitas di sistem!");
        } else {
            $query = "DELETE FROM pengguna WHERE id_pengguna = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id_pengguna);
            
            if (mysqli_stmt_execute($stmt)) {
                logActivity($_SESSION['user_id'], "Menghapus pengguna ID: $id_pengguna");
                setAlert("success", "Pengguna berhasil dihapus!");
                header("Location: pengguna.php");
                exit();
            } else {
                setAlert("error", "Gagal menghapus pengguna: " . mysqli_error($conn));
            }
        }
    }
}

// Get all users
$query = "SELECT p.*, a.nama_aktor 
          FROM pengguna p 
          JOIN aktor a ON p.id_aktor = a.id_aktor 
          ORDER BY p.nama_pengguna ASC";
$users = mysqli_query($conn, $query);

// Get all roles
$query = "SELECT * FROM aktor ORDER BY nama_aktor ASC";
$roles = mysqli_query($conn, $query);
?>

<!-- Main Content -->
<div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium text-gray-800 flex items-center">
            <i class="fas fa-users text-blue-500 mr-2"></i> Daftar Pengguna
        </h2>
        
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition-all" 
                onclick="showModal('addUserModal')">
            <i class="fas fa-plus-circle mr-2"></i> Tambah Pengguna
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white data-table">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Nama</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Username</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Email</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Role</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3 text-sm"><?= $user['id_pengguna'] ?></td>
                    <td class="py-2 px-3 text-sm font-medium"><?= $user['nama_pengguna'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $user['username'] ?></td>
                    <td class="py-2 px-3 text-sm text-gray-600"><?= $user['email'] ?></td>
                    <td class="py-2 px-3 text-sm">
                        <span class="px-2 py-0.5 <?= ($user['nama_aktor'] == 'administrator') ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?> rounded-full text-xs">
                            <?= ucfirst($user['nama_aktor']) ?>
                        </span>
                    </td>
                    <td class="py-2 px-3 text-sm">
                        <button class="text-blue-500 hover:text-blue-700 mr-2" 
                                onclick="editUser(<?= $user['id_pengguna'] ?>, '<?= $user['nama_pengguna'] ?>', '<?= $user['email'] ?>', <?= $user['id_aktor'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($user['id_pengguna'] != $_SESSION['user_id']): ?>
                        <button class="text-red-500 hover:text-red-700" 
                                onclick="deleteUser(<?= $user['id_pengguna'] ?>, '<?= $user['nama_pengguna'] ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Tambah Pengguna Baru</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('addUserModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addUserForm" method="POST" action="" class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="nama_pengguna">
                            Nama Lengkap
                        </label>
                        <input type="text" id="nama_pengguna" name="nama_pengguna" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Masukkan nama lengkap">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="username">
                            Username
                        </label>
                        <input type="text" id="username" name="username" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Masukkan username">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                            Email
                        </label>
                        <input type="email" id="email" name="email" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Masukkan email">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                            Password
                        </label>
                        <input type="password" id="password" name="password" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Masukkan password">
                    </div>
                    
                    <div class="mb-4 md:col-span-2">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="id_aktor">
                            Role
                        </label>
                        <select id="id_aktor" name="id_aktor" required 
                                class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Role</option>
                            <?php mysqli_data_seek($roles, 0); ?>
                            <?php while ($role = mysqli_fetch_assoc($roles)): ?>
                            <option value="<?= $role['id_aktor'] ?>"><?= ucfirst($role['nama_aktor']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('addUserModal')">
                        Batal
                    </button>
                    <button type="submit" name="add_user" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900">Edit Pengguna</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('editUserModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editUserForm" method="POST" action="" class="mt-4">
                <input type="hidden" id="edit_id_pengguna" name="id_pengguna">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_nama_pengguna">
                            Nama Lengkap
                        </label>
                        <input type="text" id="edit_nama_pengguna" name="nama_pengguna" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_email">
                            Email
                        </label>
                        <input type="email" id="edit_email" name="email" required 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_password">
                            Password Baru <span class="text-xs text-gray-500">(Kosongkan jika tidak ingin mengubah)</span>
                        </label>
                        <input type="password" id="edit_password" name="password" 
                               class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Kosongkan jika tidak ingin mengubah password">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_id_aktor">
                            Role
                        </label>
                        <select id="edit_id_aktor" name="id_aktor" required 
                                class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php mysqli_data_seek($roles, 0); ?>
                            <?php while ($role = mysqli_fetch_assoc($roles)): ?>
                            <option value="<?= $role['id_aktor'] ?>"><?= ucfirst($role['nama_aktor']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('editUserModal')">
                        Batal
                    </button>
                    <button type="submit" name="edit_user" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div id="deleteUserModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete_user_text"></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_id_pengguna" name="id_pengguna">
                
                <div class="flex justify-center gap-4 mt-4">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('deleteUserModal')">
                        Batal
                    </button>
                    <button type="submit" name="delete_user" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Hapus
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

    function editUser(id, nama, email, id_aktor) {
        document.getElementById('edit_id_pengguna').value = id;
        document.getElementById('edit_nama_pengguna').value = nama;
        document.getElementById('edit_email').value = email;
        
        // Set role
        const roleSelect = document.getElementById('edit_id_aktor');
        for (let i = 0; i < roleSelect.options.length; i++) {
            if (roleSelect.options[i].value == id_aktor) {
                roleSelect.selectedIndex = i;
                break;
            }
        }
        
        // Clear password field
        document.getElementById('edit_password').value = '';
        
        showModal('editUserModal');
    }

    function deleteUser(id, nama) {
        document.getElementById('delete_id_pengguna').value = id;
        document.getElementById('delete_user_text').textContent = `Anda yakin ingin menghapus pengguna "${nama}"?`;
        
        showModal('deleteUserModal');
    }
    
    // Initialize select2 for dropdowns if you want to use it
    $(document).ready(function() {
        $('#id_aktor').select2({
            dropdownParent: $('#addUserModal'),
            placeholder: "Pilih Role",
            width: '100%'
        });
        
        $('#edit_id_aktor').select2({
            dropdownParent: $('#editUserModal'),
            placeholder: "Pilih Role",
            width: '100%'
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?> 