-- LootLab: five user-supplied 3D collectibles. Existing 114 products remain untouched.
-- Set @base to the public URL from your APP_URL if your installation differs.
USE lootlab;
SET @base = '/projects/LootLab/public';

-- Add the column only if it does not exist (works on MySQL and MariaDB).
SELECT COUNT(*) INTO @has_model_path
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'model_path';
SET @alter_sql = IF(@has_model_path = 0,
    'ALTER TABLE products ADD COLUMN model_path VARCHAR(500) NULL AFTER image',
    'SELECT ''model_path already exists'' AS info');
PREPARE alter_stmt FROM @alter_sql;
EXECUTE alter_stmt;
DEALLOCATE PREPARE alter_stmt;

INSERT INTO product_types (name)
SELECT '3D Models' WHERE NOT EXISTS (SELECT 1 FROM product_types WHERE name = '3D Models');

-- Insert each model once. Prices/stock are demo values and editable in Admin.
INSERT INTO products
    (subcategorys_id, product_type_id, name, description, price, discount_price, image, model_path, stock_quantity, is_active)
SELECT s.id, pt.id, x.product_name, x.description, x.price, NULL,
       CONCAT(@base, '/images/products/3d/', x.preview),
       CONCAT(@base, '/models/collectibles/', x.model_file),
       10, 1
FROM (
    SELECT 'Demon Slayer' AS sub_name, 'Tanjiro Kamado 3D Model' AS product_name,
           'Interactive 3D collectible preview of Tanjiro Kamado.' AS description,
           29.00 AS price, 'tanjiro-kamado.png' AS preview, 'tanjiro-kamado.glb' AS model_file
    UNION ALL SELECT 'Demon Slayer', 'Tanjiro Katana 3D Model',
        'Interactive 3D preview of Tanjiro Kamado''s katana.', 19.00, 'tanjiro-katana.png', 'tanjiro-katana.glb'
    UNION ALL SELECT 'Naruto', 'Itachi Uchiha 3D Model',
        'Interactive 3D collectible preview of Itachi Uchiha.', 29.00, 'itachi-uchiha.png', 'itachi-uchiha.glb'
    UNION ALL SELECT 'Naruto', 'Pain 3D Model',
        'Interactive 3D collectible preview of Pain.', 29.00, 'pain.png', 'pain.glb'
    UNION ALL SELECT 'Dragon Ball', 'Goku 3D Model',
        'Interactive 3D collectible preview of Goku.', 29.00, 'goku.png', 'goku.glb'
) AS x
JOIN subcategories s ON s.name = x.sub_name
JOIN categories c ON c.id = s.category_id AND c.name = 'Anime'
JOIN product_types pt ON pt.name = '3D Models'
WHERE NOT EXISTS (
    SELECT 1 FROM products p WHERE p.subcategorys_id = s.id AND p.name = x.product_name
);

-- Repair model paths if the query is run again; don't touch prices or stock.
UPDATE products p
JOIN subcategories s ON s.id = p.subcategorys_id
JOIN categories c ON c.id = s.category_id AND c.name = 'Anime'
JOIN product_types pt ON pt.name = '3D Models'
SET p.product_type_id = pt.id,
    p.image = CONCAT(@base, '/images/products/3d/',
        CASE p.name
            WHEN 'Tanjiro Kamado 3D Model' THEN 'tanjiro-kamado.png'
            WHEN 'Tanjiro Katana 3D Model' THEN 'tanjiro-katana.png'
            WHEN 'Itachi Uchiha 3D Model' THEN 'itachi-uchiha.png'
            WHEN 'Pain 3D Model' THEN 'pain.png'
            WHEN 'Goku 3D Model' THEN 'goku.png'
        END),
    p.model_path = CONCAT(@base, '/models/collectibles/',
        CASE p.name
            WHEN 'Tanjiro Kamado 3D Model' THEN 'tanjiro-kamado.glb'
            WHEN 'Tanjiro Katana 3D Model' THEN 'tanjiro-katana.glb'
            WHEN 'Itachi Uchiha 3D Model' THEN 'itachi-uchiha.glb'
            WHEN 'Pain 3D Model' THEN 'pain.glb'
            WHEN 'Goku 3D Model' THEN 'goku.glb'
        END),
    p.is_active = 1
WHERE p.name IN ('Tanjiro Kamado 3D Model', 'Tanjiro Katana 3D Model',
    'Itachi Uchiha 3D Model', 'Pain 3D Model', 'Goku 3D Model')
    AND ((s.name = 'Demon Slayer' AND p.name LIKE 'Tanjiro%')
         OR (s.name = 'Naruto' AND p.name IN ('Itachi Uchiha 3D Model','Pain 3D Model'))
         OR (s.name = 'Dragon Ball' AND p.name = 'Goku 3D Model'));

SELECT COUNT(*) AS total_products,
       SUM(model_path IS NOT NULL AND model_path <> '') AS three_d_products
FROM products;
SELECT p.id, p.name, p.image, p.model_path
FROM products p WHERE p.model_path IS NOT NULL ORDER BY p.id;
