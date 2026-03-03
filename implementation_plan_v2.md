# InventoryIQ v2.0 — Complete Build Plan

> **For Claude (or any AI coding assistant)** — This plan is derived from **PRD v2.0**, **SAD v2.0**, **UI Design Spec v2.0**, and **AI Rules v2.0**. Execute phases sequentially. Each phase should be fully complete and tested before proceeding to the next.

---

## Current State

The existing codebase is a **v1.0 single-user CRUD system** consisting of:
- One `products` table (flat schema, no auth, no roles)
- 7 PHP files: [config.php](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/config.php), [index.php](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/index.php), [add.php](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/add.php), [view.php](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/view.php), [edit.php](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/edit.php), [delete.php](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/delete.php), [setup.sql](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/setup.sql)
- `includes/header.php` and `includes/footer.php` (basic HTML chrome)
- `css/style.css` (basic styles)

**All v1.0 code must be replaced.** The v2.0 system is a ground-up rewrite into a multi-tier, multi-company, multi-warehouse platform.

---

## Technology Stack (Non-Negotiable)

| Layer | Technology |
|---|---|
| Server | Apache 2.4 via XAMPP (localhost:80) |
| Backend | PHP 8.x — **procedural only** (no classes, no MVC, no frameworks) |
| Database | MySQL 8.0 — `inventoryiq_db`, InnoDB, utf8mb4 |
| DB Interface | `mysqli` with prepared statements ONLY (no PDO, no ORM) |
| Frontend | HTML5 + CSS3 only — **no React, Vue, jQuery, Bootstrap, or Tailwind** |
| Minimal JS | Vanilla JS for confirmations, dark mode toggle, toast animation, 3D tilt |
| PDF Export | mPDF or FPDF |
| Barcode | picqer/php-barcode-generator |
| Icons | Lucide Icons (CDN) |
| Fonts | Syne, DM Sans, Fira Code (Google Fonts) |
| **UI Generation** | **Google Stitch MCP** (connected via Antigravity MCP integration) |
| UI Design Reference | [InventoryIQ_UI_Design_v2.docx](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/InventoryIQ_UI_Design_v2.docx) (in project root) |

---

## 🎨 Frontend Generation via Google Stitch MCP (MANDATORY)

> [!IMPORTANT]
> **All frontend UI (HTML/CSS) for every screen MUST be generated using Google Stitch**, which is connected to the agent via MCP. Do NOT hand-write the UI HTML/CSS from scratch — use Stitch to generate it, then integrate the backend PHP logic into the generated markup.

### How to Use Stitch MCP

The Stitch MCP provides the following tools (available directly to the executing agent):

| Tool | Purpose |
|---|---|
| `mcp_stitch_create_project` | Create a new Stitch project (do this once at the start) |
| `mcp_stitch_generate_screen_from_text` | Generate a screen from a text prompt |
| `mcp_stitch_edit_screens` | Edit existing screens with a text prompt |
| `mcp_stitch_list_screens` | List all screens in a project |
| `mcp_stitch_get_screen` | Get the HTML/CSS output of a specific screen |
| `mcp_stitch_generate_variants` | Generate design variants of existing screens |

### Stitch Workflow (for each screen)

1. **Create a Stitch project** (once): Call `mcp_stitch_create_project` with title `"InventoryIQ v2.0"`
2. **Read the prompt from UI Design v2**: Open the file [InventoryIQ_UI_Design_v2.docx](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/InventoryIQ_UI_Design_v2.docx) (located at the project root [/Users/shrey/Desktop/Shrey/PHP/PHP MVP 2/InventoryIQ_UI_Design_v2.docx](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/InventoryIQ_UI_Design_v2.docx)). Each screen has a dedicated **"Google Stitch Prompt"** block (§8.1–8.3) — use these exact prompts.
3. **Prepend the Universal Context Block**: Before each screen prompt, prepend the **Universal Google Stitch Context Block** from UI Design v2 §13. This establishes the design system (aurora bg, glass cards, capsule nav, typography, colours) so every screen is consistent.
4. **Generate the screen**: Call `mcp_stitch_generate_screen_from_text` with the combined prompt (context block + screen-specific prompt). Set `deviceType` to `DESKTOP`.
5. **Retrieve the output**: Call `mcp_stitch_get_screen` to get the generated HTML/CSS.
6. **Integrate into PHP**: Take the generated HTML structure and embed it into the PHP file, wrapping dynamic content with PHP variables and `htmlspecialchars()`. Keep the CSS in `style.css` / `superadmin.css`.
7. **Edit if needed**: If the output needs tweaks, use `mcp_stitch_edit_screens` with specific adjustment instructions.

