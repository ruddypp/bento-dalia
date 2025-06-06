<?php
$pageTitle = "Transaksi Penjualan";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'role_permission_check.php'; // Tambahkan ini untuk memeriksa hak akses

// Initialize variables
$errors = [];
$success = false;
$cart = []; // Shopping cart for menu items

// Check if cart exists in session, if not create it
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart = &$_SESSION['cart'];

// Format currency to Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Function to get menu details by ID
function getMenuById($conn, $id_menu) {
    $query = "SELECT * FROM menu WHERE id_menu = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_menu);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to parse menu ingredients and return as array
function parseMenuIngredients($bahan_string) {
    $ingredients = [];
    $bahan_array = explode(',', $bahan_string);
    
    foreach ($bahan_array as $bahan_item) {
        $item_parts = explode(':', trim($bahan_item));
        if (count($item_parts) >= 2) {
            $nama_bahan = trim($item_parts[0]);
            $jumlah = floatval(trim($item_parts[1]));
            $ingredients[] = [
                'nama' => $nama_bahan,
                'jumlah' => $jumlah
            ];
        }
    }
    
    return $ingredients;
}

// Function to get barang ID by name
function getBarangIdByName($conn, $nama_barang) {
    $query = "SELECT id_barang FROM barang WHERE nama_barang = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $nama_barang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id_barang'];
    }
    
    return null;
}

// Function to check if there's enough stock for the menu
function checkStockForMenu($conn, $id_menu, $quantity) {
    $menu = getMenuById($conn, $id_menu);
    if (!$menu) return false;
    
    $ingredients = parseMenuIngredients($menu['bahan']);
    $stock_status = [];
    
    foreach ($ingredients as $ingredient) {
        $id_barang = getBarangIdByName($conn, $ingredient['nama']);
        if (!$id_barang) {
            $stock_status[] = [
                'nama' => $ingredient['nama'],
                'status' => false,
                'message' => 'Bahan tidak ditemukan dalam database'
            ];
            continue;
        }
        
        $query = "SELECT stok FROM barang WHERE id_barang = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_barang);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stok_tersedia = $row['stok'];
            $stok_dibutuhkan = $ingredient['jumlah'] * $quantity;
            
            $stock_status[] = [
                'nama' => $ingredient['nama'],
                'status' => $stok_tersedia >= $stok_dibutuhkan,
                'stok_tersedia' => $stok_tersedia,
                'stok_dibutuhkan' => $stok_dibutuhkan,
                'message' => $stok_tersedia >= $stok_dibutuhkan ? 'Stok mencukupi' : 'Stok tidak mencukupi'
            ];
        } else {
            $stock_status[] = [
                'nama' => $ingredient['nama'],
                'status' => false,
                'message' => 'Bahan tidak ditemukan'
            ];
        }
    }
    
    return $stock_status;
}

// Function to generate invoice number
function generateInvoiceNumber() {
    $prefix = 'INV';
    $date = date('Ymd');
    $random = mt_rand(1000, 9999);
    return $prefix . $date . $random;
}

