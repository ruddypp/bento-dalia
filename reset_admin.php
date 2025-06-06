<?php
require_once 'config/database.php';

// Cek apakah form sudah di-submit
if (isset($_POST['reset'])) {
    // Password default: admin123
    $password = 'admin123';
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user admin password
    $query = "UPDATE pengguna SET password = ? WHERE username = 'admin'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Password admin berhasil direset ke 'admin123'";
    } else {
        $error = "Gagal mereset password: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

// Cek apakah admin sudah ada, jika belum maka buat akun admin
$query = "SELECT * FROM pengguna WHERE username = 'admin'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // Admin belum ada, cek dulu apakah tabel aktor sudah ada data
    $query = "SELECT * FROM aktor WHERE nama_aktor = 'administrator'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        // Insert role administrator jika belum ada
        $query = "INSERT INTO aktor (nama_aktor) VALUES ('administrator')";
        mysqli_query($conn, $query);
        $id_aktor = mysqli_insert_id($conn);
    } else {
        $row = mysqli_fetch_assoc($result);
        $id_aktor = $row['id_aktor'];
    }
    
    // Buat akun admin baru
    $nama_pengguna = 'Administrator';
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = 'admin@example.com';
    
    $query = "INSERT INTO pengguna (nama_pengguna, username, password, email, id_aktor) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssi", $nama_pengguna, $username, $password, $email, $id_aktor);
    
    if (mysqli_stmt_execute($stmt)) {
        $info = "Akun admin baru berhasil dibuat dengan username 'admin' dan password 'admin123'";
    } else {
        $error = "Gagal membuat akun admin: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

// Cek apakah ada data toko
$query = "SELECT * FROM data_toko";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // Buat data toko default
    $query = "INSERT INTO data_toko (nama_toko, alamat, kontak) VALUES ('Toko Inventori', 'Jl. Contoh No. 123, Jakarta', '021-1234567')";
    
    if (mysqli_query($conn, $query)) {
        $info_toko = "Data toko default berhasil dibuat";
    } else {
        $error_toko = "Gagal membuat data toko: " . mysqli_error($conn);
    }
}

// Get store info for page
$query = "SELECT * FROM data_toko LIMIT 1";
$result = mysqli_query($conn, $query);
$store_info = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin - Sistem Inventori</title>
    
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
                        'bounce-slow': 'bounce 3s infinite',
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .admin-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .header-gradient {
            background: linear-gradient(to right, #7e22ce, #4f46e5);
        }
        
        .alert-box {
            border-radius: 12px;
            margin-bottom: 16px;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            animation: fadeIn 0.5s ease-out;
        }
        
        .alert-success {
            background-color: rgba(209, 250, 229, 0.8);
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background-color: rgba(254, 226, 226, 0.8);
            border-left: 4px solid #ef4444;
        }
        
        .alert-info {
            background-color: rgba(219, 234, 254, 0.8);
            border-left: 4px solid #3b82f6;
        }
        
        .reset-btn {
            transition: all 0.3s ease;
            background: linear-gradient(to right, #6366f1, #8b5cf6);
        }
        
        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            background: linear-gradient(to right, #4f46e5, #7c3aed);
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
        
        .icon-box {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
    </style>
</head>
<body class="flex items-center justify-center p-5">
    <div class="admin-card w-full max-w-md bg-white">
        <div class="header-gradient p-6 text-center">
            <div class="icon-box mb-2 animate-bounce-slow">
                <i class="fas fa-user-shield text-white text-4xl"></i>
            </div>
            <h1 class="text-white text-2xl font-bold mt-2"><?= $store_info['nama_toko'] ?? 'Sistem Inventori' ?></h1>
            <p class="text-indigo-100 opacity-80 mt-1">Reset Akun Administrator</p>
        </div>
        
        <div class="p-8">
            <h2 class="text-xl font-semibold text-center text-gray-800 mb-6">Atur Ulang Password Admin</h2>
            
            <div class="space-y-4 mb-6">
                <?php if (isset($success)): ?>
                <div class="alert-box alert-success">
                    <i class="fas fa-check-circle text-green-600 mr-3 text-xl mt-0.5"></i>
                    <div>
                        <p class="font-medium text-green-800"><?= $success ?></p>
                        <p class="text-sm text-green-600 mt-1">Silakan login menggunakan password baru.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert-box alert-error">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3 text-xl mt-0.5"></i>
                    <div>
                        <p class="font-medium text-red-800">Terjadi Kesalahan</p>
                        <p class="text-sm text-red-600 mt-1"><?= $error ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($info)): ?>
                <div class="alert-box alert-info">
                    <i class="fas fa-info-circle text-blue-600 mr-3 text-xl mt-0.5"></i>
                    <div>
                        <p class="font-medium text-blue-800">Informasi</p>
                        <p class="text-sm text-blue-600 mt-1"><?= $info ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($info_toko)): ?>
                <div class="alert-box alert-info">
                    <i class="fas fa-store text-blue-600 mr-3 text-xl mt-0.5"></i>
                    <div>
                        <p class="font-medium text-blue-800">Data Toko</p>
                        <p class="text-sm text-blue-600 mt-1"><?= $info_toko ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_toko)): ?>
                <div class="alert-box alert-error">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3 text-xl mt-0.5"></i>
                    <div>
                        <p class="font-medium text-red-800">Terjadi Kesalahan</p>
                        <p class="text-sm text-red-600 mt-1"><?= $error_toko ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-lg mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-indigo-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-indigo-700">
                            Fitur ini akan mereset password admin ke nilai default <strong class="font-semibold">'admin123'</strong>.<br>
                            Gunakan dengan hati-hati dan segera ubah password setelah berhasil login!
                        </p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="">
                <button type="submit" name="reset" class="reset-btn w-full text-white py-3 px-6 rounded-lg font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-center">
                    <i class="fas fa-key mr-2"></i> Reset Password Admin
                </button>
            </form>
            
            <div class="mt-6 flex items-center justify-center space-x-3">
                <a href="login.php" class="flex items-center text-blue-600 hover:text-blue-800 hover:underline transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Halaman Login
                </a>
                <span class="text-gray-300">|</span>
                <a href="forgot_password.php" class="flex items-center text-blue-600 hover:text-blue-800 hover:underline transition-all">
                    <i class="fas fa-unlock-alt mr-2"></i> Lupa Password
                </a>
            </div>
        </div>
        
        <div class="bg-gray-50 py-4 px-8 text-center text-gray-600 text-sm border-t">
            <p>&copy; <?= date('Y') ?> <?= $store_info['nama_toko'] ?? 'Sistem Inventori' ?> &mdash; Semua hak dilindungi</p>
        </div>
    </div>
</body>
</html> 