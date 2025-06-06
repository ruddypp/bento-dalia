<?php
// Koneksi ke database langsung
$conn = mysqli_connect('localhost', 'root', '', 'inventori_db3');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Periksa struktur tabel bahan_baku
$query = "SHOW COLUMNS FROM bahan_baku";
$result = mysqli_query($conn, $query);

echo "=== Struktur Tabel bahan_baku ===\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Default']}\n";
}

// Tambahkan ALTER TABLE untuk mengubah tipe data kolom status jika diperlukan
echo "\n=== Mengubah tipe data kolom status ===\n";
$query = "ALTER TABLE bahan_baku MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'";

if (mysqli_query($conn, $query)) {
    echo "Berhasil mengubah tipe data kolom status menjadi VARCHAR(50)\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// Periksa kembali struktur tabel setelah perubahan
$query = "SHOW COLUMNS FROM bahan_baku";
$result = mysqli_query($conn, $query);

echo "\n=== Struktur Tabel bahan_baku Setelah Perubahan ===\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Default']}\n";
}

// Tutup koneksi
mysqli_close($conn);

echo "\n=== Selesai ===\n";
echo "Silakan coba kembali proses retur di bahan_baku.php\n";
?> 