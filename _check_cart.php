<?php
require 'app/Core/Database.php';
$config = require 'config/config.php';
$pdo = (new Database($config['database']))->getConnection();
$u = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$u->execute(['audit_browser@example.invalid']);
$userId = (int) $u->fetchColumn();
echo "userId=$userId" . PHP_EOL;
$c = $pdo->prepare('SELECT id FROM carts WHERE user_id = ?');
$c->execute([$userId]);
$cartId = (int) $c->fetchColumn();
echo "cartId=$cartId" . PHP_EOL;
$q = $pdo->prepare('SELECT ci.product_id, ci.quantity, p.name, p.price FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.cart_id = ?');
$q->execute([$cartId]);
print_r($q->fetchAll());
$t = $pdo->prepare('SELECT SUM(ci.quantity * p.price) FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.cart_id = ?');
$t->execute([$cartId]);
echo 'total = ' . $t->fetchColumn() . PHP_EOL;

$o = $pdo->prepare('SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5');
$o->execute([$userId]);
echo 'orders:' . PHP_EOL;
print_r($o->fetchAll());
