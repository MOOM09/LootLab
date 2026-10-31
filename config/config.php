<?php

$projectRoot = dirname(__DIR__);

require_once $projectRoot . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->load();

return [
    'app_url' => rtrim($_ENV['APP_URL'] ?? '', '/'),

    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? '',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
    ],

    'google' => [
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI']
            ?? 'http://localhost/LootLab/public/auth/google/callback',
    ],
];