// Handle add to cart
if (isset($_POST['add_to_cart']) && isset($_POST['id_menu']) && isset($_POST['quantity'])) {
    $id_menu = intval($_POST['id_menu']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity <= 0) {
        $errors[] = "Jumlah harus lebih dari 0";
    } else {
        $menu = getMenuById($conn, $id_menu);
        
        if ($menu) {
            // Check stock availability
            $stock_check = checkStockForMenu($conn, $id_menu, $quantity);
            $stock_ok = true;
            
            foreach ($stock_check as $item) {
                if (!$item['status']) {
                    $stock_ok = false;
                    $errors[] = "Stok {$item['nama']} tidak mencukupi. {$item['message']}";
                }
            }
            
            if ($stock_ok) {
                // Add to cart or update quantity if already in cart
                $item_key = array_search($id_menu, array_column($cart, 'id_menu'));
                
                if ($item_key !== false) {
                    // Update quantity if item already in cart
                    $cart[$item_key]['quantity'] += $quantity;
                    $cart[$item_key]['subtotal'] = $cart[$item_key]['quantity'] * $cart[$item_key]['harga'];
                    $cart[$item_key]['subtotal_modal'] = $cart[$item_key]['quantity'] * $cart[$item_key]['harga_modal'];
                } else {
                    // Add new item to cart
                    $cart[] = [
                        'id_menu' => $id_menu,
                        'nama_menu' => $menu['nama_menu'],
                        'kategori' => $menu['kategori'],
                        'harga' => $menu['harga'],
                        'harga_modal' => $menu['harga_modal'],
                        'quantity' => $quantity,
                        'subtotal' => $quantity * $menu['harga'],
                        'subtotal_modal' => $quantity * $menu['harga_modal'],
                        'bahan' => $menu['bahan']
                    ];
                }
                
                $success = "Menu {$menu['nama_menu']} berhasil ditambahkan ke keranjang";
            }
        } else {
            $errors[] = "Menu tidak ditemukan";
        }
    }
}

// Handle remove from cart
if (isset($_GET['remove']) && isset($cart[$_GET['remove']])) {
    unset($cart[$_GET['remove']]);
    $cart = array_values($cart); // Reindex array
    $success = "Item berhasil dihapus dari keranjang";
}

// Handle clear cart
if (isset($_GET['clear_cart'])) {
    $cart = [];
    $success = "Keranjang berhasil dikosongkan";
}

// Handle update quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $key => $qty) {
        if (isset($cart[$key])) {
            $quantity = intval($qty);
            if ($quantity > 0) {
                // Check stock availability for new quantity
                $stock_check = checkStockForMenu($conn, $cart[$key]['id_menu'], $quantity);
                $stock_ok = true;
                
                foreach ($stock_check as $item) {
                    if (!$item['status']) {
                        $stock_ok = false;
                        $errors[] = "Stok {$item['nama']} tidak mencukupi untuk {$cart[$key]['nama_menu']}. {$item['message']}";
                    }
                }
                
                if ($stock_ok) {
                    $cart[$key]['quantity'] = $quantity;
                    $cart[$key]['subtotal'] = $quantity * $cart[$key]['harga'];
                    $cart[$key]['subtotal_modal'] = $quantity * $cart[$key]['harga_modal'];
                }
            } else {
                $errors[] = "Jumlah harus lebih dari 0";
            }
        }
    }
    
    if (empty($errors)) {
        $success = "Keranjang berhasil diperbarui";
    }
}

