USE lootlab;
SET NAMES utf8mb4;

-- بيانات تجريبية لواجهة المستخدم والأدمن.
-- الصور مؤقتة مكتوب عليها اسم المنتج وليست صور المنتجات الحقيقية.
-- لا يحذف بياناتك الحالية، ويمكن تشغيله مرة ثانية دون تكرار بياناته.
-- حسابات التجربة:
-- demo1@lootlab.test
-- demo2@lootlab.test
-- demo3@lootlab.test
-- كلمة مرور حسابات التجربة: LootDemo123!

DROP TEMPORARY TABLE IF EXISTS loothub_demo_products;
CREATE TEMPORARY TABLE loothub_demo_products (
    category_name VARCHAR(100) NOT NULL,
    subcategory_name VARCHAR(100) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock_quantity INT NOT NULL,
    is_active TINYINT NOT NULL DEFAULT 1
);

INSERT INTO loothub_demo_products
(category_name, subcategory_name, name, description,
 price, discount_price, stock_quantity, is_active)
VALUES
-- Anime
('Anime', 'Mugs', '[DEMO] Naruto Mug',
 'مج سيراميك بتصميم ناروتو، سعة 350 مل، مناسب للاستخدام اليومي.',
 8.00, 6.50, 25, 1),

('Anime', 'Mugs', '[DEMO] One Piece Mug',
 'مج بتصميم لوفي وطاقم قبعة القش، مع طباعة ملونة.',
 9.00, NULL, 18, 1),

('Anime', 'Posters', '[DEMO] Goku Poster',
 'بوستر غوكو بقياس 50 × 70 سم، مطبوع على ورق سميك.',
 6.00, NULL, 30, 1),

('Anime', 'Posters', '[DEMO] Attack on Titan Poster',
 'بوستر هجوم العمالقة بقياس 40 × 60 سم بتصميم داكن.',
 7.50, 5.50, 12, 1),

('Anime', 'Clothing', '[DEMO] Itachi Hoodie',
 'هودي أسود بتصميم إيتاتشي، مقاس تجريبي موحد L.',
 28.00, 23.00, 10, 1),

('Anime', 'Clothing', '[DEMO] Demon Slayer T-Shirt',
 'تيشيرت قطني بتصميم تانجيرو، مقاس تجريبي موحد M.',
 15.00, NULL, 0, 1),

('Anime', 'Accessories', '[DEMO] Akatsuki Keychain',
 'ميدالية مفاتيح بتصميم سحابة الأكاتسكي.',
 3.50, NULL, 45, 1),

('Anime', 'Accessories', '[DEMO] One Piece Straw Hat Necklace',
 'قلادة بتصميم قبعة لوفي، مناسبة كهديّة لمحبي ون بيس.',
 5.00, 4.00, 20, 1),

-- Gaming
('Gaming', 'Mugs', '[DEMO] PlayStation Mug',
 'مج سيراميك بطباعة مستوحاة من أزرار بلايستيشن.',
 8.50, NULL, 16, 1),

('Gaming', 'Mugs', '[DEMO] Minecraft Mug',
 'مج بتصميم مستوحى من عالم ماينكرافت.',
 9.50, 7.50, 14, 1),

('Gaming', 'Posters', '[DEMO] God of War Poster',
 'بوستر كريتوس بقياس 50 × 70 سم بألوان داكنة.',
 8.00, NULL, 22, 1),

('Gaming', 'Posters', '[DEMO] Elden Ring Poster',
 'بوستر مستوحى من عالم إلدن رينغ بقياس 40 × 60 سم.',
 7.00, NULL, 0, 1),

('Gaming', 'Clothing', '[DEMO] Cyberpunk Hoodie',
 'هودي أسود بطباعة سايبربانك، مقاس تجريبي موحد L.',
 32.00, 27.00, 7, 1),

('Gaming', 'Clothing', '[DEMO] Minecraft T-Shirt',
 'تيشيرت قطني بطباعة مستوحاة من ماينكرافت، مقاس M.',
 14.00, NULL, 19, 1),

