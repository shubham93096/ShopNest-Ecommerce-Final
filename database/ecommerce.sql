-- ============================================================
--  ShopNest E-Commerce — Full Database Schema
--  Compatible with Amazon RDS MySQL 8.0+
--  Run: mysql -u root -p < database/ecommerce.sql
-- ============================================================

-- CREATE DATABASE IF NOT EXISTS `aws_ecommerce`
--     CHARACTER SET utf8mb4
--     COLLATE utf8mb4_unicode_ci;

-- USE `aws_ecommerce`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  TABLE: admins
-- ----------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL,
    `email`         VARCHAR(150) NOT NULL UNIQUE,
    `password`      VARCHAR(255) NOT NULL,
    `role`          ENUM('super_admin','admin','manager') DEFAULT 'admin',
    `avatar`        VARCHAR(255) DEFAULT NULL,
    `last_login`    DATETIME DEFAULT NULL,
    `status`        TINYINT(1) DEFAULT 1,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: users (customers)
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`              VARCHAR(100) NOT NULL,
    `email`             VARCHAR(150) NOT NULL UNIQUE,
    `phone`             VARCHAR(20) DEFAULT NULL,
    `password`          VARCHAR(255) NOT NULL,
    `avatar`            VARCHAR(255) DEFAULT NULL,
    `address`           TEXT DEFAULT NULL,
    `city`              VARCHAR(80) DEFAULT NULL,
    `state`             VARCHAR(80) DEFAULT NULL,
    `pincode`           VARCHAR(10) DEFAULT NULL,
    `reset_token`       VARCHAR(100) DEFAULT NULL,
    `reset_expires`     DATETIME DEFAULT NULL,
    `email_verified`    TINYINT(1) DEFAULT 0,
    `status`            TINYINT(1) DEFAULT 1,
    `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: categories
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL,
    `slug`          VARCHAR(120) NOT NULL UNIQUE,
    `description`   TEXT DEFAULT NULL,
    `image`         VARCHAR(255) DEFAULT NULL,
    `parent_id`     INT UNSIGNED DEFAULT NULL,
    `sort_order`    INT DEFAULT 0,
    `status`        TINYINT(1) DEFAULT 1,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: products
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id`   INT UNSIGNED NOT NULL,
    `name`          VARCHAR(200) NOT NULL,
    `slug`          VARCHAR(220) NOT NULL UNIQUE,
    `description`   TEXT DEFAULT NULL,
    `short_desc`    VARCHAR(500) DEFAULT NULL,
    `sku`           VARCHAR(80) UNIQUE DEFAULT NULL,
    `price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `sale_price`    DECIMAL(10,2) DEFAULT NULL,
    `stock`         INT DEFAULT 0,
    `images`        TEXT DEFAULT NULL COMMENT 'JSON array of image filenames',
    `brand`         VARCHAR(100) DEFAULT NULL,
    `weight`        DECIMAL(8,2) DEFAULT NULL,
    `is_featured`   TINYINT(1) DEFAULT 0,
    `status`        TINYINT(1) DEFAULT 1,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: cart
