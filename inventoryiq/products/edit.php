<?php
/**
 * InventoryIQ v2.0 — Edit Product
 * AI Rules §5 — Manager + Staff
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
require_once '../includes/notify.php';
check_role(['wh_manager', 'wh_staff']);

$page_title = 'Edit Product';
$warehouse_id = $_SESSION['warehouse_id'];
$company_id = $_SESSION['company_id'];
$error = '';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) { header('Location: /inventoryiq/products/view.php'); exit; }

// Fetch product (scoped to warehouse)
$stmt = mysqli_prepare($conn, 'SELECT * FROM products WHERE product_id = ? AND warehouse_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $product_id, $warehouse_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) { header('Location: /inventoryiq/403.php'); exit; }

// Categories
$cat_stmt = mysqli_prepare($conn, 'SELECT category_id, category_name FROM categories WHERE company_id = ? ORDER BY category_name');
mysqli_stmt_bind_param($cat_stmt, 'i', $company_id);
mysqli_stmt_execute($cat_stmt);
$categories = [];
$cr = mysqli_stmt_get_result($cat_stmt);
while ($row = mysqli_fetch_assoc($cr)) { $categories[] = $row; }
mysqli_stmt_close($cat_stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $price = (float)($_POST['price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (empty($product_name) || $price <= 0) {
        $error = 'Product name and valid price are required.';
    }

    // Handle image upload
    $new_image = $product['primary_image'];
    if (empty($error) && isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $real_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($real_type, $allowed)) {
            $error = 'Invalid image type.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $error = 'Image exceeds 2MB.';
        } else {
            $ext_map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            $filename = bin2hex(random_bytes(16)) . '.' . $ext_map[$real_type];
            $dest = dirname(__DIR__) . '/uploads/products/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Delete old image
                if (!empty($product['primary_image'])) {
                    $old = dirname(__DIR__) . '/uploads/products/' . $product['primary_image'];
                    if (file_exists($old)) unlink($old);
                }
                $new_image = $filename;
            }
        }
    }

    if (empty($error)) {
        $stmt = mysqli_prepare($conn,
            'UPDATE products SET product_name = ?, category_id = ?, price = ?, stock_quantity = ?,
                    description = ?, primary_image = ? WHERE product_id = ? AND warehouse_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'sidiisii',
            $product_name, $category_id, $price, $stock_quantity,
            $description, $new_image, $product_id, $warehouse_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        check_low_stock($conn, $product_id, $warehouse_id);

        write_audit_log($conn, $_SESSION['user_id'], $_SESSION['role'], $company_id, $warehouse_id,
            'PRODUCT_UPDATE', 'Updated product: ' . $product_name . ' (ID:' . $product_id . ')');

        header('Location: /inventoryiq/products/view.php?success=1');
        exit;
    }

    // Refill on error
    $product['product_name'] = $product_name;
    $product['category_id'] = $category_id;
    $product['price'] = $price;
    $product['stock_quantity'] = $stock_quantity;
    $product['description'] = $description;
}

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <div>
    <h1 style="font-size:28px;">Edit Product</h1>
    <p class="label" style="margin-top:4px;">Inventory &gt; Edit</p>
  </div>
  <a href="/inventoryiq/products/view.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back
  </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert-banner alert-error mb-6">
  <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:65fr 35fr;gap:var(--space-6);">
  <div class="glass-card-static">
    <h3 class="section-title mb-4">Product Details</h3>
    <form method="POST" enctype="multipart/form-data" id="edit-product-form"
          style="display:flex;flex-direction:column;gap:20px;">

      <div class="form-group">
        <label class="form-label" for="product_name">Product Name <span class="required">*</span></label>
        <input type="text" id="product_name" name="product_name" class="glass-input"
               value="<?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="category_id">Category</label>
        <select id="category_id" name="category_id" class="glass-select">
          <option value="">None</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>" <?php echo $product['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="price">Price (₹) <span class="required">*</span></label>
          <input type="number" id="price" name="price" class="glass-input" step="0.01" min="0.01"
                 value="<?php echo (float)$product['price']; ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="stock_quantity">Stock Quantity <span class="required">*</span></label>
          <input type="number" id="stock_quantity" name="stock_quantity" class="glass-input" min="0"
                 value="<?php echo (int)$product['stock_quantity']; ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="glass-textarea" rows="4"><?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">SKU</label>
        <p class="mono" style="font-size:16px;padding:10px 0;"><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></p>
      </div>

      <div style="display:flex;gap:12px;margin-top:8px;">
        <button type="submit" class="btn btn-primary btn-lg">
          <i data-lucide="save" style="width:18px;height:18px;"></i> Save Changes
        </button>
        <button type="button" class="btn btn-danger btn-lg"
                onclick="confirmDelete('delete-product-form', '<?php echo htmlspecialchars(addslashes($product['product_name']), ENT_QUOTES, 'UTF-8'); ?>')">
          <i data-lucide="trash-2" style="width:18px;height:18px;"></i> Delete
        </button>
        <a href="/inventoryiq/products/view.php" class="btn btn-ghost btn-lg">Cancel</a>
      </div>

    </form>
    <form id="delete-product-form" method="POST" action="/inventoryiq/products/delete.php" style="display:none;">
      <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
    </form>
  </div>

  <div>
    <div class="glass-card-static mb-6">
      <h3 class="section-title mb-4">Product Image</h3>
      <?php if (!empty($product['primary_image'])): ?>
        <img src="/inventoryiq/uploads/products/<?php echo htmlspecialchars($product['primary_image'], ENT_QUOTES, 'UTF-8'); ?>"
             alt="Product" style="width:100%;border-radius:12px;margin-bottom:16px;">
      <?php endif; ?>
      <label for="product_image" class="upload-zone" style="display:block;">
        <i data-lucide="cloud-upload" style="width:40px;height:40px;" class="upload-icon"></i>
        <p style="color:var(--text-label);font-size:14px;margin-top:8px;">Upload new image</p>
        <p style="color:var(--text-muted);font-size:12px;">JPG PNG WebP — Max 2MB</p>
        <input type="file" id="product_image" name="product_image" form="edit-product-form"
               accept="image/jpeg,image/png,image/webp" style="display:none;">
      </label>
    </div>
  </div>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
