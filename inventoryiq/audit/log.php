<?php
/**
 * InventoryIQ v2.0 — Audit Log Viewer
 * AI Rules §5 — Company Admin + Super Admin
 */
require_once '../config/db.php';
require_once '../auth/check.php';
check_role(['company_admin', 'super_admin']);

$page_title = 'Audit Log';
$role = $_SESSION['role'];
$company_id = $_SESSION['company_id'];

// Filters
$action_filter = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';
$user_filter = isset($_GET['user_search']) ? trim($_GET['user_search']) : '';

$where = '1=1';
$params = [];
$types = '';

if ($role === 'company_admin') {
    $where .= ' AND al.company_id = ?';
    $params[] = $company_id;
    $types .= 'i';
}

if (!empty($action_filter)) {
    $where .= ' AND al.action_type = ?';
    $params[] = $action_filter;
    $types .= 's';
}

if (!empty($user_filter)) {
    $where .= ' AND (u.full_name LIKE ? OR al.role LIKE ?)';
    $like = '%' . $user_filter . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(al.log_id) AS cnt FROM audit_log al LEFT JOIN users u ON u.user_id = al.user_id WHERE $where";
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($types)) mysqli_stmt_bind_param($count_stmt, $types, ...$params);
mysqli_stmt_execute($count_stmt);
$total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['cnt'];
mysqli_stmt_close($count_stmt);
$total_pages = max(1, ceil($total / $per_page));

$sql = "SELECT al.log_id, al.timestamp, al.role, al.action_type, al.detail, al.ip_address,
               u.full_name
        FROM audit_log al
        LEFT JOIN users u ON u.user_id = al.user_id
        WHERE $where
        ORDER BY al.timestamp DESC
        LIMIT ? OFFSET ?";
$fetch_types = $types . 'ii';
$fetch_params = array_merge($params, [$per_page, $offset]);
$stmt = mysqli_prepare($conn, $sql);
if (!empty($fetch_types)) mysqli_stmt_bind_param($stmt, $fetch_types, ...$fetch_params);
mysqli_stmt_execute($stmt);
$logs = [];
$r = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($r)) { $logs[] = $row; }
mysqli_stmt_close($stmt);

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Audit Log</h1>
  <span class="label"><?php echo number_format($total); ?> entries</span>
</div>

<!-- Filters -->
<div class="glass-card-static mb-6" style="padding:14px 20px;">
  <form method="GET" style="display:flex;gap:12px;align-items:flex-end;">
    <div class="form-group" style="flex:1;">
      <label class="form-label">Action Type</label>
      <select name="action_type" class="glass-select">
        <option value="">All</option>
        <?php foreach (['LOGIN_SUCCESS','LOGIN_FAIL','LOGOUT','PRODUCT_CREATE','PRODUCT_UPDATE','PRODUCT_DELETE','WAREHOUSE_CREATE','WAREHOUSE_UPDATE','USER_CREATE','RESTOCK_REQUEST','RESTOCK_APPROVED','RESTOCK_REJECTED','EXPORT_CSV','NOTIFICATION_BROADCAST','CATEGORY_CREATE','CATEGORY_DELETE'] as $at): ?>
          <option value="<?php echo $at; ?>" <?php echo $action_filter === $at ? 'selected' : ''; ?>><?php echo $at; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="flex:1;">
      <label class="form-label">User / Role</label>
      <input type="text" name="user_search" class="glass-input" placeholder="Search..."
             value="<?php echo htmlspecialchars($user_filter, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <button type="submit" class="btn btn-primary" style="height:44px;">Filter</button>
  </form>
</div>

<div class="data-table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Timestamp</th>
        <th>User</th>
        <th>Role</th>
        <th>Action</th>
        <th>Detail</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td style="font-size:12px;white-space:nowrap;"><?php echo date('d M Y H:i:s', strtotime($log['timestamp'])); ?></td>
        <td><?php echo htmlspecialchars($log['full_name'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="mono" style="font-size:11px;"><?php echo htmlspecialchars($log['role'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td><span class="badge" style="background:rgba(99,102,241,0.1);color:var(--text-lavender);border:1px solid rgba(99,102,241,0.15);font-size:10px;"><?php echo htmlspecialchars($log['action_type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($log['detail'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="mono" style="font-size:11px;"><?php echo htmlspecialchars($log['ip_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?php echo $page-1; ?>&action_type=<?php echo urlencode($action_filter); ?>&user_search=<?php echo urlencode($user_filter); ?>" class="page-btn"><i data-lucide="chevron-left" style="width:14px;height:14px;"></i></a>
    <?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
      <a href="?page=<?php echo $i; ?>&action_type=<?php echo urlencode($action_filter); ?>&user_search=<?php echo urlencode($user_filter); ?>"
         class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
      <a href="?page=<?php echo $page+1; ?>&action_type=<?php echo urlencode($action_filter); ?>&user_search=<?php echo urlencode($user_filter); ?>" class="page-btn"><i data-lucide="chevron-right" style="width:14px;height:14px;"></i></a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
