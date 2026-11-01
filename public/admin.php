<?php
// Separate entry point: no changes to the shared router or existing controllers.
$root = dirname(__DIR__);
require_once $root . "/vendor/autoload.php";
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$config = require $root . "/config/config.php";
date_default_timezone_set("Asia/Amman");
header("Cache-Control: private, no-store");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
require_once $root . "/app/Core/Database.php";
require_once $root . "/app/Core/Response.php";
require_once $root . "/app/Models/User.php";
require_once $root . "/app/Middleware/AuthMiddleware.php";
require_once $root . "/app/Middleware/AdminMiddleware.php";
if (!isset($_GET["api"]) && empty($_SESSION["user_id"])) {
    header("Location: " . rtrim($config["app_url"], "/") . "/login");
    exit();
}
(new AuthMiddleware($config))->handle();
(new AdminMiddleware($config))->handle();
require_once $root . "/app/Models/AdminPanelStore.php";
require_once $root . "/app/Services/AdminPanelFiles.php";
require_once $root . "/app/Controllers/Admin/PanelController.php";
try {
    (new AdminPanelController($config, $root))->handle();
} catch (Throwable $e) {
    error_log((string) $e);
    Response::error("The admin panel could not load. Check your database connection and schema.", 503);
}
