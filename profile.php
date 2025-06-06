<?php
$pageTitle = "Profil Pengguna";
require_once 'includes/header.php';
checkLogin();

// Ambil data pengguna
$user_id = $_SESSION['user_id'];
$user = getUsers($user_id);

// Handle form update profil
if (isset($_POST['update_profile'])) {
    $nama = sanitize($_POST['nama_pengguna']);
    $alamat = sanitize($_POST['alamat_user']);
    
    $query = "UPDATE users SET nama_lengkap = ?, alamat_user = ? WHERE id_user = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $nama, $alamat, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['user_name'] = $nama;
        logActivity($user_id, "Mengubah profil pengguna");
        setAlert("success", "Profil berhasil diperbarui!");
        
        // Refresh data
        $user = getUsers($user_id);
    } else {
        setAlert("error", "Gagal memperbarui profil: " . mysqli_error($conn));
    }
}

// Handle form ubah password
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Cek password saat ini
    $query = "SELECT password FROM users WHERE id_user = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($current_password === $row['password']) {
        // Cek konfirmasi password
        if ($new_password === $confirm_password) {
            // Update password
            $query = "UPDATE users SET password = ? WHERE id_user = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $new_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                logActivity($user_id, "Mengubah password");
                setAlert("success", "Password berhasil diperbarui!");
            } else {
                setAlert("error", "Gagal memperbarui password: " . mysqli_error($conn));
            }
        } else {
            setAlert("error", "Konfirmasi password baru tidak sesuai!");
        }
    } else {
        setAlert("error", "Password saat ini salah!");
    }
}
?>

