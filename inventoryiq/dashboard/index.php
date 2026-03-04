<?php
/**
 * InventoryIQ v2.0 — Role-Specific Dashboard (Screen 03/07/19)
 * AI Rules §5 — Different dashboard per role
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';

$page_title = 'Dashboard';
$role = $_SESSION['role'] ?? '';
$company_id = $_SESSION['company_id'] ?? 0;
$warehouse_id = $_SESSION['warehouse_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? 'User';

// Super Admin should use their own dashboard
if ($role === 'super_admin') {
    header('Location: /inventoryiq/superadmin/dashboard.php');
    exit;
}

// Time-aware greeting
$hour = (int)date('H');
if ($hour < 12) { $greeting = 'Good morning'; }
elseif ($hour < 17) { $greeting = 'Good afternoon'; }
else { $greeting = 'Good evening'; }

// ============================================================
// Company Admin Dashboard Data
// ============================================================
if ($role === 'company_admin') {
    // Total warehouses
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(warehouse_id) AS cnt FROM warehouses WHERE company_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $company_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $total_warehouses = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    // Total products
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(p.product_id) AS cnt FROM products p JOIN warehouses w ON w.warehouse_id = p.warehouse_id WHERE w.company_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $company_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $total_products = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    // Inventory value
    $stmt = mysqli_prepare($conn, 'SELECT COALESCE(SUM(p.price * p.stock_quantity), 0) AS val FROM products p JOIN warehouses w ON w.warehouse_id = p.warehouse_id WHERE w.company_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $company_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $inventory_value = (float)mysqli_fetch_assoc($r)['val'];
    mysqli_stmt_close($stmt);

    // Pending restock requests
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(rr.request_id) AS cnt FROM restock_requests rr JOIN warehouses w ON w.warehouse_id = rr.warehouse_id WHERE w.company_id = ? AND rr.status = ?');
    $pending = 'pending';
    mysqli_stmt_bind_param($stmt, 'is', $company_id, $pending);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $pending_requests = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    // Warehouse list
    $stmt = mysqli_prepare($conn,
        'SELECT w.warehouse_id, w.warehouse_name, w.location, w.status, w.priority_rank,
                COUNT(p.product_id) AS product_count,
                COALESCE(SUM(p.price * p.stock_quantity), 0) AS warehouse_value
         FROM warehouses w
         LEFT JOIN products p ON p.warehouse_id = w.warehouse_id
         WHERE w.company_id = ?
         GROUP BY w.warehouse_id
         ORDER BY w.priority_rank ASC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $company_id);
    mysqli_stmt_execute($stmt);
    $warehouses_result = mysqli_stmt_get_result($stmt);
    $warehouses = [];
    while ($row = mysqli_fetch_assoc($warehouses_result)) {
        $warehouses[] = $row;
    }
    mysqli_stmt_close($stmt);

    // Company name
    $stmt = mysqli_prepare($conn, 'SELECT company_name FROM companies WHERE company_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $company_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $company_name = mysqli_fetch_assoc($r)['company_name'];
    mysqli_stmt_close($stmt);

    // Recent activity
    $stmt = mysqli_prepare($conn, 'SELECT action_type, detail, timestamp FROM audit_log WHERE company_id = ? ORDER BY timestamp DESC LIMIT 10');
    mysqli_stmt_bind_param($stmt, 'i', $company_id);
    mysqli_stmt_execute($stmt);
    $activity_result = mysqli_stmt_get_result($stmt);
    $activities = [];
    while ($row = mysqli_fetch_assoc($activity_result)) {
        $activities[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// ============================================================
// Warehouse Manager Dashboard Data
// ============================================================
if ($role === 'wh_manager') {
    // Total products
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(product_id) AS cnt FROM products WHERE warehouse_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $total_products = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    // Low stock count
    $threshold = 10; // Will use get_low_stock_threshold later
    require_once '../includes/notify.php';
    $threshold = get_low_stock_threshold($conn, $warehouse_id);
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(product_id) AS cnt FROM products WHERE warehouse_id = ? AND stock_quantity > 0 AND stock_quantity <= ?');
    mysqli_stmt_bind_param($stmt, 'ii', $warehouse_id, $threshold);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $low_stock = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    // Out of stock
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(product_id) AS cnt FROM products WHERE warehouse_id = ? AND stock_quantity = 0');
    mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $out_of_stock = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    // Total value
    $stmt = mysqli_prepare($conn, 'SELECT COALESCE(SUM(price * stock_quantity), 0) AS val FROM products WHERE warehouse_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $inventory_value = (float)mysqli_fetch_assoc($r)['val'];
    mysqli_stmt_close($stmt);

    // Warehouse info
    $stmt = mysqli_prepare($conn, 'SELECT warehouse_name, location FROM warehouses WHERE warehouse_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $wh_info = mysqli_fetch_assoc($r);
    mysqli_stmt_close($stmt);

    // Recent activity
    $stmt = mysqli_prepare($conn, 'SELECT action_type, detail, timestamp FROM audit_log WHERE warehouse_id = ? ORDER BY timestamp DESC LIMIT 10');
    mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
    mysqli_stmt_execute($stmt);
    $activity_result = mysqli_stmt_get_result($stmt);
    $activities = [];
    while ($row = mysqli_fetch_assoc($activity_result)) {
        $activities[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// ============================================================
// Warehouse Staff Dashboard Data
// ============================================================
if ($role === 'wh_staff') {
    require_once '../includes/notify.php';
    $threshold = get_low_stock_threshold($conn, $warehouse_id);

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(product_id) AS cnt FROM products WHERE warehouse_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $total_products = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(product_id) AS cnt FROM products WHERE warehouse_id = ? AND stock_quantity > 0 AND stock_quantity <= ?');
    mysqli_stmt_bind_param($stmt, 'ii', $warehouse_id, $threshold);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $low_stock = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(product_id) AS cnt FROM products WHERE warehouse_id = ? AND stock_quantity = 0');
    mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $out_of_stock = (int)mysqli_fetch_assoc($r)['cnt'];
    mysqli_stmt_close($stmt);

    // Last 5 personal actions
    $stmt = mysqli_prepare($conn, 'SELECT action_type, detail, timestamp FROM audit_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5');
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $activity_result = mysqli_stmt_get_result($stmt);
    $activities = [];
    while ($row = mysqli_fetch_assoc($activity_result)) {
        $activities[] = $row;
    }
    mysqli_stmt_close($stmt);
}

require_once '../includes/header.php';
?>

<!-- Hero -->
<div class="mb-8">
  <h1 class="text-gradient" style="font-size:36px;">
    <?php echo htmlspecialchars($greeting . ', ' . $full_name, ENT_QUOTES, 'UTF-8'); ?>
  </h1>
  <?php if ($role === 'company_admin' && isset($company_name)): ?>
    <p style="color:var(--text-muted);font-size:16px;margin-top:4px;">
      <?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?> Control Centre
    </p>
  <?php elseif ($role === 'wh_manager' && isset($wh_info)): ?>
    <p style="color:var(--text-muted);font-size:16px;margin-top:4px;">
      <?php echo htmlspecialchars($wh_info['warehouse_name'], ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($wh_info['location'], ENT_QUOTES, 'UTF-8'); ?>
    </p>
  <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- COMPANY ADMIN DASHBOARD -->
<!-- ============================================================ -->
<?php if ($role === 'company_admin'): ?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card accent-teal card-animate">
    <div class="stat-icon"><i data-lucide="warehouse" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $total_warehouses; ?>">0</div>
    <div class="stat-label">Total Warehouses</div>
  </div>
  <div class="stat-card accent-indigo card-animate">
    <div class="stat-icon"><i data-lucide="package" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $total_products; ?>">0</div>
    <div class="stat-label">Total Products</div>
  </div>
  <div class="stat-card accent-amber card-animate">
    <div class="stat-icon"><i data-lucide="indian-rupee" style="width:28px;height:28px;"></i></div>
    <div class="stat-number">₹<?php echo number_format($inventory_value, 0); ?></div>
    <div class="stat-label">Inventory Value</div>
  </div>
  <div class="stat-card accent-pink card-animate">
    <div class="stat-icon"><i data-lucide="clock" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $pending_requests; ?>">0</div>
    <div class="stat-label">Pending Requests</div>
  </div>
</div>

<!-- Warehouse Fleet + Activity Feed -->
<div style="display:grid;grid-template-columns:7fr 3fr;gap:var(--space-6);">
  <!-- Warehouse Fleet -->
  <div>
    <h3 class="section-title mb-4">
      <i data-lucide="building-2" style="width:20px;height:20px;display:inline;"></i>
      Warehouse Fleet
    </h3>
    <div class="grid-2">
      <?php foreach ($warehouses as $wh): ?>
      <div class="glass-card card-animate" style="border-left:3px solid var(--accent-teal);padding:20px;">
        <div class="flex-between mb-4">
          <div>
            <h3 style="font-size:18px;color:var(--text-primary);margin-bottom:2px;">
              <?php echo htmlspecialchars($wh['warehouse_name'], ENT_QUOTES, 'UTF-8'); ?>
            </h3>
            <span class="label"><?php echo htmlspecialchars($wh['location'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <span class="badge badge-<?php echo $wh['status'] === 'active' ? 'active' : 'inactive'; ?>">
            <?php echo htmlspecialchars($wh['status'], ENT_QUOTES, 'UTF-8'); ?>
          </span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
          <span class="badge" style="background:rgba(99,102,241,0.1);color:var(--text-lavender);border:1px solid rgba(99,102,241,0.2);">
            <?php echo (int)$wh['product_count']; ?> products
          </span>
          <span class="badge" style="background:rgba(14,165,176,0.1);color:var(--accent-teal-light);border:1px solid rgba(14,165,176,0.2);">
            ₹<?php echo number_format((float)$wh['warehouse_value'], 0); ?>
          </span>
        </div>
        <div style="display:flex;gap:8px;">
          <a href="/inventoryiq/warehouse/view_as.php?id=<?php echo (int)$wh['warehouse_id']; ?>" class="btn btn-ghost" style="font-size:12px;padding:6px 12px;">
            <i data-lucide="eye" style="width:14px;height:14px;"></i> View
          </a>
          <a href="/inventoryiq/warehouse/edit.php?id=<?php echo (int)$wh['warehouse_id']; ?>" class="btn btn-ghost" style="font-size:12px;padding:6px 12px;">
            <i data-lucide="pencil" style="width:14px;height:14px;"></i> Edit
          </a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($warehouses)): ?>
      <div class="glass-card-static" style="grid-column:1/-1;text-align:center;padding:40px;">
        <i data-lucide="warehouse" style="width:48px;height:48px;color:var(--text-muted);display:block;margin:0 auto 16px;"></i>
        <p style="color:var(--text-muted);margin-bottom:16px;">No warehouses yet</p>
        <a href="/inventoryiq/warehouse/add.php" class="btn btn-primary">Add Your First Warehouse</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Activity Feed -->
  <div>
    <h3 class="section-title mb-4" style="display:flex;align-items:center;gap:8px;">
      <span style="width:8px;height:8px;background:#10B981;border-radius:50%;display:inline-block;animation:bellPulse 2s infinite;"></span>
      Live Activity
    </h3>
    <div class="glass-card-static" style="max-height:400px;overflow-y:auto;">
      <?php if (empty($activities)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:20px;">No recent activity</p>
      <?php else: ?>
        <?php foreach ($activities as $act): ?>
        <div style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.04);display:flex;gap:12px;align-items:flex-start;">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--accent-indigo);margin-top:6px;flex-shrink:0;"></div>
          <div>
            <p style="font-size:13px;color:var(--text-body);"><?php echo htmlspecialchars($act['detail'], ENT_QUOTES, 'UTF-8'); ?></p>
            <span style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($act['timestamp'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- WAREHOUSE MANAGER DASHBOARD -->
<!-- ============================================================ -->
<?php elseif ($role === 'wh_manager'): ?>

<div class="stat-grid">
  <div class="stat-card accent-indigo card-animate">
    <div class="stat-icon"><i data-lucide="package" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $total_products; ?>">0</div>
    <div class="stat-label">Total Products</div>
  </div>
  <div class="stat-card accent-amber card-animate">
    <div class="stat-icon"><i data-lucide="alert-triangle" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $low_stock; ?>">0</div>
    <div class="stat-label">Low Stock Items</div>
  </div>
  <div class="stat-card accent-pink card-animate">
    <div class="stat-icon"><i data-lucide="x-circle" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $out_of_stock; ?>">0</div>
    <div class="stat-label">Out of Stock</div>
  </div>
  <div class="stat-card accent-teal card-animate">
    <div class="stat-icon"><i data-lucide="indian-rupee" style="width:28px;height:28px;"></i></div>
    <div class="stat-number">₹<?php echo number_format($inventory_value, 0); ?></div>
    <div class="stat-label">Total Value</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:6fr 4fr;gap:var(--space-6);">
  <!-- Recent Activity -->
  <div>
    <h3 class="section-title mb-4">Recent Activity</h3>
    <div class="glass-card-static" style="max-height:350px;overflow-y:auto;">
      <?php foreach ($activities as $act): ?>
      <div style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.04);display:flex;gap:12px;align-items:flex-start;">
        <div style="width:8px;height:8px;border-radius:50%;background:var(--accent-indigo);margin-top:6px;flex-shrink:0;"></div>
        <div>
          <p style="font-size:13px;color:var(--text-body);"><?php echo htmlspecialchars($act['detail'], ENT_QUOTES, 'UTF-8'); ?></p>
          <span style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($act['timestamp'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($activities)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:20px;">No recent activity</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Quick Actions -->
  <div>
    <h3 class="section-title mb-4">Quick Actions</h3>
    <div class="grid-2" style="gap:16px;">
      <a href="/inventoryiq/products/add.php" class="glass-card" style="text-align:center;padding:24px;">
        <i data-lucide="plus-circle" style="width:32px;height:32px;color:var(--accent-indigo);display:block;margin:0 auto 8px;"></i>
        <span style="font-weight:600;color:var(--text-primary);">Add Product</span>
      </a>
      <a href="/inventoryiq/products/view.php" class="glass-card" style="text-align:center;padding:24px;">
        <i data-lucide="list" style="width:32px;height:32px;color:var(--accent-teal);display:block;margin:0 auto 8px;"></i>
        <span style="font-weight:600;color:var(--text-primary);">View Inventory</span>
      </a>
      <a href="/inventoryiq/restock/request.php" class="glass-card" style="text-align:center;padding:24px;">
        <i data-lucide="package-plus" style="width:32px;height:32px;color:var(--accent-amber);display:block;margin:0 auto 8px;"></i>
        <span style="font-weight:600;color:var(--text-primary);">Request Restock</span>
      </a>
      <a href="/inventoryiq/export/csv.php" class="glass-card" style="text-align:center;padding:24px;">
        <i data-lucide="download" style="width:32px;height:32px;color:var(--accent-teal);display:block;margin:0 auto 8px;"></i>
        <span style="font-weight:600;color:var(--text-primary);">Export Report</span>
      </a>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- WAREHOUSE STAFF DASHBOARD -->
<!-- ============================================================ -->
<?php elseif ($role === 'wh_staff'): ?>

<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
  <div class="stat-card accent-indigo card-animate">
    <div class="stat-icon"><i data-lucide="package" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $total_products; ?>">0</div>
    <div class="stat-label">Total Products</div>
  </div>
  <div class="stat-card accent-amber card-animate">
    <div class="stat-icon"><i data-lucide="alert-triangle" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $low_stock; ?>">0</div>
    <div class="stat-label">Low Stock</div>
  </div>
  <div class="stat-card accent-pink card-animate">
    <div class="stat-icon"><i data-lucide="x-circle" style="width:28px;height:28px;"></i></div>
    <div class="stat-number count-up" data-target="<?php echo $out_of_stock; ?>">0</div>
    <div class="stat-label">Out of Stock</div>
  </div>
</div>

<!-- Quick Actions + Recent Actions -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);">
  <div>
    <h3 class="section-title mb-4">Quick Actions</h3>
    <div style="display:flex;gap:16px;">
      <a href="/inventoryiq/products/add.php" class="btn btn-primary btn-lg" style="flex:1;">
        <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
        Add Product
      </a>
      <a href="/inventoryiq/products/view.php" class="btn btn-secondary btn-lg" style="flex:1;">
        <i data-lucide="list" style="width:18px;height:18px;"></i>
        View Inventory
      </a>
    </div>
  </div>
  <div>
    <h3 class="section-title mb-4">Your Recent Actions</h3>
    <div class="glass-card-static">
      <?php foreach ($activities as $act): ?>
      <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
        <p style="font-size:13px;color:var(--text-body);"><?php echo htmlspecialchars($act['detail'], ENT_QUOTES, 'UTF-8'); ?></p>
        <span style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($act['timestamp'], ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (empty($activities)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:20px;">No recent actions</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php endif; ?>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
