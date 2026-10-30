<?php

class Router
{
    private array $routes = [];

    public function get(
        string $path,
        array $handler,
        array $middleware = []
    ): void {
        $this->routes['GET'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function post(
        string $path,
        array $handler,
        array $middleware = []
    ): void {
        $this->routes['POST'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function put(
        string $path,
        array $handler,
        array $middleware = []
    ): void {
        $this->routes['PUT'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function patch(
        string $path,
        array $handler,
        array $middleware = []
    ): void {
        $this->routes['PATCH'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function delete(
        string $path,
        array $handler,
        array $middleware = []
    ): void {
        $this->routes['DELETE'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            http_response_code(404);
            echo 'Page not found';
            return;
        }

        foreach ($route['middleware'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $middleware->handle();
        }

        [$controllerClass, $action] = $route['handler'];

        $controller = new $controllerClass();
        $controller->$action();
    }
}

// ? How to use?
// Create a Router object in public/index.php:
// $router = new Router();

// ? Register a GET route (display a page)
// $router->get('/login', [AuthController::class, 'showLogin']);

// ? Register a POST route (submit a form)
// $router->post('/login', [AuthController::class, 'login']);

// ? What is ::class?
// AuthController::class gives the class name as a string.
// It does not create an object.

// ? Add middleware to protect a route
// $router->get('/favorites', [FavoriteController::class, 'index'], [
//     AuthMiddleware::class,
// ]);

// ? Add multiple middleware
// $router->get('/admin', [DashboardController::class, 'index'], [
//     AuthMiddleware::class,
//     AdminMiddleware::class,
// ]);

// ? Handle an incoming request
// $router->dispatch('GET', '/login');

// ? What does dispatch() do?
// 1. Finds the route matching the request method and path.
// 2. If no route matches, displays a 404 response.
// 3. Runs the route's middleware in order.
// 4. Creates the controller object.
// 5. Calls the controller method.

// ? For the /login GET route, the final two steps are:
// $controller = new AuthController();
// $controller->showLogin();

// ? Paths passed to dispatch() must match the registered paths.
// For our XAMPP URL, public/index.php will remove
// /projects/LootLab/public from the request path before dispatching.
