<?php
session_start();
// Set timezone to Western Indonesia Time (WIB)
date_default_timezone_set('Asia/Jakarta');

require_once 'config/database.php';
require_once 'config/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT u.*, r.nama_role FROM users u 
              LEFT JOIN roles r ON u.role_id = r.id_role 
              WHERE u.username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_name'] = $user['nama_lengkap'];
            
            // Set role berdasarkan role_id dari database
            $_SESSION['user_role'] = $user['nama_role'] ? $user['nama_role'] : $user['username'];

            // Mencoba mencatat aktivitas login, tapi tetap lanjut meski gagal
            logActivity($user['id_user'], 'Login ke sistem');
            
            header("Location: index.php");
            exit();
        } else {
            $login_error = "Password salah!";
        }
    } else {
        $login_error = "Username tidak ditemukan!";
    }
}

$query = "SELECT * FROM data_toko LIMIT 1";
$result = mysqli_query($conn, $query);
$store_info = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Bento Kopi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        .header-gradient {
            background: linear-gradient(to right,rgb(72, 161, 105),rgb(27, 150, 72));
        }
        
        /* Responsive login form */
        @media (max-width: 640px) {
            .max-w-md {
                max-width: 90%;
                width: 100%;
            }
            
            input[type="text"],
            input[type="password"] {
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .p-8 {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="bg-primary-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white shadow-lg rounded-xl overflow-hidden login-container">
        <div class="p-8 header-gradient text-white text-center">
            <div class="inline-block mb-2">
                <img src="logo_bentokopi.png" class="w-20 h-20 object-contain rounded-full bg-white p-2 shadow-md">
            </div>
            <h2 class="text-2xl font-bold">Bento Kopi</h2>
            <p class="text-sm">Pamulang</p>
        </div>

        <form action="" method="POST" class="p-8">
            <?php if (isset($login_error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                    <p><?= $login_error ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <button type="submit" name="login"
                class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transform hover:-translate-y-1 transition-all duration-200">
                <i class="fas fa-sign-in-alt mr-2"></i> Masuk
            </button>
        </form>

        <div class="bg-gray-50 py-4 px-8 text-center text-gray-600 text-sm border-t">
            <p>&copy; <?= date('Y') ?> <?= $store_info['nama_toko '] ?? 'Bento Kopi' ?> &mdash; Semua hak dilindungi</p>
        </div>
    </div>
</body>
</html>
