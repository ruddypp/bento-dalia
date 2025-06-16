<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access. Please login first.";
    exit;
}

// Function to print formatted data
function printData($data, $title = '') {
    echo "<h3>$title</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

// Function to print errors
function printError($message) {
    echo "<div style='color:red; padding:10px; margin:10px 0; border:1px solid red;'>";
    echo $message;
    echo "</div>";
}

// Function to print success
function printSuccess($message) {
    echo "<div style='color:green; padding:10px; margin:10px 0; border:1px solid green;'>";
    echo $message;
    echo "</div>";
}

// Check database tables
$tables_to_check = ['barang_masuk', 'laporan_masuk', 'laporan_masuk_detail'];
$table_info = [];

foreach ($tables_to_check as $table) {
    $exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
    $table_info[$table] = [
        'exists' => $exists,
        'count' => $exists ? $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'] : 0
    ];
}

// Get one record from each table for testing
$laporan_masuk_row = null;
$barang_masuk_row = null;

if ($table_info['laporan_masuk']['count'] > 0) {
    $laporan_masuk_row = $conn->query("SELECT * FROM laporan_masuk LIMIT 1")->fetch_assoc();
}

if ($table_info['barang_masuk']['count'] > 0) {
    $barang_masuk_row = $conn->query("SELECT * FROM barang_masuk LIMIT 1")->fetch_assoc();
}

// Test case variables
$test_success = false;
$test_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_insert'])) {
    $id_laporan = $_POST['id_laporan'] ?? '';
    $id_masuk = $_POST['id_masuk'] ?? '';
    
    if (empty($id_laporan) || empty($id_masuk)) {
        $test_error = "Please select both a laporan and a barang masuk entry";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Drop test table if it exists
            $conn->query("DROP TABLE IF EXISTS test_laporan_masuk_detail");
            
            // Create test table
            $create_table = "CREATE TABLE test_laporan_masuk_detail (
                id_detail INT NOT NULL AUTO_INCREMENT,
                id_laporan INT DEFAULT NULL,
                id_masuk INT DEFAULT NULL,
                PRIMARY KEY (id_detail),
                KEY id_laporan (id_laporan),
                KEY id_masuk (id_masuk),
                CONSTRAINT test_laporan_masuk_detail_ibfk_1 FOREIGN KEY (id_laporan) REFERENCES laporan_masuk (id_laporan_masuk) ON DELETE CASCADE,
                CONSTRAINT test_laporan_masuk_detail_ibfk_2 FOREIGN KEY (id_masuk) REFERENCES barang_masuk (id_masuk) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $conn->query($create_table);
            
            // Try to insert
            $query = "INSERT INTO test_laporan_masuk_detail (id_laporan, id_masuk) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("ii", $id_laporan, $id_masuk);
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Error executing statement: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Success!
            $test_success = true;
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $test_error = $e->getMessage();
        }
    }
}

// Get all laporan_masuk records for dropdown
$all_laporan = [];
if ($table_info['laporan_masuk']['count'] > 0) {
    $result = $conn->query("SELECT id_laporan_masuk, tanggal_laporan FROM laporan_masuk ORDER BY id_laporan_masuk DESC");
    while ($row = $result->fetch_assoc()) {
        $all_laporan[] = $row;
    }
}

// Get all barang_masuk records for dropdown
$all_barang_masuk = [];
if ($table_info['barang_masuk']['count'] > 0) {
    $result = $conn->query("SELECT bm.id_masuk, b.nama_barang, bm.tanggal_masuk, bm.qty_masuk 
                           FROM barang_masuk bm 
                           JOIN barang b ON bm.id_barang = b.id_barang 
                           ORDER BY bm.id_masuk DESC LIMIT 20");
    while ($row = $result->fetch_assoc()) {
        $all_barang_masuk[] = $row;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Insert Query</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        pre { background-color: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .container { max-width: 1000px; margin: 0 auto; }
        .btn { 
            display: inline-block; 
            background-color: #4CAF50; 
            color: white; 
            padding: 10px 15px; 
            text-decoration: none; 
            border-radius: 4px; 
            border: none;
            cursor: pointer;
        }
        .btn:hover { background-color: #45a049; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        select, input { padding: 8px; border-radius: 4px; border: 1px solid #ddd; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Insert Query</h1>
        
        <h2>Database Status</h2>
        <table>
            <tr>
                <th>Table</th>
                <th>Exists</th>
                <th>Records</th>
            </tr>
            <?php foreach ($table_info as $table => $info): ?>
            <tr>
                <td><?= $table ?></td>
                <td><?= $info['exists'] ? '✅ Yes' : '❌ No' ?></td>
                <td><?= $info['count'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <?php if ($test_success): ?>
            <?php printSuccess("Insert test successful! The foreign key constraints are working correctly."); ?>
        <?php elseif (!empty($test_error)): ?>
            <?php printError("Insert test failed: $test_error"); ?>
        <?php endif; ?>
        
        <h2>Test Insertion</h2>
        <?php if (count($all_laporan) > 0 && count($all_barang_masuk) > 0): ?>
            <form method="post" action="">
                <div style="margin-bottom: 15px;">
                    <label for="id_laporan"><strong>Select Laporan Masuk:</strong></label>
                    <select name="id_laporan" id="id_laporan">
                        <?php foreach ($all_laporan as $laporan): ?>
                            <option value="<?= $laporan['id_laporan_masuk'] ?>">
                                Laporan #<?= $laporan['id_laporan_masuk'] ?> (<?= $laporan['tanggal_laporan'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="id_masuk"><strong>Select Barang Masuk:</strong></label>
                    <select name="id_masuk" id="id_masuk">
                        <?php foreach ($all_barang_masuk as $barang): ?>
                            <option value="<?= $barang['id_masuk'] ?>">
                                #<?= $barang['id_masuk'] ?> - <?= $barang['nama_barang'] ?> 
                                (<?= $barang['qty_masuk'] ?> - <?= $barang['tanggal_masuk'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="test_insert" class="btn">Test Insert</button>
            </form>
        <?php else: ?>
            <p>Cannot test insertion because either laporan_masuk or barang_masuk tables are empty.</p>
        <?php endif; ?>
        
        <h2>Debug Information</h2>
        
        <?php if ($laporan_masuk_row): ?>
            <?php printData($laporan_masuk_row, 'Sample laporan_masuk record:'); ?>
        <?php endif; ?>
        
        <?php if ($barang_masuk_row): ?>
            <?php printData($barang_masuk_row, 'Sample barang_masuk record:'); ?>
        <?php endif; ?>
        
        <p><a href="laporan_masuk.php">Return to Laporan Masuk</a></p>
    </div>
</body>
</html> 