<?php
/**
 * Role Permission Check
 * 
 * Include this file at the beginning of each page to check if the user has permission to access it.
 * Usage: require_once 'role_permission_check.php';
 */

require_once 'config/functions.php';

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Halaman yang selalu diizinkan untuk semua user
$always_allowed = ['index.php', 'profile.php', 'logout.php'];

// Jika halaman selalu diizinkan, set permission ke 'full' untuk admin, 'view' untuk lainnya
if (in_array($current_page, $always_allowed)) {
    if ($_SESSION['user_role'] === 'admin') {
        $permission = 'full';
    } else if ($_SESSION['user_role'] === 'crew') {
        // Crew selalu view-only kecuali untuk profile.php
        $permission = ($current_page === 'profile.php') ? 'full' : 'view';
    } else {
        $permission = 'view';
        
        // Khusus untuk profile.php, semua user punya akses full
        if ($current_page === 'profile.php') {
            $permission = 'full';
        }
    }
} else {
    // Check if user has permission to access this page
    $permission = checkPermission($current_page);
}

// If permission is 'view', set a global variable to disable edit/delete functionality
if ($permission === 'view') {
    $VIEW_ONLY = true;
    $EDIT_ALLOWED = false;
    $DELETE_ALLOWED = false;
} elseif ($permission === 'edit') {
    $VIEW_ONLY = false;
    $EDIT_ALLOWED = true;
    $DELETE_ALLOWED = false;
} elseif ($permission === 'full') {
    $VIEW_ONLY = false;
    $EDIT_ALLOWED = true;
    $DELETE_ALLOWED = true;
} else {
    // If no permission, redirect to dashboard with error message
    header("Location: index.php?error=unauthorized&page=" . urlencode($current_page));
    exit();
}
?> 