-- ----------------------------
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `product_id`    INT UNSIGNED NOT NULL,
    `quantity`      INT NOT NULL DEFAULT 1,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `user_product` (`user_id`,`product_id`),
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: wishlist
-- ----------------------------
DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE `wishlist` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `product_id`    INT UNSIGNED NOT NULL,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_product` (`user_id`,`product_id`),
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: orders
-- ----------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_number`      VARCHAR(30) NOT NULL UNIQUE,
    `user_id`           INT UNSIGNED NOT NULL,
    `subtotal`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount`          DECIMAL(10,2) DEFAULT 0.00,
    `shipping_charge`   DECIMAL(8,2) DEFAULT 0.00,
    `tax`               DECIMAL(10,2) DEFAULT 0.00,
    `total`             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status`            ENUM('pending','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
    `payment_method`    ENUM('cod','upi','card','netbanking') DEFAULT 'cod',
    `payment_status`    ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    `shipping_name`     VARCHAR(100) DEFAULT NULL,
    `shipping_phone`    VARCHAR(20) DEFAULT NULL,
    `shipping_address`  TEXT DEFAULT NULL,
    `shipping_city`     VARCHAR(80) DEFAULT NULL,
    `shipping_state`    VARCHAR(80) DEFAULT NULL,
    `shipping_pincode`  VARCHAR(10) DEFAULT NULL,
    `notes`             TEXT DEFAULT NULL,
    `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: order_items
-- ----------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id`      INT UNSIGNED NOT NULL,
    `product_id`    INT UNSIGNED NOT NULL,
    `product_name`  VARCHAR(200) NOT NULL,
    `product_image` VARCHAR(255) DEFAULT NULL,
    `quantity`      INT NOT NULL DEFAULT 1,
    `price`         DECIMAL(10,2) NOT NULL,
    `total`         DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: payments
-- ----------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id`          INT UNSIGNED NOT NULL,
    `amount`            DECIMAL(12,2) NOT NULL,
    `method`            ENUM('cod','upi','card','netbanking') DEFAULT 'cod',
    `transaction_id`    VARCHAR(100) DEFAULT NULL,
    `gateway_response`  TEXT DEFAULT NULL,
    `status`            ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    `paid_at`           DATETIME DEFAULT NULL,
    `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: reviews
-- ----------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `product_id`    INT UNSIGNED NOT NULL,
    `order_id`      INT UNSIGNED DEFAULT NULL,
    `rating`        TINYINT NOT NULL DEFAULT 5 CHECK (`rating` BETWEEN 1 AND 5),
    `title`         VARCHAR(200) DEFAULT NULL,
    `comment`       TEXT DEFAULT NULL,
    `status`        ENUM('pending','approved','rejected') DEFAULT 'approved',
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  TABLE: inventory
-- ----------------------------
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id`    INT UNSIGNED NOT NULL,
    `quantity`      INT NOT NULL DEFAULT 0,
    `type`          ENUM('in','out','adjustment') DEFAULT 'in',
    `reference`     VARCHAR(100) DEFAULT NULL COMMENT 'Order number or note',
    `note`          TEXT DEFAULT NULL,
    `created_by`    INT UNSIGNED DEFAULT NULL,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  SEED DATA
-- ============================================================

-- Default Admin: admin@shopnest.com / Admin@1234
INSERT INTO `admins` (`name`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@shopnest.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- NOTE: Password hash is for 'Admin@1234' — change immediately after first login.

-- Sample Categories
INSERT INTO `categories` (`name`, `slug`, `description`, `status`, `sort_order`) VALUES
('Electronics',     'electronics',     'Gadgets, devices and tech accessories',       1, 1),
('Fashion',         'fashion',         'Clothing, shoes and accessories',             1, 2),
('Home & Kitchen',  'home-kitchen',    'Furniture, decor and kitchen essentials',     1, 3),
('Sports & Fitness','sports-fitness',  'Gym equipment, sportswear and gear',          1, 4),
('Books',           'books',           'Textbooks, fiction, non-fiction and more',    1, 5),
('Beauty',          'beauty',          'Skincare, makeup and personal care',          1, 6);

-- Sample Products
INSERT INTO `products` (`category_id`, `name`, `slug`, `short_desc`, `description`, `sku`, `price`, `sale_price`, `stock`, `brand`, `is_featured`, `status`) VALUES
(1, 'Wireless Bluetooth Headphones Pro', 'wireless-bluetooth-headphones-pro',
 'Premium noise-cancelling headphones with 40-hour battery life.',
 'Experience crystal-clear sound with our premium Wireless Bluetooth Headphones Pro. Features active noise cancellation, 40-hour battery, and ultra-comfortable memory foam ear cushions. Perfect for work, travel, and workouts.',
 'ELEC-WBH-001', 4999.00, 3499.00, 50, 'SoundMax', 1, 1),

(1, 'Smart Watch Series X', 'smart-watch-series-x',
 'Feature-packed smartwatch with health tracking and GPS.',
 'Track your fitness, receive notifications, and navigate with built-in GPS. The Smart Watch Series X offers heart rate monitoring, sleep tracking, and 7-day battery life in a sleek stainless steel design.',
 'ELEC-SW-002', 12999.00, 9999.00, 30, 'TechWear', 1, 1),

(1, 'USB-C Fast Charger 65W', 'usb-c-fast-charger-65w',
 'Universal 65W GaN charger for laptops, phones and tablets.',
 'Power all your devices with one compact charger. The 65W GaN technology delivers fast, safe charging for laptops, smartphones, and tablets simultaneously. Foldable plug, travel-ready.',
 'ELEC-CHG-003', 1999.00, 1499.00, 100, 'ChargeMax', 0, 1),

(2, 'Men''s Premium Cotton T-Shirt', 'mens-premium-cotton-tshirt',
 'Breathable 100% organic cotton tee for everyday comfort.',
 'Crafted from 100% GOTS-certified organic cotton, this premium T-shirt is soft, breathable, and built to last. Available in multiple colors. Machine washable.',
 'FASH-MT-001', 799.00, 599.00, 200, 'UrbanFit', 1, 1),

(2, 'Women''s Running Shoes', 'womens-running-shoes',
 'Lightweight running shoes with cushioned sole for daily runs.',
 'Engineered for performance, these running shoes feature a responsive cushioning system, breathable mesh upper, and anti-slip rubber outsole. Ideal for road running and gym workouts.',
 'FASH-WS-002', 3499.00, 2799.00, 75, 'StridePro', 1, 1),

(3, 'Stainless Steel Water Bottle 1L', 'stainless-steel-water-bottle-1l',
 'Double-wall insulated bottle keeps drinks cold 24h, hot 12h.',
 'Stay hydrated in style. Our double-wall vacuum-insulated stainless steel bottle keeps beverages cold for 24 hours and hot for 12 hours. BPA-free, leak-proof lid, fits most cup holders.',
 'HOME-WB-001', 899.00, 699.00, 150, 'HydroNest', 0, 1),

(4, 'Adjustable Dumbbell Set 10kg', 'adjustable-dumbbell-set-10kg',
 'Space-saving adjustable dumbbell set for home gym workouts.',
 'Build strength at home with this compact adjustable dumbbell set. Quick-change weight selection from 2kg to 10kg. Non-slip grip, durable cast iron plates with chrome handles.',
 'SPRT-DB-001', 2499.00, 1999.00, 40, 'PowerGym', 1, 1),

(5, 'AWS Cloud Practitioner Study Guide', 'aws-cloud-practitioner-study-guide',
 'Complete preparation guide for AWS Certified Cloud Practitioner exam.',
 'Master AWS fundamentals with this comprehensive study guide. Covers all domains: Cloud Concepts, Security, Technology, and Billing. Includes practice questions, exam tips, and real-world scenarios.',
 'BOOK-AWS-001', 1299.00, 999.00, 200, 'TechBooks', 0, 1),

(6, 'Vitamin C Face Serum 30ml', 'vitamin-c-face-serum-30ml',
 'Brightening serum with 20% Vitamin C for glowing skin.',
 'Transform your skin with our potent Vitamin C serum. Formulated with 20% ascorbic acid, hyaluronic acid, and Vitamin E to brighten, firm, and protect your skin. Dermatologist tested, suitable for all skin types.',
 'BEAU-VS-001', 1499.00, 1199.00, 80, 'GlowLab', 1, 1);

-- Inventory records for products
INSERT INTO `inventory` (`product_id`, `quantity`, `type`, `note`) VALUES
(1, 50,  'in', 'Initial stock'),
(2, 30,  'in', 'Initial stock'),
(3, 100, 'in', 'Initial stock'),
(4, 200, 'in', 'Initial stock'),
(5, 75,  'in', 'Initial stock'),
(6, 150, 'in', 'Initial stock'),
(7, 40,  'in', 'Initial stock'),
(8, 200, 'in', 'Initial stock'),
(9, 80,  'in', 'Initial stock');

-- Sample Customer (password: Customer@123)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `city`, `state`, `pincode`, `status`, `email_verified`) VALUES
('Rahul Sharma', 'rahul@example.com', '9876543210',
 '$2y$12$VvSaZhUPFX7.FIoL7XnuEuWgA.cAw4VhMJzuBWd4OuoKgJkwJKwuG',
 'Mumbai', 'Maharashtra', '400001', 1, 1);
