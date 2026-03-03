<?php
/**
 * InventoryIQ v2.0 — Restock Request (Screen 11)
 * AI Rules §5 — WH Manager/Staff create requests
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
require_once '../includes/notify.php';
check_role(['wh_manager', 'wh_staff']);

$page_title = 'Request Restock';
$warehouse_id = $_SESSION['warehouse_id'];
$company_id = $_SESSION['company_id'];
$error = '';

// Products for dropdown
$stmt = mysqli_prepare($conn, 'SELECT product_id, product_name, sku, stock_quantity FROM products WHERE warehouse_id = ? ORDER BY product_name');
mysqli_stmt_bind_param($stmt, 'i', $warehouse_id);
mysqli_stmt_execute($stmt);
$products = [];
$r = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($r)) { $products[] = $row; }
mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity_needed = (int)($_POST['quantity_needed'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($product_id <= 0 || $quantity_needed <= 0) {
        $error = 'Please select a product and enter a valid quantity.';
    } else {
        // Verify product belongs to warehouse
        $chk = mysqli_prepare($conn, 'SELECT product_name FROM products WHERE product_id = ? AND warehouse_id = ?');
        mysqli_stmt_bind_param($chk, 'ii', $product_id, $warehouse_id);
        mysqli_stmt_execute($chk);
        $prod = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
        mysqli_stmt_close($chk);

        if (!$prod) {
            $error = 'Invalid product.';
        } else {
            $stmt = mysqli_prepare($conn,
                'INSERT INTO restock_requests (warehouse_id, product_id, requested_by, quantity_needed, note) VALUES (?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmt, 'iiiis', $warehouse_id, $product_id, $_SESSION['user_id'], $quantity_needed, $note);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Notify company admin
            create_notification($conn, $company_id, null,
                'Restock Request: ' . $prod['product_name'],
                $_SESSION['full_name'] . ' requested ' . $quantity_needed . ' units of ' . $prod['product_name'] . '.',
                'info', 'restock', $_SESSION['user_id']
            );

            write_audit_log($conn, $_SESSION['user_id'], $_SESSION['role'], $company_id, $warehouse_id,
                'RESTOCK_REQUEST', 'Requested restock: ' . $prod['product_name'] . ' x' . $quantity_needed);

            header('Location: /inventoryiq/restock/request.php?success=1');
            exit;
        }
    }
}

// Past requests
$hist = mysqli_prepare($conn,
    'SELECT rr.request_id, rr.quantity_needed, rr.note, rr.status, rr.created_at, rr.response_note,
            p.product_name
     FROM restock_requests rr
     JOIN products p ON p.product_id = rr.product_id
     WHERE rr.warehouse_id = ? AND rr.requested_by = ?
     ORDER BY rr.created_at DESC LIMIT 20'
);
mysqli_stmt_bind_param($hist, 'ii', $warehouse_id, $_SESSION['user_id']);
mysqli_stmt_execute($hist);
$history = [];
$r = mysqli_stmt_get_result($hist);
while ($row = mysqli_fetch_assoc($r)) { $history[] = $row; }
mysqli_stmt_close($hist);

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Request Restock</h1>
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
  <span>Restock request submitted!</span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);">
  <!-- Submit Form -->
  <div class="glass-card-static">
    <h3 class="section-title mb-4">New Request</h3>
    <form method="POST" style="display:flex;flex-direction:column;gap:20px;">
      <div class="form-group">
        <label class="form-label" for="product_id">Product <span class="required">*</span></label>
        <select id="product_id" name="product_id" class="glass-select" required>
          <option value="">Select Product</option>
          <?php foreach ($products as $p): ?>
            <option value="<?php echo $p['product_id']; ?>">
              <?php echo htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8'); ?> (Stock: <?php echo (int)$p['stock_quantity']; ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="quantity_needed">Quantity Needed <span class="required">*</span></label>
        <input type="number" id="quantity_needed" name="quantity_needed" class="glass-input" min="1" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="note">Note</label>
        <textarea id="note" name="note" class="glass-textarea" rows="3" placeholder="Reason for restock..."></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-lg">
        <i data-lucide="package-plus" style="width:18px;height:18px;"></i> Submit Request
      </button>
    </form>
  </div>

  <!-- History -->
  <div class="glass-card-static">
    <h3 class="section-title mb-4">Your Requests</h3>
    <?php if (empty($history)): ?>
      <p style="color:var(--text-muted);">No requests yet.</p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <?php foreach ($history as $h): ?>
        <div style="padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;border-left:3px solid
          <?php echo $h['status']==='approved'?'var(--accent-green)':($h['status']==='rejected'?'var(--accent-red)':'var(--accent-indigo)'); ?>;">
          <div class="flex-between" style="margin-bottom:4px;">
            <span style="font-weight:600;color:var(--text-primary);font-size:13px;">
              <?php echo htmlspecialchars($h['product_name'], ENT_QUOTES, 'UTF-8'); ?> × <?php echo (int)$h['quantity_needed']; ?>
            </span>
            <span class="badge badge-<?php echo $h['status']; ?>"><?php echo ucfirst($h['status']); ?></span>
          </div>
          <span style="font-size:11px;color:var(--text-muted);"><?php echo date('d M Y', strtotime($h['created_at'])); ?></span>
          <?php if (!empty($h['response_note'])): ?>
            <p style="font-size:12px;color:var(--text-label);margin-top:4px;font-style:italic;">"<?php echo htmlspecialchars($h['response_note'], ENT_QUOTES, 'UTF-8'); ?>"</p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
