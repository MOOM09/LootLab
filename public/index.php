<?php

session_start();

$config = require __DIR__ . '/../config/config.php';

require_once DIR . '/../app/Core/Database.php';
require_once DIR . '/../app/Core/Controller.php';
require_once DIR . '/../app/Core/Response.php';
require_once DIR . '/../app/Core/Router.php';

require_once DIR . '/../app/Controllers/AuthController.php';
require_once DIR . '/../app/Controllers/OrderController.php';
require_once DIR . '/../app/Controllers/ReviewController.php';

require_once DIR . '/../app/Models/User.php';
require_once DIR . '/../app/Models/Review.php';
require_once DIR . '/../app/Models/Order.php';

require_once DIR . '/../app/Middleware/AuthMiddleware.php';

$router = new Router($config);

require DIR . '/../routes/web.php';


$router = new Router();

require __DIR__ . '/../routes/web.php';

//? This code bellow converts the full request path into a short path that matches your routes.
// Browser path                         → Router path
// /projects/LootLab/public/            → /
// /projects/LootLab/public/products    → /products
// /projects/LootLab/public/login       → /login

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = parse_url($config['app_url'], PHP_URL_PATH) ?? '';
$basePath = rtrim($basePath, '/');

if ($basePath !== '') {
    if ($path === $basePath) {
        $path = '/';
    } elseif (str_starts_with($path, $basePath . '/')) {
        $path = substr($path, strlen($basePath));
    } else {
        http_response_code(404);
        exit('Page not found');
    }
}

// find the matching route and run it.
$router->dispatch($method, $path);