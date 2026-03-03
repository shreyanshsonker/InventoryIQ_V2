<?php
/**
 * InventoryIQ v2.0 — Company Management (Screen 16)
 * AI Rules §5 — SA only, suspend/reactivate companies
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
check_role(['super_admin']);

$page_title = 'Companies';

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $cid = (int)$_POST['company_id'];
    $new_status = $_POST['new_status'] === 'suspended' ? 'suspended' : 'active';

    $stmt = mysqli_prepare($conn, 'UPDATE companies SET status = ? WHERE company_id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $new_status, $cid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    write_audit_log($conn, null, 'super_admin', $cid, null,
        'COMPANY_' . strtoupper($new_status), 'Company status set to: ' . $new_status);

    header('Location: /inventoryiq/superadmin/companies.php?success=1');
    exit;
}

// Fetch all companies
$result = mysqli_query($conn,
    'SELECT c.company_id, c.company_name, c.handle, c.owner_name, c.email,
            c.status, c.created_at,
            COUNT(DISTINCT u.user_id) AS user_count,
            COUNT(DISTINCT w.warehouse_id) AS wh_count,
            COUNT(DISTINCT p.product_id) AS product_count
     FROM companies c
     LEFT JOIN users u ON u.company_id = c.company_id
     LEFT JOIN warehouses w ON w.company_id = c.company_id
     LEFT JOIN products p ON p.warehouse_id = w.warehouse_id
     GROUP BY c.company_id
     ORDER BY c.created_at DESC'
);
$companies = [];
while ($row = mysqli_fetch_assoc($result)) { $companies[] = $row; }

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Company Management</h1>
  <span class="label"><?php echo count($companies); ?> companies</span>
</div>

<div class="data-table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Company</th>
        <th>Handle</th>
        <th>Owner</th>
        <th>Email</th>
        <th>Users</th>
        <th>WHs</th>
        <th>Products</th>
        <th>Status</th>
        <th>Registered</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($companies as $co): ?>
      <tr>
        <td style="font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($co['company_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="mono" style="font-size:11px;">@<?php echo htmlspecialchars($co['handle'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td><?php echo htmlspecialchars($co['owner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td style="font-size:12px;"><?php echo htmlspecialchars($co['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo (int)$co['user_count']; ?></td>
        <td><?php echo (int)$co['wh_count']; ?></td>
        <td><?php echo (int)$co['product_count']; ?></td>
        <td><span class="badge badge-<?php echo $co['status']; ?>"><?php echo ucfirst($co['status']); ?></span></td>
        <td style="font-size:12px;color:var(--text-muted);"><?php echo date('d M Y', strtotime($co['created_at'])); ?></td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="toggle_status" value="1">
            <input type="hidden" name="company_id" value="<?php echo (int)$co['company_id']; ?>">
            <?php if ($co['status'] === 'active'): ?>
              <input type="hidden" name="new_status" value="suspended">
              <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:11px;"
                      onclick="return confirm('Suspend this company?')">Suspend</button>
            <?php else: ?>
              <input type="hidden" name="new_status" value="active">
              <button type="submit" class="btn btn-success" style="padding:4px 10px;font-size:11px;">Reactivate</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
