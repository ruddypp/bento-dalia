<?php
// Script to run SQL commands from a file
require_once 'config/database.php';

if ($argc < 2) {
    echo "Usage: php run_sql.php <sql_file>\n";
    exit(1);
}

$sqlFile = $argv[1];

if (!file_exists($sqlFile)) {
    echo "Error: File '$sqlFile' does not exist.\n";
    exit(1);
}

echo "Reading SQL from file: $sqlFile\n";
$sql = file_get_contents($sqlFile);

if (!$sql) {
    echo "Error: Could not read SQL from file.\n";
    exit(1);
}

echo "Executing SQL...\n";

// Use mysqli multi_query instead for better statement handling
if (mysqli_multi_query($conn, $sql)) {
    $statementCount = 0;
    do {
        $statementCount++;
        echo "Statement $statementCount executed successfully.\n";
        
        // Free result
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    
    echo "All SQL statements executed successfully.\n";
} else {
    echo "Error executing SQL: " . mysqli_error($conn) . "\n";
    exit(1);
} 