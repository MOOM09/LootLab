<?php
class AiDatabase
{
    private PDO $connection;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'];
        $name = $_ENV['DB_NAME'];

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

        $this->connection = new PDO(
            $dsn,
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}