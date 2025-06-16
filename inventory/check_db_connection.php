<?php
// Database connection settings (same as used in your application)
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'inventori_db';

// Connect to the database
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    echo "Database connection successful!<br>";
}

// Check if the harga column exists
$check_column = "SHOW COLUMNS FROM barang LIKE 'harga'";
$result = mysqli_query($conn, $check_column);

if ($result === false) {
    echo "Error checking for column: " . mysqli_error($conn) . "<br>";
} else {
    if (mysqli_num_rows($result) == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE barang ADD COLUMN harga DECIMAL(10,2) DEFAULT 0";
        
        if (mysqli_query($conn, $sql)) {
            echo "Column 'harga' added successfully to table 'barang'<br>";
            
            // Update existing records to have some example prices
            $update_sql = "UPDATE barang SET harga = 10000 WHERE id_barang = 1"; // susu
            if(mysqli_query($conn, $update_sql)) {
                echo "Updated price for id 1<br>";
            } else {
                echo "Error updating price for id 1: " . mysqli_error($conn) . "<br>";
            }
            
            $update_sql = "UPDATE barang SET harga = 25000 WHERE id_barang = 2"; // kopi
            if(mysqli_query($conn, $update_sql)) {
                echo "Updated price for id 2<br>";
            } else {
                echo "Error updating price for id 2: " . mysqli_error($conn) . "<br>";
            }
            
            $update_sql = "UPDATE barang SET harga = 50000 WHERE id_barang = 3"; // rudy
            if(mysqli_query($conn, $update_sql)) {
                echo "Updated price for id 3<br>";
            } else {
                echo "Error updating price for id 3: " . mysqli_error($conn) . "<br>";
            }
        } else {
            echo "Error adding column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Column 'harga' already exists in table 'barang'<br>";
    }
}

// Test running the problematic query
$query = "SELECT *, (stok * harga) as total_harga FROM barang ORDER BY nama_barang ASC";
$items = mysqli_query($conn, $query);

if ($items === false) {
    echo "Error with main query: " . mysqli_error($conn) . "<br>";
} else {
    echo "Main query executed successfully!<br>";
}

// Test total calculation query
$query = "SELECT SUM(stok * harga) as total_inventory_value FROM barang";
$total_result = mysqli_query($conn, $query);

if ($total_result === false) {
    echo "Error with total calculation query: " . mysqli_error($conn) . "<br>";
} else {
    $total_value = mysqli_fetch_assoc($total_result)['total_inventory_value'] ?? 0;
    echo "Total inventory value: " . $total_value . "<br>";
}

mysqli_close($conn);
echo "<br><a href='barang.php'>Go back to Barang page</a>";
?> 