### Screen-to-Prompt Mapping (from UI Design v2 §8)

| Screen # | Name | Role | UI Design v2 Section |
|---|---|---|---|
| 01 | Company Login Page | Public | §8.1 — Screen 01 |
| 02 | Company Registration | Public | §8.1 — Screen 02 |
| 03 | Company Admin Dashboard | Company Admin | §8.2 — Screen 03 |
| 04 | Warehouse Fleet Management | Company Admin | §8.2 — Screen 04 |
| 05 | Broadcast Composer | Company Admin | §8.2 — Screen 05 |
| 06 | Restock Management | Company Admin | §8.2 — Screen 06 |
| 07 | Warehouse Manager Dashboard | Wh. Manager | §8.2 — Screen 07 |
| 08 | Product Inventory Table | Manager/Staff | §8.2 — Screen 08 |
| 09 | Add / Edit Product Form | Manager/Staff | §8.2 — Screen 09 |
| 10 | Notification Inbox | Manager+ | §8.2 — Screen 10 |
| 11 | Settings Page | All roles | §8.2 — Screen 11 |
| 12 | Audit Log Viewer | Manager+ | §8.2 — Screen 12 |
| 13 | Export Report | Manager+ | §8.2 — Screen 13 |
| 14 | Super Admin Login | Super Admin | §8.3 — Screen 14 |
| 15 | Super Admin Dashboard | Super Admin | §8.3 — Screen 15 |
| 16 | Company Directory | Super Admin | §8.3 — Screen 16 |
| 17 | Company Drill-Down | Super Admin | §8.3 — Screen 17 |
| 18 | Maintenance Mode Control | Super Admin | §8.3 — Screen 18 |
| 19 | Warehouse Staff Dashboard | Wh. Staff | §8.2 — Screen 19 |
| 20 | View-As Read-Only Inventory | Company Admin | §8.2 — Screen 20 |
| 21 | Stock Transfer Form | Company Admin | §8.2 — Screen 21 |
| 22 | Category Management | Company Admin | §8.2 — Screen 22 |
| 23 | Staff Account Management | Wh. Manager | §8.2 — Screen 23 |
| 24 | Password Reset Page | Public | §8.2 — Screen 24 |

> [!WARNING]
> **Do NOT skip Stitch.** The UI Design v2 document contains highly specific, ready-to-paste prompts for each screen. These prompts define exact colours, spacing, glass effects, animations, and layout that cannot be easily replicated by hand-coding. Always generate through Stitch first, then add PHP backend logic.

---

## Phase 0: Project Scaffolding & Database Schema
**Duration: ~1 day** | **Priority: CRITICAL — everything depends on this**

### 0.1 — Create the directory structure
Create the entire directory tree as defined in AI Rules §3.1:

```
htdocs/inventoryiq/
├── config/
│   └── db.php
├── auth/
│   ├── check.php
│   ├── reset_request.php
│   └── reset_password.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── audit.php
│   └── notify.php
├── login.php
├── logout.php
├── register.php
├── 403.php
├── 500.php
├── dashboard/
│   └── index.php
├── company/
│   └── profile.php
├── warehouse/
│   ├── add.php
│   ├── list.php
│   ├── edit.php
│   ├── view_as.php
│   └── delete.php
├── products/
│   ├── add.php
│   ├── view.php
│   ├── edit.php
│   ├── delete.php
│   └── transfer.php
├── categories/
│   └── manage.php
├── notifications/
│   ├── index.php
│   ├── broadcast.php
│   └── mark_read.php
├── restock/
│   ├── request.php
│   └── manage.php
├── export/
│   ├── csv.php
│   └── pdf.php
├── audit/
│   └── log.php
├── settings/
│   ├── index.php
│   └── staff.php
├── superadmin/
│   ├── login.php
│   ├── dashboard.php
│   ├── companies.php
│   ├── company_view.php
│   ├── company_actions.php
│   ├── audit_export.php
│   └── maintenance.php
├── uploads/
│   ├── products/
│   └── logos/
├── exports/
├── css/
│   ├── style.css
│   ├── dark.css
│   └── superadmin.css
├── js/
│   └── app.js
├── .gitignore
└── README.md
```

