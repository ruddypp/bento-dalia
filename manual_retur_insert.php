<?php
// Koneksi ke database
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Manual Insert Data Retur</h1>";

// Ambil data barang untuk dropdown
$query = "SELECT id_barang, nama_barang FROM barang ORDER BY nama_barang";
$barang_result = mysqli_query($conn, $query);

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        // Ambil data dari form
        $id_barang = (int)$_POST['id_barang'];
        $qty_retur = (int)$_POST['qty_retur'];
        $alasan_retur = $_POST['alasan_retur'];
        $id_user = 1; // Default user ID
        $supplier = $_POST['supplier'] ?: '';
        $harga_satuan = (float)$_POST['harga_satuan'];
        $total = $qty_retur * $harga_satuan;
        $periode = (int)$_POST['periode'];
        $id_pesanan = !empty($_POST['id_pesanan']) ? (int)$_POST['id_pesanan'] : 0;
        
        // Insert ke tabel retur_barang
        $query = "INSERT INTO retur_barang (id_barang, qty_retur, tanggal_retur, alasan_retur, id_user, supplier, harga_satuan, total, periode, id_pesanan) 
                  VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "iisisidii", 
                             $id_barang, 
                             $qty_retur, 
                             $alasan_retur, 
                             $id_user, 
                             $supplier, 
                             $harga_satuan, 
                             $total, 
                             $periode, 
                             $id_pesanan);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error executing statement: " . mysqli_stmt_error($stmt));
        }
        
        $id_retur = mysqli_insert_id($conn);
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "Berhasil insert data retur dengan ID: $id_retur";
        echo "</div>";
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "Error: " . $e->getMessage();
        echo "</div>";
    }
}

// Tampilkan form
?>

<form method="post" style="max-width: 500px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
    <div style="margin-bottom: 15px;">
        <label for="id_barang" style="display: block; margin-bottom: 5px; font-weight: bold;">Barang:</label>
        <select id="id_barang" name="id_barang" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">-- Pilih Barang --</option>
            <?php while ($barang = mysqli_fetch_assoc($barang_result)): ?>
                <option value="<?= $barang['id_barang'] ?>"><?= $barang['nama_barang'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="qty_retur" style="display: block; margin-bottom: 5px; font-weight: bold;">Jumlah Retur:</label>
        <input type="number" id="qty_retur" name="qty_retur" required min="1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="harga_satuan" style="display: block; margin-bottom: 5px; font-weight: bold;">Harga Satuan:</label>
        <input type="number" id="harga_satuan" name="harga_satuan" required min="0" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="periode" style="display: block; margin-bottom: 5px; font-weight: bold;">Periode:</label>
        <select id="periode" name="periode" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="1">Periode 1</option>
            <option value="2">Periode 2</option>
            <option value="3">Periode 3</option>
            <option value="4">Periode 4</option>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="supplier" style="display: block; margin-bottom: 5px; font-weight: bold;">Supplier:</label>
        <input type="text" id="supplier" name="supplier" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="id_pesanan" style="display: block; margin-bottom: 5px; font-weight: bold;">ID Pesanan (opsional):</label>
        <input type="number" id="id_pesanan" name="id_pesanan" min="0" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="alasan_retur" style="display: block; margin-bottom: 5px; font-weight: bold;">Alasan Retur:</label>
        <textarea id="alasan_retur" name="alasan_retur" required rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
    </div>
    
    <button type="submit" style="background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Submit</button>
</form>

<h2>Data di Tabel retur_barang</h2>

<?php
// Tampilkan data di tabel retur_barang
$query = "SELECT rb.*, b.nama_barang FROM retur_barang rb JOIN barang b ON rb.id_barang = b.id_barang ORDER BY rb.id_retur DESC";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th><th>Barang</th><th>Qty</th><th>Tanggal</th><th>Alasan</th><th>Supplier</th><th>Harga</th><th>Total</th><th>Periode</th><th>ID Pesanan</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['id_retur']}</td>";
        echo "<td>{$row['nama_barang']}</td>";
        echo "<td>{$row['qty_retur']}</td>";
        echo "<td>{$row['tanggal_retur']}</td>";
        echo "<td>{$row['alasan_retur']}</td>";
        echo "<td>" . ($row['supplier'] ? $row['supplier'] : '-') . "</td>";
        echo "<td>{$row['harga_satuan']}</td>";
        echo "<td>{$row['total']}</td>";
        echo "<td>{$row['periode']}</td>";
        echo "<td>" . ($row['id_pesanan'] ? $row['id_pesanan'] : '-') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Tidak ada data di tabel retur_barang</p>";
}

mysqli_close($conn);
?> 