// Handle checkout process
if (isset($_POST['checkout']) && !empty($cart)) {
    try {
        $conn->begin_transaction();
        
        // Get form data
        $nama_pelanggan = isset($_POST['nama_pelanggan']) ? trim($_POST['nama_pelanggan']) : '';
        $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';
        
        // Generate invoice number
        $no_invoice = generateInvoiceNumber();
        
        // Calculate cart totals
        $cart_total = 0;
        $cart_modal_total = 0;
        $cart_profit = 0;
        
        foreach ($cart as $item) {
            $cart_total += $item['subtotal'];
            $cart_modal_total += $item['subtotal_modal'];
        }
        $cart_profit = $cart_total - $cart_modal_total;
        
        // Create penjualan record
        $query_penjualan = "INSERT INTO penjualan (no_invoice, tanggal_penjualan, total_harga, total_modal, keuntungan, id_user, nama_pelanggan, catatan) 
                           VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)";
        $stmt_penjualan = $conn->prepare($query_penjualan);
        
        if (!$stmt_penjualan) {
            throw new Exception("Error preparing penjualan query: " . $conn->error);
        }
        
        $user_id = $_SESSION['user_id'] ?? null;
        $stmt_penjualan->bind_param("sdddiss", $no_invoice, $cart_total, $cart_modal_total, $cart_profit, $user_id, $nama_pelanggan, $catatan);
        
        if (!$stmt_penjualan->execute()) {
            throw new Exception("Error executing penjualan query: " . $stmt_penjualan->error);
        }
        
        $id_penjualan = $conn->insert_id;
        $stmt_penjualan->close();
        
        // Process each item in cart
        foreach ($cart as $item) {
            // Insert penjualan_detail
            $query_detail = "INSERT INTO penjualan_detail (id_penjualan, id_menu, jumlah, harga_satuan, harga_modal_satuan, subtotal, subtotal_modal) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($query_detail);
            
            if (!$stmt_detail) {
                throw new Exception("Error preparing penjualan_detail query: " . $conn->error);
            }
            
            $stmt_detail->bind_param("iiidddd", $id_penjualan, $item['id_menu'], $item['quantity'], 
                                    $item['harga'], $item['harga_modal'], $item['subtotal'], $item['subtotal_modal']);
            
            if (!$stmt_detail->execute()) {
                throw new Exception("Error executing penjualan_detail query: " . $stmt_detail->error);
            }
            
            $id_penjualan_detail = $conn->insert_id;
            $stmt_detail->close();
            
            // Process ingredients and update stock
            $ingredients = parseMenuIngredients($item['bahan']);
            
            foreach ($ingredients as $ingredient) {
                $id_barang = getBarangIdByName($conn, $ingredient['nama']);
                if (!$id_barang) continue;
                
                $jumlah_used = $ingredient['jumlah'] * $item['quantity'];
                
                // Insert penjualan_bahan
                $query_bahan = "INSERT INTO penjualan_bahan (id_penjualan_detail, id_barang, jumlah) VALUES (?, ?, ?)";
                $stmt_bahan = $conn->prepare($query_bahan);
                
                if (!$stmt_bahan) {
                    throw new Exception("Error preparing penjualan_bahan query: " . $conn->error);
                }
                
                $stmt_bahan->bind_param("iid", $id_penjualan_detail, $id_barang, $jumlah_used);
                
                if (!$stmt_bahan->execute()) {
                    throw new Exception("Error executing penjualan_bahan query: " . $stmt_bahan->error);
                }
                
                $stmt_bahan->close();
                
                // Update stock
                $query_stock = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
                $stmt_stock = $conn->prepare($query_stock);
                
                if (!$stmt_stock) {
                    throw new Exception("Error preparing stock update query: " . $conn->error);
                }
                
                $stmt_stock->bind_param("di", $jumlah_used, $id_barang);
                
                if (!$stmt_stock->execute()) {
                    throw new Exception("Error executing stock update query: " . $stmt_stock->error);
                }
                
                $stmt_stock->close();
                
                // Create barang_keluar record
                $query_keluar = "INSERT INTO barang_keluar (id_barang, tanggal_keluar, id_user, qty_keluar) VALUES (?, NOW(), ?, ?)";
                $stmt_keluar = $conn->prepare($query_keluar);
                
                if (!$stmt_keluar) {
                    throw new Exception("Error preparing barang_keluar query: " . $conn->error);
                }
                
                $stmt_keluar->bind_param("iid", $id_barang, $user_id, $jumlah_used);
                
                if (!$stmt_keluar->execute()) {
                    throw new Exception("Error executing barang_keluar query: " . $stmt_keluar->error);
                }
                
                $stmt_keluar->close();
            }
        }
        
        // Update or create daily sales report
        $today = date('Y-m-d');
        
        // Check if report for today exists
        $query_check = "SELECT id_laporan FROM laporan_penjualan WHERE tanggal = ?";
        $stmt_check = $conn->prepare($query_check);
        
        if (!$stmt_check) {
            throw new Exception("Error preparing laporan check query: " . $conn->error);
        }
        
        $stmt_check->bind_param("s", $today);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Update existing report
            $row = $result_check->fetch_assoc();
            $id_laporan = $row['id_laporan'];
            
            $query_update = "UPDATE laporan_penjualan SET 
                            total_penjualan = total_penjualan + ?,
                            total_modal = total_modal + ?,
                            total_keuntungan = total_keuntungan + ?,
                            jumlah_transaksi = jumlah_transaksi + 1
                            WHERE id_laporan = ?";
            
            $stmt_update = $conn->prepare($query_update);
            
            if (!$stmt_update) {
                throw new Exception("Error preparing laporan update query: " . $conn->error);
            }
            
            $stmt_update->bind_param("dddi", $cart_total, $cart_modal_total, $cart_profit, $id_laporan);
            
            if (!$stmt_update->execute()) {
                throw new Exception("Error executing laporan update query: " . $stmt_update->error);
            }
            
            $stmt_update->close();
        } else {
            // Create new report
            $query_insert = "INSERT INTO laporan_penjualan (tanggal, total_penjualan, total_modal, total_keuntungan, jumlah_transaksi, id_user)
                            VALUES (?, ?, ?, ?, 1, ?)";
            
            $stmt_insert = $conn->prepare($query_insert);
            
            if (!$stmt_insert) {
                throw new Exception("Error preparing laporan insert query: " . $conn->error);
            }
            
            $stmt_insert->bind_param("sdddi", $today, $cart_total, $cart_modal_total, $cart_profit, $user_id);
            
            if (!$stmt_insert->execute()) {
                throw new Exception("Error executing laporan insert query: " . $stmt_insert->error);
            }
            
            $stmt_insert->close();
        }
        
        $stmt_check->close();
        
        // Log activity
        if (function_exists('logActivity')) {
            logActivity($user_id, "Membuat transaksi penjualan #$no_invoice senilai " . formatRupiah($cart_total));
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear cart
        $cart = [];
        $_SESSION['cart'] = [];
        
        // Set success message
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => "Transaksi berhasil! No. Invoice: $no_invoice"
        ];
        
        // Redirect to print page or back to penjualan
        header("Location: penjualan.php?success=1&invoice=$no_invoice");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $errors[] = "Gagal memproses transaksi: " . $e->getMessage();
    }
}

