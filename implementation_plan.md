# InventoryIQ – Complete Build Plan

> **Purpose**: Step-by-step implementation plan for building the InventoryIQ PHP/MySQL inventory management system, derived from the PRD v1.0, SAD v1.0, and AGRD v1.0. This plan is designed to be **executed by Claude** (or any AI assistant) in strict compliance with all governance rules.

---

## AI Context Block (AGRD Section 6.1 – Mandatory)

> [!CAUTION]
> Every AI session that generates code for this project **MUST** begin with the following context block pasted into the prompt. This is a non-negotiable requirement from the AGRD.

```
You are assisting with the InventoryIQ PHP/MySQL project.
STRICT RULES:
1. Technology stack: PHP 8.x, MySQL 8.0, HTML5, CSS3 only. No frameworks.
2. Files: config.php, index.php, add.php, view.php, edit.php, delete.php,
   includes/header.php, includes/footer.php, css/style.css only.
3. Database: Only the 'products' table with columns: product_id, product_name,
   category, price, stock_quantity, description, created_at, updated_at.
4. All SQL must use mysqli prepared statements with ? placeholders.
5. All echoed DB values must be wrapped in htmlspecialchars().
6. NO authentication, NO image upload, NO search, NO payments, NO APIs,
   NO external libraries.
7. Deployment target: XAMPP localhost only.
If my request violates any rule above, tell me which rule it violates
instead of complying.
```

---

## Project Directory Structure (SAD Section 9.2)

```
htdocs/
└── inventoryiq/
     ├── config.php          ← DB connection (excluded from VCS)
     ├── index.php           ← Dashboard / entry point
     ├── add.php             ← Create product (GET form + POST handler)
     ├── view.php            ← Read / list all products
     ├── edit.php            ← Update product (GET form + POST handler)
     ├── delete.php          ← Delete product (GET handler + redirect)
     ├── css/
     │    └── style.css      ← Application-wide styles
     ├── includes/
     │    ├── header.php     ← Common HTML <head> + navbar
     │    └── footer.php     ← Common HTML footer
     └── README.md           ← Setup and usage instructions
```

---

---

## Phase 0: UI Generation via Stitch MCP

**Purpose**: Generate the raw HTML and CSS structures for the 4 main pages using Stitch MCP. This phase creates the visual scaffolding that will be converted into the final PHP files in later phases.

**CRITICAL CONSTRAINT FOR STITCH**: Every prompt sent to Stitch MUST explicitly include the following instruction to comply with project governance (ARC-04):
> "CRITICAL: Use ONLY semantic HTML5 and plain Vanilla CSS3. Do NOT use Tailwind, Bootstrap, or ANY other CSS framework. Do NOT include any external stylesheets or CDNs. Write all CSS manually."

### Step 0.1 — Generate `index.php` (Dashboard) UI

**Stitch Prompt**:
```text
Create a dashboard page for an inventory management system called "InventoryIQ". 
CRITICAL: Use ONLY semantic HTML5 and plain Vanilla CSS3. Do NOT use Tailwind, Bootstrap, or ANY other CSS framework. Do NOT include any external stylesheets or CDNs. Write all CSS manually.

Requirements:
1. A top navigation bar with links: Dashboard, Add Product, View Inventory.
2. A main content area with a clean, professional design (suggest a pink/red color scheme).
3. A prominent summary card or widget displaying "Total Products: [Number]".
4. Use flexbox for layout, targeting desktop screens (min 1024px).
5. Include a simple footer with copyright info.
```

### Step 0.2 — Generate `add.php` (Add Product) UI

**Stitch Prompt**:
```text
Create an "Add Product" form page for the "InventoryIQ" system.
CRITICAL: Use ONLY semantic HTML5 and plain Vanilla CSS3. Do NOT use Tailwind, Bootstrap, or ANY other CSS framework. Do NOT include any external stylesheets or CDNs. Write all CSS manually.

Requirements:
1. Include the same top navigation bar from the Dashboard.
2. A clean, well-spaced form centered on the page.
3. Form fields:
   - Product Name (text input, required, marked with *)
   - Category (text input, required, marked with *)
   - Price ₹ (number input, required, marked with *)
   - Stock Quantity (number input, required, marked with *)
   - Description (textarea, optional)
4. A prominent "Submit" or "Add Product" button styled appropriately.
5. Provide a placeholder for a green success message and a red error message above the form.
```

