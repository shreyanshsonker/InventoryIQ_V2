<?php
/**
 * InventoryIQ v2.0 — Category Manager
 * AI Rules §5 — Company Admin + WH Manager
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
check_role(['company_admin', 'wh_manager']);

$page_title = 'Categories';
$company_id = $_SESSION['company_id'];
$error = '';

// Handle ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['category_name'] ?? '');
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            // Duplicate check
            $chk = mysqli_prepare($conn, 'SELECT category_id FROM categories WHERE company_id = ? AND category_name = ?');
            mysqli_stmt_bind_param($chk, 'is', $company_id, $name);
            mysqli_stmt_execute($chk);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
                $error = 'Category already exists.';
            } else {
                $stmt = mysqli_prepare($conn, 'INSERT INTO categories (company_id, category_name) VALUES (?, ?)');
                mysqli_stmt_bind_param($stmt, 'is', $company_id, $name);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                write_audit_log($conn, $_SESSION['user_id'], $_SESSION['role'], $company_id, $_SESSION['warehouse_id'], 'CATEGORY_CREATE', 'Created category: ' . $name);
                header('Location: /inventoryiq/categories/manage.php?success=1');
                exit;
            }
            mysqli_stmt_close($chk);
        }
    } elseif ($_POST['action'] === 'delete') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        // Check no products use this category
        $usage = mysqli_prepare($conn, 'SELECT COUNT(product_id) AS cnt FROM products p JOIN warehouses w ON w.warehouse_id = p.warehouse_id WHERE p.category_id = ? AND w.company_id = ?');
        mysqli_stmt_bind_param($usage, 'ii', $cat_id, $company_id);
        mysqli_stmt_execute($usage);
        $count = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($usage))['cnt'];
        mysqli_stmt_close($usage);

        if ($count > 0) {
            $error = 'Cannot delete category with ' . $count . ' assigned product(s). Reassign them first.';
        } else {
            $del = mysqli_prepare($conn, 'DELETE FROM categories WHERE category_id = ? AND company_id = ?');
            mysqli_stmt_bind_param($del, 'ii', $cat_id, $company_id);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);
            write_audit_log($conn, $_SESSION['user_id'], $_SESSION['role'], $company_id, $_SESSION['warehouse_id'], 'CATEGORY_DELETE', 'Deleted category ID: ' . $cat_id);
            header('Location: /inventoryiq/categories/manage.php?success=1');
            exit;
        }
    }
}

// Fetch categories with product count
$stmt = mysqli_prepare($conn,
    'SELECT c.category_id, c.category_name, COUNT(p.product_id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.category_id
     WHERE c.company_id = ?
     GROUP BY c.category_id
     ORDER BY c.category_name'
);
mysqli_stmt_bind_param($stmt, 'i', $company_id);
mysqli_stmt_execute($stmt);
$categories = [];
$r = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($r)) { $categories[] = $row; }
mysqli_stmt_close($stmt);

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Categories</h1>
</div>

<?php if (!empty($error)): ?>
<div class="alert-banner alert-error mb-6">
  <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);">
  <!-- Add Category -->
  <div class="glass-card-static">
    <h3 class="section-title mb-4">Add Category</h3>
    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;">
      <input type="hidden" name="action" value="add">
      <div class="form-group" style="flex:1;">
        <label class="form-label" for="category_name">Category Name</label>
        <input type="text" id="category_name" name="category_name" class="glass-input" required>
      </div>
      <button type="submit" class="btn btn-primary" style="height:44px;">
        <i data-lucide="plus" style="width:16px;height:16px;"></i> Add
      </button>
    </form>
  </div>

  <!-- Category List -->
  <div class="glass-card-static">
    <h3 class="section-title mb-4">All Categories (<?php echo count($categories); ?>)</h3>
    <?php if (empty($categories)): ?>
      <p style="color:var(--text-muted);">No categories yet.</p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($categories as $cat): ?>
        <div class="flex-between" style="padding:10px 12px;background:rgba(255,255,255,0.03);border-radius:10px;">
          <div style="display:flex;align-items:center;gap:8px;">
            <i data-lucide="tag" style="width:16px;height:16px;color:var(--accent-indigo-light);"></i>
            <span style="color:var(--text-primary);font-weight:500;"><?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="badge" style="background:rgba(99,102,241,0.1);color:var(--text-lavender);border:1px solid rgba(99,102,241,0.2);font-size:10px;">
              <?php echo (int)$cat['product_count']; ?> products
            </span>
          </div>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="category_id" value="<?php echo (int)$cat['category_id']; ?>">
            <button type="submit" class="action-icon delete" onclick="return confirm('Delete this category?')" title="Delete">
              <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
            </button>
          </form>
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
