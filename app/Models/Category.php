<?php

class Category
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
                c.id,
                c.name,
                c.image,
                c.created_at,
                c.updated_at,
                COUNT(DISTINCT s.id) AS subcategories_count
             FROM categories c
             LEFT JOIN subcategories s ON s.category_id = c.id
             GROUP BY c.id, c.name, c.image, c.created_at, c.updated_at
             ORDER BY c.name ASC'
        );

        return $statement->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                c.id,
                c.name,
                c.image,
                c.created_at,
                c.updated_at
             FROM categories c
             WHERE c.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    public function getSubcategories(int $id): array
    {
        $statement = $this->db->prepare(
            'SELECT
                id,
                category_id,
                name,
                image,
                created_at,
                updated_at
             FROM subcategories
             WHERE category_id = :category_id
             ORDER BY name ASC'
        );
        $statement->execute(['category_id' => $id]);

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO categories (name, image)
             VALUES (:name, :image)'
        );
        $statement->execute($this->categoryParameters($data));

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $parameters = $this->categoryParameters($data);
        $parameters['id'] = $id;

        $statement = $this->db->prepare(
            'UPDATE categories
             SET name = :name, image = :image
             WHERE id = :id'
        );
        $statement->execute($parameters);

        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM categories
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function categoryParameters(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Category name is required.');
        }

        return [
            'name' => $name,
            'image' => $this->getImage($data['image'] ?? null),
        ];
    }

    private function getImage(mixed $image): ?string
    {
        $image = trim((string) ($image ?? ''));

        return $image === '' ? null : $image;
    }
}