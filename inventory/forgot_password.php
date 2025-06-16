<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Jika user sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$success = false;

// Cek jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    
    // Cek apakah username dan email cocok
    $query = "SELECT * FROM pengguna WHERE username = ? AND email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Generate password baru secara acak
        $new_password = substr(md5(rand()), 0, 8);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $query = "UPDATE pengguna SET password = ? WHERE id_pengguna = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user['id_pengguna']);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            $message = "Password Anda telah direset. Password baru Anda adalah: <strong>$new_password</strong><br>
                       Silakan gunakan password ini untuk login dan segera ubah password Anda.";
            
            // Log aktivitas
            logActivity($user['id_pengguna'], 'Password direset melalui forgot password');
        } else {
            $message = "Gagal mereset password. Silakan coba lagi nanti.";
        }
    } else {
        $message = "Username dan email tidak cocok. Silakan coba lagi.";
    }
}

// Get store info for login page
$query = "SELECT * FROM data_toko LIMIT 1";
$result = mysqli_query($conn, $query);
$store_info = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sistem Inventori</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    },
                    animation: {
                        'fade-in-down': 'fadeInDown 0.5s ease-out',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
   
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .reset-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out;
        }
        
        .reset-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .header-gradient {
            background: linear-gradient(to right, #0369a1, #2563eb);
        }
        
        .input-group {
            transition: all 0.3s ease;
        }
        
        .input-group:focus-within {
            transform: translateY(-2px);
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background-color: rgba(209, 250, 229, 0.8);
            border-left: 4px solid #10b981;
            color: #065f46;
        }
        
        .alert-error {
            background-color: rgba(254, 226, 226, 0.8);
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }
    </style>
</head>
<body class="flex items-center justify-center p-5">
    <div class="reset-card w-full max-w-md bg-white">
        <div class="header-gradient p-6 text-center">
            <div class="inline-block p-3 bg-white/20 rounded-xl mb-2">
                <i class="fas fa-key text-white text-3xl"></i>
            </div>
            <h1 class="text-white text-2xl font-bold mt-2"><?= $store_info['nama_toko'] ?? 'Sistem Inventori' ?></h1>
            <p class="text-blue-100 opacity-80 mt-1">Reset Password</p>
        </div>
        
        <div class="p-8">
            <h2 class="text-xl font-semibold text-center text-gray-800 mb-6">Lupa Password?</h2>
            
            <?php if (!empty($message)): ?>
            <div class="<?= $success ? 'alert-success' : 'alert-error' ?> p-4 mb-6 rounded-md animate-pulse-slow">
                <div class="flex items-center">
                    <i class="<?= $success ? 'fas fa-check-circle' : 'fas fa-exclamation-circle' ?> mr-3 text-2xl"></i>
                    <div>
                        <?= $message ?>
                        <?php if ($success): ?>
                        <div class="mt-2">
                            <a href="login.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <i class="fas fa-sign-in-alt mr-2"></i> Login Sekarang
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <p class="text-gray-600 mb-6 text-center">
                Masukkan username dan email yang terdaftar pada akun Anda. 
                Kami akan mengirimkan password baru untuk Anda.
            </p>
            
            <form method="POST" action="" class="space-y-6">
                <div class="input-group">
                    <label for="username" class="block text-gray-700 mb-2 font-medium">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" 
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                            required placeholder="Masukkan username" autocomplete="username">
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="email" class="block text-gray-700 mb-2 font-medium">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" 
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                            required placeholder="Masukkan email" autocomplete="email">
                    </div>
                </div>
                
                <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:-translate-y-1 transition-all duration-200">
                    <i class="fas fa-key mr-2"></i> Reset Password
                </button>
            </form>
            <?php endif; ?>
            
            <div class="mt-6 flex items-center justify-center space-x-3">
                <a href="login.php" class="flex items-center text-blue-600 hover:text-blue-800 hover:underline transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Halaman Login
                </a>
                <span class="text-gray-300">|</span>
                <a href="reset_admin.php" class="flex items-center text-blue-600 hover:text-blue-800 hover:underline transition-all">
                    <i class="fas fa-user-shield mr-2"></i> Reset Admin
                </a>
            </div>
        </div>
        
        <div class="bg-gray-50 py-4 px-8 text-center text-gray-600 text-sm border-t">
            <p>&copy; <?= date('Y') ?> <?= $store_info['nama_toko'] ?? 'Sistem Inventori' ?> &mdash; Semua hak dilindungi</p>
        </div>
    </div>
    
    <script>
        // Animasi form saat loading
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateY(20px)';
                input.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    input.style.opacity = '1';
                    input.style.transform = 'translateY(0)';
                }, 300 + (index * 100));
            });
            
            // Efek highlight pada input saat focus
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-blue-100', 'ring-opacity-50');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-blue-100', 'ring-opacity-50');
                });
            });
        });
    </script>
</body>
</html> 