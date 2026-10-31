<?php

// Local development only: php database/seed-products.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$config = require __DIR__ . '/../config/config.php';
if (!in_array($config['database']['host'], ['localhost', '127.0.0.1', '::1'], true)) {
    exit("This seed script only supports a local database.\n");
}
require_once __DIR__ . '/../app/Core/Database.php';
$pdo = (new Database($config['database']))->getConnection();

$products = [
    ['Demo Anime Cloud Keychain', 'Anime-inspired cloud charm for bags and keys. Dummy product for favorites testing.', 25.00, null, 20, 1],
    ['Demo Manga Panel Tote Bag', 'Reusable tote with a fictional manga panel design. Discounted dummy product.', 65.00, 55.00, 10, 1],
    ['Demo Movie Fan Enamel Pin', 'Enamel pin with a fictional space movie emblem. Dummy product for favorites testing.', 40.00, null, 15, 1],
    ['Demo Anime Character Acrylic Stand', 'Fictional anime character display stand. Out-of-stock dummy product.', 12.00, null, 0, 1],
    ['Demo Movie Replica Pendant', 'Fantasy movie-inspired pendant. Inactive dummy product.', 35.00, null, 5, 0],
];

try {
    $pdo->beginTransaction();
    $pdo->exec("INSERT INTO categories (name) VALUES ('Demo Fan Accessories') ON DUPLICATE KEY UPDATE id = id");
    $categoryId = $pdo->query("SELECT id FROM categories WHERE name = 'Demo Fan Accessories'")->fetchColumn();
    $statement = $pdo->prepare("INSERT INTO subcategories (category_id, name) VALUES (?, 'Anime, Manga and Movies') ON DUPLICATE KEY UPDATE id = id");
    $statement->execute([$categoryId]);
    $statement = $pdo->prepare("SELECT id FROM subcategories WHERE category_id = ? AND name = 'Anime, Manga and Movies'");
    $statement->execute([$categoryId]);
    $subcategoryId = $statement->fetchColumn();

    // Migrate only the original demo products; preserve IDs and favorite references.
    $oldNames = ['Demo Gaming Mouse', 'Demo Mechanical Keyboard', 'Demo Gaming Headset', 'Demo Mouse Pad', 'Demo Controller'];
    $migrate = $pdo->prepare("UPDATE products p JOIN subcategories s ON s.id = p.subcategorys_id JOIN categories c ON c.id = s.category_id SET p.subcategorys_id = ?, p.name = ?, p.description = ? WHERE p.name = ? AND s.name = 'Demo Peripherals' AND c.name = 'Demo Gaming Accessories'");
    foreach ($products as $index => $product) {
        $migrate->execute([$subcategoryId, $product[0], $product[1], $oldNames[$index]]);
    }
    // Remove the old demo grouping only when nothing references it.
    $pdo->exec("DELETE s FROM subcategories s JOIN categories c ON c.id = s.category_id WHERE s.name = 'Demo Peripherals' AND c.name = 'Demo Gaming Accessories' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.subcategorys_id = s.id)");
    $pdo->exec("DELETE c FROM categories c WHERE c.name = 'Demo Gaming Accessories' AND NOT EXISTS (SELECT 1 FROM subcategories s WHERE s.category_id = c.id)");

    $find = $pdo->prepare('SELECT id FROM products WHERE subcategorys_id = ? AND name = ?');
    $insert = $pdo->prepare('INSERT INTO products (subcategorys_id, name, description, price, discount_price, stock_quantity, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($products as $product) {
        $find->execute([$subcategoryId, $product[0]]);
        if (!$find->fetchColumn()) {
            $insert->execute(array_merge([$subcategoryId], $product));
        }
    }
    $pdo->commit();

    $statement = $pdo->prepare('SELECT id, name, price, discount_price, stock_quantity, is_active FROM products WHERE subcategorys_id = ? ORDER BY id');
    $statement->execute([$subcategoryId]);
    echo json_encode($statement->fetchAll(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Sample products could not be seeded; check the local database connection and schema.\n");
    exit(1);
}
