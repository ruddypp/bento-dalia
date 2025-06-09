<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Cek login kecuali untuk halaman login
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'forgot_password.php' && basename($_SERVER['PHP_SELF']) != 'reset_admin.php') {
    header("Location: login.php");
    exit();
}

// Get store info
$store_info = getStoreInfo();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Inventori - <?= isset($pageTitle) ? $pageTitle : 'Dashboard' ?></title>
    <link rel="icon" type="image/x-icon" href="/logo_bentokopi.png">
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
                    boxShadow: {
                        'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                        'card-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables with Responsive extension -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Critical hamburger menu fix -->
    <style>
        @media (max-width: 1023px) {
            #sidebar-toggle {
                display: flex !important;
                opacity: 1 !important;
                visibility: visible !important;
                z-index: 9999 !important;
                position: fixed !important;
                top: 16px !important;
                left: 16px !important;
            }
        }
        
        @media (min-width: 1024px) {
            #sidebar-toggle {
                display: none !important;
            }
        }
    </style>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg,rgb(68, 131, 52),rgb(15, 83, 41));
            box-shadow: 0 4px 12px rgba(30, 146, 36, 0.15);
            transition: all 0.3s ease;
            height: 100vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
        }
        
        .sidebar-item {
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
        }
        
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
        }
        
        .card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        .btn {
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform-origin: top right;
            transition: all 0.2s ease;
        }
        
        .dropdown-item {
            border-radius: 6px;
            margin: 0.25rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Inline script for hamburger menu visibility -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Force hamburger menu to be visible on mobile
            const hamburgerBtn = document.getElementById('sidebar-toggle');
            if (hamburgerBtn) {
                hamburgerBtn.style.display = 'flex';
                hamburgerBtn.style.visibility = 'visible';
                hamburgerBtn.style.opacity = '1';
                hamburgerBtn.style.zIndex = '9999';
            }
            
            // Initial visibility check
            if (window.innerWidth >= 1024) {
                hamburgerBtn.style.display = 'none';
            }
            
            // Add resize listener to maintain visibility
            window.addEventListener('resize', function() {
                if (window.innerWidth < 1024) {
                    hamburgerBtn.style.display = 'flex';
                    hamburgerBtn.style.visibility = 'visible';
                    hamburgerBtn.style.opacity = '1';
                } else {
                    hamburgerBtn.style.display = 'none';
                }
            });
        });
    </script>
    <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Hamburger menu button - outside of sidebar -->
<button id="sidebar-toggle" class="fixed top-4 left-4 z-50 bg-green-700 text-white rounded-full p-2 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-400 shadow-lg block">
    <i class="fas fa-bars text-lg"></i>
</button>

<div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar text-white w-64 px-4 py-4 fixed h-full z-50 lg:transform-none">
        <div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold flex items-center">
            <i class="fas fa-boxes mr-2"></i> BKIS
        </h2>
        <p class="text-sm font-light ml-7">(Bento Kopi Inventory System)</p>
    </div>
    <button id="sidebar-close" class="lg:hidden text-white opacity-80 hover:opacity-100 focus:outline-none">
        <i class="fas fa-times text-lg"></i>
    </button>
