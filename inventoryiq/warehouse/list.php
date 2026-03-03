<?php
/**
 * InventoryIQ v2.0 — Warehouse List (Screen 05)
 * AI Rules §5 — Company Admin only
 */
require_once '../config/db.php';
require_once '../auth/check.php';
check_role(['company_admin']);

$page_title = 'Warehouses';
$company_id = $_SESSION['company_id'];

// Fetch warehouses with product counts
$stmt = mysqli_prepare($conn,
    'SELECT w.warehouse_id, w.warehouse_name, w.handle, w.location, w.contact_person,
            w.capacity_limit, w.priority_rank, w.status, w.created_at,
            COUNT(p.product_id) AS product_count,
            COALESCE(SUM(p.stock_quantity), 0) AS total_stock
     FROM warehouses w
     LEFT JOIN products p ON p.warehouse_id = w.warehouse_id
     WHERE w.company_id = ?
     GROUP BY w.warehouse_id
     ORDER BY w.priority_rank ASC, w.warehouse_name ASC'
);
mysqli_stmt_bind_param($stmt, 'i', $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$warehouses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $warehouses[] = $row;
}
mysqli_stmt_close($stmt);

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="flex-between mb-6">
  <div>
    <h1 style="font-size:28px;">Warehouses</h1>
    <p class="label" style="margin-top:4px;"><?php echo count($warehouses); ?> warehouse(s) registered</p>
  </div>
  <a href="/inventoryiq/warehouse/add.php" class="btn btn-primary">
    <i data-lucide="plus" style="width:18px;height:18px;"></i>
    Add Warehouse
  </a>
</div>

<?php if (empty($warehouses)): ?>
<!-- Empty State -->
<div class="glass-card-static" style="text-align:center;padding:60px;">
  <i data-lucide="warehouse" style="width:64px;height:64px;color:var(--text-muted);display:block;margin:0 auto 20px;"></i>
  <h2 class="text-gradient" style="font-size:24px;margin-bottom:8px;">No warehouses yet</h2>
  <p style="color:var(--text-muted);margin-bottom:24px;">Add your first warehouse to start managing inventory</p>
  <a href="/inventoryiq/warehouse/add.php" class="btn btn-primary btn-lg">
    <i data-lucide="plus" style="width:18px;height:18px;"></i>
    Add Your First Warehouse
  </a>
</div>

<?php else: ?>
<!-- Warehouse Grid -->
<div class="grid-2">
  <?php foreach ($warehouses as $i => $wh): ?>
  <div class="glass-card card-animate" style="border-left:3px solid var(--accent-teal);padding:24px;">
    <div class="flex-between mb-4">
      <div>
        <h3 style="font-size:18px;color:var(--text-primary);margin-bottom:4px;">
          <?php echo htmlspecialchars($wh['warehouse_name'], ENT_QUOTES, 'UTF-8'); ?>
        </h3>
        <span class="mono" style="font-size:12px;">@<?php echo htmlspecialchars($wh['handle'], ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <span class="badge badge-<?php echo $wh['status'] === 'active' ? 'active' : 'inactive'; ?>">
        <?php echo htmlspecialchars($wh['status'], ENT_QUOTES, 'UTF-8'); ?>
      </span>
    </div>

    <?php if (!empty($wh['location'])): ?>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
      <i data-lucide="map-pin" style="width:14px;height:14px;"></i>
      <?php echo htmlspecialchars($wh['location'], ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <?php endif; ?>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
      <span class="badge" style="background:rgba(99,102,241,0.1);color:var(--text-lavender);border:1px solid rgba(99,102,241,0.2);">
        <?php echo (int)$wh['product_count']; ?> products
      </span>
      <span class="badge" style="background:rgba(14,165,176,0.1);color:var(--accent-teal-light);border:1px solid rgba(14,165,176,0.2);">
        <?php echo number_format((int)$wh['total_stock']); ?> units
      </span>
      <?php if ($wh['capacity_limit']): ?>
      <span class="badge" style="background:rgba(245,158,11,0.1);color:#FCD34D;border:1px solid rgba(245,158,11,0.2);">
        Cap: <?php echo number_format((int)$wh['capacity_limit']); ?>
      </span>
      <?php endif; ?>
    </div>

    <div style="display:flex;gap:8px;">
      <a href="/inventoryiq/warehouse/edit.php?id=<?php echo (int)$wh['warehouse_id']; ?>" class="btn btn-ghost" style="font-size:12px;padding:6px 14px;">
        <i data-lucide="pencil" style="width:14px;height:14px;"></i> Edit
      </a>
      <a href="/inventoryiq/warehouse/users.php?id=<?php echo (int)$wh['warehouse_id']; ?>" class="btn btn-ghost" style="font-size:12px;padding:6px 14px;">
        <i data-lucide="users" style="width:14px;height:14px;"></i> Users
      </a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