### 0.2 — Create `config/db.php`
Use the exact template from AI Rules §3.2:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventoryiq_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    http_response_code(500);
    die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
?>
```

### 0.3 — Create [setup.sql](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/setup.sql) (11 tables)
Write the full SQL setup script creating all 11 tables in dependency order:
1. `super_admin`
2. `companies`
3. `warehouses` (FK → companies)
4. `users` (FK → companies, warehouses)
5. `categories` (FK → companies)
6. `products` (FK → warehouses, categories)
7. `product_images` (FK → products)
8. `notifications` (FK → companies, users, warehouses)
9. `restock_requests` (FK → warehouses, products, users)
10. `stock_transfers` (FK → companies, warehouses, products, users)
11. `audit_log` (FK → users only)

Use the exact column definitions from AI Rules §2.1–2.11. Include the seed Super Admin INSERT.

### 0.4 — Create `.gitignore`
```
config/db.php
exports/
uploads/
```

### 0.5 — Verification
- [ ] Import [setup.sql](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/inventoryiq/setup.sql) into phpMyAdmin → all 11 tables created, no errors
- [ ] Verify FK constraints exist on all tables
- [ ] Verify `config/db.php` connects successfully

---

## Phase 1: CSS Design System, Shared Includes & Stitch UI Generation
**Duration: ~3 days** | **Priority: CRITICAL — all pages depend on this**

> [!IMPORTANT]
> **Before writing any CSS manually**, create a Stitch project and generate the Login screen (Screen 01) using the prompt from UI Design v2 §8.1. Extract the generated CSS into `style.css` as the baseline design system. Then generate each subsequent screen as you reach its phase, using the exact prompts from UI Design v2.

### 1.1 — Create `css/style.css` (Main Portal — Aurora Glass)
Implement the complete 3D glassmorphic design system from UI Design Spec v2.0:
- CSS custom properties (`:root` variables from UI Spec §10)
- Aurora mesh gradient background animation (`@keyframes`, 3 radial gradient layers)
- Floating particle system (CSS pseudo-elements)
- `.glass-card` class with full glass material properties
- `.glass-card:hover` with 3D lift + tilt
- All button classes: `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-success`, `.btn-ghost`
- All status badge classes: `.badge-in-stock`, `.badge-low-stock`, `.badge-out-stock`, `.badge-active`, `.badge-inactive`, `.badge-pending`, `.badge-approved`, `.badge-rejected`
- Glass input fields styling (focus glow rings)
- Data table styles (frosted glass container, header, rows, hover)
- Top bar styles (60px fixed, glass material)
- Bottom capsule navigation styles (pill shape, glass, active/inactive items, entrance animation)
- Modal styles (backdrop, card, entrance animation)
- Toast notification styles (slide-in, auto-dismiss, priority colors)
- Page layout (content area padding, grid system)
- Card entrance stagger animation
- Typography classes matching the font system (Syne/DM Sans/Fira Code)
- Notification card styles with priority left-borders

### 1.2 — Create `css/superadmin.css` (Cosmic Dark Theme)
Implement the Super Admin portal theme from UI Spec §4:
- Override CSS variables for SA portal
- `#000008` base with CSS grid texture overlay
- Purple-tinted glass cards
- Neon purple/pink/cyan accent palette
- SA-specific button classes (`.btn-sa-primary`, `.btn-sa-danger`)
- SA table styles (purple-tinted headers, rows)
- SA capsule nav variant (purple glass)
- SA top bar (with `SUPER ADMIN` label)
- Scanline overlay effect
- Cyan particle system

