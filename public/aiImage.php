<?php

session_start();

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

$config = require $root . '/config/config.php';

require_once $root . '/app/Core/Database.php';
require_once $root . '/app/Core/Response.php';
require_once $root . '/app/Models/User.php';
require_once $root . '/app/Middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($config);
$auth->handle();

require_once $root . '/app/Controllers/AiImageController.php';

header('Content-Type: text/html; charset=utf-8');

$controller = new AiImageController($config);
$controller->index();