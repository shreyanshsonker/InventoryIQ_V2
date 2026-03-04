<?php
/**
 * InventoryIQ v2.0 — Edit Warehouse
 * AI Rules §5 — Company Admin only
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
check_role(['company_admin']);

$page_title = 'Edit Warehouse';
$company_id = $_SESSION['company_id'];
$error = '';

$wh_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($wh_id <= 0) { header('Location: /inventoryiq/warehouse/list.php'); exit; }

// Fetch warehouse (scoped to company)
$stmt = mysqli_prepare($conn, 'SELECT * FROM warehouses WHERE warehouse_id = ? AND company_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $wh_id, $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$wh = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$wh) { header('Location: /inventoryiq/403.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $warehouse_name = trim($_POST['warehouse_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $capacity_limit = !empty($_POST['capacity_limit']) ? (int)$_POST['capacity_limit'] : null;
    $priority_rank = !empty($_POST['priority_rank']) ? (int)$_POST['priority_rank'] : 99;
    $low_stock_override = !empty($_POST['low_stock_override']) ? (int)$_POST['low_stock_override'] : null;
    $status = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

    if (empty($warehouse_name)) {
        $error = 'Warehouse name is required.';
    } else {
        $stmt = mysqli_prepare($conn,
            'UPDATE warehouses SET warehouse_name = ?, location = ?, contact_person = ?,
                    capacity_limit = ?, priority_rank = ?, low_stock_override = ?, status = ?
             WHERE warehouse_id = ? AND company_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'sssiiisii',
            $warehouse_name, $location, $contact_person,
            $capacity_limit, $priority_rank, $low_stock_override, $status,
            $wh_id, $company_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        write_audit_log($conn, $_SESSION['user_id'], 'company_admin', $company_id, $wh_id,
            'WAREHOUSE_UPDATE', 'Updated warehouse: ' . $warehouse_name);

        header('Location: /inventoryiq/warehouse/list.php?success=1');
        exit;
    }

    // Fill form with posted data on error
    $wh['warehouse_name'] = $warehouse_name;
    $wh['location'] = $location;
    $wh['contact_person'] = $contact_person;
    $wh['capacity_limit'] = $capacity_limit;
    $wh['priority_rank'] = $priority_rank;
    $wh['low_stock_override'] = $low_stock_override;
    $wh['status'] = $status;
}

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <div>
    <h1 style="font-size:28px;">Edit Warehouse</h1>
    <p class="label" style="margin-top:4px;">@<?php echo htmlspecialchars($wh['handle'], ENT_QUOTES, 'UTF-8'); ?></p>
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
               value="<?php echo htmlspecialchars($wh['warehouse_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="glass-select">
          <option value="active" <?php echo $wh['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="inactive" <?php echo $wh['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="location">Location</label>
      <input type="text" id="location" name="location" class="glass-input"
             value="<?php echo htmlspecialchars($wh['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="contact_person">Contact Person</label>
      <input type="text" id="contact_person" name="contact_person" class="glass-input"
             value="<?php echo htmlspecialchars($wh['contact_person'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </div>

    <div class="grid-2" style="grid-template-columns:1fr 1fr 1fr;">
      <div class="form-group">
        <label class="form-label" for="capacity_limit">Capacity Limit</label>
        <input type="number" id="capacity_limit" name="capacity_limit" class="glass-input"
               min="0" value="<?php echo $wh['capacity_limit'] !== null ? (int)$wh['capacity_limit'] : ''; ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="priority_rank">Priority Rank</label>
        <input type="number" id="priority_rank" name="priority_rank" class="glass-input"
               min="1" max="99" value="<?php echo (int)$wh['priority_rank']; ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="low_stock_override">Low Stock Override</label>
        <input type="number" id="low_stock_override" name="low_stock_override" class="glass-input"
               min="0" value="<?php echo $wh['low_stock_override'] !== null ? (int)$wh['low_stock_override'] : ''; ?>">
      </div>
    </div>

    <div style="display:flex;gap:12px;margin-top:8px;">
      <button type="submit" class="btn btn-primary btn-lg">
        <i data-lucide="save" style="width:18px;height:18px;"></i> Save Changes
      </button>
      <a href="/inventoryiq/warehouse/list.php" class="btn btn-ghost btn-lg">Cancel</a>
    </div>

  </form>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