### Step 0.3 — Generate `view.php` (View Inventory) UI

**Stitch Prompt**:
```text
Create an inventory listing page for the "InventoryIQ" system.
CRITICAL: Use ONLY semantic HTML5 and plain Vanilla CSS3. Do NOT use Tailwind, Bootstrap, or ANY other CSS framework. Do NOT include any external stylesheets or CDNs. Write all CSS manually.

Requirements:
1. Include the same top navigation bar.
2. A professional-looking data table to display products.
3. Table columns: Product ID, Product Name, Category, Price (₹), Stock Quantity, Actions.
4. Populate the table with 3-5 rows of sample dummy data (e.g., Wireless Mouse, Cotton T-Shirt).
5. The 'Actions' column should contain two distinct buttons or links per row: 'Edit' and 'Delete'.
6. Style the table with clear borders, alternating row colors (zebra striping), and hover effects.
```

### Step 0.4 — Generate `edit.php` (Edit Product) UI

**Stitch Prompt**:
```text
Create an "Edit Product" form page for the "InventoryIQ" system.
CRITICAL: Use ONLY semantic HTML5 and plain Vanilla CSS3. Do NOT use Tailwind, Bootstrap, or ANY other CSS framework. Do NOT include any external stylesheets or CDNs. Write all CSS manually.

Requirements:
1. Include the same top navigation bar.
2. The form layout should match the "Add Product" page perfectly for consistency.
3. Pre-fill the form fields with sample data (e.g., editing a 'Wireless Mouse').
4. Add a read-only display or disabled input field at the top of the form for "Product ID" (e.g., ID: 101).
5. The submit button should say "Save Changes".
```

### Step 0.5 — Consolidate CSS

After generating the screens, review the generated HTML/CSS. 
1. Extract all the `<style>` blocks or inline styles generated by Stitch.
2. Consolidate them into a single `css/style.css` file as required by Step 2.4.
3. Ensure class names are consistent across shared components (like the navbar and buttons).

---

## Phase 1: Database Setup

### Step 1.1 — Create the Database & Table

**File**: Execute directly in MySQL (phpMyAdmin or CLI)

