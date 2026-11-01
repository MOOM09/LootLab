-- LootLab / optional LOCAL TEST DATA for the homepage Top 10 marquee.
-- Execute ONLY in your local/development LootLab database.
-- Does not create orders or fake payments, modify inventory, or reset anything.
-- The application always ranks genuine processing/shipped/completed order quantities first.
-- demo_units are simulated leaderboard scores used ONLY as tie-breakers/fallback.
-- Every selected product ID belongs to an actual existing active product.
-- MySQL 8+ / MariaDB 10.2+ required for ROW_NUMBER().

CREATE TABLE IF NOT EXISTS product_demo_popularity (
    product_id INT(10) UNSIGNED NOT NULL,
    demo_units INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id),
    CONSTRAINT fk_demo_popularity_product
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Select up to 10 different active products, spreading picks across categories
-- before taking the next product from a category; insert sample units 160..61.
-- INSERT IGNORE preserves existing demo choices when the script is run again.
INSERT IGNORE INTO product_demo_popularity (product_id, demo_units)
SELECT picked.id,
       CAST(160 - 11 * (ROW_NUMBER() OVER (ORDER BY picked.category_rank ASC, picked.id DESC) - 1) AS UNSIGNED) AS demo_units
FROM (
    SELECT p.id,
           ROW_NUMBER() OVER (PARTITION BY s.category_id ORDER BY p.id DESC) AS category_rank
    FROM products p
    INNER JOIN subcategories s ON s.id = p.subcategorys_id
    WHERE p.is_active = 1
) AS picked
ORDER BY picked.category_rank ASC, picked.id DESC
LIMIT 10;

-- Inspect the demo leaderboard and products. These are NOT real purchases.
SELECT d.product_id, p.name, d.demo_units
FROM product_demo_popularity d
JOIN products p ON p.id = d.product_id
ORDER BY d.demo_units DESC, d.product_id DESC
LIMIT 10;

-- Optional cleanup when the demo is no longer needed (run separately):
-- DROP TABLE product_demo_popularity;
