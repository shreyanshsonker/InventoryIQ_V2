<?php
/**
 * InventoryIQ v2.0 — Super Admin Audit Export
 * PRD §7 — Full audit trail with CSV export
 */
require_once '../config/db.php';
require_once '../auth/check.php';
check_role(['super_admin']);

$page_title = 'Audit Export';

// Filters
$action_filter = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';
$company_filter = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build query
$where = '1=1';
$params = [];
$types = '';

if (!empty($action_filter)) {
    $where .= ' AND al.action_type = ?';
    $params[] = $action_filter;
    $types .= 's';
}
if ($company_filter > 0) {
    $where .= ' AND al.company_id = ?';
    $params[] = $company_filter;
    $types .= 'i';
}
if (!empty($date_from)) {
    $where .= ' AND al.timestamp >= ?';
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}
if (!empty($date_to)) {
    $where .= ' AND al.timestamp <= ?';
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

// Handle CSV download
if (isset($_GET['download'])) {
    $sql = "SELECT al.timestamp, u.full_name, al.role, c.company_name, al.action_type, al.detail, al.ip_address
            FROM audit_log al
            LEFT JOIN users u ON u.user_id = al.user_id
            LEFT JOIN companies c ON c.company_id = al.company_id
            WHERE $where
            ORDER BY al.timestamp DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($types)) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $filename = 'audit_log_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Timestamp', 'User', 'Role', 'Company', 'Action', 'Detail', 'IP Address']);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($out, array_values($row));
    }
    fclose($out);
    mysqli_stmt_close($stmt);
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(al.log_id) AS cnt FROM audit_log al WHERE $where";
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($types)) mysqli_stmt_bind_param($count_stmt, $types, ...$params);
mysqli_stmt_execute($count_stmt);
$total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['cnt'];
mysqli_stmt_close($count_stmt);
$total_pages = max(1, ceil($total / $per_page));

// Fetch logs
$fetch_types = $types . 'ii';
$fetch_params = array_merge($params, [$per_page, $offset]);
$sql = "SELECT al.log_id, al.timestamp, al.role, al.action_type, al.detail, al.ip_address,
               u.full_name, c.company_name
        FROM audit_log al
        LEFT JOIN users u ON u.user_id = al.user_id
        LEFT JOIN companies c ON c.company_id = al.company_id
        WHERE $where
        ORDER BY al.timestamp DESC
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($fetch_types)) mysqli_stmt_bind_param($stmt, $fetch_types, ...$fetch_params);
mysqli_stmt_execute($stmt);
$logs = [];
$r = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($r)) { $logs[] = $row; }
mysqli_stmt_close($stmt);

// Get companies for filter
$companies = [];
$cr = mysqli_query($conn, 'SELECT company_id, company_name FROM companies ORDER BY company_name');
while ($row = mysqli_fetch_assoc($cr)) { $companies[] = $row; }

// Build query string for pagination + export links
$qs = http_build_query(array_filter([
    'action_type' => $action_filter,
    'company_id' => $company_filter ?: '',
    'date_from' => $date_from,
    'date_to' => $date_to,
]));

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <div>
    <h1 style="font-size:28px;">Audit Export</h1>
    <p class="label" style="margin-top:4px;"><?php echo number_format($total); ?> entries</p>
  </div>
  <a href="?<?php echo htmlspecialchars($qs, ENT_QUOTES, 'UTF-8'); ?>&download=1" class="btn btn-primary">
    <i data-lucide="download" style="width:16px;height:16px;"></i> Export CSV
  </a>
</div>

<!-- Filters -->
<div class="glass-card-static mb-6" style="padding:14px 20px;">
  <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <div class="form-group" style="flex:1;min-width:140px;">
      <label class="form-label">Action Type</label>
      <select name="action_type" class="glass-select">
        <option value="">All Actions</option>
        <?php foreach (['LOGIN_SUCCESS','LOGIN_FAIL','LOGOUT','PRODUCT_CREATE','PRODUCT_UPDATE','PRODUCT_DELETE','WAREHOUSE_CREATE','WAREHOUSE_UPDATE','USER_CREATE','RESTOCK_REQUEST','RESTOCK_APPROVED','RESTOCK_REJECTED','EXPORT_CSV','NOTIFICATION_BROADCAST','CATEGORY_CREATE','CATEGORY_DELETE','PASSWORD_CHANGE','PROFILE_UPDATE','COMPANY_ACTIVE','COMPANY_SUSPENDED','MAINTENANCE'] as $at): ?>
          <option value="<?php echo $at; ?>" <?php echo $action_filter === $at ? 'selected' : ''; ?>><?php echo $at; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="flex:1;min-width:140px;">
      <label class="form-label">Company</label>
      <select name="company_id" class="glass-select">
        <option value="">All Companies</option>
        <?php foreach ($companies as $co): ?>
          <option value="<?php echo $co['company_id']; ?>" <?php echo $company_filter == $co['company_id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($co['company_name'], ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="min-width:140px;">
      <label class="form-label">From</label>
      <input type="date" name="date_from" class="glass-input" value="<?php echo htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="form-group" style="min-width:140px;">
      <label class="form-label">To</label>
      <input type="date" name="date_to" class="glass-input" value="<?php echo htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <button type="submit" class="btn btn-secondary" style="height:44px;">
      <i data-lucide="filter" style="width:14px;height:14px;"></i> Filter
    </button>
    <a href="?" class="btn btn-ghost" style="height:44px;">Clear</a>
  </form>
</div>

<!-- Audit Table -->
<div class="data-table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Timestamp</th>
        <th>User</th>
        <th>Role</th>
        <th>Company</th>
        <th>Action</th>
        <th>Detail</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($logs)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No audit entries match your filters.</td></tr>
    <?php else: ?>
      <?php foreach ($logs as $log): ?>
      <tr>
        <td style="font-size:12px;white-space:nowrap;"><?php echo date('d M Y H:i:s', strtotime($log['timestamp'])); ?></td>
        <td><?php echo htmlspecialchars($log['full_name'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="mono" style="font-size:11px;"><?php echo htmlspecialchars($log['role'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td style="font-size:12px;"><?php echo htmlspecialchars($log['company_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="badge" style="background:rgba(167,139,250,0.15);color:#C4B5FD;border:1px solid rgba(167,139,250,0.2);font-size:10px;"><?php echo htmlspecialchars($log['action_type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($log['detail'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="mono" style="font-size:11px;"><?php echo htmlspecialchars($log['ip_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>

  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?<?php echo htmlspecialchars($qs, ENT_QUOTES, 'UTF-8'); ?>&page=<?php echo $page-1; ?>" class="page-btn"><i data-lucide="chevron-left" style="width:14px;height:14px;"></i></a>
    <?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
      <a href="?<?php echo htmlspecialchars($qs, ENT_QUOTES, 'UTF-8'); ?>&page=<?php echo $i; ?>"
         class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
      <a href="?<?php echo htmlspecialchars($qs, ENT_QUOTES, 'UTF-8'); ?>&page=<?php echo $page+1; ?>" class="page-btn"><i data-lucide="chevron-right" style="width:14px;height:14px;"></i></a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
