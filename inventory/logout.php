<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Catat log aktivitas jika user sudah login
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'Logout dari sistem');
}

// Hapus semua session
$_SESSION = array();

// Destroy session
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit();
?> 