('Gaming', 'Accessories', '[DEMO] Controller Keychain',
 'ميدالية صغيرة على شكل يد تحكم.',
 3.00, NULL, 50, 1),

('Gaming', 'Accessories', '[DEMO] Zelda Necklace',
 'قلادة مستوحاة من عالم زيلدا.',
 6.50, 5.00, 11, 1),

-- Marvel and DC
('Marvel & DC', 'Mugs', '[DEMO] Spider-Man Mug',
 'مج سبايدرمان بطباعة حمراء وسوداء، سعة 350 مل.',
 9.00, 7.00, 24, 1),

('Marvel & DC', 'Mugs', '[DEMO] Batman Mug',
 'مج أسود بشعار باتمان، مناسب للمكتب والمنزل.',
 8.00, NULL, 21, 1),

('Marvel & DC', 'Posters', '[DEMO] Avengers Poster',
 'بوستر يجمع شخصيات أفنجرز بقياس 50 × 70 سم.',
 8.50, NULL, 17, 1),

('Marvel & DC', 'Posters', '[DEMO] The Dark Knight Poster',
 'بوستر باتمان بتصميم داكن بقياس 40 × 60 سم.',
 7.50, 6.00, 13, 1),

('Marvel & DC', 'Clothing', '[DEMO] Venom Hoodie',
 'هودي أسود بتصميم فينوم، مقاس تجريبي موحد XL.',
 30.00, NULL, 0, 1),

('Marvel & DC', 'Clothing', '[DEMO] Iron Man T-Shirt',
 'تيشيرت قطني بتصميم آيرون مان، مقاس تجريبي موحد L.',
 16.00, 12.50, 15, 1),

('Marvel & DC', 'Accessories', '[DEMO] Batman Keychain',
 'ميدالية معدنية بتصميم شعار باتمان.',
 4.00, NULL, 38, 1),

('Marvel & DC', 'Accessories', '[DEMO] Captain America Shield Pin',
 'دبوس إكسسوار بتصميم درع كابتن أمريكا.',
 3.50, NULL, 28, 1),

-- Movies and Series
('Movies & Series', 'Mugs', '[DEMO] Harry Potter Mug',
 'مج بتصميم مستوحى من عالم هاري بوتر.',
 10.00, 8.00, 20, 1),

('Movies & Series', 'Mugs', '[DEMO] Friends Mug',
 'مج بطباعة مستوحاة من مسلسل فريندز.',
 8.00, NULL, 26, 1),

('Movies & Series', 'Posters', '[DEMO] Interstellar Poster',
 'بوستر إنترستيلر بقياس 50 × 70 سم.',
 9.00, 7.50, 9, 1),

('Movies & Series', 'Posters', '[DEMO] Stranger Things Poster',
 'بوستر سترينجر ثينغز بقياس 40 × 60 سم.',
 7.00, NULL, 0, 1),

('Movies & Series', 'Clothing', '[DEMO] Hogwarts Hoodie',
 'هودي بتصميم هوغوورتس، مقاس تجريبي موحد L.',
 29.00, 24.00, 8, 1),

('Movies & Series', 'Clothing', '[DEMO] Star Wars T-Shirt',
 'تيشيرت قطني مستوحى من ستار وورز، مقاس M.',
 15.00, NULL, 18, 1),

('Movies & Series', 'Accessories', '[DEMO] Harry Potter Wand',
 'عصا ديكور مستوحاة من عالم هاري بوتر.',
 12.00, NULL, 10, 1),

('Movies & Series', 'Accessories', '[DEMO] Hidden Sample Keychain',
 'منتج غير نشط لاختبار إخفائه عن واجهة المستخدم.',
 2.50, NULL, 5, 0);


START TRANSACTION;

