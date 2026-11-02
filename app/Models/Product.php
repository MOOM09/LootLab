
<?php

class Product
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
            return;
        }

        global $config;

        if (!isset($config['database'])) {
            throw new RuntimeException('Database configuration is not available.');
        }

        $database = new Database($config['database']);
        $this->db = $database->getConnection();
    }

    public function getAll(): array
    {
        $statement = $this->db->query(
            "SELECT
                p.id,
                (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id=p.id) AS average_rating,
                (SELECT COUNT(*) FROM reviews r WHERE r.product_id=p.id) AS review_count,
                p.subcategorys_id,
                p.name,
                p.description,
                p.price,
                p.discount_price,
                p.image,
                p.model_path,
                p.product_type_id,
                pt.name AS product_type_name,
                p.stock_quantity,
                p.is_active,
                p.created_at,
                p.updated_at,
                s.name AS subcategorys_name,
                c.id AS category_id,
                c.name AS category_name
             FROM products p
             INNER JOIN subcategories s ON s.id = p.subcategorys_id
             INNER JOIN categories c ON c.id = s.category_id
             LEFT JOIN product_types pt ON pt.id = p.product_type_id
             WHERE p.is_active = 1
               AND p.name NOT LIKE 'LOOTE Custom %'
             ORDER BY p.created_at DESC, p.id DESC"
        );

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../Services/ContentTranslationService.php';
        return (new ContentTranslationService($this->db))->localizeProducts($rows);
    }

    public function getTopSellingProducts(int $limit = 10): array
    {
        $limit = max(1, min(30, $limit));

        $hasDemoTable = (int) $this->db->query(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = 'product_demo_popularity'"
        )->fetchColumn() > 0;

        $demoJoin = $hasDemoTable
            ? 'LEFT JOIN product_demo_popularity demo ON demo.product_id = p.id'
            : '';

        $demoSelect = $hasDemoTable
            ? 'COALESCE(demo.demo_units, 0)'
            : '0';

        $sql = "SELECT
                    p.id,
                    p.name,
                    p.description,
                    p.image,
                    p.price,
                    p.discount_price,
                    p.stock_quantity,
                    COALESCE(sales.units_sold, 0) AS units_sold,
                    {$demoSelect} AS demo_units
                FROM products p
                LEFT JOIN (
                    SELECT
                        oi.product_id,
                        SUM(oi.quantity) AS units_sold
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    WHERE o.status IN ('processing', 'shipped', 'completed')
                    GROUP BY oi.product_id
                ) sales ON sales.product_id = p.id
                {$demoJoin}
                WHERE p.is_active = 1
                  AND p.name NOT LIKE 'LOOTE Custom %'
                ORDER BY
                    (COALESCE(sales.units_sold, 0) + {$demoSelect}) DESC,
                    COALESCE(sales.units_sold, 0) DESC,
                    p.id DESC
                LIMIT {$limit}";

        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../Services/ContentTranslationService.php';
        return (new ContentTranslationService($this->db))->localizeProducts($rows);
    }

    public function getById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                p.id,
                p.subcategorys_id,
                p.name,
                p.description,
                p.price,
                p.discount_price,
                p.image,
                p.model_path,
                p.product_type_id,
                pt.name AS product_type_name,
                p.stock_quantity,
                p.is_active,
                p.created_at,
                p.updated_at,
                s.name AS subcategorys_name,
                c.id AS category_id,
                c.name AS category_name
             FROM products p
             INNER JOIN subcategories s ON s.id = p.subcategorys_id
             INNER JOIN categories c ON c.id = s.category_id
             LEFT JOIN product_types pt ON pt.id = p.product_type_id
             WHERE p.id = :id
               AND p.is_active = 1
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        $product = $statement->fetch(PDO::FETCH_ASSOC);

        if ($product === false) return null;
        require_once __DIR__ . '/../Services/ContentTranslationService.php';
        return (new ContentTranslationService($this->db))->localizeProducts([$product])[0];
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO products (
                subcategorys_id,
                name,
                description,
                price,
                discount_price,
                image,
                stock_quantity
             ) VALUES (
                :subcategorys_id,
                :name,
                :description,
                :price,
                :discount_price,
                :image,
                :stock_quantity
             )'
        );

        $statement->execute($this->productParameters($data));

        $id = (int) $this->db->lastInsertId();
        require_once __DIR__ . '/../Services/ContentTranslationService.php';
        (new ContentTranslationService($this->db))->save('product', $id, [
            'name' => $data['name'], 'description' => $data['description']
        ]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $parameters = $this->productParameters($data);
        $parameters['id'] = $id;

        $statement = $this->db->prepare(
            'UPDATE products
             SET
                subcategorys_id = :subcategorys_id,
                name = :name,
                description = :description,
                price = :price,
                discount_price = :discount_price,
                image = :image,
                stock_quantity = :stock_quantity
             WHERE id = :id'
        );

        $statement->execute($parameters);

        $updated = $statement->rowCount() > 0;
        if ($updated) {
            require_once __DIR__ . '/../Services/ContentTranslationService.php';
            (new ContentTranslationService($this->db))->save('product', $id, [
                'name' => $data['name'], 'description' => $data['description']
            ]);
        }
        return $updated;
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE products
             SET is_active = 0
             WHERE id = :id
               AND is_active = 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function productParameters(array $data): array
    {
        return [
            'subcategorys_id' => (int) $data['subcategorys_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'image' => $data['image'] !== '' ? $data['image'] : null,
            'stock_quantity' => (int) $data['stock_quantity'],
        ];
    }
}