// Get all menu items for selection
$menu_makanan = [];
$menu_minuman = [];

$query_makanan = "SELECT * FROM menu WHERE kategori = 'makanan' AND status = 'available' ORDER BY nama_menu";
$result_makanan = $conn->query($query_makanan);
if ($result_makanan && $result_makanan->num_rows > 0) {
    while ($row = $result_makanan->fetch_assoc()) {
        $menu_makanan[] = $row;
    }
}

$query_minuman = "SELECT * FROM menu WHERE kategori = 'minuman' AND status = 'available' ORDER BY nama_menu";
$result_minuman = $conn->query($query_minuman);
if ($result_minuman && $result_minuman->num_rows > 0) {
    while ($row = $result_minuman->fetch_assoc()) {
        $menu_minuman[] = $row;
    }
}

// Calculate cart totals
$cart_total = 0;
$cart_modal_total = 0;
$cart_profit = 0;

foreach ($cart as $item) {
    $cart_total += $item['subtotal'];
    $cart_modal_total += $item['subtotal_modal'];
}
$cart_profit = $cart_total - $cart_modal_total;

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $invoice = isset($_GET['invoice']) ? $_GET['invoice'] : '';
    $success = "Transaksi berhasil! No. Invoice: $invoice";
}

?>

<div class="ml-17 p-2">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-700 flex items-center">
            <i class="fas fa-cash-register mr-2"></i> Penjualan
        </h1>
        <div class="flex items-center mt-2 text-sm">
            <a href="dashboard.php" class="text-blue-500 hover:text-blue-700">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-600">Penjualan</span>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p class="font-bold">Terdapat kesalahan:</p>
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p><?= $success ?></p>
    </div>
    <?php endif; ?>

    <!-- Main content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Menu Selection Section -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-utensils mr-2 text-green-600"></i> Pilih Menu
                </h2>
                
                <!-- Tab Navigation -->
                <div class="mb-4 border-b">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                        <li class="mr-2">
                            <a href="#" class="tab-link active inline-block p-4 border-b-2 border-green-600 rounded-t-lg" data-tab="makanan">
                                <i class="fas fa-hamburger mr-1"></i> Makanan
                            </a>
                        </li>
                        <li class="mr-2">
                            <a href="#" class="tab-link inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" data-tab="minuman">
                                <i class="fas fa-coffee mr-1"></i> Minuman
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Search Box -->
                <div class="mb-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="menu-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full pl-10 p-2.5" placeholder="Cari menu...">
                    </div>
                </div>
                
                <!-- Menu Tabs -->
                <div>
                    <!-- Makanan Tab -->
                    <div id="makanan-tab" class="tab-content active">
                        <?php if (empty($menu_makanan)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-hamburger text-4xl mb-2"></i>
                            <p>Belum ada menu makanan yang tersedia</p>
                        </div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                            <?php foreach ($menu_makanan as $menu): ?>
                            <div class="menu-item bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
                                <?php if (!empty($menu['foto']) && file_exists('uploads/menu/' . $menu['foto'])): ?>
                                <img src="uploads/menu/<?= $menu['foto'] ?>" alt="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-full h-32 object-cover">
                                <?php else: ?>
                                <div class="w-full h-32 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-hamburger text-gray-400 text-3xl"></i>
                                </div>
                                <?php endif; ?>
                                
                                <div class="p-4">
                                    <h3 class="menu-name text-lg font-semibold mb-1"><?= htmlspecialchars($menu['nama_menu']) ?></h3>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-green-600 font-bold"><?= formatRupiah($menu['harga']) ?></span>
                                        <span class="text-xs text-gray-500">Modal: <?= formatRupiah($menu['harga_modal']) ?></span>
                                    </div>
                                    
                                    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" class="mt-2">
                                        <input type="hidden" name="id_menu" value="<?= $menu['id_menu'] ?>">
                                        <div class="flex items-center">
                                            <input type="number" name="quantity" value="1" min="1" class="w-16 px-2 py-1 border border-gray-300 rounded-md text-center mr-2">
                                            <button type="submit" name="add_to_cart" class="flex-grow bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded-md text-sm transition-colors duration-200">
                                                <i class="fas fa-cart-plus mr-1"></i> Tambah
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Minuman Tab -->
                    <div id="minuman-tab" class="tab-content hidden">
                        <?php if (empty($menu_minuman)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-coffee text-4xl mb-2"></i>
                            <p>Belum ada menu minuman yang tersedia</p>
                        </div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                            <?php foreach ($menu_minuman as $menu): ?>
                            <div class="menu-item bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
                                <?php if (!empty($menu['foto']) && file_exists('uploads/menu/' . $menu['foto'])): ?>
                                <img src="uploads/menu/<?= $menu['foto'] ?>" alt="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-full h-32 object-cover">
                                <?php else: ?>
                                <div class="w-full h-32 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-coffee text-gray-400 text-3xl"></i>
                                </div>
                                <?php endif; ?>
                                
                                <div class="p-4">
                                    <h3 class="menu-name text-lg font-semibold mb-1"><?= htmlspecialchars($menu['nama_menu']) ?></h3>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-green-600 font-bold"><?= formatRupiah($menu['harga']) ?></span>
                                        <span class="text-xs text-gray-500">Modal: <?= formatRupiah($menu['harga_modal']) ?></span>
                                    </div>
                                    
                                    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" class="mt-2">
                                        <input type="hidden" name="id_menu" value="<?= $menu['id_menu'] ?>">
                                        <div class="flex items-center">
                                            <input type="number" name="quantity" value="1" min="1" class="w-16 px-2 py-1 border border-gray-300 rounded-md text-center mr-2">
                                            <button type="submit" name="add_to_cart" class="flex-grow bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded-md text-sm transition-colors duration-200">
                                                <i class="fas fa-cart-plus mr-1"></i> Tambah
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Shopping Cart Section -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-shopping-cart mr-2 text-blue-600"></i> Keranjang
                </h2>
                
                <?php if (empty($cart)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-shopping-basket text-4xl mb-2"></i>
                    <p>Keranjang belanja kosong</p>
                </div>
                <?php else: ?>
                <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <div class="max-h-80 overflow-y-auto mb-4">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase">
                                <tr>
                                    <th class="px-2 py-2 text-left">Menu</th>
                                    <th class="px-2 py-2 text-center">Qty</th>
                                    <th class="px-2 py-2 text-right">Subtotal</th>
                                    <th class="px-2 py-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $index => $item): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-2 py-3">
                                        <div>
                                            <div class="font-medium"><?= htmlspecialchars($item['nama_menu']) ?></div>
                                            <div class="text-xs text-gray-500"><?= formatRupiah($item['harga']) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-3 text-center">
                                        <input type="number" name="quantity[<?= $index ?>]" value="<?= $item['quantity'] ?>" min="1" class="w-12 px-1 py-0.5 border border-gray-300 rounded-md text-center">
                                    </td>
                                    <td class="px-2 py-3 text-right font-medium"><?= formatRupiah($item['subtotal']) ?></td>
                                    <td class="px-2 py-3 text-center">
                                        <a href="?remove=<?= $index ?>" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-between mb-2">
                        <button type="submit" name="update_cart" class="text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-sync-alt mr-1"></i> Update Keranjang
                        </button>
                        <a href="?clear_cart=1" class="text-sm text-red-600 hover:text-red-800">
                            <i class="fas fa-trash mr-1"></i> Kosongkan
                        </a>
                    </div>
                </form>
                
                <div class="border-t pt-4 mt-2">
                    <div class="flex justify-between mb-1">
                        <span class="text-gray-600">Total Harga:</span>
                        <span class="font-semibold"><?= formatRupiah($cart_total) ?></span>
                    </div>
                    <div class="flex justify-between mb-1">
                        <span class="text-gray-600">Total Modal:</span>
                        <span class="font-semibold"><?= formatRupiah($cart_modal_total) ?></span>
                    </div>
                    <div class="flex justify-between mb-4">
                        <span class="text-gray-600">Keuntungan:</span>
                        <span class="font-semibold text-green-600"><?= formatRupiah($cart_profit) ?></span>
                    </div>
                    
                    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                        <div class="mb-3">
                            <label for="nama_pelanggan" class="block text-sm font-medium text-gray-700 mb-1">Nama Pelanggan</label>
                            <input type="text" id="nama_pelanggan" name="nama_pelanggan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Opsional">
                        </div>
                        
                        <div class="mb-4">
                            <label for="catatan" class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea id="catatan" name="catatan" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Opsional"></textarea>
                        </div>
                        
                        <button type="submit" name="checkout" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-cash-register mr-2"></i> Proses Pembayaran
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Daily Sales Report Section -->
    <div class="mt-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-chart-line mr-2 text-purple-600"></i> Laporan Penjualan Hari Ini
            </h2>
            
            <?php
            // Get today's sales report
            $today = date('Y-m-d');
            $report = null;
            
            $query_report = "SELECT * FROM laporan_penjualan WHERE tanggal = ?";
            $stmt_report = $conn->prepare($query_report);
            
            if ($stmt_report) {
                $stmt_report->bind_param("s", $today);
                $stmt_report->execute();
                $result_report = $stmt_report->get_result();
                
                if ($result_report->num_rows > 0) {
                    $report = $result_report->fetch_assoc();
                }
                
                $stmt_report->close();
            }
            ?>
            
            <?php if ($report): ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div class="text-sm text-blue-600 mb-1">Total Penjualan</div>
                    <div class="text-2xl font-bold"><?= formatRupiah($report['total_penjualan']) ?></div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <div class="text-sm text-green-600 mb-1">Total Keuntungan</div>
                    <div class="text-2xl font-bold"><?= formatRupiah($report['total_keuntungan']) ?></div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                    <div class="text-sm text-yellow-600 mb-1">Total Modal</div>
                    <div class="text-2xl font-bold"><?= formatRupiah($report['total_modal']) ?></div>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <div class="text-sm text-purple-600 mb-1">Jumlah Transaksi</div>
                    <div class="text-2xl font-bold"><?= $report['jumlah_transaksi'] ?></div>
                </div>
            </div>
            
            <?php
            // Get recent transactions
            $transactions = [];
            $query_transactions = "SELECT p.*, COUNT(pd.id_penjualan_detail) as total_items 
                                  FROM penjualan p 
                                  LEFT JOIN penjualan_detail pd ON p.id_penjualan = pd.id_penjualan 
                                  WHERE DATE(p.tanggal_penjualan) = ? 
                                  GROUP BY p.id_penjualan 
                                  ORDER BY p.tanggal_penjualan DESC 
                                  LIMIT 10";
            
            $stmt_transactions = $conn->prepare($query_transactions);
            
            if ($stmt_transactions) {
                $stmt_transactions->bind_param("s", $today);
                $stmt_transactions->execute();
                $result_transactions = $stmt_transactions->get_result();
                
                while ($row = $result_transactions->fetch_assoc()) {
                    $transactions[] = $row;
                }
                
                $stmt_transactions->close();
            }
            ?>
            
            <h3 class="text-lg font-semibold mb-2">Transaksi Terbaru</h3>
            
            <?php if (empty($transactions)): ?>
            <div class="text-center py-4 text-gray-500">
                <p>Belum ada transaksi hari ini</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">No. Invoice</th>
                            <th class="px-4 py-2">Waktu</th>
                            <th class="px-4 py-2">Pelanggan</th>
                            <th class="px-4 py-2">Jumlah Item</th>
                            <th class="px-4 py-2">Total</th>
                            <th class="px-4 py-2">Keuntungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trx): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium"><?= htmlspecialchars($trx['no_invoice']) ?></td>
                            <td class="px-4 py-2"><?= date('H:i', strtotime($trx['tanggal_penjualan'])) ?></td>
                            <td class="px-4 py-2"><?= !empty($trx['nama_pelanggan']) ? htmlspecialchars($trx['nama_pelanggan']) : '<span class="text-gray-400">-</span>' ?></td>
                            <td class="px-4 py-2"><?= $trx['total_items'] ?> item</td>
                            <td class="px-4 py-2 font-medium"><?= formatRupiah($trx['total_harga']) ?></td>
                            <td class="px-4 py-2 text-green-600"><?= formatRupiah($trx['keuntungan']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-right">
                <a href="laporan_penjualan.php" class="text-blue-600 hover:text-blue-800 text-sm">
                    Lihat semua transaksi <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-chart-bar text-4xl mb-2"></i>
                <p>Belum ada transaksi penjualan hari ini</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabLinks.forEach(tab => tab.classList.remove('active', 'border-green-600', 'text-green-600'));
            tabLinks.forEach(tab => tab.classList.add('border-transparent'));
            
            // Add active class to current tab
            this.classList.add('active', 'border-green-600', 'text-green-600');
            this.classList.remove('border-transparent');
            
            // Hide all tab contents
            tabContents.forEach(content => content.classList.add('hidden'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Show current tab content
            const tabId = this.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.remove('hidden');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('menu-search');
    const menuItems = document.querySelectorAll('.menu-item');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        menuItems.forEach(item => {
            const menuName = item.querySelector('.menu-name').textContent.toLowerCase();
            
            if (menuName.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
