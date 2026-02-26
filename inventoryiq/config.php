<?php
/**
 * InventoryIQ - Database Configuration
 * 
 * Sole location for database credentials and mysqli connection (ARC-05).
 * XAMPP localhost deployment only.
 */

// Database credentials
$db_host = 'localhost';
$db_username = 'root';
$db_password = '1234';
$db_name = 'inventoryiq_db';

// Create mysqli connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection - display user-readable error and exit on failure (NFR-04)
if ($conn->connect_error) {
    die('<div style="color: red; font-family: Arial, sans-serif; padding: 20px; text-align: center;">
        <h2>Database Connection Failed</h2>
        <p>Unable to connect to the database. Please ensure MySQL is running in XAMPP.</p>
    </div>');
}

// Set charset to utf8mb4 for proper encoding
$conn->set_charset("utf8mb4");
?>
