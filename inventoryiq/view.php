<?php
/**
 * InventoryIQ — View Inventory (view.php)
 * FR-02: Fetch and display all products in an HTML table.
 */

// Include database connection (ARC-05)
include 'config.php';

// Include common header (COD-02)
include 'includes/header.php';

// Check for success message from delete redirect
$success_message = '';
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $success_message = 'Product deleted successfully!';
}
if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success_message = 'Product updated successfully!';
}

// Execute SELECT query (SAD Section 4.3)
$stmt = $conn->prepare("SELECT * FROM products ORDER BY product_id ASC");
$stmt->execute();
$result = $stmt->get_result();
?>

    <div class="page-header">
        <h1>📋 View Inventory</h1>
        <p>Browse and manage all products in your inventory.</p>
    </div>

    <?php if ($success_message): ?>
        <div class="message message-success">✅ <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-container">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price (₹)</th>
                        <th>Stock Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="price-col">₹<?php echo htmlspecialchars(number_format($product['price'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['stock_quantity'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="table-actions">
                                <a href="edit.php?id=<?php echo htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-edit">✏️ Edit</a>
                                <a href="delete.php?id=<?php echo htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Are you sure you want to delete this product?')">🗑️ Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No Products Found</h3>
                <p>Your inventory is empty. Start by adding your first product.</p>
                <a href="add.php" class="quick-action-link">➕ Add Your First Product</a>
            </div>
        </div>
    <?php endif; ?>

<?php
$stmt->close();

// Include common footer (COD-02)
include 'includes/footer.php';

// Close database connection
$conn->close();
?>
