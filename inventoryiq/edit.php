<?php
/**
 * InventoryIQ — Edit Product (edit.php)
 * FR-03: Pre-filled edit form + UPDATE handler.
 */

// Include database connection (ARC-05)
include 'config.php';

$success_message = '';
$error_message = '';

// Read product_id from GET or POST
$product_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['product_id']) ? intval($_POST['product_id']) : 0);

if ($product_id <= 0) {
    header('Location: view.php');
    exit;
}

// POST Request — Save Changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock_quantity = $_POST['stock_quantity'] ?? '';
    $description = trim($_POST['description'] ?? '');

    // Server-side validation (SEC-05)
    $errors = [];

    if (empty($product_name)) {
        $errors[] = 'Product Name is required.';
    }
    if (empty($category)) {
        $errors[] = 'Category is required.';
    }
    if (!is_numeric($price) || floatval($price) <= 0) {
        $errors[] = 'Price must be a number greater than 0.';
    }
    if (!is_numeric($stock_quantity) || intval($stock_quantity) < 0 || floor($stock_quantity) != $stock_quantity) {
        $errors[] = 'Stock Quantity must be a non-negative integer.';
    }

    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    } else {
        // Execute prepared UPDATE (SEC-01)
        $stmt = $conn->prepare("UPDATE products SET product_name=?, category=?, price=?, stock_quantity=?, description=? WHERE product_id=?");
        $price_val = floatval($price);
        $stock_val = intval($stock_quantity);
        $stmt->bind_param("ssdisi", $product_name, $category, $price_val, $stock_val, $description, $product_id);

        if ($stmt->execute()) {
            $stmt->close();
            // Redirect to view.php with success message
            header('Location: view.php?updated=1');
            exit;
        } else {
            $error_message = 'Failed to update product. Please try again.';
        }
        $stmt->close();
    }
}

// GET Request — Fetch product data to pre-fill form (SEC-01)
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header('Location: view.php');
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

// Use POST values if validation failed, otherwise use DB values
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($errors)) {
    $product_name = $product['product_name'];
    $category = $product['category'];
    $price = $product['price'];
    $stock_quantity = $product['stock_quantity'];
    $description = $product['description'];
}

// Include common header (COD-02)
include 'includes/header.php';
?>

    <div class="page-header">
        <h1>✏️ Edit Product</h1>
        <p>Update the details for this product.</p>
    </div>

    <?php if ($success_message): ?>
        <div class="message message-success">✅ <?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message message-error">❌ <?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="edit.php">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label for="product_id_display">Product ID</label>
                <input type="text" id="product_id_display"
                       value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>"
                       disabled readonly>
            </div>

            <div class="form-group">
                <label for="product_name">Product Name <span class="required">*</span></label>
                <input type="text" id="product_name" name="product_name" required
                       value="<?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="category">Category <span class="required">*</span></label>
                <input type="text" id="category" name="category" required
                       value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="price">Price ₹ <span class="required">*</span></label>
                <input type="number" id="price" name="price" step="0.01" min="0.01" required
                       value="<?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="stock_quantity">Stock Quantity <span class="required">*</span></label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required
                       value="<?php echo htmlspecialchars($stock_quantity, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        </form>
    </div>

<?php
// Include common footer (COD-02)
include 'includes/footer.php';

// Close database connection
$conn->close();
?>
