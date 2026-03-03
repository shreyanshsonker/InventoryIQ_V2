<?php
/**
 * InventoryIQ v2.0 — Product Inventory Table (Screen 08)
 * AI Rules §5 — Manager + Staff
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/notify.php';
check_role(['wh_manager', 'wh_staff', 'company_admin']);

$page_title = 'Inventory';
$warehouse_id = $_SESSION['warehouse_id'];
$company_id = $_SESSION['company_id'];
$role = $_SESSION['role'];

// For company_admin, allow viewing any warehouse via ?wh=
if ($role === 'company_admin' && isset($_GET['wh'])) {
    $view_wh = (int)$_GET['wh'];
    // Verify warehouse belongs to company
    $chk = mysqli_prepare($conn, 'SELECT warehouse_id FROM warehouses WHERE warehouse_id = ? AND company_id = ?');
    mysqli_stmt_bind_param($chk, 'ii', $view_wh, $company_id);
    mysqli_stmt_execute($chk);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
        $warehouse_id = $view_wh;
    }
    mysqli_stmt_close($chk);
}

if (!$warehouse_id && $role !== 'company_admin') {
    header('Location: /inventoryiq/403.php');
    exit;
}

// Get low stock threshold
$threshold = $warehouse_id ? get_low_stock_threshold($conn, $warehouse_id) : 10;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stock_filter = isset($_GET['stock_status']) ? $_GET['stock_status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'product_name';
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query
$where = '1=1';
$params = [];
$types = '';

if ($warehouse_id) {
    $where .= ' AND p.warehouse_id = ?';
    $params[] = $warehouse_id;
    $types .= 'i';
} elseif ($role === 'company_admin') {
    $where .= ' AND w.company_id = ?';
    $params[] = $company_id;
    $types .= 'i';
}

if (!empty($search)) {
    $like = '%' . $search . '%';
    $where .= ' AND (p.product_name LIKE ? OR p.sku LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($cat_filter > 0) {
    $where .= ' AND p.category_id = ?';
    $params[] = $cat_filter;
    $types .= 'i';
}

if ($stock_filter === 'in_stock') {
    $where .= ' AND p.stock_quantity > ?';
    $params[] = $threshold;
    $types .= 'i';
} elseif ($stock_filter === 'low_stock') {
    $where .= ' AND p.stock_quantity > 0 AND p.stock_quantity <= ?';
    $params[] = $threshold;
    $types .= 'i';
} elseif ($stock_filter === 'out_of_stock') {
    $where .= ' AND p.stock_quantity = 0';
}

// Allowed sort columns
$sort_map = [
    'product_name' => 'p.product_name',
    'sku' => 'p.sku',
    'price' => 'p.price',
    'stock_quantity' => 'p.stock_quantity',
    'updated_at' => 'p.updated_at'
];
$order_by = isset($sort_map[$sort]) ? $sort_map[$sort] : 'p.product_name';

// Count total
$count_sql = "SELECT COUNT(p.product_id) AS total FROM products p JOIN warehouses w ON w.warehouse_id = p.warehouse_id WHERE $where";
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
mysqli_stmt_close($count_stmt);
$total_pages = max(1, ceil($total / $per_page));

// Fetch products
$sql = "SELECT p.product_id, p.product_name, p.sku, p.price, p.stock_quantity,
               p.primary_image, p.updated_at, c.category_name
        FROM products p
        JOIN warehouses w ON w.warehouse_id = p.warehouse_id
        LEFT JOIN categories c ON c.category_id = p.category_id
        WHERE $where
        ORDER BY $order_by $order
        LIMIT ? OFFSET ?";
$fetch_types = $types . 'ii';
$fetch_params = array_merge($params, [$per_page, $offset]);

$stmt = mysqli_prepare($conn, $sql);
if (!empty($fetch_types)) {
    mysqli_stmt_bind_param($stmt, $fetch_types, ...$fetch_params);
}
mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);
$products = [];
while ($row = mysqli_fetch_assoc($products_result)) { $products[] = $row; }
mysqli_stmt_close($stmt);

// Categories for filter dropdown
$cat_sql = 'SELECT category_id, category_name FROM categories WHERE company_id = ? ORDER BY category_name';
$cat_stmt = mysqli_prepare($conn, $cat_sql);
mysqli_stmt_bind_param($cat_stmt, 'i', $company_id);
mysqli_stmt_execute($cat_stmt);
$cat_result = mysqli_stmt_get_result($cat_stmt);
$categories = [];
while ($row = mysqli_fetch_assoc($cat_result)) { $categories[] = $row; }
mysqli_stmt_close($cat_stmt);

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="flex-between mb-6">
  <h1 style="font-size:28px;">Inventory</h1>
  <?php if ($role !== 'company_admin' || $warehouse_id): ?>
  <a href="/inventoryiq/products/add.php<?php echo $role === 'company_admin' ? '?wh=' . $warehouse_id : ''; ?>" class="btn btn-primary">
    <i data-lucide="plus" style="width:18px;height:18px;"></i> Add Product
  </a>
  <?php endif; ?>
</div>

<!-- Search & Filter Bar -->
<div class="glass-card-static mb-6" style="padding:16px 20px;">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <?php if ($role === 'company_admin' && $warehouse_id): ?>
      <input type="hidden" name="wh" value="<?php echo $warehouse_id; ?>">
    <?php endif; ?>
    <div class="form-group" style="flex:2;min-width:200px;">
      <label class="form-label">Search</label>
      <div class="input-group">
        <i data-lucide="search" class="input-icon" style="width:16px;height:16px;"></i>
        <input type="text" name="search" class="glass-input" placeholder="Search products or SKU..."
               value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </div>
    <div class="form-group" style="flex:1;min-width:140px;">
      <label class="form-label">Category</label>
      <select name="category" class="glass-select">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['category_id']; ?>" <?php echo $cat_filter === (int)$cat['category_id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="flex:1;min-width:140px;">
      <label class="form-label">Stock Status</label>
      <select name="stock_status" class="glass-select">
        <option value="">All</option>
        <option value="in_stock" <?php echo $stock_filter === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
        <option value="low_stock" <?php echo $stock_filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
        <option value="out_of_stock" <?php echo $stock_filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
      </select>
    </div>
    <div class="form-group" style="flex:1;min-width:120px;">
      <label class="form-label">Sort By</label>
      <select name="sort" class="glass-select">
        <option value="product_name" <?php echo $sort === 'product_name' ? 'selected' : ''; ?>>Name</option>
        <option value="sku" <?php echo $sort === 'sku' ? 'selected' : ''; ?>>SKU</option>
        <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price</option>
        <option value="stock_quantity" <?php echo $sort === 'stock_quantity' ? 'selected' : ''; ?>>Stock</option>
        <option value="updated_at" <?php echo $sort === 'updated_at' ? 'selected' : ''; ?>>Last Updated</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary" style="height:44px;">
      <i data-lucide="filter" style="width:16px;height:16px;"></i> Filter
    </button>
  </form>
</div>

<?php if (empty($products)): ?>
<!-- Empty State -->
<div class="glass-card-static" style="text-align:center;padding:60px;">
  <i data-lucide="package" style="width:64px;height:64px;color:var(--text-muted);display:block;margin:0 auto 20px;"></i>
  <h2 class="text-gradient" style="font-size:24px;margin-bottom:8px;">No products yet</h2>
  <p style="color:var(--text-muted);margin-bottom:24px;">Add your first product to start tracking inventory</p>
  <a href="/inventoryiq/products/add.php" class="btn btn-primary btn-lg">
    <i data-lucide="plus" style="width:18px;height:18px;"></i> Add Your First Product
  </a>
</div>
<?php else: ?>

<!-- Data Table -->
<div class="data-table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:60px;"></th>
        <th>Product</th>
        <th>SKU</th>
        <th>Category</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Status</th>
        <th>Updated</th>
        <th style="width:100px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $p):
      $qty = (int)$p['stock_quantity'];
      if ($qty === 0) { $status_class = 'badge-out-stock'; $status_label = 'Out of Stock'; }
      elseif ($qty <= $threshold) { $status_class = 'badge-low-stock'; $status_label = 'Low Stock'; }
      else { $status_class = 'badge-in-stock'; $status_label = 'In Stock'; }
    ?>
      <tr>
        <td>
          <?php if (!empty($p['primary_image'])): ?>
            <img src="/inventoryiq/uploads/products/<?php echo htmlspecialchars($p['primary_image'], ENT_QUOTES, 'UTF-8'); ?>"
                 alt="" style="width:40px;height:40px;border-radius:8px;object-fit:cover;">
          <?php else: ?>
            <div style="width:40px;height:40px;border-radius:8px;background:rgba(99,102,241,0.1);display:flex;align-items:center;justify-content:center;">
              <i data-lucide="image" style="width:18px;height:18px;color:var(--text-muted);"></i>
            </div>
          <?php endif; ?>
        </td>
        <td style="font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="mono"><?php echo htmlspecialchars($p['sku'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td>
          <?php if (!empty($p['category_name'])): ?>
            <span class="badge" style="background:rgba(99,102,241,0.12);color:var(--accent-indigo-light);border:1px solid rgba(99,102,241,0.2);">
              <?php echo htmlspecialchars($p['category_name'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
          <?php else: ?>
            <span style="color:var(--text-muted);">—</span>
          <?php endif; ?>
        </td>
        <td>₹<?php echo number_format((float)$p['price'], 2); ?></td>
        <td style="font-weight:700;color:#fff;"><?php echo $qty; ?></td>
        <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
        <td style="font-size:12px;color:var(--text-muted);"><?php echo date('d M Y', strtotime($p['updated_at'])); ?></td>
        <td>
          <a href="/inventoryiq/products/edit.php?id=<?php echo (int)$p['product_id']; ?>" class="action-icon edit" title="Edit">
            <i data-lucide="pencil" style="width:16px;height:16px;"></i>
          </a>
          <button class="action-icon delete" title="Delete"
                  onclick="confirmDelete('delete-form-<?php echo (int)$p['product_id']; ?>', '<?php echo htmlspecialchars(addslashes($p['product_name']), ENT_QUOTES, 'UTF-8'); ?>')">
            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
          </button>
          <form id="delete-form-<?php echo (int)$p['product_id']; ?>" method="POST" action="/inventoryiq/products/delete.php" style="display:none;">
            <input type="hidden" name="product_id" value="<?php echo (int)$p['product_id']; ?>">
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $cat_filter; ?>&stock_status=<?php echo $stock_filter; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-btn">
        <i data-lucide="chevron-left" style="width:14px;height:14px;"></i>
      </a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $cat_filter; ?>&stock_status=<?php echo $stock_filter; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>"
         class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
      <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $cat_filter; ?>&stock_status=<?php echo $stock_filter; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-btn">
        <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
      </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
