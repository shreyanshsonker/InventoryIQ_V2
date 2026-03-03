<?php
/**
 * InventoryIQ v2.0 — Manage Restock Requests (Screen 12)
 * AI Rules §5 — Company Admin approves/rejects
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
require_once '../includes/notify.php';
check_role(['company_admin']);

$page_title = 'Restock Requests';
$company_id = $_SESSION['company_id'];
$error = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $response_note = trim($_POST['response_note'] ?? '');

    if ($request_id > 0 && in_array($action, ['approved', 'rejected'])) {
        // Verify request belongs to company
        $chk = mysqli_prepare($conn,
            'SELECT rr.request_id, rr.warehouse_id, rr.product_id, rr.quantity_needed, rr.requested_by,
                    p.product_name, p.stock_quantity
             FROM restock_requests rr
             JOIN warehouses w ON w.warehouse_id = rr.warehouse_id
             JOIN products p ON p.product_id = rr.product_id
             WHERE rr.request_id = ? AND w.company_id = ? AND rr.status = ?'
        );
        $pending = 'pending';
        mysqli_stmt_bind_param($chk, 'iis', $request_id, $company_id, $pending);
        mysqli_stmt_execute($chk);
        $req = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
        mysqli_stmt_close($chk);

        if ($req) {
            // Update request
            $upd = mysqli_prepare($conn,
                'UPDATE restock_requests SET status = ?, response_note = ?, responded_by = ?, responded_at = NOW() WHERE request_id = ?'
            );
            mysqli_stmt_bind_param($upd, 'ssii', $action, $response_note, $_SESSION['user_id'], $request_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            // If approved, add stock
            if ($action === 'approved') {
                $new_qty = (int)$req['stock_quantity'] + (int)$req['quantity_needed'];
                $stock_upd = mysqli_prepare($conn, 'UPDATE products SET stock_quantity = ? WHERE product_id = ?');
                mysqli_stmt_bind_param($stock_upd, 'ii', $new_qty, $req['product_id']);
                mysqli_stmt_execute($stock_upd);
                mysqli_stmt_close($stock_upd);
            }

            // Notify requester
            $prio = $action === 'approved' ? 'info' : 'warning';
            create_notification($conn, $company_id, $req['warehouse_id'],
                'Restock ' . ucfirst($action) . ': ' . $req['product_name'],
                'Your request for ' . $req['quantity_needed'] . ' units has been ' . $action . '.' .
                (!empty($response_note) ? ' Note: ' . $response_note : ''),
                $prio, 'restock', $_SESSION['user_id']
            );

            write_audit_log($conn, $_SESSION['user_id'], 'company_admin', $company_id, $req['warehouse_id'],
                'RESTOCK_' . strtoupper($action), ucfirst($action) . ' restock: ' . $req['product_name'] . ' x' . $req['quantity_needed']);

            header('Location: /inventoryiq/restock/manage.php?success=1');
            exit;
        }
    }
}

// Fetch all pending requests
$stmt = mysqli_prepare($conn,
    'SELECT rr.request_id, rr.quantity_needed, rr.note, rr.status, rr.created_at,
            p.product_name, p.stock_quantity, p.sku,
            w.warehouse_name,
            u.full_name AS requester
     FROM restock_requests rr
     JOIN warehouses w ON w.warehouse_id = rr.warehouse_id
     JOIN products p ON p.product_id = rr.product_id
     JOIN users u ON u.user_id = rr.requested_by
     WHERE w.company_id = ?
     ORDER BY FIELD(rr.status, "pending", "approved", "rejected"), rr.created_at DESC
     LIMIT 50'
);
mysqli_stmt_bind_param($stmt, 'i', $company_id);
mysqli_stmt_execute($stmt);
$requests = [];
$r = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($r)) { $requests[] = $row; }
mysqli_stmt_close($stmt);

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Restock Requests</h1>
</div>

<?php if (empty($requests)): ?>
<div class="glass-card-static" style="text-align:center;padding:60px;">
  <i data-lucide="package-check" style="width:48px;height:48px;color:var(--text-muted);display:block;margin:0 auto 16px;"></i>
  <h2 class="text-gradient" style="font-size:22px;">No requests</h2>
</div>
<?php else: ?>

<div class="data-table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Product</th>
        <th>SKU</th>
        <th>Warehouse</th>
        <th>Requester</th>
        <th>Qty</th>
        <th>Stock</th>
        <th>Note</th>
        <th>Status</th>
        <th>Date</th>
        <th style="width:180px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $rq): ?>
      <tr>
        <td style="font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($rq['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="mono"><?php echo htmlspecialchars($rq['sku'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td><?php echo htmlspecialchars($rq['warehouse_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($rq['requester'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td style="font-weight:700;color:#fff;"><?php echo (int)$rq['quantity_needed']; ?></td>
        <td><?php echo (int)$rq['stock_quantity']; ?></td>
        <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($rq['note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="badge badge-<?php echo $rq['status']; ?>"><?php echo ucfirst($rq['status']); ?></span></td>
        <td style="font-size:12px;color:var(--text-muted);"><?php echo date('d M', strtotime($rq['created_at'])); ?></td>
        <td>
          <?php if ($rq['status'] === 'pending'): ?>
          <form method="POST" style="display:inline-flex;gap:6px;">
            <input type="hidden" name="request_id" value="<?php echo (int)$rq['request_id']; ?>">
            <input type="hidden" name="response_note" value="">
            <button type="submit" name="action" value="approved" class="btn btn-success" style="padding:4px 10px;font-size:11px;">Approve</button>
            <button type="submit" name="action" value="rejected" class="btn btn-danger" style="padding:4px 10px;font-size:11px;">Reject</button>
          </form>
          <?php else: ?>
            <span style="color:var(--text-muted);font-size:12px;">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
