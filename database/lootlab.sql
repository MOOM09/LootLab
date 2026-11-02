-- LootLab: readable schema (structure only).
-- Run in a new/empty lootlab database; existing tables are not deleted.
-- Includes the 13 CHECK constraints verified in your screenshot.
CREATE DATABASE IF NOT EXISTS lootlab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lootlab;
SET NAMES utf8mb4;

-- 1. USERS
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `session_version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens: one current token per user; store only its SHA-256 hash.
CREATE TABLE `password_reset_tokens` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),

  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_password_reset_token` (`token_hash`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset request limits: hashed email/IP keys and UTC window expiry.
CREATE TABLE `password_reset_rate_limits` (
  `rate_key` char(64) NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `expires_at` datetime NOT NULL,

  PRIMARY KEY (`rate_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CATEGORIES
CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SUBCATEGORIES
CREATE TABLE `subcategories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_subcategory` (`category_id`,`name`),
  CONSTRAINT `fk_subcategories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PRODUCTS
CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subcategorys_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  KEY `fk_products_subcategory` (`subcategorys_id`),
  CONSTRAINT `fk_products_subcategory` FOREIGN KEY (`subcategorys_id`) REFERENCES `subcategories` (`id`),
  CONSTRAINT chk_product_price CHECK (price >= 0),
  CONSTRAINT chk_product_stock CHECK (stock_quantity >= 0),
  CONSTRAINT chk_product_discount CHECK (discount_price IS NULL OR (discount_price >= 0 AND discount_price < price))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PROMO_CODES
CREATE TABLE `promo_codes` (
  `promo_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),

  PRIMARY KEY (`promo_id`),
  UNIQUE KEY `code` (`code`),
  CONSTRAINT chk_promo_discount CHECK (discount_value > 0),
  CONSTRAINT chk_promo_percentage CHECK (discount_type <> 'percentage' OR discount_value <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. DESIGN_TEMPLATES
CREATE TABLE `design_templates` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `image` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. CUSTOM_DESIGNS
CREATE TABLE `custom_designs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `face_image` varchar(500) NOT NULL,
  `result_image` varchar(500) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  KEY `fk_custom_designs_user` (`user_id`),
  KEY `fk_custom_designs_template` (`template_id`),
  CONSTRAINT `fk_custom_designs_template` FOREIGN KEY (`template_id`) REFERENCES `design_templates` (`id`),
  CONSTRAINT `fk_custom_designs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. CARTS
CREATE TABLE `carts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `promo_code_id` int(10) UNSIGNED DEFAULT NULL,
  `promo_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_carts_promo_code` (`promo_code_id`),
  CONSTRAINT `fk_carts_promo_code` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`promo_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT chk_cart_promo_discount CHECK (promo_discount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. CART_ITEMS
CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `custom_design_id` int(10) UNSIGNED DEFAULT NULL,
  `design_key` int(10) UNSIGNED GENERATED ALWAYS AS (ifnull(`custom_design_id`,0)) STORED,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_product_design` (`cart_id`,`product_id`,`design_key`),
  KEY `fk_cart_items_product` (`product_id`),
  KEY `fk_cart_items_design` (`custom_design_id`),
  CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_design` FOREIGN KEY (`custom_design_id`) REFERENCES `custom_designs` (`id`),
  CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT chk_cart_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. ORDERS
CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `promo_code_id` int(10) UNSIGNED DEFAULT NULL,
  `promo_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `phone` varchar(25) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  KEY `fk_orders_user` (`user_id`),
  KEY `fk_orders_promo_code` (`promo_code_id`),
  CONSTRAINT `fk_orders_promo_code` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`promo_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT chk_order_total CHECK (total_amount >= 0),
  CONSTRAINT chk_order_promo_discount CHECK (promo_discount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. ORDER_ITEMS
CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `custom_design_id` int(10) UNSIGNED DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`),
  KEY `fk_order_items_design` (`custom_design_id`),
  CONSTRAINT `fk_order_items_design` FOREIGN KEY (`custom_design_id`) REFERENCES `custom_designs` (`id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT chk_order_item_quantity CHECK (quantity > 0),
  CONSTRAINT chk_order_item_price CHECK (unit_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. REVIEWS
CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_product_review` (`user_id`,`product_id`),
  KEY `fk_reviews_product` (`product_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. FAVORITES
CREATE TABLE `favorites` (
  `fav_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`fav_id`),
  UNIQUE KEY `uq_favorites_user` (`user_id`),
  UNIQUE KEY `uq_favorites_session` (`session_id`),
  CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT chk_favorites_owner CHECK ((user_id IS NOT NULL AND session_id IS NULL) OR (user_id IS NULL AND session_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. FAVORITE_ITEMS
CREATE TABLE `favorite_items` (
  `favorite_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fav_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`favorite_item_id`),
  UNIQUE KEY `uq_favorite_product` (`fav_id`,`product_id`),
  KEY `fk_favorite_items_product` (`product_id`),
  CONSTRAINT `fk_favorite_items_favorite` FOREIGN KEY (`fav_id`) REFERENCES `favorites` (`fav_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_favorite_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. AI_CONVERSATIONS
CREATE TABLE `ai_conversations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT 'New conversation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  KEY `idx_conversations_user_date` (`user_id`,`updated_at`),
  CONSTRAINT `fk_ai_conversations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. AI_MESSAGES
CREATE TABLE `ai_messages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` mediumtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),

  PRIMARY KEY (`id`),
  KEY `idx_messages_conversation_id` (`conversation_id`,`id`),
  CONSTRAINT `fk_ai_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Local admin account. Re-running this resets this account's password and role.
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`)
VALUES (
    'LootHub Admin',
    'admin@loothub.com',
    '$2y$10$Oh6RpZxF6Kbl.JYtvBVfC.6WhiwmzEer9FH9M48OfnMMDK7HCJXiS',
    'admin',
    1
)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `role` = 'admin',
    `is_active` = 1,
    `session_version` = `session_version` + 1;

-- BEGIN SYNTHETIC DEMO ACTIVITY
-- Synthetic demo activity only; no real customers or payments.
-- Run against an existing LootLab database. Safe to re-run sequentially.
-- Customer password: LootDemo123!
USE lootlab;
SET NAMES utf8mb4;
START TRANSACTION;
INSERT INTO categories (name) VALUES ('Fan Collectibles') ON DUPLICATE KEY UPDATE name=VALUES(name);
SET @demo_category=(SELECT id FROM categories WHERE name='Fan Collectibles');
INSERT INTO subcategories (category_id,name) VALUES (@demo_category,'Featured Collection') ON DUPLICATE KEY UPDATE name=VALUES(name);
SET @demo_subcategory=(SELECT id FROM subcategories WHERE category_id=@demo_category AND name='Featured Collection');
INSERT INTO products (subcategorys_id,name,description,price,image,stock_quantity) SELECT @demo_subcategory,'Akatsuki Hoodie','Collectible for fans of anime and pop culture.',25,'/projects/LootLab/public/images/products/akatsukiHoodie.png',40 WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Akatsuki Hoodie');
INSERT INTO products (subcategorys_id,name,description,price,image,stock_quantity) SELECT @demo_subcategory,'Tanjiro Wall Art','Collectible for fans of anime and pop culture.',18,'/projects/LootLab/public/images/products/tanjiroWallArt.png',40 WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Tanjiro Wall Art');
INSERT INTO products (subcategorys_id,name,description,price,image,stock_quantity) SELECT @demo_subcategory,'Goku Super Saiyan Figure','Collectible for fans of anime and pop culture.',35,'/projects/LootLab/public/images/products/gokuSuperSaiyanFigure.png',40 WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Goku Super Saiyan Figure');
INSERT INTO products (subcategorys_id,name,description,price,image,stock_quantity) SELECT @demo_subcategory,'The Boys Logo Hoodie','Collectible for fans of anime and pop culture.',25,'/projects/LootLab/public/images/products/theBoysLogoHoodie.png',40 WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='The Boys Logo Hoodie');
INSERT INTO products (subcategorys_id,name,description,price,image,stock_quantity) SELECT @demo_subcategory,'Darth Vader Poster','Collectible for fans of anime and pop culture.',15,'/projects/LootLab/public/images/products/starWarsDarthVaderPoster.png',40 WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Darth Vader Poster');
INSERT INTO products (subcategorys_id,name,description,price,image,stock_quantity) SELECT @demo_subcategory,'Straw Hat Pirates Mug','Collectible for fans of anime and pop culture.',12,'/projects/LootLab/public/images/products/strawHatPiratesMug.png',40 WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Straw Hat Pirates Mug');
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Lina Haddad','demo.customer1@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 430 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Omar Khalil','demo.customer2@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 428 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Sara Nasser','demo.customer3@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 426 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Ahmad Saleh','demo.customer4@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 424 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Noor Hamdan','demo.customer5@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 422 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Yousef Ali','demo.customer6@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 420 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Maya Farah','demo.customer7@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 418 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Kareem Awad','demo.customer8@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 416 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Rana Saad','demo.customer9@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 414 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Zaid Mansour','demo.customer10@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 412 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Dina Hassan','demo.customer11@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 410 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Fadi Omar','demo.customer12@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 408 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Hala Taha','demo.customer13@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 406 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Sami Yasin','demo.customer14@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 404 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Rami Issa','demo.customer15@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 402 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Reem Najjar','demo.customer16@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 400 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Tala Zaki','demo.customer17@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 398 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Adam Khaled','demo.customer18@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 396 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Salma Adel','demo.customer19@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 394 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Laith Amin','demo.customer20@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 392 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Jana Samir','demo.customer21@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 390 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Nour Fares','demo.customer22@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 388 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Basil Hani','demo.customer23@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 386 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
INSERT INTO users (name,email,password,role,is_active,created_at) VALUES ('Leen Osama','demo.customer24@loothub.test','$2y$10$ZTuH3RHstpJHc1Zvh7FYn.D5vqxx22QyQoOlH25XbX4P1YPpjMH9C','user',1,DATE_SUB(NOW(),INTERVAL 384 DAY)) ON DUPLICATE KEY UPDATE email=VALUES(email);
DROP TEMPORARY TABLE IF EXISTS loothub_activity_products;
CREATE TEMPORARY TABLE loothub_activity_products (slot INT PRIMARY KEY,product_id INT UNSIGNED,price DECIMAL(10,2));
INSERT INTO loothub_activity_products SELECT 1,id,COALESCE(discount_price,price) FROM products WHERE name='Akatsuki Hoodie' ORDER BY id LIMIT 1;
INSERT INTO loothub_activity_products SELECT 2,id,COALESCE(discount_price,price) FROM products WHERE name='Tanjiro Wall Art' ORDER BY id LIMIT 1;
INSERT INTO loothub_activity_products SELECT 3,id,COALESCE(discount_price,price) FROM products WHERE name='Goku Super Saiyan Figure' ORDER BY id LIMIT 1;
INSERT INTO loothub_activity_products SELECT 4,id,COALESCE(discount_price,price) FROM products WHERE name='The Boys Logo Hoodie' ORDER BY id LIMIT 1;
INSERT INTO loothub_activity_products SELECT 5,id,COALESCE(discount_price,price) FROM products WHERE name='Darth Vader Poster' ORDER BY id LIMIT 1;
INSERT INTO loothub_activity_products SELECT 6,id,COALESCE(discount_price,price) FROM products WHERE name='Straw Hat Pirates Mug' ORDER BY id LIMIT 1;
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer1@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 0 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0001 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'processing','DEMO-LH-ACTIVITY-0001 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer2@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 0 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0002 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'shipped','DEMO-LH-ACTIVITY-0002 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer3@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 0 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0003 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0003 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer4@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 1 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0004 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'pending','DEMO-LH-ACTIVITY-0004 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer5@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 1 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0005 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'processing','DEMO-LH-ACTIVITY-0005 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer6@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 1 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0006 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'shipped','DEMO-LH-ACTIVITY-0006 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer7@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 2 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0007 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0007 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer8@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 2 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0008 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'pending','DEMO-LH-ACTIVITY-0008 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer9@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 2 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0009 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'processing','DEMO-LH-ACTIVITY-0009 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer10@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 3 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0010 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'shipped','DEMO-LH-ACTIVITY-0010 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer11@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 3 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0011 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0011 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer12@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 3 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0012 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'pending','DEMO-LH-ACTIVITY-0012 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer13@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 4 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0013 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0013 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer14@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 4 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0014 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0014 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer15@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 4 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0015 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0015 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer16@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 5 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0016 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0016 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer17@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 5 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0017 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0017 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer18@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 5 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0018 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0018 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer19@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 6 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0019 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'cancelled','DEMO-LH-ACTIVITY-0019 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer20@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 6 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0020 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0020 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer21@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 6 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0021 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0021 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer22@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 7 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0022 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0022 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer23@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 7 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0023 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0023 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer24@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 7 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0024 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0024 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer1@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 8 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0025 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0025 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer2@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 8 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0026 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0026 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer3@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 8 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0027 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0027 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer4@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 9 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0028 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0028 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer5@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 9 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0029 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0029 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer6@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 9 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0030 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0030 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer7@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 10 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0031 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0031 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer8@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 10 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0032 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0032 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer9@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 10 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0033 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0033 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer10@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 11 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0034 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0034 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer11@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 11 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0035 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0035 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer12@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 11 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0036 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0036 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer13@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 12 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0037 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0037 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer14@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 12 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0038 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'cancelled','DEMO-LH-ACTIVITY-0038 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer15@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 12 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0039 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0039 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer16@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 13 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0040 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0040 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer17@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 13 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0041 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0041 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer18@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 13 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0042 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0042 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer19@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 14 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0043 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0043 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer20@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 17 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0044 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0044 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer21@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 20 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0045 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0045 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer22@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 23 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0046 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0046 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer23@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 26 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0047 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0047 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer24@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 29 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0048 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0048 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer1@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 32 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0049 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0049 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer2@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 35 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0050 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0050 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer3@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 38 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0051 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0051 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer4@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 41 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0052 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0052 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer5@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 44 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0053 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0053 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer6@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 47 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0054 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0054 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer7@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 50 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0055 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0055 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer8@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 53 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0056 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0056 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer9@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 56 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0057 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'cancelled','DEMO-LH-ACTIVITY-0057 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer10@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 59 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0058 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0058 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer11@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 62 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0059 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0059 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer12@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 65 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0060 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0060 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer13@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 68 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0061 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0061 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer14@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 71 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0062 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0062 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer15@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 74 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0063 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0063 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer16@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 77 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0064 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0064 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer17@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 80 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0065 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0065 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer18@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 83 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0066 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0066 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer19@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 86 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0067 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0067 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer20@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 89 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0068 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0068 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer21@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 92 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0069 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0069 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer22@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 95 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0070 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0070 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer23@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 98 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0071 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0071 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer24@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 101 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0072 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0072 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer1@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 104 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0073 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0073 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer2@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 107 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0074 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0074 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer3@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 110 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0075 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0075 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer4@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 113 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0076 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'cancelled','DEMO-LH-ACTIVITY-0076 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer5@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 116 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0077 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0077 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer6@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 119 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0078 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0078 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer7@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 122 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0079 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0079 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer8@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 125 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0080 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0080 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer9@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 128 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0081 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0081 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer10@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 131 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0082 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0082 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer11@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 134 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0083 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0083 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer12@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 137 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0084 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0084 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer13@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 140 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0085 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0085 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer14@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 143 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0086 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0086 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer15@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 146 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0087 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0087 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer16@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 149 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0088 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0088 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer17@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 152 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0089 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0089 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer18@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 155 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0090 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0090 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer19@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 158 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0091 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0091 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer20@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 161 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0092 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0092 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer21@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 164 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0093 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0093 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer22@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 167 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0094 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0094 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer23@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 170 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0095 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'cancelled','DEMO-LH-ACTIVITY-0095 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer24@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 173 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0096 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0096 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer1@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 176 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0097 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0097 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer2@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 179 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0098 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0098 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer3@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 182 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0099 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0099 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer4@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 185 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0100 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0100 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer5@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 188 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0101 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0101 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer6@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 191 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0102 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0102 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer7@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 194 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0103 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0103 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer8@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 197 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0104 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0104 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer9@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 200 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0105 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0105 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer10@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 203 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0106 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0106 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer11@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 206 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0107 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0107 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer12@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 209 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0108 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0108 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer13@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 212 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0109 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0109 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer14@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 215 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0110 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0110 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer15@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 218 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0111 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0111 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer16@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 221 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0112 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0112 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer17@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 224 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0113 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0113 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer18@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 227 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0114 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'cancelled','DEMO-LH-ACTIVITY-0114 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer19@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 230 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0115 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0115 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer20@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 233 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0116 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0116 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer21@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 236 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0117 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0117 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer22@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 239 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0118 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0118 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer23@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 242 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0119 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0119 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer24@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 245 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0120 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0120 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer1@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 248 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0121 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0121 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer2@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 251 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0122 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0122 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer3@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 254 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0123 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0123 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer4@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 257 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0124 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0124 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer5@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 260 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0125 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0125 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer6@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 263 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0126 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0126 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer7@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 266 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0127 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0127 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer8@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 269 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0128 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0128 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer9@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 272 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0129 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0129 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer10@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 275 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0130 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0130 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer11@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 278 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0131 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0131 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer12@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 281 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0132 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0132 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer13@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 284 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0133 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'cancelled','DEMO-LH-ACTIVITY-0133 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer14@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 287 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0134 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0134 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer15@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 290 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0135 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0135 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer16@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 293 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0136 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0136 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer17@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 296 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0137 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0137 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer18@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 299 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0138 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0138 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer19@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 302 DAY),INTERVAL 16 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0139 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0139 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer20@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 305 DAY),INTERVAL 17 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0140 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0140 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer21@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 308 DAY),INTERVAL 18 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0141 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0141 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer22@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 311 DAY),INTERVAL 19 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0142 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0142 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer23@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 314 DAY),INTERVAL 20 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0143 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0143 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer24@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 317 DAY),INTERVAL 9 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0144 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0144 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer1@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 320 DAY),INTERVAL 10 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0145 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=2)+(SELECT price FROM loothub_activity_products WHERE slot=4),'completed','DEMO-LH-ACTIVITY-0145 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer2@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 323 DAY),INTERVAL 11 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0146 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=3)+(SELECT price FROM loothub_activity_products WHERE slot=5),'completed','DEMO-LH-ACTIVITY-0146 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer3@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 326 DAY),INTERVAL 12 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0147 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=4)+(SELECT price FROM loothub_activity_products WHERE slot=6),'completed','DEMO-LH-ACTIVITY-0147 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=4 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,5,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=4 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer4@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 329 DAY),INTERVAL 13 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0148 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=5)+(SELECT price FROM loothub_activity_products WHERE slot=1),'completed','DEMO-LH-ACTIVITY-0148 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=5 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer5@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 332 DAY),INTERVAL 14 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0149 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*1 FROM loothub_activity_products WHERE slot=6)+(SELECT price FROM loothub_activity_products WHERE slot=2),'completed','DEMO-LH-ACTIVITY-0149 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=6 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=2 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
SET @demo_user=(SELECT id FROM users WHERE email='demo.customer6@loothub.test');
SET @demo_date=LEAST(NOW(),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 335 DAY),INTERVAL 15 HOUR));
SET @demo_order=(SELECT id FROM orders WHERE user_id=@demo_user AND shipping_address='DEMO-LH-ACTIVITY-0150 | Amman, Jordan - synthetic demo address' LIMIT 1);
INSERT INTO orders (user_id,total_amount,status,shipping_address,phone,created_at,updated_at) SELECT @demo_user,(SELECT price*2 FROM loothub_activity_products WHERE slot=1)+(SELECT price FROM loothub_activity_products WHERE slot=3),'completed','DEMO-LH-ACTIVITY-0150 | Amman, Jordan - synthetic demo address','+962000000000',@demo_date,@demo_date WHERE @demo_order IS NULL;
SET @demo_order=COALESCE(@demo_order,LAST_INSERT_ID());
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,2,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=1 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO order_items (order_id,product_id,quantity,unit_price,created_at,updated_at) SELECT @demo_order,p.product_id,1,p.price,@demo_date,@demo_date FROM loothub_activity_products p WHERE p.slot=3 AND NOT EXISTS (SELECT 1 FROM order_items WHERE order_id=@demo_order AND product_id=p.product_id);
INSERT INTO reviews (user_id,product_id,rating,comment,created_at,updated_at) SELECT @demo_user,product_id,4,'Great detail and quality. Looks lovely on my shelf.',@demo_date,@demo_date FROM loothub_activity_products p WHERE slot=1 AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=@demo_user AND product_id=p.product_id);
DROP TEMPORARY TABLE loothub_activity_products;
COMMIT;
