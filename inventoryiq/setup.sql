-- InventoryIQ Database Setup Script
-- SAD Section 4.2 — exact schema

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

-- Sample seed data (Phase 9)
INSERT INTO products (product_name, category, price, stock_quantity, description) VALUES
('Wireless Mouse', 'Electronics', 599.00, 50, 'Ergonomic wireless mouse with USB receiver'),
('Cotton T-Shirt', 'Clothing', 349.00, 120, 'Plain white cotton t-shirt, size M'),
('Notebook A5', 'Stationery', 85.00, 200, '200-page ruled notebook'),
('USB-C Cable', 'Electronics', 199.00, 75, '1-meter braided USB-C charging cable'),
('Water Bottle', 'Accessories', 450.00, 30, 'Stainless steel insulated 750ml bottle');
