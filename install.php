<?php
require_once 'config/database.php';

// Function to run SQL from a file
function runSQLFile($conn, $file) {
    $queries = file_get_contents($file);
    
    // Try to execute the SQL queries
    if (mysqli_multi_query($conn, $queries)) {
        echo "<p>✅ SQL file executed successfully: $file</p>";
        
        // Clear results to allow more queries
        while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
            // Consume results to allow more queries
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        }
    } else {
        echo "<p>❌ Error executing SQL file: " . mysqli_error($conn) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Inventory System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #333;
        }
        .step {
            background-color: #f7f7f7;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin-bottom: 20px;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .buttons {
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            background-color: #0066cc;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h1>Inventory System Installation</h1>
    
    <div class="step">
        <h2>Step 1: Create Required Tables</h2>
        <?php
        if (isset($_GET['create_tables'])) {
            runSQLFile($conn, 'create_tables.sql');
        } else {
            echo "<p>Click the button below to create the tables needed for the inventory system:</p>";
            echo "<a href='?create_tables=1' class='button'>Create Tables</a>";
        }
        ?>
    </div>
    
    <div class="step">
        <h2>Step 2: Insert Sample Data</h2>
        <?php
        if (isset($_GET['insert_data'])) {
            require_once 'insert_sample_data.php';
        } else {
            echo "<p>Click the button below to insert sample data for testing:</p>";
            echo "<a href='?insert_data=1' class='button'>Insert Sample Data</a>";
        }
        ?>
    </div>
    
    <div class="step">
        <h2>Step 3: Check Tables</h2>
        <?php
        if (isset($_GET['check_tables'])) {
            // Check if tables exist
            $tables = [
                'barang_masuk',
                'barang_keluar',
                'laporan_masuk',
                'laporan_masuk_detail',
                'laporan_keluar',
                'detail_laporan_keluar'
            ];
            
            $all_exist = true;
            echo "<ul>";
            foreach ($tables as $table) {
                $query = "SHOW TABLES LIKE '$table'";
                $result = mysqli_query($conn, $query);
                $exists = mysqli_num_rows($result) > 0;
                
                echo "<li>" . ($exists ? "✅" : "❌") . " Table $table: " . ($exists ? "Exists" : "Does not exist") . "</li>";
                
                if (!$exists) {
                    $all_exist = false;
                }
            }
            echo "</ul>";
            
            if ($all_exist) {
                echo "<p class='success'>All required tables exist. You can now use the inventory system.</p>";
            } else {
                echo "<p class='error'>Some tables are missing. Please complete Step 1 to create all required tables.</p>";
            }
        } else {
            echo "<p>Click the button below to check if all required tables exist:</p>";
            echo "<a href='?check_tables=1' class='button'>Check Tables</a>";
        }
        ?>
    </div>
    
    <div class="buttons">
        <a href="index.php" class="button">Go to Dashboard</a>
        <a href="laporan_masuk.php" class="button">Go to Laporan Masuk</a>
        <a href="laporan_keluar.php" class="button">Go to Laporan Keluar</a>
    </div>
</body>
</html> 