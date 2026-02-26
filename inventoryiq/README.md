# InventoryIQ — Inventory Management System

A simple, clean PHP/MySQL inventory management system for tracking products with full CRUD functionality.

## Prerequisites

- **XAMPP** (with PHP 8.x + MySQL 8.0)
- A modern web browser (Chrome, Firefox, or Edge)

## Setup Instructions

1. **Install XAMPP**  
   Download and install from [https://www.apachefriends.org/](https://www.apachefriends.org/)

2. **Start Apache + MySQL**  
   Open the XAMPP Control Panel and start both **Apache** and **MySQL** modules.

3. **Import the SQL Script**  
   - Open phpMyAdmin at `http://localhost/phpmyadmin/`
   - Click the **Import** tab
   - Select the `setup.sql` file from this project
   - Click **Go** to create the database, table, and sample data

4. **Copy Project to htdocs**  
   Copy the `inventoryiq/` folder into your XAMPP `htdocs/` directory:
   ```
   C:\xampp\htdocs\inventoryiq\
   ```

5. **Open in Browser**  
   Navigate to: [http://localhost/inventoryiq/](http://localhost/inventoryiq/)

## Directory Structure

```
inventoryiq/
├── config.php          ← Database connection
├── index.php           ← Dashboard / entry point
├── add.php             ← Add new product
├── view.php            ← View all products
├── edit.php            ← Edit existing product
├── delete.php          ← Delete product
├── setup.sql           ← Database setup script
├── css/
│   └── style.css       ← Application styles
├── includes/
│   ├── header.php      ← Common header & navigation
│   └── footer.php      ← Common footer
└── README.md           ← This file
```

## Features

- **Dashboard**: View total product count at a glance
- **Add Product**: Create new inventory items with validation
- **View Inventory**: Browse all products in a sortable table
- **Edit Product**: Update existing product details
- **Delete Product**: Remove products with confirmation dialog

## Technology Stack

- PHP 8.x (no frameworks)
- MySQL 8.0 (via mysqli)
- HTML5 + CSS3 (no external libraries)
