<?php
/**
 * InventoryIQ v2.0 — Broadcast Notification
 * AI Rules §5 — Company Admin only
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
require_once '../includes/notify.php';
check_role(['company_admin']);

$page_title = 'Broadcast';
$company_id = $_SESSION['company_id'];
$error = '';
$success = '';

// Get warehouses for targeting
$wh_stmt = mysqli_prepare($conn, 'SELECT warehouse_id, warehouse_name FROM warehouses WHERE company_id = ? AND status = ? ORDER BY warehouse_name');
$active = 'active';
mysqli_stmt_bind_param($wh_stmt, 'is', $company_id, $active);
mysqli_stmt_execute($wh_stmt);
$warehouses = [];
$r = mysqli_stmt_get_result($wh_stmt);
while ($row = mysqli_fetch_assoc($r)) { $warehouses[] = $row; }
mysqli_stmt_close($wh_stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $priority = in_array($_POST['priority'] ?? '', ['info','warning','critical']) ? $_POST['priority'] : 'info';
    $target = $_POST['target'] ?? 'all';

    if (empty($title) || empty($body)) {
        $error = 'Title and message body are required.';
    } else {
        $recipient_wh = null;
        if ($target !== 'all') {
            $recipient_wh = (int)$target;
        }

        create_notification($conn, $company_id, $recipient_wh, $title, $body, $priority, 'broadcast', $_SESSION['user_id']);

        write_audit_log($conn, $_SESSION['user_id'], 'company_admin', $company_id, null,
            'NOTIFICATION_BROADCAST', 'Broadcast: ' . $title . ($recipient_wh ? ' (WH:' . $recipient_wh . ')' : ' (All)'));

        header('Location: /inventoryiq/notifications/broadcast.php?success=1');
        exit;
    }
}

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Broadcast Notification</h1>
</div>

<?php if (!empty($error)): ?>
<div class="alert-banner alert-error mb-6">
  <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert-banner alert-success mb-6">
  <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span>Notification broadcast successfully!</span>
</div>
<?php endif; ?>

<div class="glass-card-static" style="max-width:600px;">
  <form method="POST" style="display:flex;flex-direction:column;gap:20px;">

    <div class="form-group">
      <label class="form-label" for="title">Title <span class="required">*</span></label>
      <input type="text" id="title" name="title" class="glass-input" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="body">Message <span class="required">*</span></label>
      <textarea id="body" name="body" class="glass-textarea" rows="5" required></textarea>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="priority">Priority</label>
        <select id="priority" name="priority" class="glass-select">
          <option value="info">Info</option>
          <option value="warning">Warning</option>
          <option value="critical">Critical</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="target">Target</label>
        <select id="target" name="target" class="glass-select">
          <option value="all">All Warehouses</option>
          <?php foreach ($warehouses as $wh): ?>
            <option value="<?php echo $wh['warehouse_id']; ?>"><?php echo htmlspecialchars($wh['warehouse_name'], ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">
      <i data-lucide="megaphone" style="width:18px;height:18px;"></i> Send Broadcast
    </button>

  </form>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
