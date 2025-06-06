<?php
require_once 'config/database.php';

// SQL script to update user roles
$sql_update_user_roles = "
-- Update user roles based on username
UPDATE users SET role_id = 1 WHERE username = 'admin'; -- Admin role
UPDATE users SET role_id = 3 WHERE username = 'kasir'; -- Kasir role
UPDATE users SET role_id = 4 WHERE username = 'headproduksi'; -- Head Produksi role
UPDATE users SET role_id = 2 WHERE username = 'purchasing'; -- Purchasing role
UPDATE users SET role_id = 5 WHERE username = 'crew'; -- Crew role
";

// Run the SQL script
if (mysqli_multi_query($conn, $sql_update_user_roles)) {
    echo "User roles updated successfully!";
} else {
    echo "Error updating user roles: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?> 