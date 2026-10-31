<?php

/** @var Router $router */

// Preserve the existing AI page URL through the shared entry point.
$router->get( '/ai.php',[
        AiController::class,
        'index'
    ]
);

$router->post('/ai.php',[
        AiController::class,
        'index'
    ]
);

$router->get('/auth/google', [
    AuthController::class,
    'redirectToGoogle',
]);

$router->get('/auth/google/callback', [
    AuthController::class,
    'googleCallback',
]);

$router->get('/login', [
    AuthController::class,
    'showLogin',
], [
    GuestMiddleware::class,
]);

$router->post('/login', [
    AuthController::class,
    'login',
]);

$router->get('/register', [
    AuthController::class,
    'showRegister',
], [
    GuestMiddleware::class,
]);

$router->post('/register', [
    AuthController::class,
    'register',
]);

// Display the email form.
$router->get('/forgot-password', [
    PasswordResetController::class,
    'showForgot',
]);

// Request a reset link.
$router->post('/forgot-password', [
    PasswordResetController::class,
    'requestReset',
]);

// Display the new-password form.
$router->get('/reset-password', [
    PasswordResetController::class,
    'showReset',
]);

// Submit the new password.
$router->post('/reset-password', [
    PasswordResetController::class,
    'resetPassword',
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
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->get('/admin/categories/show', [
    CategoryController::class,
    'show',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->post('/admin/categories', [
    CategoryController::class,
    'store',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->post('/admin/categories/update', [
    CategoryController::class,
    'update',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->post('/admin/categories/delete', [
    CategoryController::class,
    'destroy',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
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
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->post('/products/update', [
    ProductController::class,
    'update',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->post('/products/delete', [
    ProductController::class,
    'destroy',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
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
], [
    AuthMiddleware::class,
]);

$router->delete('/reviews/delete', [
    ReviewController::class,
    'destroy',
], [
    AuthMiddleware::class,
]);

$router->get('/orders', [
    OrderController::class,
    'index',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->get('/orders/show', [
    OrderController::class,
    'show',
], [
    AuthMiddleware::class,
]);

$router->post('/orders', [
    OrderController::class,
    'store',
], [
    AuthMiddleware::class,
]);

$router->post('/orders/update', [
    OrderController::class,
    'update',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

$router->post('/orders/delete', [
    OrderController::class,
    'destroy',
], [
    AuthMiddleware::class,
    AdminMiddleware::class,
]);

// Checkout
$router->get('/checkout', [
    CheckoutController::class,
    'index',
], [
    AuthMiddleware::class,
]);

$router->post('/checkout', [
    CheckoutController::class,
    'placeOrder',
], [
    AuthMiddleware::class,
]);

$router->post('/checkout/stripe', [
    CheckoutController::class,
    'payWithStripe',
], [
    AuthMiddleware::class,
]);

$router->get('/checkout/success', [
    CheckoutController::class,
    'success',
], [
    AuthMiddleware::class,
]);

$router->get('/checkout/stripe-success', [
    CheckoutController::class,
    'stripeSuccess',
], [
    AuthMiddleware::class,
]);

// Cart (guests and logged-in users; ownership is resolved in the controller)
$router->get('/cart', [
    CartController::class,
    'index',
]);

$router->post('/cart/add', [
    CartController::class,
    'add',
]);

$router->post('/cart/update', [
    CartController::class,
    'update',
]);

$router->post('/cart/remove', [
    CartController::class,
    'remove',
]);

$router->post('/cart/clear', [
    CartController::class,
    'clear',
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
