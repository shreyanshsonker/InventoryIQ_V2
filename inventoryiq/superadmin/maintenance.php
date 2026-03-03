<?php
/**
 * InventoryIQ v2.0 — Maintenance Mode (Screen 17)
 * AI Rules §5 — SA only
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
check_role(['super_admin']);

$page_title = 'Maintenance';

// DB stats
$db_name = DB_NAME;
$table_stats = [];
$result = mysqli_query($conn, "SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
                                FROM information_schema.TABLES
                                WHERE TABLE_SCHEMA = '$db_name'
                                ORDER BY TABLE_NAME");
while ($row = mysqli_fetch_assoc($result)) { $table_stats[] = $row; }

// Orphan check
$orphan_products = mysqli_query($conn,
    'SELECT COUNT(p.product_id) AS cnt FROM products p LEFT JOIN warehouses w ON w.warehouse_id = p.warehouse_id WHERE w.warehouse_id IS NULL'
);
$orphan_count = (int)mysqli_fetch_assoc($orphan_products)['cnt'];

// Handle purge old logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purge_logs'])) {
    $days = max(30, (int)$_POST['days_to_keep']);
    $stmt = mysqli_prepare($conn, 'DELETE FROM audit_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)');
    mysqli_stmt_bind_param($stmt, 'i', $days);
    mysqli_stmt_execute($stmt);
    $deleted = mysqli_affected_rows($conn);
    mysqli_stmt_close($stmt);
    write_audit_log($conn, null, 'super_admin', null, null, 'MAINTENANCE', 'Purged ' . $deleted . ' audit log entries older than ' . $days . ' days');
    header('Location: /inventoryiq/superadmin/maintenance.php?purged=' . $deleted);
    exit;
}

require_once '../includes/header.php';
?>

<div class="mb-6">
  <h1 style="font-size:28px;">Maintenance Tools</h1>
  <p class="label" style="margin-top:4px;">System health & cleanup utilities</p>
</div>

<?php if (isset($_GET['purged'])): ?>
<div class="alert-banner alert-success mb-6">
  <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span>Purged <?php echo (int)$_GET['purged']; ?> old audit log entries.</span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);">

  <!-- Database Tables -->
  <div class="glass-card-static">
    <h3 class="section-title mb-4">
      <i data-lucide="database" style="width:18px;height:18px;display:inline;"></i> Database Tables
    </h3>
    <table class="data-table">
      <thead>
        <tr><th>Table</th><th>Rows</th><th>Size</th></tr>
      </thead>
      <tbody>
      <?php foreach ($table_stats as $ts): ?>
        <tr>
          <td><span class="mono" style="font-size:12px;"><?php echo htmlspecialchars($ts['TABLE_NAME'], ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td><?php echo number_format((int)$ts['TABLE_ROWS']); ?></td>
          <td style="font-size:12px;"><?php echo number_format(((int)$ts['DATA_LENGTH'] + (int)$ts['INDEX_LENGTH']) / 1024, 1); ?> KB</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div>
    <!-- System Health -->
    <div class="glass-card-static mb-6">
      <h3 class="section-title mb-4">
        <i data-lucide="activity" style="width:18px;height:18px;display:inline;"></i> System Health
      </h3>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="flex-between">
          <span style="color:var(--text-label);">Database</span>
          <span class="badge badge-active">Connected</span>
        </div>
        <div class="flex-between">
          <span style="color:var(--text-label);">PHP Version</span>
          <span class="mono" style="font-size:12px;"><?php echo phpversion(); ?></span>
        </div>
        <div class="flex-between">
          <span style="color:var(--text-label);">Orphan Products</span>
          <span class="badge <?php echo $orphan_count > 0 ? 'badge-warning' : 'badge-active'; ?>">
            <?php echo $orphan_count; ?> found
          </span>
        </div>
        <div class="flex-between">
          <span style="color:var(--text-label);">Server Time</span>
          <span class="mono" style="font-size:12px;"><?php echo date('Y-m-d H:i:s'); ?></span>
        </div>
      </div>
    </div>

    <!-- Purge Logs -->
    <div class="glass-card-static">
      <h3 class="section-title mb-4">
        <i data-lucide="trash" style="width:18px;height:18px;display:inline;"></i> Purge Audit Logs
      </h3>
      <form method="POST" style="display:flex;gap:12px;align-items:flex-end;">
        <div class="form-group" style="flex:1;">
          <label class="form-label">Keep entries from last (days)</label>
          <input type="number" name="days_to_keep" class="glass-input" value="90" min="30" max="365">
        </div>
        <button type="submit" name="purge_logs" value="1" class="btn btn-danger" style="height:44px;"
                onclick="return confirm('This will permanently delete old audit entries. Continue?')">
          Purge
        </button>
      </form>
    </div>
  </div>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
