<?php
/**
 * InventoryIQ v2.0 — Notification Inbox (Screen 10)
 * AI Rules §5 — Manager+ roles
 */
require_once '../config/db.php';
require_once '../auth/check.php';
check_role(['company_admin', 'wh_manager']);

$page_title = 'Notifications';
$company_id = $_SESSION['company_id'];
$warehouse_id = $_SESSION['warehouse_id'];
$role = $_SESSION['role'];

// Mark single notification as read
if (isset($_GET['mark_read'])) {
    $nid = (int)$_GET['mark_read'];
    $stmt = mysqli_prepare($conn, 'UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND company_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $nid, $company_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: /inventoryiq/notifications/index.php');
    exit;
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    if ($role === 'company_admin') {
        $stmt = mysqli_prepare($conn, 'UPDATE notifications SET is_read = 1 WHERE company_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $company_id);
    } else {
        $stmt = mysqli_prepare($conn, 'UPDATE notifications SET is_read = 1 WHERE company_id = ? AND (recipient_warehouse_id = ? OR recipient_warehouse_id IS NULL)');
        mysqli_stmt_bind_param($stmt, 'ii', $company_id, $warehouse_id);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: /inventoryiq/notifications/index.php');
    exit;
}

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where = 'n.company_id = ?';
$params = [$company_id];
$types = 'i';

if ($role !== 'company_admin') {
    $where .= ' AND (n.recipient_warehouse_id = ? OR n.recipient_warehouse_id IS NULL)';
    $params[] = $warehouse_id;
    $types .= 'i';
}

if ($filter === 'unread') { $where .= ' AND n.is_read = 0'; }
elseif ($filter === 'info') { $where .= " AND n.priority = 'info'"; }
elseif ($filter === 'warning') { $where .= " AND n.priority = 'warning'"; }
elseif ($filter === 'critical') { $where .= " AND n.priority = 'critical'"; }

// Count unread
$unread_stmt = mysqli_prepare($conn, "SELECT COUNT(notification_id) AS cnt FROM notifications n WHERE $where AND n.is_read = 0");
mysqli_stmt_bind_param($unread_stmt, $types, ...$params);
mysqli_stmt_execute($unread_stmt);
$unread_total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($unread_stmt))['cnt'];
mysqli_stmt_close($unread_stmt);

// Fetch notifications
$sql = "SELECT n.notification_id, n.title, n.body, n.priority, n.type, n.is_read, n.created_at,
               u.full_name AS sender_name
        FROM notifications n
        LEFT JOIN users u ON u.user_id = n.sender_user_id
        WHERE $where
        ORDER BY n.created_at DESC
        LIMIT 50";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$notifications = [];
$r = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($r)) { $notifications[] = $row; }
mysqli_stmt_close($stmt);

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Notifications</h1>
  <a href="/inventoryiq/notifications/index.php?mark_all_read=1" class="btn btn-ghost">
    <i data-lucide="check-check" style="width:16px;height:16px;"></i> Mark All Read
  </a>
</div>

<!-- Filter Pills -->
<div style="display:flex;gap:8px;margin-bottom:var(--space-6);flex-wrap:wrap;">
  <?php
  $filters = [
    'all' => 'All',
    'unread' => 'Unread (' . $unread_total . ')',
    'info' => 'Info',
    'warning' => 'Warning',
    'critical' => 'Critical'
  ];
  foreach ($filters as $key => $label): ?>
    <a href="?filter=<?php echo $key; ?>"
       class="badge" style="padding:8px 16px;font-size:13px;cursor:pointer;text-decoration:none;
       <?php echo $filter === $key
         ? 'background:linear-gradient(135deg,var(--accent-indigo),var(--accent-indigo-light));color:#fff;border:none;box-shadow:0 0 20px rgba(99,102,241,0.4);'
         : 'background:rgba(255,255,255,0.06);color:var(--text-label);border:1px solid rgba(255,255,255,0.12);'; ?>">
      <?php echo $label; ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (empty($notifications)): ?>
<div class="glass-card-static" style="text-align:center;padding:60px;">
  <i data-lucide="mail-check" style="width:48px;height:48px;color:var(--text-muted);display:block;margin:0 auto 16px;"></i>
  <h2 class="text-gradient" style="font-size:22px;">All caught up!</h2>
  <p style="color:var(--text-muted);margin-top:8px;">No notifications to show.</p>
</div>
<?php else: ?>

<div style="display:flex;flex-direction:column;gap:12px;">
  <?php foreach ($notifications as $n):
    $prio_class = 'priority-' . $n['priority'];
  ?>
  <div class="notification-card <?php echo $prio_class; ?> <?php echo !$n['is_read'] ? 'unread' : ''; ?>">
    <div class="flex-between" style="margin-bottom:6px;">
      <div style="display:flex;align-items:center;gap:8px;">
        <?php if ($n['priority'] === 'critical'): ?>
          <i data-lucide="alert-triangle" style="width:16px;height:16px;color:var(--accent-red);"></i>
        <?php elseif ($n['priority'] === 'warning'): ?>
          <i data-lucide="alert-triangle" style="width:16px;height:16px;color:var(--accent-amber);"></i>
        <?php else: ?>
          <i data-lucide="info" style="width:16px;height:16px;color:var(--accent-indigo-light);"></i>
        <?php endif; ?>
        <span class="notification-title"><?php echo htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <?php if (!$n['is_read']): ?>
        <a href="?mark_read=<?php echo (int)$n['notification_id']; ?>" class="btn btn-ghost" style="font-size:11px;padding:4px 10px;">Mark Read</a>
      <?php endif; ?>
    </div>
    <p class="notification-body"><?php echo htmlspecialchars($n['body'], ENT_QUOTES, 'UTF-8'); ?></p>
    <div class="flex-between" style="margin-top:8px;">
      <span style="font-size:12px;color:var(--text-label);"><?php echo $n['sender_name'] ? htmlspecialchars($n['sender_name'], ENT_QUOTES, 'UTF-8') : 'System'; ?></span>
      <span style="font-size:11px;color:var(--text-muted);"><?php echo date('d M Y H:i', strtotime($n['created_at'])); ?></span>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
