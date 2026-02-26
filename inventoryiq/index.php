<?php
/**
 * InventoryIQ — Dashboard (index.php)
 * FR-05: Main entry point showing product count + navigation links.
 */

// Include database connection (ARC-05)
include 'config.php';

// Include common header (COD-02)
include 'includes/header.php';

// Query total product count (SAD Section 4.3)
// Using prepared statement for consistency (SEC-01)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_products = $row['total'];
$stmt->close();
?>

    <div class="page-header">
        <h1>📊 Dashboard</h1>
        <p>Welcome to InventoryIQ — your central inventory management hub.</p>
    </div>

    <div class="dashboard-cards">
        <div class="dashboard-card">
            <div class="card-icon">📦</div>
            <div class="card-label">Total Products</div>
            <div class="card-value"><?php echo htmlspecialchars($total_products, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="quick-actions">
        <a href="add.php" class="quick-action-link">➕ Add New Product</a>
        <a href="view.php" class="quick-action-link secondary">📋 View Inventory</a>
    </div>

<?php
// Include common footer (COD-02)
include 'includes/footer.php';

// Close database connection
$conn->close();
?>
