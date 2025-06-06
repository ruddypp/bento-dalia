<?php
// Database connection settings
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'inventori_db3';

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Add SQL script to update user roles
$sql_update_user_roles = "
-- Update user roles based on username
UPDATE users SET role_id = 1 WHERE username = 'admin'; -- Admin role
UPDATE users SET role_id = 3 WHERE username = 'kasir'; -- Kasir role
UPDATE users SET role_id = 4 WHERE username = 'headproduksi'; -- Head Produksi role
UPDATE users SET role_id = 2 WHERE username = 'purchasing'; -- Purchasing role
UPDATE users SET role_id = 5 WHERE username = 'crew'; -- Crew role
";

// Uncomment and run this once to update user roles
// mysqli_multi_query($conn, $sql_update_user_roles);
?> 