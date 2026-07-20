-- ============================================================
-- GROCIFY - Complete Database Schema (All Features)
-- ============================================================
-- Features: Stock, Reviews, Coupons, Wishlist, Analytics,
--           Delivery Boy, GST Invoice, Product Gallery, Demo Payment
-- ============================================================

CREATE DATABASE IF NOT EXISTS grocify_db;
USE grocify_db;

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    phone      VARCHAR(20)  DEFAULT NULL,
    address    TEXT         DEFAULT NULL,
    is_admin   TINYINT(1)   DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password, phone, address, is_admin) VALUES
('admin',      'admin@grocify.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543210', '123 Admin Street, Mumbai',    1),
('john_doe',   'john@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543211', '456 Customer Lane, Delhi',    0),
('jane_smith', 'jane@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543212', '789 Buyer Road, Bangalore',   0)
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- PRODUCTS  (+ stock, GST, SKU fields)
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)   NOT NULL,
    description     TEXT,
    price           DECIMAL(10,2)  NOT NULL,
    image_url       VARCHAR(255)   DEFAULT 'assets/images/default.jpg',
    category        VARCHAR(50),
    stock           INT            DEFAULT 100,
    low_stock_alert INT            DEFAULT 10,
    sku             VARCHAR(50)    DEFAULT NULL,
    gst_rate        DECIMAL(5,2)   DEFAULT 5.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (name, description, price, image_url, category, stock, low_stock_alert, sku, gst_rate) VALUES
