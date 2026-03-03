-- InventoryIQ v2.0 — Complete Database Setup
-- AI Rules §2.1–2.11 — 11 Tables in dependency order

CREATE DATABASE IF NOT EXISTS inventoryiq_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE inventoryiq_db;

-- 1. super_admin
CREATE TABLE IF NOT EXISTS super_admin (
  admin_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. companies
CREATE TABLE IF NOT EXISTS companies (
  company_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_name     VARCHAR(150) NOT NULL,
  handle           VARCHAR(60) NOT NULL UNIQUE,
  owner_name       VARCHAR(120) NOT NULL,
  email            VARCHAR(150) NOT NULL UNIQUE,
  phone            VARCHAR(20),
  address          TEXT,
  logo_path        VARCHAR(255),
  currency         VARCHAR(10) DEFAULT 'INR',
  low_stock_default INT UNSIGNED DEFAULT 10,
  status           ENUM('active','suspended') DEFAULT 'active',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. warehouses (FK → companies)
CREATE TABLE IF NOT EXISTS warehouses (
  warehouse_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id        INT UNSIGNED NOT NULL,
  warehouse_name    VARCHAR(150) NOT NULL,
  handle            VARCHAR(60) NOT NULL,
  location          VARCHAR(200),
  contact_person    VARCHAR(120),
  capacity_limit    INT UNSIGNED,
  priority_rank     TINYINT UNSIGNED DEFAULT 99,
  low_stock_override INT UNSIGNED,
  status            ENUM('active','inactive') DEFAULT 'active',
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. users (FK → companies, warehouses)
CREATE TABLE IF NOT EXISTS users (
  user_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id       INT UNSIGNED NOT NULL,
  warehouse_id     INT UNSIGNED,
  full_name        VARCHAR(120) NOT NULL,
  login_identifier VARCHAR(160) NOT NULL UNIQUE,
  password_hash    VARCHAR(255) NOT NULL,
  role             ENUM('company_admin','wh_manager','wh_staff') NOT NULL,
  failed_attempts  TINYINT UNSIGNED DEFAULT 0,
  locked_until     DATETIME,
  remember_token   VARCHAR(64),
  status           ENUM('active','inactive') DEFAULT 'active',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id)   REFERENCES companies(company_id) ON DELETE CASCADE,
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. categories (FK → companies)
CREATE TABLE IF NOT EXISTS categories (
  category_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id    INT UNSIGNED NOT NULL,
  category_name VARCHAR(100) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. products (FK → warehouses, categories)
CREATE TABLE IF NOT EXISTS products (
  product_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  warehouse_id   INT UNSIGNED NOT NULL,
  category_id    INT UNSIGNED,
  product_name   VARCHAR(200) NOT NULL,
  sku            VARCHAR(80) NOT NULL UNIQUE,
  price          DECIMAL(12,2) NOT NULL CHECK (price > 0),
  stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  description    TEXT,
  primary_image  VARCHAR(255),
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id) ON DELETE CASCADE,
  FOREIGN KEY (category_id)  REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. product_images (FK → products)
CREATE TABLE IF NOT EXISTS product_images (
  image_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  image_path  VARCHAR(255) NOT NULL,
  is_primary  TINYINT(1) DEFAULT 0,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. notifications (FK → companies, users, warehouses)
CREATE TABLE IF NOT EXISTS notifications (
  notification_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id           INT UNSIGNED NOT NULL,
  sender_user_id       INT UNSIGNED,
  recipient_warehouse_id INT UNSIGNED,
  title                VARCHAR(200) NOT NULL,
  body                 TEXT NOT NULL,
  priority             ENUM('info','warning','critical') DEFAULT 'info',
  type                 ENUM('broadcast','alert','restock','system') DEFAULT 'broadcast',
  is_read              TINYINT(1) DEFAULT 0,
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id)             REFERENCES companies(company_id) ON DELETE CASCADE,
  FOREIGN KEY (sender_user_id)         REFERENCES users(user_id) ON DELETE SET NULL,
  FOREIGN KEY (recipient_warehouse_id) REFERENCES warehouses(warehouse_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. restock_requests (FK → warehouses, products, users)
CREATE TABLE IF NOT EXISTS restock_requests (
  request_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  warehouse_id    INT UNSIGNED NOT NULL,
  product_id      INT UNSIGNED NOT NULL,
  requested_by    INT UNSIGNED NOT NULL,
  quantity_needed INT UNSIGNED NOT NULL CHECK (quantity_needed > 0),
  note            TEXT,
  status          ENUM('pending','approved','rejected') DEFAULT 'pending',
  response_note   TEXT,
  responded_by    INT UNSIGNED,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  responded_at    DATETIME,
  FOREIGN KEY (warehouse_id)  REFERENCES warehouses(warehouse_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id)    REFERENCES products(product_id) ON DELETE CASCADE,
  FOREIGN KEY (requested_by)  REFERENCES users(user_id),
  FOREIGN KEY (responded_by)  REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. stock_transfers (FK → companies, warehouses, products, users)
CREATE TABLE IF NOT EXISTS stock_transfers (
  transfer_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id         INT UNSIGNED NOT NULL,
  from_warehouse_id  INT UNSIGNED NOT NULL,
  to_warehouse_id    INT UNSIGNED NOT NULL,
  product_id         INT UNSIGNED NOT NULL,
  quantity           INT UNSIGNED NOT NULL CHECK (quantity > 0),
  note               TEXT,
  initiated_by       INT UNSIGNED NOT NULL,
  created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id)        REFERENCES companies(company_id) ON DELETE CASCADE,
  FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(warehouse_id),
  FOREIGN KEY (to_warehouse_id)   REFERENCES warehouses(warehouse_id),
  FOREIGN KEY (product_id)        REFERENCES products(product_id),
  FOREIGN KEY (initiated_by)      REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. audit_log (FK → users only; role/company_id/warehouse_id are copies, not FK)
CREATE TABLE IF NOT EXISTS audit_log (
  log_id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  timestamp    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  user_id      INT UNSIGNED,
  role         VARCHAR(30),
  company_id   INT UNSIGNED,
  warehouse_id INT UNSIGNED,
  action_type  VARCHAR(50) NOT NULL,
  detail       TEXT,
  ip_address   VARCHAR(45),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Super Admin (username: superadmin, password: SuperAdmin@123)
INSERT INTO super_admin (username, password_hash)
VALUES ('superadmin', '$2y$12$AoMUEa/RnnehwwLhJWsMduffcwI0uwaV5SGJXx/NwvL/R7xTDuKWG');