### 1.3 — Create `css/dark.css` (Dark Mode Overrides)
CSS class toggle overrides for the main portal dark mode (minimal, since the base theme is already dark).

### 1.4 — Create `js/app.js`
Minimal vanilla JS for:
- Confirmation dialogs before destructive actions (custom glass modal, not `confirm()`)
- Toast notification auto-dismiss (4 seconds)
- Dark mode toggle (adds/removes CSS class on `<body>`)
- 3D card tilt effect on mouse move (perspective + rotateX/rotateY)
- Stat number count-up animation on page load
- Notification bell panel open/close
- Lucide icon initialization (`lucide.createIcons()`)

### 1.5 — Create `includes/header.php`
Role-aware PHP template generating:
- `<!DOCTYPE html>` + `<head>` with Google Fonts, Lucide CDN, CSS links
- Fixed top bar with page title, notification bell (with unread count badge), user avatar
- Conditional `<link>` for `superadmin.css` when role = `super_admin`
- Body class: `aurora-bg` for main portal, `cosmic-bg` for SA

### 1.6 — Create `includes/footer.php`
PHP template generating:
- Bottom capsule navigation bar rendered dynamically by `$_SESSION['role']`
  - Company Admin: Home, Warehouses, Broadcast, Requests, Reports, Settings
  - Warehouse Manager: Home, Inventory, Notifications, Restock, Export, Settings
  - Warehouse Staff: Home, Inventory, Settings
  - Super Admin: Home, Companies, Activity, Audit, Maintenance, Logout
- Toast notification container `<div>`
- `<script src="/js/app.js"></script>` and Lucide init

### 1.7 — Create `includes/audit.php`
Helper function:
```php
function write_audit_log($conn, $user_id, $role, $company_id, $warehouse_id, $action_type, $detail) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = mysqli_prepare($conn, 'INSERT INTO audit_log (...) VALUES (?, ?, ?, ?, ?, ?, ?)');
    // bind and execute
}
```

### 1.8 — Create `includes/notify.php`
Helper functions:
- `create_notification($conn, $company_id, $recipient_warehouse_id, $title, $body, $priority, $type, $sender_user_id = null)`
- `check_low_stock($conn, $product_id, $warehouse_id)` — checks against `COALESCE(warehouses.low_stock_override, companies.low_stock_default, 10)`
- `get_low_stock_threshold($conn, $warehouse_id)`

### 1.9 — Verification
- [ ] Open a test PHP page that includes header + footer → capsule nav renders, aurora bg visible
- [ ] Glass card component displays correctly with hover effect
- [ ] All button classes render with correct gradients and glow
- [ ] Status badges display properly
- [ ] Console shows no JS errors

---

## Phase 2: Authentication & Session System
**Duration: ~3 days** | **Priority: CRITICAL**

### 2.1 — Create `auth/check.php`
Session validation middleware (included at top of every protected page):
- `session_start()` if not started
- Check `$_SESSION['user_id']` → redirect to `/login.php` if missing
- Check 30-min inactivity timeout via `$_SESSION['last_activity']` → destroy + redirect with `?timeout=1`
- Update `$_SESSION['last_activity'] = time()`
- Accept `$allowed_roles` array → check `$_SESSION['role']` → redirect to `/403.php` if unauthorized

### 2.2 — Create `login.php`
Company/Manager/Staff login page:
- GET: render the 3D glass login form (Screen 01 from UI Spec)
- POST: full login flow per AI Rules §4.3:
  - Lookup `login_identifier` in `users` table
  - Check company status (must be `active`)
  - Check `locked_until`
  - `password_verify()`
  - On fail: increment `failed_attempts`, lock after 5
  - On success: reset attempts, set all `$_SESSION` keys, `session_regenerate_id(true)`
  - Remember Me: generate token, store hash, set cookie
  - Write LOGIN_SUCCESS/LOGIN_FAIL to `audit_log`
  - Redirect to `/dashboard/index.php`

