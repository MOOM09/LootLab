<?php

// INI => key/value
//? Read settings from a file and returns them as an array.
$env = parse_ini_file (__DIR__ . '.env', false, INI_SCANNER_RAW);

if ($env === false)
    throw new RuntimeException("Could not read the .env file.");

return [
    //? rtim: removes specified characters from the end of a string.
    'app_url' => rtrim($env['APP_URL'], '/'),

    'database' => [
        'host' => $env['DB_HOST'],
        'name' => $env['DB_NAME'],
        'username' => $env['DB_USER'],
        'password' => $env['DB_PASS'],    
        ]
];