**SQL Script** (SAD Section 4.2 — exact copy):
```sql
CREATE DATABASE IF NOT EXISTS inventoryiq_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE inventoryiq_db;

CREATE TABLE IF NOT EXISTS products (
  product_id      INT            NOT NULL AUTO_INCREMENT,
  product_name    VARCHAR(255)   NOT NULL,
  category        VARCHAR(100)   NOT NULL,
  price           DECIMAL(10,2)  NOT NULL CHECK (price > 0),
  stock_quantity  INT            NOT NULL DEFAULT 0
                                 CHECK (stock_quantity >= 0),
  description     TEXT,
  created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Governance checks**:
- ✅ DB-01: Exact schema from SAD Section 4.1
- ✅ DB-02: Only `products` table — no other tables
- ✅ DB-04: Database name `inventoryiq_db`, charset `utf8mb4`, engine `InnoDB`

---

## Phase 2: Configuration & Shared Includes

### Step 2.1 — Create `config.php`

**Purpose**: Sole location for database credentials and the `mysqli` connection object (ARC-05).

**Requirements**:
- Store DB host, username, password, database name
- Create `mysqli` connection
- On failure: display user-readable error message and exit (NFR-04)
- Use `<?php` full open tag (COD-01)
- Set charset to `utf8mb4` via `$conn->set_charset("utf8mb4")`

**Governance checks**:
- ✅ ARC-05: config.php is the sole location for DB credentials
- ✅ SEC-04: No credentials in any other file
- ✅ DB-05: Use `mysqli` only, never deprecated `mysql_*`

---

### Step 2.2 — Create `includes/header.php`

**Purpose**: Common HTML `<head>`, navigation bar, included by every page (COD-02).

**Requirements**:
- HTML5 doctype, `<head>` with charset UTF-8, viewport meta tag
- `<title>` tag for SEO
- Link to `/css/style.css` (only permitted stylesheet — ARC-04)
- Navigation bar (`<nav>`) with links to:
  - Dashboard (`index.php`)
  - Add Product (`add.php`)
  - View Inventory (`view.php`)
- Use semantic HTML (`<nav>`, `<header>`)
- **No** `<script src>` tags for any library (ARC-03)
- **No** `<link>` tags for Bootstrap/Tailwind/etc. (ARC-04)

---

### Step 2.3 — Create `includes/footer.php`

**Purpose**: Common HTML footer, closing tags.

**Requirements**:
- `<footer>` with copyright / project name
- Close `</body>` and `</html>` tags
- Semantic HTML

---

### Step 2.4 — Create `css/style.css`

**Purpose**: All application styling in a single CSS3 file (ARC-04).

**Requirements** (SAD Section 10 — Interface Design Guidelines):
- Consistent styling for navigation bar, forms, tables, buttons
- Success messages styled in **green**, error messages in **red**
- Form labels explicit; required fields marked with asterisk (*)
- CSS flexbox layout targeting desktop screens (min 1024px width)
- Table styling for the product inventory view
- Button styling for Edit, Delete, Add, and Submit actions
- Clean, professional look — plain CSS3 only
- **No** CSS framework references whatsoever

---

## Phase 3: Dashboard (FR-05)

### Step 3.1 — Create `index.php`

**Purpose**: Main entry point / dashboard showing product count + navigation links.

**Requirements** (PRD Section 6.5 + SAD Section 3.2):
- `include config.php` for DB connection
- `include includes/header.php` and `includes/footer.php` (COD-02)
- Execute: `SELECT COUNT(*) AS total FROM products` (SAD Section 4.3)
- Use **prepared statement** even though no user input (consistency — SEC-01)
- Display total product count on the dashboard
- Navigation links to all CRUD pages (Add, View)
- Handle DB errors gracefully (NFR-04)
- Wrap any DB values in `htmlspecialchars()` (SEC-02)

**SQL pattern**: `SELECT COUNT(*) AS total FROM products`

---

## Phase 4: Add Product — FR-01 (Create)

### Step 4.1 — Create `add.php`

**Purpose**: HTML form to add new products + PHP POST handler (PRD Section 6.1).

**GET Request — Render Form**:
- `include config.php`, `includes/header.php`, `includes/footer.php`
- HTML form with fields:
  - Product Name (text, required)
  - Category (text, required)
  - Price ₹ (number, required)
  - Stock Quantity (number, required)
  - Description (textarea, optional)
- Submit button
- Form `method="POST"` and `action="add.php"`

**POST Request — Process Form**:
1. **Server-side validation** (SEC-05):
   - Product Name: not empty
   - Category: not empty
   - Price: numeric AND > 0
   - Stock Quantity: integer AND >= 0
2. If **invalid**: re-render form with error message(s), retain user input
3. If **valid**: execute prepared INSERT statement
   ```sql
   INSERT INTO products (product_name, category, price, stock_quantity, description)
   VALUES (?, ?, ?, ?, ?)
   ```
4. Use `$stmt = $conn->prepare()`, `$stmt->bind_param("ssdis", ...)`, `$stmt->execute()` (SEC-01)
5. On success: show green success message
6. On failure: show red error message, no record inserted (FR-01)

**Governance checks**:
- ✅ SEC-01: Prepared statements with `?` placeholders
- ✅ SEC-02: `htmlspecialchars()` on any echoed values
- ✅ SEC-05: Server-side validation before any SQL
- ✅ DB-03: Only the INSERT pattern from SAD Section 4.3
- ✅ COD-04: Descriptive variable names (`$product_name`, `$stock_quantity`)

---

## Phase 5: View Inventory — FR-02 (Read)

### Step 5.1 — Create `view.php`

**Purpose**: Fetch and display all products in an HTML table (PRD Section 6.2).

**Requirements**:
- `include config.php`, `includes/header.php`, `includes/footer.php`
- Execute: `SELECT * FROM products ORDER BY product_id ASC`
- Render results in an `<table>` with columns:
  - Product ID
  - Product Name
  - Category
  - Price
  - Stock Quantity
  - Actions (Edit | Delete buttons/links)
- **Empty state**: if no products, display informational message (FR-02)
- Every echoed DB value **must** use `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` (SEC-02)
- Edit link → `edit.php?id=<product_id>`
- Delete link → JS `confirm()` → `delete.php?id=<product_id>` (SEC-03)

**SQL pattern**: `SELECT * FROM products ORDER BY product_id ASC`

---

## Phase 6: Edit Product — FR-03 (Update)

### Step 6.1 — Create `edit.php`

**Purpose**: Pre-filled edit form + UPDATE handler (PRD Section 6.3).

**GET Request — Pre-fill Form**:
1. Read `product_id` from `$_GET['id']`
2. Execute prepared statement: `SELECT * FROM products WHERE product_id = ?`
3. Pre-fill the form with fetched values
4. `product_id` is **read-only** (displayed but not editable)

**POST Request — Save Changes**:
1. Server-side validation (same rules as add.php — SEC-05)
2. Execute prepared UPDATE:
   ```sql
   UPDATE products SET product_name=?, category=?, price=?, stock_quantity=?, description=?
   WHERE product_id=?
   ```
3. Use `$stmt->bind_param("ssdisi", ...)` (SEC-01)
4. On success: show success message + redirect to `view.php`
5. On failure: show error message, record unchanged (FR-03)

**Governance checks**:
- ✅ SEC-01: Prepared statements for both SELECT and UPDATE
- ✅ SEC-02: `htmlspecialchars()` on pre-filled form values
- ✅ SEC-05: Server-side validation before UPDATE

---

## Phase 7: Delete Product — FR-04

### Step 7.1 — Create `delete.php`

**Purpose**: Delete a product by ID with confirmation (PRD Section 6.4).

**Requirements**:
1. Read `product_id` from `$_GET['id']`
2. Execute prepared DELETE:
   ```sql
   DELETE FROM products WHERE product_id = ?
   ```
3. Use `$stmt->bind_param("i", ...)` (SEC-01)
4. On success: redirect to `view.php` with success message
5. On failure: show error message, record unchanged

**JS Confirmation Dialog** (SEC-03):
- The Delete button/link in `view.php` must trigger `onclick="return confirm('Are you sure you want to delete this product?')"` before navigating to `delete.php`
- This is the **only** permitted JavaScript in the entire project (ARC-03)

---

## Phase 8: README & Documentation

### Step 8.1 — Create `README.md`

**Purpose**: Setup and usage instructions (DOC-03).

**Must include** (and **only** include):
- Project description (InventoryIQ — what it does)
- Prerequisites (XAMPP with PHP 8.x + MySQL 8.0)
- Setup steps:
  1. Install XAMPP
  2. Start Apache + MySQL
  3. Import the SQL script via phpMyAdmin
  4. Copy project to `htdocs/inventoryiq/`
  5. Open `http://localhost/inventoryiq/` in browser
