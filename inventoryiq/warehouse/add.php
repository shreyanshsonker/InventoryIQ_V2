<?php
/**
 * InventoryIQ v2.0 — Add Warehouse
 * AI Rules §5 — Company Admin only
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
check_role(['company_admin']);

$page_title = 'Add Warehouse';
$company_id = $_SESSION['company_id'];
$error = '';

$form = [
    'warehouse_name' => '', 'handle' => '', 'location' => '',
    'contact_person' => '', 'capacity_limit' => '', 'priority_rank' => '',
    'low_stock_override' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $val) {
        $form[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    }

    if (empty($form['warehouse_name']) || empty($form['handle'])) {
        $error = 'Warehouse name and handle are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $form['handle'])) {
        $error = 'Handle can only contain letters, numbers, hyphens, and underscores.';
    } else {
        // Check duplicate handle within company
        $chk = mysqli_prepare($conn, 'SELECT warehouse_id FROM warehouses WHERE company_id = ? AND handle = ?');
        mysqli_stmt_bind_param($chk, 'is', $company_id, $form['handle']);
        mysqli_stmt_execute($chk);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
            $error = 'This handle is already in use.';
        }
        mysqli_stmt_close($chk);
    }

    if (empty($error)) {
        $cap = !empty($form['capacity_limit']) ? (int)$form['capacity_limit'] : null;
        $rank = !empty($form['priority_rank']) ? (int)$form['priority_rank'] : 99;
        $lso = !empty($form['low_stock_override']) ? (int)$form['low_stock_override'] : null;

        $stmt = mysqli_prepare($conn,
            'INSERT INTO warehouses (company_id, warehouse_name, handle, location, contact_person, capacity_limit, priority_rank, low_stock_override)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'issssiii',
            $company_id, $form['warehouse_name'], $form['handle'],
            $form['location'], $form['contact_person'], $cap, $rank, $lso
        );
        mysqli_stmt_execute($stmt);
        $new_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        write_audit_log($conn, $_SESSION['user_id'], 'company_admin', $company_id, $new_id,
            'WAREHOUSE_CREATE', 'Created warehouse: ' . $form['warehouse_name']);

        header('Location: /inventoryiq/warehouse/list.php?success=1');
        exit;
    }
}

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <div>
    <h1 style="font-size:28px;">Add Warehouse</h1>
    <p class="label" style="margin-top:4px;">Register a new storage location</p>
  </div>
  <a href="/inventoryiq/warehouse/list.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back
  </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert-banner alert-error mb-6">
  <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php endif; ?>

<div class="glass-card-static" style="max-width:700px;">
  <form method="POST" style="display:flex;flex-direction:column;gap:20px;">

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="warehouse_name">Warehouse Name <span class="required">*</span></label>
        <input type="text" id="warehouse_name" name="warehouse_name" class="glass-input"
               value="<?php echo htmlspecialchars($form['warehouse_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="handle">Handle <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-icon" style="color:var(--accent-indigo-light);font-size:14px;font-weight:600;">@</span>
          <input type="text" id="handle" name="handle" class="glass-input"
                 placeholder="e.g. main-wh"
                 value="<?php echo htmlspecialchars($form['handle'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="location">Location</label>
      <input type="text" id="location" name="location" class="glass-input"
             placeholder="e.g. Mumbai, Maharashtra"
             value="<?php echo htmlspecialchars($form['location'], ENT_QUOTES, 'UTF-8'); ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="contact_person">Contact Person</label>
      <input type="text" id="contact_person" name="contact_person" class="glass-input"
             value="<?php echo htmlspecialchars($form['contact_person'], ENT_QUOTES, 'UTF-8'); ?>">
    </div>

    <div class="grid-2" style="grid-template-columns:1fr 1fr 1fr;">
      <div class="form-group">
        <label class="form-label" for="capacity_limit">Capacity Limit</label>
        <input type="number" id="capacity_limit" name="capacity_limit" class="glass-input"
               min="0" value="<?php echo htmlspecialchars($form['capacity_limit'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="priority_rank">Priority Rank</label>
        <input type="number" id="priority_rank" name="priority_rank" class="glass-input"
               min="1" max="99" placeholder="99"
               value="<?php echo htmlspecialchars($form['priority_rank'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="low_stock_override">Low Stock Override</label>
        <input type="number" id="low_stock_override" name="low_stock_override" class="glass-input"
               min="0" value="<?php echo htmlspecialchars($form['low_stock_override'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </div>

    <div style="display:flex;gap:12px;margin-top:8px;">
      <button type="submit" class="btn btn-primary btn-lg">
        <i data-lucide="plus" style="width:18px;height:18px;"></i> Create Warehouse
      </button>
      <a href="/inventoryiq/warehouse/list.php" class="btn btn-ghost btn-lg">Cancel</a>
    </div>

  </form>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
