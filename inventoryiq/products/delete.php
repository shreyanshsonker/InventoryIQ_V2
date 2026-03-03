<?php
/**
 * InventoryIQ v2.0 — Delete Product (POST only)
 * AI Rules §5 — Manager + Staff
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
check_role(['wh_manager', 'wh_staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /inventoryiq/products/view.php');
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$warehouse_id = $_SESSION['warehouse_id'];

if ($product_id <= 0) {
    header('Location: /inventoryiq/products/view.php');
    exit;
}

// Verify ownership
$stmt = mysqli_prepare($conn, 'SELECT product_name, primary_image FROM products WHERE product_id = ? AND warehouse_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $product_id, $warehouse_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) {
    header('Location: /inventoryiq/403.php');
    exit;
}

// Delete image file
if (!empty($product['primary_image'])) {
    $img_path = dirname(__DIR__) . '/uploads/products/' . $product['primary_image'];
    if (file_exists($img_path)) unlink($img_path);
}

// Delete product (cascades to product_images via FK)
$del = mysqli_prepare($conn, 'DELETE FROM products WHERE product_id = ? AND warehouse_id = ?');
mysqli_stmt_bind_param($del, 'ii', $product_id, $warehouse_id);
mysqli_stmt_execute($del);
mysqli_stmt_close($del);

write_audit_log($conn, $_SESSION['user_id'], $_SESSION['role'], $_SESSION['company_id'], $warehouse_id,
    'PRODUCT_DELETE', 'Deleted product: ' . $product['product_name'] . ' (ID:' . $product_id . ')');

mysqli_close($conn);
header('Location: /inventoryiq/products/view.php?success=1');
exit;
?>
