<?php
/**
 * InventoryIQ v2.0 — Notification Helpers
 * AI Rules §7.4, §7.5 — create_notification(), check_low_stock()
 */

/**
 * Create a notification record.
 *
 * @param mysqli $conn
 * @param int $company_id
 * @param int|null $recipient_warehouse_id NULL = broadcast to all warehouses
 * @param string $title
 * @param string $body
 * @param string $priority 'info', 'warning', 'critical'
 * @param string $type 'broadcast', 'alert', 'restock', 'system'
 * @param int|null $sender_user_id
 */
function create_notification($conn, $company_id, $recipient_warehouse_id, $title, $body, $priority, $type, $sender_user_id = null) {
    $stmt = mysqli_prepare($conn,
        'INSERT INTO notifications (company_id, sender_user_id, recipient_warehouse_id, title, body, priority, type)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    mysqli_stmt_bind_param($stmt, 'iiissss',
        $company_id,
        $sender_user_id,
        $recipient_warehouse_id,
        $title,
        $body,
        $priority,
        $type
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Get the low stock threshold for a warehouse.
 * Uses: COALESCE(warehouses.low_stock_override, companies.low_stock_default, 10)
 *
 * @param mysqli $conn
 * @param int $warehouse_id
 * @return int
 */
function get_low_stock_threshold($conn, $warehouse_id) {
    $stmt = mysqli_prepare($conn,
        'SELECT COALESCE(w.low_stock_override, c.low_stock_default, 10) AS threshold
         FROM warehouses w
         JOIN companies c ON c.company_id = w.company_id
         WHERE w.warehouse_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ? (int)$row['threshold'] : 10;
}

/**
 * Check if a product is below low stock threshold and create alert notification.
 *
 * @param mysqli $conn
 * @param int $product_id
 * @param int $warehouse_id
 */
function check_low_stock($conn, $product_id, $warehouse_id) {
    $threshold = get_low_stock_threshold($conn, $warehouse_id);

    $stmt = mysqli_prepare($conn,
        'SELECT p.product_name, p.stock_quantity, w.company_id
         FROM products p
         JOIN warehouses w ON w.warehouse_id = p.warehouse_id
         WHERE p.product_id = ? AND p.warehouse_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $product_id, $warehouse_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$product) return;

    if ((int)$product['stock_quantity'] <= $threshold && (int)$product['stock_quantity'] > 0) {
        create_notification(
            $conn,
            $product['company_id'],
            $warehouse_id,
            'Low Stock Alert: ' . $product['product_name'],
            $product['product_name'] . ' has only ' . $product['stock_quantity'] . ' units remaining (threshold: ' . $threshold . ').',
            'warning',
            'alert'
        );
    } elseif ((int)$product['stock_quantity'] === 0) {
        create_notification(
            $conn,
            $product['company_id'],
            $warehouse_id,
            'Out of Stock: ' . $product['product_name'],
            $product['product_name'] . ' is completely out of stock.',
            'critical',
            'alert'
        );
    }
}
?>