('Organic Bananas',    'Fresh organic bananas, 1 bunch (approx 6-8 pieces). Perfect for smoothies.',   60.00, 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?w=300&h=200&fit=crop', 'Fruits',     150, 10, 'FRT-001', 0.00),
('Fresh Strawberries', 'Sweet juicy strawberries, 1lb pack. Great for desserts and breakfast.',        180.00, 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?w=300&h=200&fit=crop', 'Fruits',      80, 10, 'FRT-002', 0.00),
('Avocado',            'Ripe and creamy avocados, perfect for salads. 2 pieces.',                     120.00, 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?w=300&h=200&fit=crop', 'Fruits',      60, 10, 'FRT-003', 0.00),
('Whole Wheat Bread',  'Freshly baked whole wheat bread, 400g loaf. Soft and nutritious.',             45.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=300&h=200&fit=crop', 'Bakery',     120, 15, 'BAK-001', 5.00),
('Almond Milk',        'Unsweetened almond milk, 1L carton. Dairy-free and vegan friendly.',          180.00, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=300&h=200&fit=crop', 'Dairy',       90, 10, 'DAI-001', 5.00),
('Free-Range Eggs',    'Dozen free-range large eggs. Rich in protein and omega-3.',                   120.00, 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=300&h=200&fit=crop', 'Dairy',      200, 20, 'DAI-002', 0.00),
('Organic Tomatoes',   'Fresh vine-ripened tomatoes, 500g pack. Perfect for salads.',                  40.00, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=300&h=200&fit=crop', 'Vegetables', 300, 30, 'VEG-001', 0.00),
('Fresh Spinach',      'Organic spinach leaves, 250g bag. Washed and ready to eat.',                   35.00, 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=300&h=200&fit=crop', 'Vegetables',   8,  5, 'VEG-002', 0.00),
('Brown Rice',         'Organic brown rice, 1kg pack. High in fiber and nutrients.',                   95.00, 'https://images.unsplash.com/photo-1536304993881-ff6e9eefa2a6?w=300&h=200&fit=crop', 'Grains',     250, 25, 'GRN-001', 5.00),
('Olive Oil',          'Extra virgin olive oil, 500ml bottle. Cold-pressed and pure.',                450.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=300&h=200&fit=crop', 'Pantry',      45, 10, 'PAN-001', 18.00),
('Greek Yogurt',       'Creamy full-fat Greek yogurt, 400g. High protein, probiotics.',               110.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=300&h=200&fit=crop', 'Dairy',      100, 15, 'DAI-003', 5.00),
('Oat Granola',        'Crunchy honey-oat granola, 500g. Perfect for breakfast.',                     220.00, 'https://images.unsplash.com/photo-1517093157656-b9eccef91cb1?w=300&h=200&fit=crop', 'Grains',       0, 10, 'GRN-002', 12.00)
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- PRODUCT GALLERY
-- ============================================================
CREATE TABLE IF NOT EXISTS product_gallery (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT  NOT NULL,
    image_url  TEXT NOT NULL,
    sort_order INT  DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample gallery images for Banana (product 1)
INSERT INTO product_gallery (product_id, image_url, sort_order) VALUES
(1, 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?w=600&h=400&fit=crop', 0),
(1, 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=600&h=400&fit=crop', 1),
(2, 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?w=600&h=400&fit=crop', 0),
(2, 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?w=600&h=400&fit=crop', 1)
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- REVIEWS
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT  NOT NULL,
    user_id    INT  NOT NULL,
    rating     TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title      VARCHAR(255) DEFAULT NULL,
    body       TEXT NOT NULL,
    status     ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO reviews (product_id, user_id, rating, title, body, status) VALUES
(1, 2, 5, 'Super fresh!',       'These bananas arrived perfectly ripe. Great quality!', 'Approved'),
(1, 3, 4, 'Good value',         'Nice bananas, delivery was quick too.',                'Approved'),
(2, 2, 5, 'Love these berries', 'Strawberries were sweet and fresh. Will order again.', 'Approved'),
(7, 3, 4, 'Fresh tomatoes',     'Good quality tomatoes, perfect for cooking.',          'Approved')
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- COUPONS
-- ============================================================
CREATE TABLE IF NOT EXISTS coupons (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(30)   NOT NULL UNIQUE,
    type       ENUM('percent','fixed') DEFAULT 'percent',
    value      DECIMAL(10,2) NOT NULL,
    min_order  DECIMAL(10,2) DEFAULT 0,
    max_uses   INT           DEFAULT 100,
    used_count INT           DEFAULT 0,
    expires_at DATE          DEFAULT NULL,
    is_active  TINYINT(1)    DEFAULT 1,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO coupons (code, type, value, min_order, max_uses, expires_at) VALUES
('WELCOME10', 'percent', 10.00,   0.00, 1000, '2027-12-31'),
('SAVE50',    'fixed',   50.00, 300.00,  500, '2027-12-31'),
('FRESH20',   'percent', 20.00, 500.00,  200, '2027-06-30'),
('NEWUSER',   'fixed',   30.00,   0.00,  999, NULL)
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- COUPON USAGE
-- ============================================================
CREATE TABLE IF NOT EXISTS coupon_usage (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id   INT NOT NULL,
    order_id  INT DEFAULT NULL,
    used_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- WISHLIST
-- ============================================================
CREATE TABLE IF NOT EXISTS wishlist (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wish (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO wishlist (user_id, product_id) VALUES
(2, 5), (2, 10), (3, 1), (3, 6)
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- ORDERS  (+ coupon, GST, payment_id fields)
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT           NOT NULL,
    total_amount    DECIMAL(10,2) NOT NULL,
    billing_name    VARCHAR(100)  DEFAULT NULL,
    billing_email   VARCHAR(100)  DEFAULT NULL,
    billing_phone   VARCHAR(20)   DEFAULT NULL,
    billing_address TEXT          DEFAULT NULL,
    payment_method  VARCHAR(50)   DEFAULT 'Online',
    payment_id      VARCHAR(100)  DEFAULT NULL,
    coupon_code     VARCHAR(30)   DEFAULT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    gst_amount      DECIMAL(10,2) DEFAULT 0.00,
    order_date      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    status          VARCHAR(50)   DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ORDER ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL,
    product_id INT           NOT NULL,
    quantity   INT           NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- FEEDBACK
-- ============================================================
CREATE TABLE IF NOT EXISTS feedback (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT           NOT NULL,
    subject    VARCHAR(255)  NOT NULL,
    message    TEXT          NOT NULL,
    rating     INT           DEFAULT 5,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    status     ENUM('Pending','Reviewed','Resolved') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DELIVERY BOYS
-- ============================================================
CREATE TABLE IF NOT EXISTS delivery_boys (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    phone             VARCHAR(20)  NOT NULL,
    email             VARCHAR(100) DEFAULT NULL,
    status            ENUM('Available','Busy','Offline') DEFAULT 'Available',
    total_deliveries  INT          DEFAULT 0,
    rating            DECIMAL(3,1) DEFAULT 5.0,
    vehicle_number    VARCHAR(20)  DEFAULT NULL,
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO delivery_boys (name, phone, email, status, total_deliveries, vehicle_number) VALUES
('Ravi Kumar',    '9876500001', 'ravi@grocify.com',   'Available', 45, 'MH-01-AB-1234'),
('Amit Sharma',   '9876500002', 'amit@grocify.com',   'Available', 32, 'MH-02-CD-5678'),
('Suresh Patil',  '9876500003', 'suresh@grocify.com', 'Offline',   18, 'MH-03-EF-9012')
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- DELIVERY ASSIGNMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS delivery_assignments (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    order_id         INT  NOT NULL,
    delivery_boy_id  INT  NOT NULL,
    status           ENUM('Assigned','Picked Up','Out for Delivery','Delivered') DEFAULT 'Assigned',
    assigned_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at     TIMESTAMP NULL DEFAULT NULL,
    notes            TEXT DEFAULT NULL,
    FOREIGN KEY (order_id)        REFERENCES orders(id)        ON DELETE CASCADE,
    FOREIGN KEY (delivery_boy_id) REFERENCES delivery_boys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE ORDERS (for dashboard/analytics demo)
-- ============================================================
INSERT INTO orders (user_id, total_amount, billing_name, billing_email, billing_phone, billing_address, payment_method, payment_id, status, order_date) VALUES
(2, 360.00, 'John Doe',   'john@example.com', '9876543211', '456 Customer Lane, Delhi',    'UPI',         'DEMO-001', 'Delivered',  DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 215.00, 'Jane Smith', 'jane@example.com', '9876543212', '789 Buyer Road, Bangalore',   'Card',        'DEMO-002', 'Delivered',  DATE_SUB(NOW(), INTERVAL 8  DAY)),
(2, 540.00, 'John Doe',   'john@example.com', '9876543211', '456 Customer Lane, Delhi',    'Net Banking', 'DEMO-003', 'Shipped',    DATE_SUB(NOW(), INTERVAL 5  DAY)),
(3, 120.00, 'Jane Smith', 'jane@example.com', '9876543212', '789 Buyer Road, Bangalore',   'UPI',         'DEMO-004', 'Pending',    DATE_SUB(NOW(), INTERVAL 2  DAY)),
(2, 680.00, 'John Doe',   'john@example.com', '9876543211', '456 Customer Lane, Delhi',    'Card',        'DEMO-005', 'Processing', DATE_SUB(NOW(), INTERVAL 1  DAY))
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 2, 60.00), (1, 6, 2, 120.00),
(2, 4, 3, 45.00), (2, 7, 2, 40.00),
(3, 5, 2, 180.00),(3, 10, 1, 450.00),(3, 9, 1, 95.00),
(4, 6, 1, 120.00),
(5, 2, 2, 180.00),(5, 3, 2, 120.00),(5, 1, 1, 60.00)
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- UPGRADE HELPERS (safe to run on existing installs)
-- ============================================================
-- Add columns if upgrading from old schema
ALTER TABLE products ADD COLUMN IF NOT EXISTS stock           INT          DEFAULT 100;
ALTER TABLE products ADD COLUMN IF NOT EXISTS low_stock_alert INT          DEFAULT 10;
ALTER TABLE products ADD COLUMN IF NOT EXISTS sku             VARCHAR(50)  DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS gst_rate        DECIMAL(5,2) DEFAULT 5.00;

ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_id      VARCHAR(100)  DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code     VARCHAR(30)   DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS gst_amount      DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE orders MODIFY COLUMN IF EXISTS status VARCHAR(50) DEFAULT 'Pending';

