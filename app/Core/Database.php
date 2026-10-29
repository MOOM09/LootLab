<?php

class Database
{
    private PDO $connection;

    public function __construct(array $config)
    {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";

        $this->connection = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                // Report database errors as exceptions
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Return rows using column names
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Use MySQL’s real prepared statements
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
