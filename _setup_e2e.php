<?php
// Fixture for the browser test: one test user, empty cart (the browser adds items itself).
require 'app/Core/Database.php';
$config = require 'config/config.php';
$pdo = (new Database($config['database']))->getConnection();

$email = 'audit_browser@example.invalid';

$u = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$u->execute([$email]);
$id = (int) $u->fetchColumn();

if ($id === 0) {
    $pdo->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)')
        ->execute(['audit browser', $email, password_hash('AuditPass123', PASSWORD_DEFAULT)]);
    echo 'user created' . PHP_EOL;
} else {
    echo 'user exists id=' . $id . PHP_EOL;
}

// Products for the browser to add.
foreach ([['audit-browser A', 4.50], ['audit-browser B', 4.46]] as [$name, $price]) {
    $check = $pdo->prepare('SELECT id FROM products WHERE name = ?');
    $check->execute([$name]);
    if ($check->fetchColumn()) {
        continue;
    }
    $cat = $pdo->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    if ($cat === false) {
        $pdo->exec("INSERT INTO categories (name) VALUES ('audit-b-cat')");
        $cat = (int) $pdo->lastInsertId();
    }
    $sub = $pdo->query('SELECT id FROM subcategories ORDER BY id LIMIT 1')->fetchColumn();
    if ($sub === false) {
        $pdo->prepare("INSERT INTO subcategories (category_id,name) VALUES (?, 'audit-b-sub')")->execute([(int) $cat]);
        $sub = (int) $pdo->lastInsertId();
    }
    $pdo->prepare('INSERT INTO products (subcategory_id,name,description,price,image,stock_quantity) VALUES (?,?,?,?,NULL,?)')
        ->execute([(int) $sub, $name, 'audit', $price, 20]);
    echo "product created: $name" . PHP_EOL;
}

$ids = $pdo->query("SELECT id, price FROM products WHERE name LIKE 'audit-browser %' ORDER BY id")->fetchAll();
echo json_encode($ids) . PHP_EOL;
