<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access. Please login first.";
    exit;
}

// Function to execute SQL and display results
function executeSQL($conn, $sql, $description) {
    echo "<h3>$description</h3>";
    echo "<pre>$sql</pre>";
    
    try {
        $result = $conn->query($sql);
        if ($result === TRUE) {
            echo "<p style='color:green'>Success: Query executed successfully</p>";
            return true;
        } else {
            echo "<p style='color:red'>Error: " . $conn->error . "</p>";
            return false;
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
        return false;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Laporan Masuk Detail Table</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #333;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Laporan Masuk Detail Table</h1>
        
        <h2>Database Repair Script</h2>
        
        <?php
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Step 1: Check if laporan_masuk_detail exists
            $check_table = $conn->query("SHOW TABLES LIKE 'laporan_masuk_detail'");
            
            if ($check_table->num_rows > 0) {
                // Table exists, drop it
                echo "<h3>Dropping existing laporan_masuk_detail table</h3>";
                $drop_sql = "DROP TABLE IF EXISTS laporan_masuk_detail";
                executeSQL($conn, $drop_sql, "Drop existing table");
            }
            
            // Step 2: Create the table with correct structure
            $create_table_sql = "CREATE TABLE IF NOT EXISTS `laporan_masuk_detail` (
              `id_detail` int(11) NOT NULL AUTO_INCREMENT,
              `id_laporan` int(11) DEFAULT NULL,
              `id_masuk` int(11) DEFAULT NULL,
              PRIMARY KEY (`id_detail`),
              KEY `id_laporan` (`id_laporan`),
              KEY `id_masuk` (`id_masuk`),
              CONSTRAINT `laporan_masuk_detail_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_masuk` (`id_laporan_masuk`) ON DELETE CASCADE,
              CONSTRAINT `laporan_masuk_detail_ibfk_2` FOREIGN KEY (`id_masuk`) REFERENCES `barang_masuk` (`id_masuk`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            executeSQL($conn, $create_table_sql, "Create laporan_masuk_detail table with correct structure");
            
            // Step 3: Insert records for existing laporan_masuk entries
            $insert_sql = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk)
                          SELECT lm.id_laporan_masuk, bm.id_masuk
                          FROM laporan_masuk lm
                          JOIN barang_masuk bm ON DATE(lm.tanggal_laporan) = DATE(bm.tanggal_masuk)
                          WHERE NOT EXISTS (
                              SELECT 1 FROM laporan_masuk_detail lmd 
                              WHERE lmd.id_laporan = lm.id_laporan_masuk AND lmd.id_masuk = bm.id_masuk
                          )";
            
            executeSQL($conn, $insert_sql, "Insert missing records");
            
            // Commit changes
            $conn->commit();
            echo "<p class='success'>All changes committed successfully!</p>";
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            echo "<p class='error'>Error occurred: " . $e->getMessage() . ". All changes have been rolled back.</p>";
        }
        
        // Display current status
        echo "<h2>Current Database Status</h2>";
        
        // Check laporan_masuk table
        $laporan_sql = "SELECT * FROM laporan_masuk";
        $laporan_result = $conn->query($laporan_sql);
        echo "<h3>Laporan Masuk Records: " . $laporan_result->num_rows . "</h3>";
        
        // Check laporan_masuk_detail table
        $detail_sql = "SELECT * FROM laporan_masuk_detail";
        $detail_result = $conn->query($detail_sql);
        echo "<h3>Laporan Masuk Detail Records: " . $detail_result->num_rows . "</h3>";
        
        // Check barang_masuk table
        $barang_masuk_sql = "SELECT * FROM barang_masuk";
        $barang_masuk_result = $conn->query($barang_masuk_sql);
        echo "<h3>Barang Masuk Records: " . $barang_masuk_result->num_rows . "</h3>";
        ?>
        
        <p><a href="laporan_masuk.php">Return to Laporan Masuk</a></p>
    </div>
</body>
</html> 