### 2.3 — Create `register.php`
Company self-registration page:
- GET: render registration form (Screen 02)
- POST: validate all fields → MySQL transaction:
  1. `INSERT INTO companies`
  2. Insert default categories (Electronics, Clothing, Food and Beverages, Furniture, Stationery, Other)
  3. `INSERT INTO users` with `role='company_admin'`, `warehouse_id=NULL`
- Redirect to login with success message

### 2.4 — Create `logout.php`
- Write LOGOUT audit log entry
- `session_destroy()`, clear remember-me cookie
- Redirect to `/login.php`

### 2.5 — Create `superadmin/login.php`
Separate SA login (Screen 14 — Cosmic Dark theme):
- Queries `super_admin` table (NOT `users`)
- Sets `$_SESSION['role'] = 'super_admin'`
- Redirects to `/superadmin/dashboard.php`

### 2.6 — Create `403.php` and `500.php`
Glass-styled error pages matching the aurora theme.

### 2.7 — Verification
- [ ] Login with valid Company Admin → redirects to dashboard
- [ ] Login with wrong password → error message, `failed_attempts` incremented
- [ ] 5 failed attempts → account locked for 15 minutes
- [ ] Access `/dashboard/index.php` without session → redirect to login
- [ ] Access Company Admin page as Staff → redirect to 403
- [ ] 31-minute idle → timeout redirect to login with `?timeout=1`
- [ ] Company registration → company + user records created in DB
- [ ] Super Admin login at `/superadmin/login.php` → redirects to SA dashboard
- [ ] Logout → session destroyed, redirect to login

---

## Phase 3: Role-Specific Dashboards
**Duration: ~3 days** | **Priority: HIGH**

### 3.1 — Create `dashboard/index.php`
Single PHP file that renders different dashboards based on `$_SESSION['role']`:

**Company Admin Dashboard (Screen 03):**
- Hero welcome greeting (time-aware)
- 4 stat cards: Total Warehouses, Total Products, Inventory Value, Pending Requests
- Warehouse fleet grid (glass cards with stats)
- Live activity feed (right 30%)
- Low-stock alert banner

**Warehouse Manager Dashboard (Screen 07):**
- Warehouse name + location hero
- 4 stat cards: Total Products, Low Stock, Out of Stock, Total Value
- Recent activity feed (left 60%)
- Quick actions grid (right 40%): Add Product, View Inventory, Request Restock, Export

**Warehouse Staff Dashboard (Screen 19):**
- Simple stat cards: Total Products, Low Stock, Out of Stock
- Last 5 personal actions
- Quick action buttons: Add Product, View Inventory

### 3.2 — Verification
- [ ] Login as each role → correct dashboard widgets shown
- [ ] Stat card numbers match actual DB data
- [ ] Activity feed shows recent audit_log entries for correct scope
- [ ] Warehouse Manager dashboard only shows own warehouse data
- [ ] Staff dashboard only shows own warehouse data

---

## Phase 4: Company & Warehouse Management
**Duration: ~3 days** | **Priority: HIGH**

### 4.1 — Create `company/profile.php`
Company Admin edits: company name, address, logo upload, currency, `low_stock_default`. Audit log on save.

### 4.2 — Create `warehouse/add.php`
Company Admin creates a warehouse:
- MySQL transaction: `INSERT warehouse` + `INSERT manager user`
- Auto-generate Manager login: `warehouse-{handle}@{company_handle}`
- Auto-generate temp password: `base64_encode(random_bytes(8))`
- Show password ONCE in glass modal
- Insert default categories if first warehouse
- Audit log: WAREHOUSE_ADD + USER_CREATE

### 4.3 — Create `warehouse/list.php`
Fleet overview: grid of warehouse cards with stats, priority rank, status toggle.

### 4.4 — Create `warehouse/edit.php`
Edit warehouse details, adjust priority, activate/deactivate. Audit log.

### 4.5 — Create `warehouse/view_as.php`
Company Admin read-only view of any warehouse's inventory.

