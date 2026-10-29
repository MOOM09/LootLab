<?php

session_start();

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Controllers/AiController.php';

header('Content-Type: text/html; charset=utf-8');

$controller = new AiController();
$controller->index();