<?php

session_start();

// Load Composer and configuration once for this request.
require_once dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

// Core classes.
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/Router.php';

// Interfaces must be loaded before classes that implement them.
require_once __DIR__ . '/../app/Interfaces/cartInterface.php';

// Models.
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/Product.php';
require_once __DIR__ . '/../app/Models/Favorite.php';
require_once __DIR__ . '/../app/Models/CartItem.php';
require_once __DIR__ . '/../app/Models/Cart.php';
require_once __DIR__ . '/../app/Models/Review.php';
require_once __DIR__ . '/../app/Models/OrderItem.php';
require_once __DIR__ . '/../app/Models/Order.php';
require_once __DIR__ . '/../app/Models/AiConversation.php';

// Services.
require_once __DIR__ . '/../app/Services/GoogleAuthService.php';
require_once __DIR__ . '/../app/Services/AiService.php';
require_once __DIR__ . '/../app/Services/AiDatabase.php';
require_once __DIR__ . '/../app/Services/AiProductCatalog.php';

// Middleware.
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/AdminMiddleware.php';

// Controllers.
require_once __DIR__ . '/../app/Controllers/Admin/UserController.php';
require_once __DIR__ . '/../app/Controllers/Admin/CategoryController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/ProductController.php';
require_once __DIR__ . '/../app/Controllers/ProfileController.php';
require_once __DIR__ . '/../app/Controllers/FavoriteController.php';
require_once __DIR__ . '/../app/Controllers/CartController.php';
require_once __DIR__ . '/../app/Controllers/CheckoutController.php';
require_once __DIR__ . '/../app/Controllers/OrderController.php';
require_once __DIR__ . '/../app/Controllers/ReviewController.php';
require_once __DIR__ . '/../app/Controllers/AiController.php';

$router = new Router($config);

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
        Response::error('Page not found', 404);
    }
}

// find the matching route and run it.
$router->dispatch($method, $path);