- Directory structure (from SAD Section 9.2)
- **No references** to out-of-scope features (DOC-03)

---

## Phase 9: Sample Data (Optional — for Testing)

### Step 9.1 — Create seed SQL

**Purpose**: Insert sample product rows for testing.

```sql
INSERT INTO products (product_name, category, price, stock_quantity, description) VALUES
('Wireless Mouse', 'Electronics', 599.00, 50, 'Ergonomic wireless mouse with USB receiver'),
('Cotton T-Shirt', 'Clothing', 349.00, 120, 'Plain white cotton t-shirt, size M'),
('Notebook A5', 'Stationery', 85.00, 200, '200-page ruled notebook'),
('USB-C Cable', 'Electronics', 199.00, 75, '1-meter braided USB-C charging cable'),
('Water Bottle', 'Accessories', 450.00, 30, 'Stainless steel insulated 750ml bottle');
```

---

## Verification Plan

### Automated / Manual Tests (SAD Section 12)

| # | Test Type | What to Test | How to Run | Pass Criteria |
|---|-----------|-------------|------------|---------------|
| 1 | **Unit — Add** | Submit the add product form with valid data | Open `http://localhost/inventoryiq/add.php`, fill all fields, submit | New row appears in `products` table; success message shown |
| 2 | **Unit — View** | Load the inventory table | Open `http://localhost/inventoryiq/view.php` | All products displayed in table with Edit/Delete buttons |
| 3 | **Unit — Edit** | Click Edit on a product, change price, submit | Click Edit on any row in view.php, modify price, submit | Record updated in DB; redirected to view.php with updated data |
| 4 | **Unit — Delete** | Click Delete on a product | Click Delete on any row; confirm in JS dialog | Record removed; no longer in view.php table |
| 5 | **Validation** | Submit add form with empty name, price = -1, stock = -5 | Submit `add.php` with invalid data | PHP error messages displayed; no bad data in DB |
| 6 | **Validation** | Submit edit form with price = "abc" | Edit a product, set price to "abc", submit | Error message; original record unchanged |
| 7 | **Security — SQLi** | Enter `' OR '1'='1` in product name field | Submit `add.php` with SQL injection string in name field | Query fails safely or inserts the literal string; no unintended data returned |
| 8 | **Security — XSS** | Enter `<script>alert('XSS')</script>` as product name | Add product with XSS payload, then view in `view.php` | Script tag is rendered as escaped text, **not** executed |
| 9 | **Security — Delete Confirm** | Click Delete button | Click Delete in `view.php` | JS `confirm()` dialog appears; clicking Cancel does **not** delete |
| 10 | **Integration** | Full CRUD cycle | Add → View → Edit → Delete a product | All operations succeed without errors |
| 11 | **UI/UX — Cross-browser** | Open all pages in Chrome, Firefox, Edge | Navigate all 5 pages in each browser | Pages render consistently; all links/buttons work |
| 12 | **Schema Audit** | Verify only `products` table exists | Run `SHOW TABLES` in phpMyAdmin | Only one table: `products` |
| 13 | **Dependency Audit** | Verify no external dependencies | Check for `package.json`, `composer.json`, CDN `<link>`/`<script>` tags | None found |
| 14 | **Directory Audit** | Verify only authorized files exist | List contents of `htdocs/inventoryiq/` | Only the 9 authorized files + `css/` dir + `includes/` dir + `README.md` |

