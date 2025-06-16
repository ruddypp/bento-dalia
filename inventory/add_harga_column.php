<?php
// Database connection settings
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'inventori_db';

// Connect to the database
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// SQL to add harga column if it doesn't exist
$check_column = "SHOW COLUMNS FROM barang LIKE 'harga'";
$result = mysqli_query($conn, $check_column);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE barang ADD COLUMN harga DECIMAL(10,2) DEFAULT 0";
    
    if (mysqli_query($conn, $sql)) {
        echo "Column 'harga' added successfully to table 'barang'";
        
        // Update existing records to have some example prices
        $update_sql = "UPDATE barang SET harga = 10000 WHERE id_barang = 1"; // susu
        mysqli_query($conn, $update_sql);
        
        $update_sql = "UPDATE barang SET harga = 25000 WHERE id_barang = 2"; // kopi
        mysqli_query($conn, $update_sql);
        
        $update_sql = "UPDATE barang SET harga = 50000 WHERE id_barang = 3"; // rudy
        mysqli_query($conn, $update_sql);
        
        echo "<br>Updated existing records with sample prices";
    } else {
        echo "Error adding column: " . mysqli_error($conn);
    }
} else {
    echo "Column 'harga' already exists in table 'barang'";
}

mysqli_close($conn);
echo "<br><a href='barang.php'>Go back to Barang page</a>";
?> 