<?php
// Include database connection
require_once 'config/database.php';

// Function to display table structure
function displayTableStructure($conn, $tableName) {
    echo "<h3>Structure for table: $tableName</h3>";
    
    $result = $conn->query("DESCRIBE $tableName");
    
    if (!$result) {
        echo "Error: " . $conn->error;
        return;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
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
}

// Function to display foreign keys
function displayForeignKeys($conn, $tableName) {
    echo "<h3>Foreign Keys for table: $tableName</h3>";
    
    $query = "
    SELECT 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME
    FROM 
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE 
        TABLE_SCHEMA = DATABASE() AND
        TABLE_NAME = '$tableName' AND
        REFERENCED_TABLE_NAME IS NOT NULL
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        echo "Error: " . $conn->error;
        return;
    }
    
    if ($result->num_rows == 0) {
        echo "No foreign keys found.";
        return;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Column</th><th>References Table</th><th>References Column</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['COLUMN_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Function to show sample data
function displaySampleData($conn, $tableName, $limit = 5) {
    echo "<h3>Sample data for table: $tableName (max $limit rows)</h3>";
    
    $result = $conn->query("SELECT * FROM $tableName LIMIT $limit");
    
    if (!$result) {
        echo "Error: " . $conn->error;
        return;
    }
    
    if ($result->num_rows == 0) {
        echo "No data found.";
        return;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    
    // Table header
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>" . $key . "</th>";
            }
            echo "</tr>";
            $first = false;
        }
        
        // Table data
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . $value . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Structure Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #333; }
        h3 { color: #666; margin-top: 20px; }
        table { border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f2f2f2; }
        td, th { padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h2>Database Structure Check</h2>
    
    <?php
    // Check barang_masuk table
    displayTableStructure($conn, 'barang_masuk');
    displayForeignKeys($conn, 'barang_masuk');
    displaySampleData($conn, 'barang_masuk');
    
    // Check laporan_masuk table
    displayTableStructure($conn, 'laporan_masuk');
    displayForeignKeys($conn, 'laporan_masuk');
    displaySampleData($conn, 'laporan_masuk');
    
    // Check laporan_masuk_detail table
    echo "<h3>Does laporan_masuk_detail table exist?</h3>";
    $result = $conn->query("SHOW TABLES LIKE 'laporan_masuk_detail'");
    if ($result->num_rows > 0) {
        echo "Yes, table exists.";
        displayTableStructure($conn, 'laporan_masuk_detail');
        displayForeignKeys($conn, 'laporan_masuk_detail');
        displaySampleData($conn, 'laporan_masuk_detail');
    } else {
        echo "No, table does not exist.";
    }
    ?>
</body>
</html> 