<?php
/**
 * InventoryIQ v2.0 — Export CSV Report
 * AI Rules §5 — Manager+ roles
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
check_role(['company_admin', 'wh_manager']);

$warehouse_id = $_SESSION['warehouse_id'];
$company_id = $_SESSION['company_id'];
$role = $_SESSION['role'];
$page_title = 'Export Reports';

// Handle CSV download
if (isset($_GET['download'])) {
    $type = $_GET['download'];

    if ($type === 'inventory') {
        // Inventory report
        if ($role === 'company_admin') {
            $stmt = mysqli_prepare($conn,
                'SELECT p.product_name, p.sku, p.price, p.stock_quantity, c.category_name, w.warehouse_name, p.updated_at
                 FROM products p
                 JOIN warehouses w ON w.warehouse_id = p.warehouse_id
                 LEFT JOIN categories c ON c.category_id = p.category_id
                 WHERE w.company_id = ?
                 ORDER BY w.warehouse_name, p.product_name'
            );
            mysqli_stmt_bind_param($stmt, 'i', $company_id);
        } else {
            $stmt = mysqli_prepare($conn,
                'SELECT p.product_name, p.sku, p.price, p.stock_quantity, c.category_name, p.updated_at
                 FROM products p
                 LEFT JOIN categories c ON c.category_id = p.category_id
                 WHERE p.warehouse_id = ?
                 ORDER BY p.product_name'
            );
            mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $filename = 'inventory_report_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $role === 'company_admin'
            ? ['Product Name', 'SKU', 'Price', 'Stock Qty', 'Category', 'Warehouse', 'Last Updated']
            : ['Product Name', 'SKU', 'Price', 'Stock Qty', 'Category', 'Last Updated']
        );
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        mysqli_stmt_close($stmt);

        write_audit_log($conn, $_SESSION['user_id'], $role, $company_id, $warehouse_id,
            'EXPORT_CSV', 'Exported inventory CSV');
        exit;

    } elseif ($type === 'low_stock') {
        require_once '../includes/notify.php';
        $th = $warehouse_id ? get_low_stock_threshold($conn, $warehouse_id) : 10;

        if ($role === 'company_admin') {
            $stmt = mysqli_prepare($conn,
                'SELECT p.product_name, p.sku, p.stock_quantity, w.warehouse_name
                 FROM products p JOIN warehouses w ON w.warehouse_id = p.warehouse_id
                 WHERE w.company_id = ? AND p.stock_quantity <= ?
                 ORDER BY p.stock_quantity ASC'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $company_id, $th);
        } else {
            $stmt = mysqli_prepare($conn,
                'SELECT p.product_name, p.sku, p.stock_quantity
                 FROM products p WHERE p.warehouse_id = ? AND p.stock_quantity <= ?
                 ORDER BY p.stock_quantity ASC'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $warehouse_id, $th);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $filename = 'low_stock_report_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $role === 'company_admin'
            ? ['Product Name', 'SKU', 'Stock Qty', 'Warehouse']
            : ['Product Name', 'SKU', 'Stock Qty']
        );
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        mysqli_stmt_close($stmt);

        write_audit_log($conn, $_SESSION['user_id'], $role, $company_id, $warehouse_id,
            'EXPORT_CSV', 'Exported low stock CSV');
        exit;
    }
}

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Export Reports</h1>
</div>

<div class="grid-2" style="max-width:800px;">
  <!-- Inventory Report -->
  <div class="glass-card" style="text-align:center;padding:40px 24px;">
    <i data-lucide="file-spreadsheet" style="width:40px;height:40px;color:var(--accent-teal);display:block;margin:0 auto 12px;filter:drop-shadow(0 0 12px rgba(14,165,176,0.5));"></i>
    <h3 style="color:var(--text-primary);font-size:18px;margin-bottom:8px;">Inventory Report</h3>
    <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px;">Full product list with prices, stock levels, and categories</p>
    <a href="?download=inventory" class="btn btn-secondary btn-lg btn-full">
      <i data-lucide="download" style="width:18px;height:18px;"></i> Download CSV
    </a>
  </div>

  <!-- Low Stock Report -->
  <div class="glass-card" style="text-align:center;padding:40px 24px;">
    <i data-lucide="alert-triangle" style="width:40px;height:40px;color:var(--accent-amber);display:block;margin:0 auto 12px;filter:drop-shadow(0 0 12px rgba(245,158,11,0.5));"></i>
    <h3 style="color:var(--text-primary);font-size:18px;margin-bottom:8px;">Low Stock Report</h3>
    <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px;">Products at or below low-stock threshold</p>
    <a href="?download=low_stock" class="btn btn-primary btn-lg btn-full">
      <i data-lucide="download" style="width:18px;height:18px;"></i> Download CSV
    </a>
  </div>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
