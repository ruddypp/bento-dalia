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

// Check data integrity before trying to repair
function checkIntegrity($conn) {
    echo "<h2>Checking Data Integrity</h2>";
    
    // Check if we have data in laporan_masuk
    $laporan_count = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk");
    $laporan_count_row = $laporan_count->fetch_assoc();
    echo "<p>Laporan Masuk records: " . $laporan_count_row['count'] . "</p>";
    
    // Check if we have data in barang_masuk
    $barang_count = $conn->query("SELECT COUNT(*) as count FROM barang_masuk");
    $barang_count_row = $barang_count->fetch_assoc();
    echo "<p>Barang Masuk records: " . $barang_count_row['count'] . "</p>";
    
    if ($laporan_count_row['count'] == 0) {
        echo "<p style='color:red'>ERROR: No records in laporan_masuk table. Need at least one record to fix the issue.</p>";
        return false;
    }
    
    if ($barang_count_row['count'] == 0) {
        echo "<p style='color:red'>ERROR: No records in barang_masuk table. Need at least one record to fix the issue.</p>";
        return false;
    }
    
    return true;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Laporan Masuk Detail</title>
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
        .warning {
            color: orange;
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
        <h1>Fix Laporan Masuk Detail</h1>
        
        <?php
        // Check if we should run the fix
        if (isset($_GET['action']) && $_GET['action'] === 'fix') {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // First check integrity
                if (!checkIntegrity($conn)) {
                    throw new Exception("Cannot proceed: Data integrity issues found");
                }
                
                // Step 1: Check if the table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'laporan_masuk_detail'");
                if ($table_check->num_rows > 0) {
                    echo "<p class='success'>Step 1: laporan_masuk_detail table exists</p>";
                    
                    // Step 2: Check for column issues
                    $column_check = $conn->query("DESCRIBE laporan_masuk_detail");
                    $has_id_laporan = false;
                    $has_id_laporan_masuk = false;
                    
                    while ($row = $column_check->fetch_assoc()) {
                        if ($row['Field'] === 'id_laporan') $has_id_laporan = true;
                        if ($row['Field'] === 'id_laporan_masuk') $has_id_laporan_masuk = true;
                    }
                    
                    if ($has_id_laporan_masuk) {
                        echo "<p class='warning'>Found column inconsistency: Table uses id_laporan_masuk instead of id_laporan</p>";
                        
                        // Create backup
                        executeSQL($conn, "CREATE TABLE IF NOT EXISTS laporan_masuk_detail_backup LIKE laporan_masuk_detail", "Creating backup table");
                        executeSQL($conn, "INSERT INTO laporan_masuk_detail_backup SELECT * FROM laporan_masuk_detail", "Backing up data");
                        
                        // Drop table to recreate
                        executeSQL($conn, "DROP TABLE laporan_masuk_detail", "Dropping inconsistent table");
                    } else if (!$has_id_laporan) {
                        echo "<p class='error'>Table lacks required id_laporan column. Will recreate.</p>";
                        executeSQL($conn, "DROP TABLE laporan_masuk_detail", "Dropping table with missing columns");
                    }
                }
                
                // Step 3: Create the table with correct structure
                $create_table = "CREATE TABLE IF NOT EXISTS `laporan_masuk_detail` (
                  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
                  `id_laporan` int(11) DEFAULT NULL,
                  `id_masuk` int(11) DEFAULT NULL,
                  PRIMARY KEY (`id_detail`),
                  KEY `id_laporan` (`id_laporan`),
                  KEY `id_masuk` (`id_masuk`),
                  CONSTRAINT `laporan_masuk_detail_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_masuk` (`id_laporan_masuk`) ON DELETE CASCADE,
                  CONSTRAINT `laporan_masuk_detail_ibfk_2` FOREIGN KEY (`id_masuk`) REFERENCES `barang_masuk` (`id_masuk`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
                
                if (executeSQL($conn, $create_table, "Step 3: Creating table with correct structure")) {
                    echo "<p class='success'>Table structure created successfully</p>";
                } else {
                    throw new Exception("Failed to create table structure");
                }
                
                // Step 4: Create a test entry to verify foreign keys
                echo "<h3>Step 4: Creating a test entry</h3>";
                
                // Get first laporan_masuk record
                $laporan_query = "SELECT id_laporan_masuk FROM laporan_masuk LIMIT 1";
                $laporan_result = $conn->query($laporan_query);
                $laporan_row = $laporan_result->fetch_assoc();
                $laporan_id = $laporan_row['id_laporan_masuk'];
                
                // Get first barang_masuk record
                $barang_query = "SELECT id_masuk FROM barang_masuk LIMIT 1";
                $barang_result = $conn->query($barang_query);
                $barang_row = $barang_result->fetch_assoc();
                $barang_id = $barang_row['id_masuk'];
                
                echo "<p>Using laporan_masuk ID: $laporan_id and barang_masuk ID: $barang_id</p>";
                
                $test_insert = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk) VALUES ($laporan_id, $barang_id)";
                
                if (executeSQL($conn, $test_insert, "Creating test record")) {
                    echo "<p class='success'>Test record created successfully. Foreign key constraints are working!</p>";
                } else {
                    throw new Exception("Failed to create test record. Foreign key constraints may be failing.");
                }
                
                // Step 5: Link existing laporan_masuk records with matching barang_masuk records
                echo "<h3>Step 5: Linking existing records</h3>";
                
                $link_records = "INSERT INTO laporan_masuk_detail (id_laporan, id_masuk)
                                SELECT lm.id_laporan_masuk, bm.id_masuk
                                FROM laporan_masuk lm
                                JOIN barang_masuk bm ON DATE(lm.tanggal_laporan) = DATE(bm.tanggal_masuk)
                                WHERE NOT EXISTS (
                                    SELECT 1 FROM laporan_masuk_detail lmd 
                                    WHERE lmd.id_laporan = lm.id_laporan_masuk AND lmd.id_masuk = bm.id_masuk
                                )
                                LIMIT 20"; // Limit for safety
                
                if (executeSQL($conn, $link_records, "Linking records by date")) {
                    echo "<p class='success'>Records linked successfully</p>";
                } else {
                    echo "<p class='warning'>No additional records linked or an error occurred</p>";
                }
                
                // Commit transaction
                $conn->commit();
                
                // Show final status
                $final_count = $conn->query("SELECT COUNT(*) as count FROM laporan_masuk_detail");
                $final_row = $final_count->fetch_assoc();
                
                echo "<h2>Repair Complete</h2>";
                echo "<p class='success'>Total records in laporan_masuk_detail: " . $final_row['count'] . "</p>";
                
                // Show the records
                $records = $conn->query("SELECT * FROM laporan_masuk_detail LIMIT 10");
                echo "<h3>Sample Records (up to 10)</h3>";
                echo "<table>";
                echo "<tr><th>ID</th><th>id_laporan</th><th>id_masuk</th></tr>";
                
                while ($row = $records->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id_detail'] . "</td>";
                    echo "<td>" . $row['id_laporan'] . "</td>";
                    echo "<td>" . $row['id_masuk'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
            } catch (Exception $e) {
                // Roll back on error
                $conn->rollback();
                echo "<p class='error'>Error occurred: " . $e->getMessage() . ". All changes have been rolled back.</p>";
            }
        } else {
            // Show current status
            echo "<h2>Current Database Status</h2>";
            
            // Check if tables exist
            $tables = ['laporan_masuk', 'barang_masuk', 'laporan_masuk_detail'];
            echo "<table>";
            echo "<tr><th>Table</th><th>Exists</th><th>Record Count</th></tr>";
            
            foreach ($tables as $table) {
                $exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
                $count = $exists ? $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'] : 0;
                
                echo "<tr>";
                echo "<td>$table</td>";
                echo "<td>" . ($exists ? '✅ Yes' : '❌ No') . "</td>";
                echo "<td>$count</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Check for issues
            $has_issues = false;
            
            // Check if laporan_masuk_detail exists
            $detail_check = $conn->query("SHOW TABLES LIKE 'laporan_masuk_detail'");
            if ($detail_check->num_rows == 0) {
                echo "<p class='error'>⚠️ laporan_masuk_detail table does not exist</p>";
                $has_issues = true;
            } else {
                // Check for column name inconsistencies
                $column_check = $conn->query("DESCRIBE laporan_masuk_detail");
                $has_id_laporan = false;
                $has_id_laporan_masuk = false;
                
                while ($row = $column_check->fetch_assoc()) {
                    if ($row['Field'] === 'id_laporan') $has_id_laporan = true;
                    if ($row['Field'] === 'id_laporan_masuk') $has_id_laporan_masuk = true;
                }
                
                if ($has_id_laporan_masuk) {
                    echo "<p class='error'>⚠️ Column inconsistency: Table uses id_laporan_masuk instead of id_laporan</p>";
                    $has_issues = true;
                }
                
                if (!$has_id_laporan && !$has_id_laporan_masuk) {
                    echo "<p class='error'>⚠️ Required column missing: Neither id_laporan nor id_laporan_masuk exists</p>";
                    $has_issues = true;
                }
            }
            
            // Display fix button if issues found
            if ($has_issues) {
                echo "<p>Issues detected with the laporan_masuk_detail table. This fix will:</p>";
                echo "<ol>";
                echo "<li>Backup any existing data</li>";
                echo "<li>Drop and recreate the laporan_masuk_detail table with the correct structure</li>";
                echo "<li>Create a test record to verify foreign key constraints</li>";
                echo "<li>Link existing laporan_masuk records with matching barang_masuk records</li>";
                echo "</ol>";
                
                echo "<p><a href='?action=fix' class='action-btn'>Fix Issues Now</a></p>";
            } else {
                echo "<p class='success'>No issues detected with the database structure.</p>";
            }
        }
        ?>
        
        <p><a href="laporan_masuk.php">Return to Laporan Masuk</a></p>
    </div>
</body>
</html> 