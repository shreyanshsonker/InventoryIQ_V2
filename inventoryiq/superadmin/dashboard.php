<?php
/**
 * InventoryIQ v2.0 — Super Admin Dashboard (Screen 15)
 * AI Rules §5 — SA only
 */
require_once '../config/db.php';
require_once '../auth/check.php';
check_role(['super_admin']);

$page_title = 'Super Admin Dashboard';

// Total companies
$r = mysqli_query($conn, 'SELECT COUNT(company_id) AS cnt FROM companies');
$total_companies = (int)mysqli_fetch_assoc($r)['cnt'];

// Active companies
$r = mysqli_query($conn, "SELECT COUNT(company_id) AS cnt FROM companies WHERE status = 'active'");
$active_companies = (int)mysqli_fetch_assoc($r)['cnt'];

// Total users (across all companies)
$r = mysqli_query($conn, 'SELECT COUNT(user_id) AS cnt FROM users');
$total_users = (int)mysqli_fetch_assoc($r)['cnt'];

// Total warehouses
$r = mysqli_query($conn, 'SELECT COUNT(warehouse_id) AS cnt FROM warehouses');
$total_warehouses = (int)mysqli_fetch_assoc($r)['cnt'];

// Total products
$r = mysqli_query($conn, 'SELECT COUNT(product_id) AS cnt FROM products');
$total_products = (int)mysqli_fetch_assoc($r)['cnt'];

// Total inventory value
$r = mysqli_query($conn, 'SELECT COALESCE(SUM(price * stock_quantity), 0) AS val FROM products');
$total_value = (float)mysqli_fetch_assoc($r)['val'];

// Recent companies
$companies_result = mysqli_query($conn,
    'SELECT c.company_id, c.company_name, c.handle, c.status, c.created_at,
            COUNT(DISTINCT u.user_id) AS user_count,
            COUNT(DISTINCT w.warehouse_id) AS wh_count
     FROM companies c
     LEFT JOIN users u ON u.company_id = c.company_id
     LEFT JOIN warehouses w ON w.company_id = c.company_id
     GROUP BY c.company_id
     ORDER BY c.created_at DESC
     LIMIT 6'
);
$recent_companies = [];
while ($row = mysqli_fetch_assoc($companies_result)) { $recent_companies[] = $row; }

// Recent audit
$audit_result = mysqli_query($conn,
    'SELECT al.action_type, al.detail, al.timestamp, al.role, u.full_name
     FROM audit_log al LEFT JOIN users u ON u.user_id = al.user_id
     ORDER BY al.timestamp DESC LIMIT 10'
);
$recent_audit = [];
while ($row = mysqli_fetch_assoc($audit_result)) { $recent_audit[] = $row; }

require_once '../includes/header.php';
?>

<!-- Hero -->
<div class="mb-8">
  <h1 style="font-size:36px;background:linear-gradient(135deg,#A78BFA,#F0ABFC);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
    Control Panel
  </h1>
  <p style="color:var(--text-muted);font-size:16px;margin-top:4px;">Global system overview</p>
</div>

<!-- Stat Grid -->
<div style="display:grid;grid-template-columns:repeat(6, 1fr);gap:16px;margin-bottom:var(--space-8);">
  <div class="stat-card accent-indigo card-animate">
    <div class="stat-number count-up" data-target="<?php echo $total_companies; ?>">0</div>
    <div class="stat-label">Companies</div>
  </div>
  <div class="stat-card accent-teal card-animate">
    <div class="stat-number count-up" data-target="<?php echo $active_companies; ?>">0</div>
    <div class="stat-label">Active</div>
  </div>
  <div class="stat-card accent-pink card-animate">
    <div class="stat-number count-up" data-target="<?php echo $total_users; ?>">0</div>
    <div class="stat-label">Users</div>
  </div>
  <div class="stat-card accent-amber card-animate">
    <div class="stat-number count-up" data-target="<?php echo $total_warehouses; ?>">0</div>
    <div class="stat-label">Warehouses</div>
  </div>
  <div class="stat-card accent-indigo card-animate">
    <div class="stat-number count-up" data-target="<?php echo $total_products; ?>">0</div>
    <div class="stat-label">Products</div>
  </div>
  <div class="stat-card accent-teal card-animate">
    <div class="stat-number">₹<?php echo number_format($total_value, 0); ?></div>
    <div class="stat-label">Total Value</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:6fr 4fr;gap:var(--space-6);">
  <!-- Recent Companies -->
  <div>
    <div class="flex-between mb-4">
      <h3 class="section-title">Recent Companies</h3>
      <a href="/inventoryiq/superadmin/companies.php" class="btn btn-ghost" style="font-size:12px;">View All</a>
    </div>
    <div class="data-table-container">
      <table class="data-table">
        <thead>
          <tr><th>Company</th><th>Handle</th><th>Users</th><th>WHs</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recent_companies as $co): ?>
          <tr>
            <td style="font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($co['company_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="mono" style="font-size:11px;">@<?php echo htmlspecialchars($co['handle'], ENT_QUOTES, 'UTF-8'); ?></span></td>
            <td><?php echo (int)$co['user_count']; ?></td>
            <td><?php echo (int)$co['wh_count']; ?></td>
            <td><span class="badge badge-<?php echo $co['status']; ?>"><?php echo ucfirst($co['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Global Activity -->
  <div>
    <h3 class="section-title mb-4" style="display:flex;align-items:center;gap:8px;">
      <span style="width:8px;height:8px;background:#A78BFA;border-radius:50%;display:inline-block;animation:bellPulse 2s infinite;"></span>
      System Activity
    </h3>
    <div class="glass-card-static" style="max-height:360px;overflow-y:auto;">
      <?php foreach ($recent_audit as $act): ?>
      <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:4px;">
          <span class="badge" style="background:rgba(167,139,250,0.15);color:#C4B5FD;border:1px solid rgba(167,139,250,0.2);font-size:9px;"><?php echo htmlspecialchars($act['action_type'], ENT_QUOTES, 'UTF-8'); ?></span>
          <span style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($act['full_name'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <p style="font-size:12px;color:var(--text-label);"><?php echo htmlspecialchars($act['detail'], ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
