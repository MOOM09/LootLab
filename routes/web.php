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

$router->get('/admin/categories', [
    CategoryController::class,
    'index',
]);

$router->get('/admin/categories/show', [
    CategoryController::class,
    'show',
]);

$router->post('/admin/categories', [
    CategoryController::class,
    'store',
]);

$router->post('/admin/categories/update', [
    CategoryController::class,
    'update',
]);

$router->post('/admin/categories/delete', [
    CategoryController::class,
    'destroy',
]);

$router->get('/products', [
    ProductController::class,
    'index',
]);

$router->get('/products/show', [
    ProductController::class,
    'show',
]);

$router->post('/products', [
    ProductController::class,
    'store',
]);

$router->post('/products/update', [
    ProductController::class,
    'update',
]);

$router->post('/products/delete', [
    ProductController::class,
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
