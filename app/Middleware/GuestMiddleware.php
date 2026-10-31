<?php

class GuestMiddleware
{
    public function __construct(private array $config)
    {
    }

    public function handle(): void
    {
        $userId = filter_var(
            $_SESSION['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($userId === false) {
            unset($_SESSION['user_id']);
            return;
        }

        $database = new Database($this->config['database']);
        $user = (new User($database->getConnection()))->findById($userId);

        if ($user === null || (int) $user['is_active'] !== 1) {
            unset($_SESSION['user_id']);
            return;
        }

        header('Location: ' . rtrim($this->config['app_url'], '/') . '/products', true, 302);
        exit;
    }
}