-- 1. التصنيفات
INSERT INTO categories (name, image)
SELECT d.name, d.image
FROM (
    SELECT 'Anime' AS name,
           'https://placehold.co/800x500/15121C/B58BFF/png?text=Anime' AS image
    UNION ALL
    SELECT 'Gaming',
           'https://placehold.co/800x500/15121C/B58BFF/png?text=Gaming'
    UNION ALL
    SELECT 'Marvel & DC',
           'https://placehold.co/800x500/15121C/B58BFF/png?text=Marvel+DC'
    UNION ALL
    SELECT 'Movies & Series',
           'https://placehold.co/800x500/15121C/B58BFF/png?text=Movies+Series'
) AS d
WHERE NOT EXISTS (
    SELECT 1 FROM categories c WHERE c.name = d.name
);

-- 2. التصنيفات الفرعية
INSERT INTO subcategories (category_id, name)
SELECT c.id, d.subcategory_name
FROM (
    SELECT DISTINCT category_name, subcategory_name
    FROM loothub_demo_products
) AS d
JOIN categories c ON c.name = d.category_name
WHERE NOT EXISTS (
    SELECT 1
    FROM subcategories s
    WHERE s.category_id = c.id
      AND s.name = d.subcategory_name
);

-- 3. المنتجات
INSERT INTO products (
    subcategorys_id, name, description, price,
    discount_price, image, stock_quantity, is_active
)
SELECT
    s.id,
    d.name,
    d.description,
    d.price,
    d.discount_price,
    CONCAT(
        'https://placehold.co/600x600/15121C/B58BFF/png?text=',
        REPLACE(REPLACE(d.name, '[DEMO] ', ''), ' ', '+')
    ),
    d.stock_quantity,
    d.is_active
FROM loothub_demo_products d
JOIN categories c ON c.name = d.category_name
JOIN subcategories s
    ON s.category_id = c.id
   AND s.name = d.subcategory_name
WHERE NOT EXISTS (
    SELECT 1
    FROM products p
    WHERE p.subcategorys_id = s.id
      AND p.name = d.name
);

-- 4. مستخدمون وهميون
-- الحساب الرابع غير نشط لاختبار واجهة إدارة المستخدمين.
INSERT INTO users (
    name, email, password, phone, role, is_active, points
)
SELECT
    d.name,
    d.email,
    '$2b$10$38OQj74F12SmTdbVL3g0ueJAV96EcE0tKLxh3wEXtPXspKyP/4MSa',
    '0000000000',
    'user',
    d.is_active,
    d.points
FROM (
    SELECT '[DEMO] أحمد' AS name,
           'demo1@lootlab.test' AS email,
           1 AS is_active, 120 AS points
    UNION ALL
    SELECT '[DEMO] سارة', 'demo2@lootlab.test', 1, 75
    UNION ALL
    SELECT '[DEMO] عمر', 'demo3@lootlab.test', 1, 30
    UNION ALL
    SELECT '[DEMO] حساب موقوف', 'demo4@lootlab.test', 0, 0
) AS d
WHERE NOT EXISTS (
    SELECT 1 FROM users u WHERE u.email = d.email
);

-- 5. تقييمات متنوعة للمنتجات التجريبية
INSERT INTO reviews (user_id, product_id, rating, comment)
SELECT
    u.id,
    p.id,
    1 + MOD(p.id + u.id, 5),
    CASE MOD(p.id + u.id, 5)
        WHEN 0 THEN 'تقييم تجريبي: المنتج لم يناسب توقعاتي.'
        WHEN 1 THEN 'تقييم تجريبي: مقبول ويحتاج إلى تحسين.'
        WHEN 2 THEN 'تقييم تجريبي: جيد مقابل السعر.'
        WHEN 3 THEN 'تقييم تجريبي: تصميم جميل وجودة جيدة.'
        ELSE 'تقييم تجريبي: ممتاز ومناسب للهدايا.'
    END
FROM users u
CROSS JOIN products p
JOIN loothub_demo_products d ON d.name = p.name
JOIN subcategories s
    ON s.id = p.subcategorys_id
   AND s.name = d.subcategory_name
JOIN categories c
    ON c.id = s.category_id
   AND c.name = d.category_name