### 4.6 — Create `warehouse/delete.php`
Delete warehouse (only if inventory empty). Confirmation modal. Cascade delete.

### 4.7 — Verification
- [ ] Create warehouse → manager account auto-created, temp password shown once
- [ ] New manager login works with temp credentials
- [ ] Fleet list shows all warehouses with correct stats
- [ ] Deactivate warehouse → manager can't login
- [ ] Reactivate → login restored
- [ ] Delete warehouse (empty) → success; delete (with products) → blocked
- [ ] Company profile edit → changes saved, audit log written

---

## Phase 5: Enhanced Product CRUD + Photo Upload
**Duration: ~4 days** | **Priority: HIGH**

### 5.1 — Create `products/add.php`
Add product form (Screen 09):
- Product Name, Category dropdown (from `categories`), Price (₹ prefix), Stock, Description, SKU (+ Auto-Generate button)
- Image upload zone (up to 4 images, JPG/PNG/WebP, 2MB max)
- Server-side: MIME validation via `finfo_file()`, safe filename via `uniqid()`, GD thumbnail
- Prepared statement INSERT → `check_low_stock()` → `write_audit_log()`
- SKU format: `{COMPANY_HANDLE}-{WAREHOUSE_HANDLE}-{RANDOM_6}`

### 5.2 — Create `products/view.php`
Paginated inventory table (Screen 08):
- Search bar (name, SKU, category, description)
- Filter: Category dropdown, Stock Status, Price Range
- Sortable column headers
- 50px thumbnail, status badges, action icons
- Pagination (10/25/50 per page)
- Scoped to `$_SESSION['warehouse_id']`

### 5.3 — Create `products/edit.php`
Pre-filled edit form with validation. Image management (replace/add). Audit log with before/after.

### 5.4 — Create `products/delete.php`
Confirmation modal → DELETE product → `unlink()` image files → audit log.
Staff cannot delete (Manager only).

### 5.5 — Create `products/transfer.php`
Company Admin inter-warehouse stock transfer:
- Source/Destination warehouse dropdowns, Product, Quantity, Note
- MySQL transaction: DEDUCT source → ADD destination → INSERT stock_transfers
- Validate: same company, sufficient stock
- Audit log + notification

### 5.6 — Verification
- [ ] Add product with valid data → product appears in view
- [ ] Add product with invalid data (price ≤ 0, empty name) → error messages
- [ ] Upload image → stored in `/uploads/products/{id}/`, thumbnail generated
- [ ] Upload PHP file as .jpg → MIME check rejects
- [ ] Upload > 2MB → size check rejects
- [ ] Edit product → changes saved, audit log with before/after
- [ ] Delete product → images unlinked, audit logged
- [ ] Staff can add/edit but NOT delete
- [ ] Search, filter, sort, pagination all work
- [ ] Stock transfer → source decremented, destination incremented, transaction consistent
- [ ] Auto-generated SKU format is correct and unique

---

## Phase 6: Category Management
**Duration: ~1 day** | **Priority: MEDIUM**

### 6.1 — Create `categories/manage.php`
Company Admin: Add, rename, delete categories (Screen 22). Scoped to `company_id`.
Delete warning if products exist. Force-delete sets to "Uncategorized".
Default categories auto-created on registration.

### 6.2 — Verification
- [ ] Add/rename/delete categories
- [ ] Delete category with products → warning + force option
- [ ] Default categories exist for new companies

---

## Phase 7: Notifications & Messaging
**Duration: ~3 days** | **Priority: HIGH**

### 7.1 — Create `notifications/index.php`
Notification inbox (Screen 10):
- Filter pills: All, Unread, Info, Warning, Critical
- Glass notification cards with priority left-borders
- Mark Read button per card
- Unread shimmer animation

### 7.2 — Create `notifications/broadcast.php`
Company Admin broadcast composer (Screen 05):
- Title, Body, Priority (Info/Warning/Critical)
- Target: All warehouses or selected
- INSERT into notifications → audit log

### 7.3 — Create `notifications/mark_read.php`
POST endpoint: UPDATE `is_read = 1` for given notification.

