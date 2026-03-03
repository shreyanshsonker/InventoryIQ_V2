<?php
/**
 * InventoryIQ v2.0 — Session Validation Middleware
 * AI Rules §4.2 — Include at the top of every protected page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin')) {
    header('Location: /inventoryiq/login.php');
    exit;
}

// Check 30-minute inactivity timeout
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > 1800) {
        // Session expired
        session_unset();
        session_destroy();
        header('Location: /inventoryiq/login.php?timeout=1');
        exit;
    }
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

// Check maintenance mode (blocks company users, SA unaffected)
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'super_admin' && isset($conn)) {
    // Check if maintenance mode is active (stored in super_admin table or a config)
    // For simplicity, we check companies.status for the current company
    if (isset($_SESSION['company_id'])) {
        $maint_stmt = mysqli_prepare($conn, 'SELECT status FROM companies WHERE company_id = ?');
        mysqli_stmt_bind_param($maint_stmt, 'i', $_SESSION['company_id']);
        mysqli_stmt_execute($maint_stmt);
        $maint_result = mysqli_stmt_get_result($maint_stmt);
        $company_row = mysqli_fetch_assoc($maint_result);
        mysqli_stmt_close($maint_stmt);

        if ($company_row && $company_row['status'] === 'suspended') {
            session_unset();
            session_destroy();
            header('Location: /inventoryiq/login.php?suspended=1');
            exit;
        }
    }
}

/**
 * Role-based access control check.
 * Call this after including check.php:
 *   $allowed_roles = ['company_admin', 'wh_manager'];
 *   check_role($allowed_roles);
 */
function check_role($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: /inventoryiq/403.php');
        exit;
    }
}
?>