</div>

            <nav>
                <ul class="space-y-1">
                    <li class="sidebar-item">
                        <a href="index.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-700' : '' ?>">
                            <i class="fas fa-tachometer-alt w-5 text-sm"></i> 
                            <span class="text-sm ml-2">Dashboard</span>
                        </a>
                    </li>
                    
                    <?php 
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $user_role = $_SESSION['user_role'];
                    
                    // Semua role memiliki akses ke inventaris
                    $show_inventaris = true;
                    ?>
                    <li class="sidebar-item">
                        <a href="#" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600" 
                           onclick="toggleSubmenu('inventaris-submenu')" 
                           aria-expanded="false" 
                           aria-controls="inventaris-submenu">
                            <i class="fas fa-warehouse w-5 text-sm"></i>
                            <span class="text-sm ml-2">Inventaris</span>
                            <i class="fas fa-chevron-down ml-auto text-xs"></i>
                        </a>
                        <ul id="inventaris-submenu" class="hidden pl-7 mt-1 space-y-1">
                            <li>
                                <a href="barang.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'barang.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-boxes w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Stok Barang</span>
                                </a>
                            </li>
                            <li>
                                <a href="bahan_baku.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'bahan_baku.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-cube w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Data Bahan Baku</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <?php 
                    // Cek akses untuk menu Supplier
                    $show_supplier = ($user_role == 'admin' || in_array($user_role, ['kasir', 'purchasing', 'crew']));
                    
                    if ($show_supplier):
                    ?>
                    <li class="sidebar-item">
                        <a href="#" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600" 
                           onclick="toggleSubmenu('supplier-submenu')" 
                           aria-expanded="false" 
                           aria-controls="supplier-submenu">
                            <i class="fas fa-truck w-5 text-sm"></i>
                            <span class="text-sm ml-2">Supplier</span>
                            <i class="fas fa-chevron-down ml-auto text-xs"></i>
                        </a>
                        <ul id="supplier-submenu" class="hidden pl-7 mt-1 space-y-1">
                            <li>
                                <a href="supplier.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'supplier.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-address-card w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Data Supplier</span>
                                </a>
                            </li>
                            
                            <?php if ($user_role == 'admin' || in_array($user_role, ['purchasing', 'crew', 'kasir'])): ?>
                            <li>
                                <a href="pesan_barang.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'pesan_barang.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-shopping-basket w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Pesan Barang</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($user_role == 'admin' || in_array($user_role, ['kasir', 'headproduksi', 'purchasing', 'crew'])): ?>
                    <li class="sidebar-item">
                        <a href="retur_barang.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'retur_barang.php' ? 'bg-blue-700' : '' ?>">
                            <i class="fas fa-undo w-5 text-sm"></i>
                            <span class="text-sm ml-2">Retur Barang</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($user_role == 'admin' || in_array($user_role, ['headproduksi', 'purchasing', 'crew'])): ?>
                    <li class="sidebar-item">
                        <a href="barang_lost.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'barang_lost.php' ? 'bg-blue-700' : '' ?>">
                            <i class="fas fa-times w-5 text-sm"></i>
                            <span class="text-sm ml-2">Barang Lost</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php 
                    // Cek akses untuk menu Data Menu
                    $show_menu = ($user_role == 'admin' || in_array($user_role, ['kasir']));
                    
                    if ($show_menu):
                    ?>
                    <li class="sidebar-item">
                        <a href="#" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600" 
                           onclick="toggleSubmenu('menu-submenu')" 
                           aria-expanded="false" 
                           aria-controls="menu-submenu">
                            <i class="fas fa-utensils w-5 text-sm"></i>
                            <span class="text-sm ml-2">Data Menu</span>
                            <i class="fas fa-chevron-down ml-auto text-xs"></i>
                        </a>
                        <ul id="menu-submenu" class="hidden pl-7 mt-1 space-y-1">
                            <li>
                                <a href="menu_makanan.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'menu_makanan.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-hamburger w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Menu Makanan</span>
                                </a>
                            </li>
                            <li>
                                <a href="menu_minuman.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'menu_minuman.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-coffee w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Menu Minuman</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php 
                    // Cek akses untuk menu Penjualan
                    $show_penjualan = ($user_role == 'admin' || in_array($user_role, ['kasir', 'crew']));

                    if ($show_penjualan):
                    ?>
                    <li class="sidebar-item">
                        <a href="#" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600" 
                           onclick="toggleSubmenu('penjualan-submenu')" 
                           aria-expanded="false" 
                           aria-controls="penjualan-submenu">
                            <i class="fas fa-cash-register w-5 text-sm"></i>
                            <span class="text-sm ml-2">Penjualan</span>
                            <i class="fas fa-chevron-down ml-auto text-xs"></i>
                        </a>
                        <ul id="penjualan-submenu" class="hidden pl-7 mt-1 space-y-1">
                            <li>
                                <a href="penjualan.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'penjualan.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-shopping-cart w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Transaksi Penjualan</span>
                                </a>
                            </li>
                            <li>
                                <a href="laporan_penjualan.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'laporan_penjualan.php' || basename($_SERVER['PHP_SELF']) == 'detail_penjualan.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-chart-line w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Laporan Penjualan</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php 
                    // Cek akses untuk menu Laporan Barang
                    $show_laporan = ($user_role == 'admin' || in_array($user_role, ['kasir', 'purchasing', 'crew']));
                    
                    if ($show_laporan):
                    ?>
                    <li class="sidebar-item">
                        <a href="#" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600" 
                           onclick="toggleSubmenu('laporan-submenu')" 
                           aria-expanded="false" 
                           aria-controls="laporan-submenu">
                            <i class="fas fa-clipboard w-5 text-sm"></i>
                            <span class="text-sm ml-2">Laporan Barang</span>
                            <i class="fas fa-chevron-down ml-auto text-xs"></i>
                        </a>
                        <ul id="laporan-submenu" class="hidden pl-7 mt-1 space-y-1">
                            <li>
                                <a href="laporan_masuk.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'laporan_masuk.php' ? 'bg-blue-700' : '' ?>">
                                    <i class="fas fa-file-import w-5 text-sm"></i>
                                    <span class="text-sm ml-2">Laporan Barang Masuk</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($user_role == 'admin'): ?>
                    <li class="mt-3">
                        <div class="text-xs uppercase font-medium text-blue-200 opacity-75 pl-3 mb-2">Administrasi</div>
                    </li>
                    <li class="sidebar-item">
                        <a href="pengguna.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'pengguna.php' ? 'bg-blue-700' : '' ?>">
                            <i class="fas fa-users w-5 text-sm"></i>
                            <span class="text-sm ml-2">Manajemen Pengguna</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-item">
                        <a href="log_aktivitas.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'log_aktivitas.php' ? 'bg-blue-700' : '' ?>">
                            <i class="fas fa-history w-5 text-sm"></i>
                            <span class="text-sm ml-2">Log Aktivitas</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-item">
                        <a href="data_toko.php" class="flex items-center block py-2 px-3 rounded-lg transition duration-200 hover:bg-blue-600 <?= basename($_SERVER['PHP_SELF']) == 'data_toko.php' ? 'bg-blue-700' : '' ?>">
                            <i class="fas fa-store w-5 text-sm"></i>
                            <span class="text-sm ml-2">Data Toko</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="mt-auto pt-5">
                <div class="border-t border-gray-700 opacity-50 pt-4">
                    <a href="profile.php" class="flex items-center px-3 py-2 text-xs text-blue-100 hover:text-white rounded-lg hover:bg-blue-600 transition duration-200">
                        <i class="fas fa-user-cog w-5"></i>
                        <span class="ml-2">Profil</span>
                    </a>
                    <a href="logout.php" class="flex items-center px-3 py-2 text-xs text-blue-100 hover:text-white rounded-lg hover:bg-blue-600 transition duration-200">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span class="ml-2">Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div id="content" class="content flex-1 pl-0 lg:pl-64 transition-all duration-300">
            <div class="sticky top-0 z-40 bg-white shadow-sm">
                <div class="flex justify-between items-center px-4 lg:px-6 py-2.5">
                    <h1 class="text-xl font-bold text-gray-800"><?= isset($pageTitle) ? $pageTitle : 'Dashboard' ?></h1>
                    
                    <div class="flex items-center space-x-4">
                        <span class="hidden md:inline-block text-sm text-gray-600">
                            <?= $store_info['nama_toko'] ?? 'Sistem Inventori' ?>
                        </span>
                        
                        <div class="relative">
                            <button class="flex items-center space-x-2 focus:outline-none rounded-full bg-gray-100 p-1.5 hover:bg-gray-200 transition" id="user-menu-button">
                                <span class="hidden md:inline-block text-sm text-gray-700"><?= $_SESSION['user_name'] ?? 'User' ?></span>
                                <span class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm">
                                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                                </span>
                            </button>
                            
                            <div class="dropdown-menu origin-top-right absolute right-0 mt-2 w-56 rounded-xl bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden z-10" id="user-menu">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm">Login sebagai</p>
                                    <p class="text-sm font-medium text-gray-900"><?= $_SESSION['user_name'] ?? 'User' ?></p>
                                </div>
                                <a href="profile.php" class="dropdown-item flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-3 text-gray-500"></i> Profil Saya
                                </a>
                                <a href="logout.php" class="dropdown-item flex items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-3 text-red-500"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <?php displayAlert(); ?>
                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'crew'): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                    <div class="flex">
                        <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                        <div>
                            <p class="font-bold">Mode View Only</p>
                            <p>Anda memiliki akses view-only. Anda hanya dapat melihat dan mencetak data, tetapi tidak dapat menambah, mengubah, atau menghapus data.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
    <?php endif; ?>
    
<script>
    // User dropdown menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        
        if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', function() {
                userMenu.classList.toggle('hidden');
                
                // Animasi dropdown
                if (!userMenu.classList.contains('hidden')) {
                    userMenu.classList.add('animate-fade-in-down');
                    setTimeout(() => {
                        userMenu.classList.remove('animate-fade-in-down');
                    }, 300);
                }
            });
            
            // Close menu when clicked outside
            document.addEventListener('click', function(event) {
                if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        }
        
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        
        if (sidebarToggle && sidebar && content) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                content.classList.toggle('pl-0');
                content.classList.toggle('pl-64');
            });
        }
    });
    
    // Toggle submenu function
    function toggleSubmenu(submenuId) {
        const submenu = document.getElementById(submenuId);
        if (submenu) {
            submenu.classList.toggle('hidden');
        }
    }
    
    // Tutup alert secara otomatis
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.classList.add('opacity-0');
                alert.style.transition = 'opacity 1s';
                
                setTimeout(function() {
                    alert.remove();
                }, 1000);
            }, 5000);
            
            const closeButton = alert.querySelector('.alert-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    alert.remove();
                });
            }
        });
    });
</script>
</body>
</html> 