### 7.4 — Notification bell in header
Update `includes/header.php`:
- Query unread count for current user's scope
- Bell icon with pink badge (pulsing animation if unread > 0)

### 7.5 — Verification
- [ ] Send broadcast → notifications appear for all warehouses
- [ ] Mark read → badge count decreases
- [ ] Low-stock notification auto-generated when stock drops below threshold
- [ ] Priority styling (blue/amber/red borders) correct

---

## Phase 8: Restock Request Workflow
**Duration: ~2 days** | **Priority: MEDIUM**

### 8.1 — Create `restock/request.php`
Manager submits restock request: product_id, quantity, note.
INSERT → notification to Company Admin → audit log.

### 8.2 — Create `restock/manage.php`
Company Admin view (Screen 06): pending requests queue.
Approve/reject with response note → UPDATE → notification to Manager → audit log.

### 8.3 — Verification
- [ ] Manager submits request → appears as pending for Company Admin
- [ ] Admin approves → status updated, Manager notified
- [ ] Admin rejects → status updated, Manager notified
- [ ] Full audit trail for all actions

---

## Phase 9: Export Reports & Audit Log
**Duration: ~3 days** | **Priority: MEDIUM**

### 9.1 — Create `export/csv.php`
Stream CSV to browser using `header()` + PHP output. Scoped by role.
Auto-named: `InventoryIQ_{Company}_{Warehouse}_{date}.csv`

### 9.2 — Create `export/pdf.php`
Generate PDF using mPDF/FPDF. Company logo, warehouse name, date, formatted table.
Write to `/exports/`, stream with `readfile()`, then `unlink()`.

### 9.3 — Create `audit/log.php`
Filterable, paginated audit log view (Screen 12):
- Manager: own warehouse only
- Company Admin: all company warehouses
- Scoped queries with prepared statements

### 9.4 — Verification
- [ ] CSV export → valid file opens in Excel/Sheets
- [ ] PDF export → formatted document with logo and table
- [ ] Export temp files cleaned up (no files left in `/exports/`)
- [ ] Audit log shows correct scope for each role
- [ ] Audit log entries exist for EXPORT_CSV / EXPORT_PDF

---

## Phase 10: Settings Panel
**Duration: ~2 days** | **Priority: MEDIUM**

### 10.1 — Create `settings/index.php`
Role-appropriate settings (Screen 11):
- **Company Admin**: Edit profile, currency, low-stock threshold, notification prefs, change password, delete company
- **Manager**: Warehouse profile, local threshold override, change password, notification prefs
- **Staff**: Change password only
Dark mode toggle in settings + nav bar.

### 10.2 — Create `settings/staff.php`
Manager creates/manages Staff accounts (Screen 23):
- Create: name → auto-generate login (`staff-{name}@{handle}`) + temp password
- Show password ONCE in modal
- Activate/deactivate Staff

### 10.3 — Verification
- [ ] Each role sees only their permitted settings
- [ ] Password change works (verify with re-login)
- [ ] Staff account creation → login works, forced password change
- [ ] Dark mode toggle persists in session

---

## Phase 11: Super Admin Portal
**Duration: ~4 days** | **Priority: HIGH**

### 11.1 — Create `superadmin/dashboard.php`
Platform dashboard (Screen 15 — Cosmic Dark):
- 5 stat cards: Companies, Warehouses, Products, Users, 24h Activity
- Live platform activity feed
- New registrations list
- System flags (failed logins)

### 11.2 — Create `superadmin/companies.php`
Company directory (Screen 16): searchable/sortable table of all companies.

### 11.3 — Create `superadmin/company_view.php`
Full drill-down (Screen 17): tabbed view of warehouses, products, messages, audit log for a specific company.

### 11.4 — Create `superadmin/company_actions.php`
POST handler: suspend, reactivate, delete company, reset Company Admin password.
All with audit logging.

### 11.5 — Create `superadmin/audit_export.php`
Export platform-wide or per-company audit log as CSV.

### 11.6 — Create `superadmin/maintenance.php`
Toggle maintenance mode (blocks company logins with message, SA unaffected).

