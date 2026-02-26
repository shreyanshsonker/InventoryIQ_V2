<?php
/**
 * InventoryIQ — Add Product (add.php)
 * FR-01: Create new product via HTML form + POST handler.
 */

// Include database connection (ARC-05)
include 'config.php';

$success_message = '';
$error_message = '';

// Retain user input on validation failure
$product_name = '';
$category = '';
$price = '';
$stock_quantity = '';
$description = '';

// POST Request — Process Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read form values
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
        // Execute prepared INSERT statement (SEC-01, DB-03)
        $stmt = $conn->prepare("INSERT INTO products (product_name, category, price, stock_quantity, description) VALUES (?, ?, ?, ?, ?)");
        $price_val = floatval($price);
        $stock_val = intval($stock_quantity);
        $stmt->bind_param("ssdis", $product_name, $category, $price_val, $stock_val, $description);

        if ($stmt->execute()) {
            $success_message = 'Product "' . htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8') . '" has been added successfully!';
            // Clear form fields on success
            $product_name = '';
            $category = '';
            $price = '';
            $stock_quantity = '';
            $description = '';
        } else {
            $error_message = 'Failed to add product. Please try again.';
        }
        $stmt->close();
    }
}

// Include common header (COD-02)
include 'includes/header.php';
?>

    <div class="page-header">
        <h1>➕ Add New Product</h1>
        <p>Fill in the details below to add a new product to your inventory.</p>
    </div>

    <?php if ($success_message): ?>
        <div class="message message-success">✅ <?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message message-error">❌ <?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="add.php">
            <div class="form-group">
                <label for="product_name">Product Name <span class="required">*</span></label>
                <input type="text" id="product_name" name="product_name" required
                       value="<?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="e.g., Wireless Mouse">
            </div>

            <div class="form-group">
                <label for="category">Category <span class="required">*</span></label>
                <input type="text" id="category" name="category" required
                       value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="e.g., Electronics">
            </div>

            <div class="form-group">
                <label for="price">Price ₹ <span class="required">*</span></label>
                <input type="number" id="price" name="price" step="0.01" min="0.01" required
                       value="<?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="e.g., 599.00">
            </div>

            <div class="form-group">
                <label for="stock_quantity">Stock Quantity <span class="required">*</span></label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required
                       value="<?php echo htmlspecialchars($stock_quantity, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="e.g., 50">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          placeholder="Optional product description..."><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">➕ Add Product</button>
        </form>
    </div>

<?php
// Include common footer (COD-02)
include 'includes/footer.php';

// Close database connection
$conn->close();
?>
