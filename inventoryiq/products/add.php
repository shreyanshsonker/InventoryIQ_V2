<?php
/**
 * InventoryIQ v2.0 — Add Product (Screen 09)
 * AI Rules §5, §8.3 — Image upload with validation
 */
require_once '../config/db.php';
require_once '../auth/check.php';
require_once '../includes/audit.php';
require_once '../includes/notify.php';
check_role(['wh_manager', 'wh_staff']);

$page_title = 'Add Product';
$warehouse_id = $_SESSION['warehouse_id'];
$company_id = $_SESSION['company_id'];
$error = '';

$form = [
    'product_name' => '', 'category_id' => '', 'price' => '',
    'stock_quantity' => '', 'description' => '', 'sku' => ''
];

// Categories
$cat_stmt = mysqli_prepare($conn, 'SELECT category_id, category_name FROM categories WHERE company_id = ? ORDER BY category_name');
mysqli_stmt_bind_param($cat_stmt, 'i', $company_id);
mysqli_stmt_execute($cat_stmt);
$categories = [];
$cr = mysqli_stmt_get_result($cat_stmt);
while ($row = mysqli_fetch_assoc($cr)) { $categories[] = $row; }
mysqli_stmt_close($cat_stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $val) {
        $form[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    }

    // Auto-generate SKU if empty
    if (empty($form['sku'])) {
        $form['sku'] = strtoupper(substr(str_replace(' ','',$form['product_name']),0,3)) . '-' . str_pad(mt_rand(0,99999), 5, '0', STR_PAD_LEFT);
    }

    // Validation
    if (empty($form['product_name']) || empty($form['price']) || $form['stock_quantity'] === '') {
        $error = 'Product name, price, and stock quantity are required.';
    } elseif ((float)$form['price'] <= 0) {
        $error = 'Price must be greater than zero.';
    } elseif ((int)$form['stock_quantity'] < 0) {
        $error = 'Stock quantity cannot be negative.';
    } else {
        // Check duplicate SKU
        $sku_chk = mysqli_prepare($conn, 'SELECT product_id FROM products WHERE sku = ?');
        mysqli_stmt_bind_param($sku_chk, 's', $form['sku']);
        mysqli_stmt_execute($sku_chk);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($sku_chk))) {
            $error = 'This SKU already exists. Please use a unique SKU.';
        }
        mysqli_stmt_close($sku_chk);
    }

    // Image upload (AI Rules §8.3)
    $primary_image = null;
    if (empty($error) && isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $real_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($real_type, $allowed_types)) {
            $error = 'Invalid image type. Only JPG, PNG, WebP allowed.';
        } elseif ($file['size'] > $max_size) {
            $error = 'Image size exceeds 2MB limit.';
        } else {
            $ext_map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            $ext = $ext_map[$real_type];
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = dirname(__DIR__) . '/uploads/products/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $primary_image = $filename;
            } else {
                $error = 'Failed to save image.';
            }
        }
    }

    // Insert product
    if (empty($error)) {
        $cat_id = !empty($form['category_id']) ? (int)$form['category_id'] : null;
        $price = (float)$form['price'];
        $qty = (int)$form['stock_quantity'];

        $stmt = mysqli_prepare($conn,
            'INSERT INTO products (warehouse_id, category_id, product_name, sku, price, stock_quantity, description, primary_image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'iissdiss',
            $warehouse_id, $cat_id, $form['product_name'], $form['sku'],
            $price, $qty, $form['description'], $primary_image
        );
        mysqli_stmt_execute($stmt);
        $product_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Insert into product_images if uploaded
        if ($primary_image) {
            $img_stmt = mysqli_prepare($conn,
                'INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)'
            );
            mysqli_stmt_bind_param($img_stmt, 'is', $product_id, $primary_image);
            mysqli_stmt_execute($img_stmt);
            mysqli_stmt_close($img_stmt);
        }

        // Check low stock
        check_low_stock($conn, $product_id, $warehouse_id);

        write_audit_log($conn, $_SESSION['user_id'], $_SESSION['role'], $company_id, $warehouse_id,
            'PRODUCT_CREATE', 'Added product: ' . $form['product_name'] . ' (SKU: ' . $form['sku'] . ')');

        header('Location: /inventoryiq/products/view.php?success=1');
        exit;
    }
}

require_once '../includes/header.php';
?>

<div class="flex-between mb-6">
  <div>
    <h1 style="font-size:28px;">Add New Product</h1>
    <p class="label" style="margin-top:4px;">Inventory &gt; Add Product</p>
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
  <!-- Left: Form -->
  <div class="glass-card-static">
    <h3 class="section-title mb-4">Product Details</h3>
    <form method="POST" enctype="multipart/form-data" id="add-product-form"
          style="display:flex;flex-direction:column;gap:20px;">

      <div class="form-group">
        <label class="form-label" for="product_name">Product Name <span class="required">*</span></label>
        <input type="text" id="product_name" name="product_name" class="glass-input"
               value="<?php echo htmlspecialchars($form['product_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="category_id">Category</label>
        <select id="category_id" name="category_id" class="glass-select">
          <option value="">Select Category</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>" <?php echo $form['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="price">Price (₹) <span class="required">*</span></label>
          <input type="number" id="price" name="price" class="glass-input"
                 step="0.01" min="0.01"
                 value="<?php echo htmlspecialchars($form['price'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="stock_quantity">Stock Quantity <span class="required">*</span></label>
          <input type="number" id="stock_quantity" name="stock_quantity" class="glass-input"
                 min="0" value="<?php echo htmlspecialchars($form['stock_quantity'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="glass-textarea" rows="4"><?php echo htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label" for="sku">SKU</label>
        <input type="text" id="sku" name="sku" class="glass-input"
               placeholder="Leave empty to auto-generate"
               value="<?php echo htmlspecialchars($form['sku'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>

      <div style="display:flex;gap:12px;margin-top:8px;">
        <button type="submit" class="btn btn-primary btn-lg">
          <i data-lucide="save" style="width:18px;height:18px;"></i> Save Product
        </button>
        <a href="/inventoryiq/products/view.php" class="btn btn-ghost btn-lg">Cancel</a>
      </div>

    </form>
  </div>

  <!-- Right: Image Upload -->
  <div>
    <div class="glass-card-static mb-6">
      <h3 class="section-title mb-4">Product Image</h3>
      <label for="product_image" class="upload-zone" style="display:block;">
        <i data-lucide="cloud-upload" style="width:40px;height:40px;" class="upload-icon"></i>
        <p style="color:var(--text-label);font-size:14px;margin-top:8px;">Drag & drop or click to upload</p>
        <p style="color:var(--text-muted);font-size:12px;margin-top:4px;">JPG PNG WebP — Max 2MB</p>
        <input type="file" id="product_image" name="product_image" form="add-product-form"
               accept="image/jpeg,image/png,image/webp" style="display:none;">
      </label>
    </div>
  </div>
</div>

<?php
require_once '../includes/footer.php';
mysqli_close($conn);
?>
