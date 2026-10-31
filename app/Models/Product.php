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

        require_once __DIR__ . '/../Core/Database.php';

        $database = new Database($config['database']);
        $this->db = $database->getConnection();
    }

    public function getAll(): array
    {
        $statement = $this->db->query(
            'SELECT
                p.id,
                p.subcategorys_id,
                p.name,
                p.description,
                p.price,
                p.discount_price,
                p.image,
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
             WHERE p.is_active = 1
             ORDER BY p.created_at DESC, p.id DESC'
        );

        return $statement->fetchAll();
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
             WHERE p.id = :id AND p.is_active = 1
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $product = $statement->fetch();

        return $product === false ? null : $product;
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

        return (int) $this->db->lastInsertId();
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

        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE products
             SET is_active = 0
             WHERE id = :id AND is_active = 1'
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