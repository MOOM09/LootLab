<?php

/** @var Router $router */

$router->get('/login', [
    AuthController::class,
    'showLogin',
]);

$router->post('/login', [
    AuthController::class,
    'login',
]);

$router->get('/register', [
    AuthController::class,
    'showRegister',
]);

$router->post('/register', [
    AuthController::class,
    'register',
]);

$router->post('/logout', [
    AuthController::class,
    'logout',
], [
    AuthMiddleware::class,
]);

$router->get('/profile', [
    ProfileController::class,
    'index',
], [
    AuthMiddleware::class,
]);

$router->put('/profile', [
    ProfileController::class,
    'update',
], [
    AuthMiddleware::class,
]);

$router->delete('/profile', [
    ProfileController::class,
    'deleteAccount',
], [
    AuthMiddleware::class,
]);

// ? Favorites support guests and logged-in users.
// TODO: Controller actions must resolve ownership from the session and validate CSRF on writes.
$router->get('/favorites', [
    FavoriteController::class,
    'index',
]);

$router->post('/favorites', [
    FavoriteController::class,
    'store',
]);

$router->delete('/favorites', [
    FavoriteController::class,
    'destroy',
]);

$router->post('/favorites/move-to-cart', [
    FavoriteController::class,
    'moveToCart',
], [
    AuthMiddleware::class,
]);

$router->get('/admin/users', [
    App\Controllers\Admin\UserController::class,
    'index',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->patch('/admin/users/ban', [
    App\Controllers\Admin\UserController::class,
    'ban',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->patch('/admin/users/unban', [
    App\Controllers\Admin\UserController::class,
    'unban',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->get('/reviews', [
    ReviewController::class,
    'index',
]);

$router->post('/reviews', [
    ReviewController::class,
    'store',
]);

$router->post('/reviews/delete', [
    ReviewController::class,
    'destroy',
]);

$router->get('/orders', [
    OrderController::class,
    'index',
]);

$router->get('/orders/show', [
    OrderController::class,
    'show',
]);

$router->post('/orders', [
    OrderController::class,
    'store',
]);

$router->post('/orders/update', [
    OrderController::class,
    'update',
]);

$router->post('/orders/delete', [
    OrderController::class,
    'destroy',
]);


// ? How to create a route here?
// $router is already created in public/index.php before this file is loaded.
// Format: $router->get('/path', [ControllerName::class, 'methodName']);

// ? Display a page using GET
// Example: $router->get('/products', [ProductController::class, 'index']);
// This calls index() on a ProductController object when GET /products is requested.

// ? Process a submitted form using POST
// Example: $router->post('/products', [ProductController::class, 'store']);
// GET and POST can use the same path because they are different request methods.

// ? What does ::class mean?
// ProductController::class gives the class name; it does not create an object.

// ? Protect a route with middleware as the third argument
// $router->get('/favorites', [FavoriteController::class, 'index'], [
//     AuthMiddleware::class,
// ]);

// ? Need multiple checks? Add their class names in the order they should run.
// Example middleware array: [AuthMiddleware::class, AdminMiddleware::class]
// Without the third argument, no middleware runs for that route.

// ? Before using your route
// Load your controller and middleware files in public/index.php.
// Create the public controller method named in the route.
// Use paths like '/login'; index.php handles the application's base path.
// These are usage examples only; the actual authentication routes are below.