### 11.7 — Verification
- [ ] SA dashboard shows correct platform-wide stats
- [ ] Company directory lists all companies with correct data
- [ ] Drill-down shows all warehouses/products/messages/audit for selected company
- [ ] Suspend company → all company users can't login
- [ ] Reactivate → login restored
- [ ] Delete company → all data cascade deleted
- [ ] Maintenance mode → company login shows maintenance message, SA login works
- [ ] All actions write to audit log

---

## Phase 12: Final Polish, Integration & Testing
**Duration: ~2 days** | **Priority: HIGH**

### 12.1 — Update [README.md](file:///Users/shrey/Desktop/Shrey/PHP/PHP%20MVP%202/README.md)
Complete setup instructions for v2.0:
- XAMPP setup, SQL import, config file, SA seeding, directory permissions

### 12.2 — Cross-cutting verification
Run through the complete testing checklist from AI Rules §13:

**Authentication Tests:**
- [ ] Login each of 4 roles → correct redirect
- [ ] 5 wrong passwords → lockout
- [ ] Direct URL access without session → login redirect
- [ ] Access higher-role URL → 403

**Data Scoping Tests:**
- [ ] Company A admin can't see Company B data
- [ ] Warehouse X manager can't see Warehouse Y data
- [ ] Manually crafted URLs with wrong IDs → blocked

**Security Tests:**
- [ ] `' OR '1'='1` in all form fields → no SQL injection
- [ ] `<script>alert('xss')</script>` in product name → renders as escaped text
- [ ] PHP file renamed as .jpg → MIME rejected
- [ ] Image > 2MB → size rejected

**Business Logic Tests:**
- [ ] Warehouse create → manager auto-created with temp password
- [ ] Staff creation → login works, password change forced
- [ ] Low-stock product → notification auto-generated
- [ ] Restock request → pending → approve/reject → notifications
- [ ] Product delete → images unlinked
- [ ] Stock transfer → transaction consistent

**Audit Log Tests:**
- [ ] Every action_type in the list has corresponding audit entry
- [ ] Deleted users still have historical log entries

---

## Phase Dependency Diagram

```
Phase 0 (Schema + Scaffolding)
    ↓
Phase 1 (CSS Design System + Shared Includes)
    ↓
Phase 2 (Auth + Sessions)
    ↓
Phase 3 (Dashboards) ←─── Phase 4 (Company/Warehouse Mgmt)
    ↓                          ↓
Phase 5 (Product CRUD) ←── Phase 6 (Categories)
    ↓
Phase 7 (Notifications) ←─ Phase 8 (Restock)
    ↓
Phase 9 (Export + Audit)
    ↓
Phase 10 (Settings)
    ↓
Phase 11 (Super Admin)
    ↓
Phase 12 (Polish + Test)
```

---

## Critical Rules Summary (for the executing AI)

> [!CAUTION]
> **NEVER do any of the following:**
> - Use PDO, Laravel, jQuery, React, Bootstrap, Tailwind, or any framework
> - Concatenate user input into SQL strings (always prepared statements)
> - Echo user data without `htmlspecialchars()`
> - Trust file extensions for upload validation (always `finfo_file()`)
> - Store plain text passwords
> - Add a left sidebar (capsule nav only)
> - Use white/light backgrounds
> - Skip audit logging for ANY data-modifying action
> - Use `SELECT *` in production queries
> - Create files outside the defined directory structure

> [!IMPORTANT] 
> **ALWAYS do the following:**
> - Include `auth/check.php` at the top of every protected page
> - Use `$_SESSION` for role/scope checks, never trust URL params
> - Call `write_audit_log()` after every data modification
> - Call `check_low_stock()` after every stock change
> - Use MySQL transactions for multi-step operations
> - Apply the 3D glassmorphic CSS classes from the design system
> - Follow the exact HTML page template from AI Rules §9.2

---

## File Count Summary

| Category | Count |
|---|---|
| PHP pages (new) | ~38 |
| Shared includes | 4 |
| CSS files | 3 |
| JS files | 1 |
| SQL setup | 1 |
| **Total new files** | **~47** |
