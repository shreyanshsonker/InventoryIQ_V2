# InventoryIQ v2.0 — Multi-Tenant Inventory Management System

A full-featured, multi-tenant inventory management platform built with **PHP 8.x** and **MySQL 8.0**. Features a cinematic **3D glassmorphic UI** with Aurora backgrounds, 4-role RBAC, and a dedicated Super Admin control panel.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

---

## ✨ Key Features

| Feature | Description |
|---|---|
| **Multi-Tenancy** | Companies register independently; data is fully isolated |
| **4-Role RBAC** | Super Admin → Company Admin → WH Manager → WH Staff |
| **3D Glassmorphic UI** | Aurora gradient backgrounds, glass cards with tilt effects, capsule navigation |
| **Product Management** | Full CRUD with image upload (MIME-validated), SKU auto-generation, search, filters, pagination |
| **Warehouse Fleet** | Create/edit warehouses, assign staff, set capacity limits and low-stock thresholds |
| **Real-Time Notifications** | Low-stock alerts, broadcast messaging, priority-based inbox |
| **Restock Workflow** | Request → Approve/Reject → Auto stock update |
| **CSV Reports** | Export inventory and low-stock reports |
| **Audit Trail** | Every action logged with user, role, IP, and timestamp |
| **Super Admin Portal** | Cosmic Dark theme, company management, DB maintenance tools |

---

## 🛡️ Security

- **SQL Injection Prevention** — All queries use `mysqli_prepare()` with bound parameters
- **XSS Prevention** — All output escaped with `htmlspecialchars()`
- **Image Upload Security** — MIME type validated via `finfo`, not file extension
- **Session Security** — 30-minute idle timeout, `session_regenerate_id()` on login
- **Account Lockout** — 5 failed attempts → 15-minute lock
- **Company Isolation** — All queries scoped to `company_id`

---

## 🚀 Setup Instructions

### Prerequisites
- **PHP 8.x** with `mysqli` and `fileinfo` extensions
- **MySQL 8.0+**
- A modern web browser

### 1. Clone the Repository
```bash
git clone <repo-url>
cd "PHP MVP 2"
```

### 2. Import the Database
```bash
mysql -u root -p < inventoryiq/setup.sql
```
This creates the `inventoryiq_db` database with 11 tables and seeds a Super Admin account.

### 3. Configure Database Credentials
Edit `inventoryiq/config/db.php` if your MySQL credentials differ from the defaults:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventoryiq_db');
```

### 4. Start the Server
```bash
php -S localhost:8000 -t inventoryiq/
```

### 5. Open in Browser
- **Company Portal:** [http://localhost:8000/login.php](http://localhost:8000/login.php)
- **Super Admin:** [http://localhost:8000/superadmin/login.php](http://localhost:8000/superadmin/login.php)

---

## 🔑 Default Credentials

| Role | Login URL | Username | Password |
|---|---|---|---|
| **Super Admin** | `/superadmin/login.php` | `superadmin` | `Admin@123` |
| **Company Admin** | `/login.php` | *(email used during registration)* | *(password set during registration)* |

> Register a new company at `/register.php`, then add warehouses and staff from the dashboard.

---

## 📁 Directory Structure

```
inventoryiq/
├── config/
│   └── db.php                 ← Database connection (procedural mysqli)
├── auth/
│   └── check.php              ← Session middleware + RBAC
├── includes/
│   ├── header.php             ← Role-aware top bar + HTML head
│   ├── footer.php             ← Capsule nav + toast container
│   ├── audit.php              ← write_audit_log() helper
│   └── notify.php             ← Notification + low-stock helpers
├── css/
│   ├── style.css              ← Aurora Glass design system (700+ lines)
│   ├── superadmin.css          ← Cosmic Dark SA theme
│   └── dark.css               ← Dark mode overrides
├── js/
│   └── app.js                 ← Tilt effects, toasts, modals, counters
├── dashboard/
│   └── index.php              ← Role-specific dashboards (CA/WM/WS)
├── warehouse/
│   ├── list.php               ← Warehouse fleet grid
│   ├── add.php / edit.php     ← Warehouse CRUD
│   └── users.php              ← Staff management
├── products/
│   ├── view.php               ← Inventory table with search/filter/pagination
│   ├── add.php / edit.php     ← Product CRUD with image upload
│   └── delete.php             ← POST-only delete with image cleanup
├── categories/
│   └── manage.php             ← Category CRUD
├── notifications/
│   ├── index.php              ← Notification inbox with filter pills
│   └── broadcast.php          ← Broadcast to warehouses
├── restock/
│   ├── request.php            ← Submit restock requests
│   └── manage.php             ← Approve/reject requests
├── export/
│   └── csv.php                ← CSV report downloads
├── audit/
│   └── log.php                ← Filterable audit trail
├── settings/
│   └── index.php              ← Profile + password management
├── superadmin/
│   ├── login.php              ← SA login (Cosmic Dark theme)
│   ├── dashboard.php          ← Global stats + activity feed
│   ├── companies.php          ← Suspend/reactivate companies
│   └── maintenance.php        ← DB stats, health, log purge
├── uploads/products/          ← Product images (gitignored)
├── login.php                  ← Company user login
├── register.php               ← Company self-registration
├── logout.php                 ← Session destroy + redirect
├── 403.php / 500.php          ← Error pages
└── setup.sql                  ← 11-table schema + SA seed
```

---

## 🎨 Design System

### Main Portal — Aurora Glass
- **Background:** Deep space (#050816) with shifting indigo/violet/teal radial gradients
- **Cards:** Frosted glass (`backdrop-filter: blur(24px)`) with 3D tilt on hover
- **Navigation:** Bottom capsule nav bar (no sidebar)
- **Typography:** Syne (headings), DM Sans (body), Fira Code (monospace)

### Super Admin — Cosmic Dark
- **Background:** Deep black with purple neon grid overlay
- **Accents:** Violet (#A78BFA) and pink (#F0ABFC) neon glows
- **Aesthetic:** Cyberpunk control room

---

## 🗄️ Database Schema (11 Tables)

| Table | Purpose |
|---|---|
| `companies` | Registered companies with handle, status |
| `warehouses` | Storage locations per company |
| `users` | All roles (CA, WM, WS) with bcrypt passwords |
| `super_admin` | Separate SA credentials |
| `categories` | Product categories per company |
| `products` | Inventory items with SKU, price, stock |
| `product_images` | Multi-image support per product |
| `notifications` | Alerts, broadcasts, restock notices |
| `restock_requests` | Request → approval workflow |
| `audit_log` | Full action trail with IP tracking |
| `settings` | Key-value config per company |

---

## 🧰 Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x (procedural, no frameworks) |
| Database | MySQL 8.0 (InnoDB, utf8mb4) |
| Frontend | HTML5 + CSS3 (vanilla, no Tailwind/Bootstrap) |
| JavaScript | Vanilla JS (enhancement only, app works without JS) |
| Icons | Lucide Icons (CDN) |
| Fonts | Google Fonts (Syne, DM Sans, Fira Code) |

---

## 📄 License

MIT License — See [LICENSE](LICENSE) for details.