<div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
    <div class="md:flex">
        <div class="md:w-1/3 bg-gradient-to-br from-blue-600 to-blue-800 p-8 text-white">
            <div class="flex flex-col items-center justify-center h-full">
                <div class="w-32 h-32 rounded-full bg-white/20 flex items-center justify-center border-4 border-white/30 mb-6">
                    <span class="text-5xl font-bold"><?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?></span>
                </div>
                <h2 class="text-2xl font-bold text-center mb-2"><?= $user['nama_lengkap'] ?></h2>
                
                <div class="w-full mt-8">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-user w-6 text-blue-300"></i>
                        <span class="ml-2"><?= $user['username'] ?></span>
                    </div>
                    <?php if (isset($user['alamat_user']) && !empty($user['alamat_user'])): ?>
                    <div class="flex items-center mb-4">
                        <i class="fas fa-map-marker-alt w-6 text-blue-300"></i>
                        <span class="ml-2"><?= $user['alamat_user'] ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt w-6 text-blue-300"></i>
                        <span class="ml-2">ID: <?= $user['id_user'] ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="md:w-2/3 p-6">
            <ul class="flex border-b border-gray-200">
                <li class="-mb-px mr-1">
                    <a href="#profile" class="inline-block py-2 px-4 text-blue-600 hover:text-blue-800 font-medium border-b-2 border-blue-600 active-tab">Informasi Profil</a>
                </li>
                <li class="mr-1">
                    <a href="#password" class="inline-block py-2 px-4 text-gray-600 hover:text-blue-800 font-medium">Ubah Password</a>
                </li>
                <li class="mr-1">
                    <a href="#activity" class="inline-block py-2 px-4 text-gray-600 hover:text-blue-800 font-medium">Aktivitas</a>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content mt-6">
                <!-- Profile Tab -->
                <div id="profile-tab" class="tab-pane active">
                    <form method="POST" action="" class="space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label for="nama_pengguna" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input type="text" id="nama_pengguna" name="nama_pengguna" 
                                        class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                        value="<?= $user['nama_lengkap'] ?>" required>
                                </div>
                            </div>
                            
                            <div>
                                <label for="alamat_user" class="block text-gray-700 text-sm font-medium mb-2">Alamat</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-map-marker-alt text-gray-400"></i>
                                    </div>
                                    <input type="text" id="alamat_user" name="alamat_user" 
                                        class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                        value="<?= $user['alamat_user'] ?>">
                                </div>
                            </div>
                            
                            <div>
                                <label for="username" class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-id-card text-gray-400"></i>
                                    </div>
                                    <input type="text" id="username" 
                                        class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg w-full bg-gray-100 text-gray-600 cursor-not-allowed" 
                                        value="<?= $user['username'] ?>" disabled readonly>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Username tidak dapat diubah</p>
                            </div>
                            
                            <div>
                                <label for="jenis_kelamin" class="block text-gray-700 text-sm font-medium mb-2">Jenis Kelamin</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-venus-mars text-gray-400"></i>
                                    </div>
                                    <input type="text" id="jenis_kelamin" 
                                        class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg w-full bg-gray-100 text-gray-600 cursor-not-allowed" 
                                        value="<?= $user['jenis_kelamin'] ?: 'Tidak diketahui' ?>" disabled readonly>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" 
                            class="bg-gradient-to-r from-blue-600 to-blue-700 text-white py-2 px-6 rounded-lg font-medium hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:-translate-y-1 transition-all duration-200 inline-flex items-center">
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
                
                <!-- Password Tab -->
                <div id="password-tab" class="tab-pane hidden">
                    <form method="POST" action="" id="passwordForm" class="space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-gray-700 text-sm font-medium mb-2">Password Saat Ini</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="current_password" name="current_password" 
                                        class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                        required>
                                </div>
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-gray-700 text-sm font-medium mb-2">Password Baru</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-key text-gray-400"></i>
                                    </div>
                                    <input type="password" id="new_password" name="new_password" 
                                        class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                        minlength="6" required>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <button type="button" class="toggle-password text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="text-sm text-gray-600 mb-1">Kekuatan Password:</div>
                                    <div class="h-2 bg-gray-200 rounded-full">
                                        <div class="password-strength h-2 rounded-full bg-gray-500 w-0"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password Baru</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-check-circle text-gray-400"></i>
                                    </div>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                        class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                        minlength="6" required>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <button type="button" class="toggle-password text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <p id="password-match" class="text-sm text-gray-500 mt-1 hidden">Password sesuai</p>
                                <p id="password-not-match" class="text-sm text-red-500 mt-1 hidden">Password tidak sesuai</p>
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Keamanan Password</h3>
                                    <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                                        <li>Gunakan minimal 8 karakter</li>
                                        <li>Kombinasikan huruf besar, huruf kecil, angka, dan karakter khusus</li>
                                        <li>Hindari penggunaan password yang sama dengan akun lain</li>
                                        <li>Jangan bagikan password Anda kepada siapapun</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" 
                            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white py-2 px-6 rounded-lg font-medium hover:from-yellow-600 hover:to-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transform hover:-translate-y-1 transition-all duration-200 inline-flex items-center">
                            <i class="fas fa-key mr-2"></i> Ubah Password
                        </button>
                    </form>
                </div>
                
                <!-- Activity Tab -->
                <div id="activity-tab" class="tab-pane hidden">
                    <?php
                    // Ambil riwayat aktivitas terbaru
                    $query = "SELECT * FROM log_aktivitas WHERE id_user = ? ORDER BY waktu DESC LIMIT 10";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    ?>
                    
                    <div class="space-y-4">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($log = mysqli_fetch_assoc($result)): ?>
                            <div class="bg-white border-l-4 border-blue-500 p-4 rounded shadow-sm hover:shadow-md transition-all duration-200">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        <span class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-history text-blue-600"></i>
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-gray-800"><?= $log['aktivitas'] ?></p>
                                        <p class="text-sm text-gray-500">
                                            <i class="far fa-clock mr-1"></i> <?= date('d/m/Y H:i:s', strtotime($log['waktu'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <span class="block mx-auto mb-4 w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                    <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                                </span>
                                <p class="text-gray-500">Belum ada aktivitas yang tercatat</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabs = document.querySelectorAll('ul.flex a');
        const tabContents = document.querySelectorAll('.tab-pane');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active classes
                tabs.forEach(t => {
                    t.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                    t.classList.add('text-gray-600');
                });
                
                // Add active class to clicked tab
                this.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                this.classList.remove('text-gray-600');
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show content based on href
                const target = this.getAttribute('href').substring(1);
                document.getElementById(target + '-tab').classList.remove('hidden');
            });
        });
        
        // Toggle password visibility
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.previousElementSibling.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Password strength indicator
        const newPassword = document.getElementById('new_password');
        const strengthBar = document.querySelector('.password-strength');
        
        if (newPassword && strengthBar) {
            newPassword.addEventListener('input', function() {
                const value = this.value;
                let strength = 0;
                
                if (value.length >= 8) strength += 20;
                if (value.match(/[A-Z]/)) strength += 20;
                if (value.match(/[a-z]/)) strength += 20;
                if (value.match(/[0-9]/)) strength += 20;
                if (value.match(/[^A-Za-z0-9]/)) strength += 20;
                
                strengthBar.style.width = strength + '%';
                
                if (strength <= 20) {
                    strengthBar.className = 'password-strength h-2 rounded-full bg-red-500 transition-all duration-300';
                } else if (strength <= 40) {
                    strengthBar.className = 'password-strength h-2 rounded-full bg-orange-500 transition-all duration-300';
                } else if (strength <= 60) {
                    strengthBar.className = 'password-strength h-2 rounded-full bg-yellow-500 transition-all duration-300';
                } else if (strength <= 80) {
                    strengthBar.className = 'password-strength h-2 rounded-full bg-lime-500 transition-all duration-300';
                } else {
                    strengthBar.className = 'password-strength h-2 rounded-full bg-green-500 transition-all duration-300';
                }
            });
        }
        
        // Password match indicator
        const confirmPassword = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('password-match');
        const passwordNotMatch = document.getElementById('password-not-match');
        
        if (newPassword && confirmPassword && passwordMatch && passwordNotMatch) {
            confirmPassword.addEventListener('input', checkPasswordMatch);
            newPassword.addEventListener('input', checkPasswordMatch);
            
            function checkPasswordMatch() {
                const newValue = newPassword.value;
                const confirmValue = confirmPassword.value;
                
                if (confirmValue === '') {
                    passwordMatch.classList.add('hidden');
                    passwordNotMatch.classList.add('hidden');
                } else if (newValue === confirmValue) {
                    passwordMatch.classList.remove('hidden');
                    passwordNotMatch.classList.add('hidden');
                } else {
                    passwordMatch.classList.add('hidden');
                    passwordNotMatch.classList.remove('hidden');
                }
            }
        }
        
        // Form validation
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const newValue = newPassword.value;
                const confirmValue = confirmPassword.value;
                
                if (newValue !== confirmValue) {
                    e.preventDefault();
                    alert('Konfirmasi password baru tidak sesuai!');
                    return false;
                }
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?> 