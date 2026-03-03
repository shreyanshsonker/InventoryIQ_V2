<?php
/**
 * InventoryIQ v2.0 — Audit Log Helper
 * AI Rules §2.11 — write_audit_log() function
 */

/**
 * Write an entry to the audit_log table.
 *
 * @param mysqli $conn       Database connection
 * @param int|null $user_id  User performing the action (NULL for SA)
 * @param string $role       Role string copy
 * @param int|null $company_id Company ID (NULL for SA)
 * @param int|null $warehouse_id Warehouse ID (NULL for admins)
 * @param string $action_type One of the defined action_type values
 * @param string $detail     Human-readable description
 */
function write_audit_log($conn, $user_id, $role, $company_id, $warehouse_id, $action_type, $detail) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

    $stmt = mysqli_prepare($conn,
        'INSERT INTO audit_log (user_id, role, company_id, warehouse_id, action_type, detail, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    mysqli_stmt_bind_param($stmt, 'isiisss',
        $user_id,
        $role,
        $company_id,
        $warehouse_id,
        $action_type,
        $detail,
        $ip
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
