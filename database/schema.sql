-- Custom Business Cards — schema
-- Engine: MySQL 8 / MariaDB (XAMPP)

CREATE DATABASE IF NOT EXISTS web_to_print
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE web_to_print;

-- ---------------------------------------------------------------------------
-- Products
-- One row = one merchandisable parent (e.g. Custom Business Cards)
-- ---------------------------------------------------------------------------
CREATE TABLE products (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku           VARCHAR(64)  NULL COMMENT 'Optional parent SKU; variants have their own',
  name          VARCHAR(255) NOT NULL,
  description   TEXT         NULL,
  product_type  VARCHAR(100) NOT NULL DEFAULT 'Business Cards',
  status        ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_status (status),
  KEY idx_products_type (product_type)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Product options (Size, Paper Type, Quantity)
-- ---------------------------------------------------------------------------
CREATE TABLE product_options (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL COMMENT 'Size, Paper Type, Quantity',
  position    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_options_product
    FOREIGN KEY (product_id) REFERENCES products (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_option_per_product (product_id, name),
  KEY idx_options_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE product_option_values (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  option_id   INT UNSIGNED NOT NULL,
  value       VARCHAR(100) NOT NULL COMMENT 'Standard, Matte, 100, ...',
  label       VARCHAR(150) NOT NULL COMMENT 'Display label e.g. Standard (3.5\" x 2\")',
  position    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  CONSTRAINT fk_option_values_option
    FOREIGN KEY (option_id) REFERENCES product_options (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_value_per_option (option_id, value),
  KEY idx_option_values_option (option_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Variants — Size × Paper × Quantity (12 rows for this product)
-- ---------------------------------------------------------------------------
CREATE TABLE product_variants (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id    INT UNSIGNED NOT NULL,
  sku           VARCHAR(64)  NOT NULL,
  size          VARCHAR(50)  NOT NULL,
  paper_type    VARCHAR(50)  NOT NULL,
  quantity      INT UNSIGNED NOT NULL,
  price         DECIMAL(10,2) NOT NULL,
  status        ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'active',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_variants_product
    FOREIGN KEY (product_id) REFERENCES products (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_variants_sku (sku),
  UNIQUE KEY uq_variant_combo (product_id, size, paper_type, quantity),
  KEY idx_variants_product (product_id),
  KEY idx_variants_price_lookup (product_id, paper_type, quantity)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Pricing matrix — source of truth (quantity + paper; size is not a factor)
-- ---------------------------------------------------------------------------
CREATE TABLE pricing (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  quantity    INT UNSIGNED NOT NULL,
  paper_type  VARCHAR(50)  NOT NULL,
  price       DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_pricing_product
    FOREIGN KEY (product_id) REFERENCES products (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_price_matrix (product_id, quantity, paper_type),
  KEY idx_pricing_lookup (product_id, quantity, paper_type)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------------------
CREATE TABLE orders (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number  VARCHAR(32)  NOT NULL,
  customer_name VARCHAR(150) NOT NULL,
  customer_email VARCHAR(255) NOT NULL,
  status        ENUM('pending', 'paid', 'in_production', 'shipped', 'cancelled', 'failed')
                NOT NULL DEFAULT 'pending',
  subtotal      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  notes         TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_orders_number (order_number),
  KEY idx_orders_status (status),
  KEY idx_orders_email (customer_email),
  KEY idx_orders_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id      INT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED NOT NULL,
  variant_id    INT UNSIGNED NULL,
  size          VARCHAR(50)  NOT NULL,
  paper_type    VARCHAR(50)  NOT NULL,
  quantity      INT UNSIGNED NOT NULL,
  unit_price    DECIMAL(10,2) NOT NULL,
  line_total    DECIMAL(10,2) NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_items_order
    FOREIGN KEY (order_id) REFERENCES orders (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_items_product
    FOREIGN KEY (product_id) REFERENCES products (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_items_variant
    FOREIGN KEY (variant_id) REFERENCES product_variants (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  KEY idx_items_order (order_id),
  KEY idx_items_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Uploaded artwork — one file per order line (made-to-order)
-- ---------------------------------------------------------------------------
CREATE TABLE artwork_files (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_item_id      INT UNSIGNED NULL COMMENT 'Null until checkout completes; set after order is created',
  original_filename  VARCHAR(255) NOT NULL,
  stored_path        VARCHAR(500) NOT NULL,
  mime_type          VARCHAR(100) NOT NULL,
  file_size_bytes    INT UNSIGNED NOT NULL,
  checksum_sha256    CHAR(64)     NULL,
  uploaded_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_artwork_order_item
    FOREIGN KEY (order_item_id) REFERENCES order_items (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  KEY idx_artwork_item (order_item_id),
  KEY idx_artwork_uploaded (uploaded_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Seed: Custom Business Cards
-- ---------------------------------------------------------------------------
INSERT INTO products (id, sku, name, description, product_type, status)
VALUES (
  1,
  'BC',
  'Custom Business Cards',
  'Printed to order. Choose size, paper, and quantity, then upload your artwork.',
  'Business Cards',
  'active'
);

INSERT INTO product_options (id, product_id, name, position) VALUES
  (1, 1, 'Size', 1),
  (2, 1, 'Paper Type', 2),
  (3, 1, 'Quantity', 3);

INSERT INTO product_option_values (option_id, value, label, position) VALUES
  (1, 'Standard', 'Standard (3.5" × 2")', 1),
  (1, 'Square',   'Square (2.5" × 2.5")', 2),
  (2, 'Matte',    'Matte', 1),
  (2, 'Glossy',   'Glossy', 2),
  (3, '100',      '100 cards', 1),
  (3, '500',      '500 cards', 2),
  (3, '1000',     '1000 cards', 3);

INSERT INTO pricing (product_id, quantity, paper_type, price) VALUES
  (1, 100,  'Matte',  20.00),
  (1, 500,  'Matte',  80.00),
  (1, 1000, 'Matte', 140.00),
  (1, 100,  'Glossy',  25.00),
  (1, 500,  'Glossy', 100.00),
  (1, 1000, 'Glossy', 180.00);

INSERT INTO product_variants (product_id, sku, size, paper_type, quantity, price) VALUES
  (1, 'BC-STD-MAT-100',  'Standard', 'Matte',  100,  20.00),
  (1, 'BC-STD-MAT-500',  'Standard', 'Matte',  500,  80.00),
  (1, 'BC-STD-MAT-1000', 'Standard', 'Matte', 1000, 140.00),
  (1, 'BC-STD-GLO-100',  'Standard', 'Glossy',  100,  25.00),
  (1, 'BC-STD-GLO-500',  'Standard', 'Glossy',  500, 100.00),
  (1, 'BC-STD-GLO-1000', 'Standard', 'Glossy', 1000, 180.00),
  (1, 'BC-SQR-MAT-100',  'Square',   'Matte',  100,  20.00),
  (1, 'BC-SQR-MAT-500',  'Square',   'Matte',  500,  80.00),
  (1, 'BC-SQR-MAT-1000', 'Square',   'Matte', 1000, 140.00),
  (1, 'BC-SQR-GLO-100',  'Square',   'Glossy',  100,  25.00),
  (1, 'BC-SQR-GLO-500',  'Square',   'Glossy',  500, 100.00),
  (1, 'BC-SQR-GLO-1000', 'Square',   'Glossy', 1000, 180.00);