WHERE u.email IN (
    'demo1@lootlab.test',
    'demo2@lootlab.test',
    'demo3@lootlab.test'
)
AND p.is_active = 1
AND NOT EXISTS (
    SELECT 1
    FROM reviews r
    WHERE r.user_id = u.id
      AND r.product_id = p.id
);

-- 6. أكواد خصم تجريبية
INSERT INTO promo_codes (
    code, discount_type, discount_value, expires_at
)
SELECT d.code, d.discount_type, d.discount_value, d.expires_at
FROM (
    SELECT 'DEMO10' AS code,
           'percentage' AS discount_type,
           10.00 AS discount_value,
           DATE_ADD(NOW(), INTERVAL 30 DAY) AS expires_at
    UNION ALL
    SELECT 'DEMO5JD', 'fixed', 5.00,
           DATE_ADD(NOW(), INTERVAL 30 DAY)
    UNION ALL
    SELECT 'DEMOEXPIRED', 'percentage', 15.00,
           DATE_SUB(NOW(), INTERVAL 1 DAY)
) AS d
WHERE NOT EXISTS (
    SELECT 1 FROM promo_codes pc WHERE pc.code = d.code
);

-- 7. سلال حسابات التجربة
INSERT INTO carts (user_id)
SELECT u.id
FROM users u
WHERE u.email IN (
    'demo1@lootlab.test',
    'demo2@lootlab.test',
    'demo3@lootlab.test'
)
AND NOT EXISTS (
    SELECT 1 FROM carts c WHERE c.user_id = u.id
);

INSERT INTO cart_items (cart_id, product_id, quantity)
SELECT c.id, p.id, d.quantity
FROM carts c
JOIN users u ON u.id = c.user_id
JOIN (
    SELECT '[DEMO] Naruto Mug' AS name, 2 AS quantity
    UNION ALL
    SELECT '[DEMO] Goku Poster', 1
) AS d ON 1 = 1
JOIN products p ON p.name = d.name
WHERE u.email IN (
    'demo1@lootlab.test',
    'demo2@lootlab.test',
    'demo3@lootlab.test'
)
AND NOT EXISTS (
    SELECT 1
    FROM cart_items ci
    WHERE ci.cart_id = c.id
      AND ci.product_id = p.id
      
);

-- 8. مفضلة حسابات التجربة
INSERT INTO favorites (user_id, session_id)
SELECT u.id, NULL
FROM users u
WHERE u.email IN (
    'demo1@lootlab.test',
    'demo2@lootlab.test',
    'demo3@lootlab.test'
)
AND NOT EXISTS (
    SELECT 1 FROM favorites f WHERE f.user_id = u.id
);

INSERT INTO favorite_items (fav_id, product_id)
SELECT f.fav_id, p.id
FROM favorites f
JOIN users u ON u.id = f.user_id
JOIN products p ON p.name IN (
    '[DEMO] Itachi Hoodie',
    '[DEMO] Spider-Man Mug',
    '[DEMO] Harry Potter Wand',
    '[DEMO] Elden Ring Poster'
)
WHERE u.email IN (
    'demo1@lootlab.test',
    'demo2@lootlab.test',
    'demo3@lootlab.test'
)
AND NOT EXISTS (
    SELECT 1
    FROM favorite_items fi
    WHERE fi.fav_id = f.fav_id
      AND fi.product_id = p.id
);

COMMIT;


-- 9. طلبات وهمية بحالات مختلفة
DROP TEMPORARY TABLE IF EXISTS loothub_demo_orders;
CREATE TEMPORARY TABLE loothub_demo_orders (
    marker VARCHAR(30) NOT NULL,
    email VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    days_ago INT NOT NULL,
    product_a VARCHAR(150) NOT NULL,
    quantity_a INT NOT NULL,
    product_b VARCHAR(150) NOT NULL,
    quantity_b INT NOT NULL
);

INSERT INTO loothub_demo_orders VALUES
('DEMO-ORDER-001', 'demo1@lootlab.test', 'completed', 20,
 '[DEMO] Naruto Mug', 2, '[DEMO] Goku Poster', 1),

