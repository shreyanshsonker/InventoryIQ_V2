<?php
/**
 * InventoryIQ v2.0 — Logout
 * AI Rules §4 — Destroy session, clear cookies, redirect
 */
session_start();
require_once 'config/db.php';
require_once 'includes/audit.php';

// Write audit log before destroying session
if (isset($_SESSION['user_id'])) {
    write_audit_log($conn, $_SESSION['user_id'], $_SESSION['role'],
        isset($_SESSION['company_id']) ? $_SESSION['company_id'] : null,
        isset($_SESSION['warehouse_id']) ? $_SESSION['warehouse_id'] : null,
        'LOGOUT', 'User logged out'
    );
}

// Clear remember-me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/inventoryiq/', '', false, true);
    setcookie('remember_user', '', time() - 3600, '/inventoryiq/', '', false, true);
}

$is_sa = isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';

session_unset();
session_destroy();
mysqli_close($conn);

if ($is_sa) {
    header('Location: /inventoryiq/superadmin/login.php');
} else {
    header('Location: /inventoryiq/login.php');
}
exit;
?>