### AGRD Compliance Checklist

Before considering the build complete, run through **all checklists** from AGRD Sections 7.1–7.4:

- [ ] **S1–S5**: Scope checks (only 9 files, no extra tables, no out-of-scope UI, no dependencies)
- [ ] **A1–A6**: Architecture checks (three-tier, config.php only DB creds, no JS/CSS frameworks, header/footer included)
- [ ] **SC1–SC5**: Security checks (prepared statements, XSS protection, delete confirm, server-side validation, no raw SQL)
- [ ] **D1–D8**: Database schema checks (all 8 columns match SAD Section 4.1 exactly)

---

## Execution Order Summary

| Step | File(s) to Create | AGRD Rules to Verify |
|------|--------------------|---------------------|
| 1 | SQL script (DB + table) | DB-01, DB-02, DB-04 |
| 2 | `config.php` | ARC-05, SEC-04, DB-05 |
| 3 | `includes/header.php` | COD-02, ARC-03, ARC-04 |
| 4 | `includes/footer.php` | COD-02 |
| 5 | `css/style.css` | ARC-04 |
| 6 | `index.php` (Dashboard) | FR-05, DB-03 |
| 7 | `add.php` | FR-01, SEC-01, SEC-05 |
| 8 | `view.php` | FR-02, SEC-02, SEC-03 |
| 9 | `edit.php` | FR-03, SEC-01, SEC-02, SEC-05 |
| 10 | `delete.php` | FR-04, SEC-01, SEC-03 |
| 11 | `README.md` | DOC-03, DEP-01, DEP-02 |
| 12 | Seed data SQL (optional) | DB-01 schema compliance |
| 13 | Full test suite run | SAD Section 12, AGRD Sections 7.1–7.4 |

---

> [!IMPORTANT]
> **Constraints reminder for the executing AI**:
> - **Zero external dependencies** — no npm, no Composer, no CDN links
> - **Only `mysqli`** — never `mysql_*` or PDO
> - **Prepared statements everywhere** — `?` placeholders, `bind_param()`, `execute()`
> - **`htmlspecialchars()` on ALL echoed DB values** — no exceptions
> - **Only 9 PHP/CSS files** — no additional pages, no new directories (except `css/` and `includes/`)
> - **XAMPP localhost only** — no cloud config, no Docker, no `.env`
> - **Plain CSS3 only** — no frameworks of any kind
> - **Only native `confirm()` JS** — no jQuery, no React, no Vue, no Alpine.js
