<?php
/**
 * InventoryIQ — Delete Product (delete.php)
 * FR-04: Delete a product by ID with confirmation.
 * JS confirm() dialog is triggered from view.php (SEC-03).
 */

// Include database connection (ARC-05)
include 'config.php';

// Read product_id from GET
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: view.php');
    exit;
}

// Execute prepared DELETE (SEC-01)
$stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    $stmt->close();
    // Redirect to view.php with success message
    header('Location: view.php?deleted=1');
    exit;
} else {
    $stmt->close();
    // On failure, show error
    include 'includes/header.php';
    echo '<div class="page-header"><h1>❌ Error</h1></div>';
    echo '<div class="message message-error">Failed to delete the product. Please try again.</div>';
    echo '<a href="view.php" class="quick-action-link">← Back to Inventory</a>';
    include 'includes/footer.php';
}

// Close database connection
$conn->close();
?>
