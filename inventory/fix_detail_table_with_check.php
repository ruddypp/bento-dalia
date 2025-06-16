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

// Check for issues with foreign key references
function checkForeignKeyReferences($conn) {
    echo "<h2>Checking Foreign Key References</h2>";
    
    // Check if barang_masuk has any records
    $barang_count = $conn->query("SELECT COUNT(*) as count FROM barang_masuk");
    $barang_row = $barang_count->fetch_assoc();
    echo "<p>Total barang_masuk records: " . $barang_row['count'] . "</p>";
    
    if ($barang_row['count'] == 0) {
        echo "<p style='color:red'>WARNING: No records in barang_masuk table. Foreign key constraints will fail if you try to reference this table.</p>";
        return false;
    }
    
    // Check if laporan_masuk has any records
    $laporan_count = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk");
    $laporan_row = $laporan_count->fetch_assoc();
    echo "<p>Total laporan_masuk records: " . $laporan_row['count'] . "</p>";
    
    if ($laporan_row['count'] == 0) {
        echo "<p style='color:red'>WARNING: No records in laporan_masuk table. Foreign key constraints will fail if you try to reference this table.</p>";
        return false;
    }
    
    return true;
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
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Laporan Masuk Detail Table</h1>
        
        <?php
        // Check if we're running the actual fix
        if (isset($_GET['fix']) && $_GET['fix'] == 'true') {
            echo "<h2>Running Database Repair</h2>";
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // First check references
                $references_ok = checkForeignKeyReferences($conn);
                
                if (!$references_ok) {
                    echo "<p class='error'>Cannot proceed with repair due to missing reference data.</p>";
                    throw new Exception("Reference data missing");
                }
                
                // Step 1: Check if laporan_masuk_detail exists
                $check_table = $conn->query("SHOW TABLES LIKE 'laporan_masuk_detail'");
                
                if ($check_table->num_rows > 0) {
                    // Table exists, show current structure
                    echo "<h3>Current laporan_masuk_detail table structure:</h3>";
                    $structure = $conn->query("DESCRIBE laporan_masuk_detail");
                    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                    while ($row = $structure->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['Field'] . "</td>";
                        echo "<td>" . $row['Type'] . "</td>";
                        echo "<td>" . $row['Null'] . "</td>";
                        echo "<td>" . $row['Key'] . "</td>";
                        echo "<td>" . $row['Default'] . "</td>";
                        echo "<td>" . $row['Extra'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                    // Check for column name inconsistencies
                    $has_id_laporan = false;
                    $has_id_laporan_masuk = false;
                    
                    $structure = $conn->query("DESCRIBE laporan_masuk_detail");
                    while ($row = $structure->fetch_assoc()) {
                        if ($row['Field'] == 'id_laporan') $has_id_laporan = true;
                        if ($row['Field'] == 'id_laporan_masuk') $has_id_laporan_masuk = true;
                    }
                    
                    if ($has_id_laporan_masuk && !$has_id_laporan) {
                        echo "<p class='error'>Found column inconsistency: Table uses id_laporan_masuk instead of id_laporan</p>";
                    }
                    
                    // Backup data
                    executeSQL($conn, "CREATE TABLE IF NOT EXISTS laporan_masuk_detail_backup LIKE laporan_masuk_detail", "Creating backup table");
                    executeSQL($conn, "INSERT INTO laporan_masuk_detail_backup SELECT * FROM laporan_masuk_detail", "Backing up data");
                    
                    // Drop the table to recreate it
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
                
                // Step 3: Create test data
                echo "<h3>Creating test data for verification</h3>";
                
                // Get one valid laporan_masuk record
                $laporan_result = $conn->query("SELECT id_laporan_masuk FROM laporan_masuk LIMIT 1");
                $laporan_row = $laporan_result->fetch_assoc();
                $laporan_id = $laporan_row['id_laporan_masuk'];
                
                // Get one valid barang_masuk record
                $masuk_result = $conn->query("SELECT id_masuk FROM barang_masuk LIMIT 1");
                $masuk_row = $masuk_result->fetch_assoc();
                $masuk_id = $masuk_row['id_masuk'];
                
                // Insert one test record to verify foreign keys work
                $test_sql = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk) VALUES ($laporan_id, $masuk_id)";
                executeSQL($conn, $test_sql, "Insert test record with proper references");
                
                // Step 4: Insert records for existing laporan_masuk entries
                $insert_sql = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk)
                              SELECT lm.id_laporan_masuk, bm.id_masuk
                              FROM laporan_masuk lm
                              JOIN barang_masuk bm ON DATE(lm.tanggal_laporan) = DATE(bm.tanggal_masuk)
                              WHERE NOT EXISTS (
                                  SELECT 1 FROM laporan_masuk_detail lmd 
                                  WHERE lmd.id_laporan = lm.id_laporan_masuk AND lmd.id_masuk = bm.id_masuk
                              )
                              LIMIT 10"; // Limit for safety
                
                executeSQL($conn, $insert_sql, "Insert records for existing laporan_masuk entries (limited to 10)");
                
                // Commit changes
                $conn->commit();
                echo "<p class='success'>All changes committed successfully!</p>";
                
                // Display updated status
                $detail_sql = "SELECT * FROM laporan_masuk_detail";
                $detail_result = $conn->query($detail_sql);
                echo "<h3>Updated Laporan Masuk Detail Records: " . $detail_result->num_rows . "</h3>";
                
                if ($detail_result->num_rows > 0) {
                    echo "<table><tr><th>id_detail</th><th>id_laporan</th><th>id_masuk</th></tr>";
                    while ($row = $detail_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id_detail'] . "</td>";
                        echo "<td>" . $row['id_laporan'] . "</td>";
                        echo "<td>" . $row['id_masuk'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
            } catch (Exception $e) {
                // Roll back on error
                $conn->rollback();
                echo "<p class='error'>Error occurred: " . $e->getMessage() . ". All changes have been rolled back.</p>";
            }
        } else {
            // Display current database status
            echo "<h2>Current Database Status</h2>";
            
            // Check for barang_masuk table
            $barang_masuk_check = $conn->query("SHOW TABLES LIKE 'barang_masuk'");
            if ($barang_masuk_check->num_rows > 0) {
                echo "<p class='success'>barang_masuk table exists</p>";
                
                // Show count
                $barang_count = $conn->query("SELECT COUNT(*) as count FROM barang_masuk");
                $barang_row = $barang_count->fetch_assoc();
                echo "<p>Total barang_masuk records: " . $barang_row['count'] . "</p>";
            } else {
                echo "<p class='error'>barang_masuk table does not exist!</p>";
            }
            
            // Check for laporan_masuk table
            $laporan_check = $conn->query("SHOW TABLES LIKE 'laporan_masuk'");
            if ($laporan_check->num_rows > 0) {
                echo "<p class='success'>laporan_masuk table exists</p>";
                
                // Show count
                $laporan_count = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk");
                $laporan_row = $laporan_count->fetch_assoc();
                echo "<p>Total laporan_masuk records: " . $laporan_row['count'] . "</p>";
            } else {
                echo "<p class='error'>laporan_masuk table does not exist!</p>";
            }
            
            // Check for laporan_masuk_detail table
            $detail_check = $conn->query("SHOW TABLES LIKE 'laporan_masuk_detail'");
            if ($detail_check->num_rows > 0) {
                echo "<p class='success'>laporan_masuk_detail table exists</p>";
                
                // Show structure
                echo "<h3>Current laporan_masuk_detail table structure:</h3>";
                $structure = $conn->query("DESCRIBE laporan_masuk_detail");
                echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                while ($row = $structure->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['Field'] . "</td>";
                    echo "<td>" . $row['Type'] . "</td>";
                    echo "<td>" . $row['Null'] . "</td>";
                    echo "<td>" . $row['Key'] . "</td>";
                    echo "<td>" . $row['Default'] . "</td>";
                    echo "<td>" . $row['Extra'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Check foreign keys
                echo "<h3>Foreign Keys:</h3>";
                $fk_query = "
                SELECT 
                    COLUMN_NAME, 
                    REFERENCED_TABLE_NAME, 
                    REFERENCED_COLUMN_NAME
                FROM 
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE 
                    TABLE_SCHEMA = DATABASE() AND
                    TABLE_NAME = 'laporan_masuk_detail' AND
                    REFERENCED_TABLE_NAME IS NOT NULL
                ";
                
                $fk_result = $conn->query($fk_query);
                if ($fk_result->num_rows > 0) {
                    echo "<table><tr><th>Column</th><th>References Table</th><th>References Column</th></tr>";
                    while ($row = $fk_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['COLUMN_NAME'] . "</td>";
                        echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
                        echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No foreign keys defined.</p>";
                }
                
                // Show count
                $detail_count = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk_detail");
                $detail_row = $detail_count->fetch_assoc();
                echo "<p>Total laporan_masuk_detail records: " . $detail_row['count'] . "</p>";
            } else {
                echo "<p class='error'>laporan_masuk_detail table does not exist!</p>";
            }
            
            echo "<p>This script will:</p>";
            echo "<ol>";
            echo "<li>Back up any existing laporan_masuk_detail data</li>";
            echo "<li>Drop the laporan_masuk_detail table if it exists</li>";
            echo "<li>Recreate the table with proper structure</li>";
            echo "<li>Insert test data to verify foreign keys work</li>";
            echo "<li>Insert records matching existing laporan_masuk and barang_masuk entries</li>";
            echo "</ol>";
            
            echo "<p><a href='?fix=true' class='action-btn'>Click here to run the fix</a></p>";
        }
        ?>
        
        <p><a href="laporan_masuk.php">Return to Laporan Masuk</a></p>
    </div>
</body>
</html> 