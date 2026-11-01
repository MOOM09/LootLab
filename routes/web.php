
<?php

/** @var Router $router */

// ======================================================
// HOME & PRODUCTS
// ======================================================

// Homepage: landing with About.
$router->get('/', [
    ProductController::class,
    'home',
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

// ======================================================
// AUTHENTICATION
// ======================================================

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

$router->post('/logout', [
    AuthController::class,
    'logout',
], [
    AuthMiddleware::class,
]);

// Email verification: enter a one-time code (no public email links).
$router->get('/verify-email', [EmailVerificationController::class, 'verify']);
$router->get('/verify-email/pending', [EmailVerificationController::class, 'pending']);
$router->post('/verify-email/resend', [EmailVerificationController::class, 'resend']);
$router->post('/verify-email/confirm', [EmailVerificationController::class, 'confirm']);

// Google OAuth
$router->get('/auth/google', [
    AuthController::class,
    'redirectToGoogle',
]);

$router->get('/auth/google/callback', [
    AuthController::class,
    'googleCallback',
]);

// Password reset
$router->get('/forgot-password', [
    PasswordResetController::class,
    'showForgot',
]);

$router->post('/forgot-password', [
    PasswordResetController::class,
    'requestReset',
]);

$router->get('/reset-password', [
    PasswordResetController::class,
    'showReset',
]);

$router->post('/reset-password', [
    PasswordResetController::class,
    'resetPassword',
]);

// ======================================================
// CART
// ======================================================

// Guest and authenticated carts.
// CartController must handle session ownership securely.

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

// ======================================================
// FAVORITES
// ======================================================

// Guest and authenticated favorites.
// FavoriteController must enforce ownership and CSRF.

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

// ======================================================
// REVIEWS
// ======================================================

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

// ======================================================
// PROFILE
// ======================================================

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

$router->get('/profile/design-image', [
    ProfileController::class,
    'designImage',
], [
    AuthMiddleware::class,
]);

// ======================================================
// ORDERS
// ======================================================

// IMPORTANT:
// OrderController::show must verify that the order belongs
// to the logged-in user or that the user is an admin.

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

// ======================================================
// CHECKOUT
// ======================================================
$router->post('/checkout/promo', [
    CheckoutController::class,
    'applyPromo',
], [
    AuthMiddleware::class,
]);

$router->post('/checkout/promo/remove', [
    CheckoutController::class,
    'removePromo',
], [
    AuthMiddleware::class,
]);

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

// SMS verification must complete before COD or Stripe checkout.
$router->get('/checkout/verify', [CheckoutController::class, 'showOtp'], [AuthMiddleware::class]);
$router->post('/checkout/verify', [CheckoutController::class, 'verifyOtp'], [AuthMiddleware::class]);
$router->post('/checkout/verify/resend', [CheckoutController::class, 'resendOtp'], [AuthMiddleware::class]);

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

// ======================================================
// AI
// ======================================================

// Keep the existing AI page URL.

$router->get('/ai.php', [
    AiController::class,
    'index',
], [
    AuthMiddleware::class,
]);

$router->post('/ai.php', [
    AiController::class,
    'index',
], [
    AuthMiddleware::class,
]);

// ======================================================
// ADMIN: CATEGORIES
// ======================================================

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

// ======================================================
// ADMIN: USERS
// ======================================================

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