('DEMO-ORDER-002', 'demo1@lootlab.test', 'pending', 1,
 '[DEMO] Itachi Hoodie', 1, '[DEMO] Akatsuki Keychain', 2),

('DEMO-ORDER-003', 'demo2@lootlab.test', 'processing', 3,
 '[DEMO] Spider-Man Mug', 1, '[DEMO] Batman Keychain', 2),

('DEMO-ORDER-004', 'demo2@lootlab.test', 'shipped', 5,
 '[DEMO] Hogwarts Hoodie', 1, '[DEMO] Harry Potter Wand', 1),

('DEMO-ORDER-005', 'demo3@lootlab.test', 'cancelled', 8,
 '[DEMO] Cyberpunk Hoodie', 1, '[DEMO] Controller Keychain', 1),

('DEMO-ORDER-006', 'demo3@lootlab.test', 'completed', 12,
 '[DEMO] Friends Mug', 2, '[DEMO] Interstellar Poster', 1);


START TRANSACTION;

INSERT INTO orders (
    user_id, promo_code_id, promo_discount,
    total_amount, status, shipping_address, phone,
    created_at, updated_at
)
SELECT
    u.id,
    NULL,
    0,
    COALESCE(pa.discount_price, pa.price) * d.quantity_a
        + COALESCE(pb.discount_price, pb.price) * d.quantity_b,
    d.status,
    CONCAT(d.marker, ' | عنوان تجريبي فقط'),
    '0000000000',
    DATE_SUB(NOW(), INTERVAL d.days_ago DAY),
    DATE_SUB(NOW(), INTERVAL d.days_ago DAY)
FROM loothub_demo_orders d
JOIN users u ON u.email = d.email
JOIN products pa ON pa.name = d.product_a
JOIN products pb ON pb.name = d.product_b
WHERE NOT EXISTS (
    SELECT 1
    FROM orders o
    WHERE o.user_id = u.id
      AND o.shipping_address =
          CONCAT(d.marker, ' | عنوان تجريبي فقط')
);

INSERT INTO order_items (
    order_id, product_id, quantity, unit_price,
    created_at, updated_at
)
SELECT
    o.id,
    p.id,
    d.quantity_a,
    COALESCE(p.discount_price, p.price),
    o.created_at,
    o.updated_at
FROM loothub_demo_orders d
JOIN users u ON u.email = d.email
JOIN orders o
    ON o.user_id = u.id
   AND o.shipping_address =
       CONCAT(d.marker, ' | عنوان تجريبي فقط')
JOIN products p ON p.name = d.product_a
WHERE NOT EXISTS (
    SELECT 1
    FROM order_items oi
    WHERE oi.order_id = o.id
      AND oi.product_id = p.id
);

INSERT INTO order_items (
    order_id, product_id, quantity, unit_price,
    created_at, updated_at
)
SELECT
    o.id,
    p.id,
    d.quantity_b,
    COALESCE(p.discount_price, p.price),
    o.created_at,
    o.updated_at
FROM loothub_demo_orders d
JOIN users u ON u.email = d.email
JOIN orders o
    ON o.user_id = u.id
   AND o.shipping_address =
       CONCAT(d.marker, ' | عنوان تجريبي فقط')
JOIN products p ON p.name = d.product_b
WHERE NOT EXISTS (
    SELECT 1
    FROM order_items oi
    WHERE oi.order_id = o.id
      AND oi.product_id = p.id
);

COMMIT;

DROP TEMPORARY TABLE IF EXISTS loothub_demo_orders;
DROP TEMPORARY TABLE IF EXISTS loothub_demo_products;

-- ملخص البيانات التجريبية
SELECT
    p.id,
    c.name AS category,
    s.name AS subcategory,
    p.name,
    p.price,
    p.discount_price,
    p.stock_quantity,
    p.is_active
FROM products p
JOIN subcategories s ON s.id = p.subcategorys_id
JOIN categories c ON c.id = s.category_id
WHERE p.name LIKE '[DEMO] %'
ORDER BY c.name, s.name, p.id;