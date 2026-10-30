<?php

class AdminMiddleware
{
    private User $user;

    public function __construct(array $config)
    {
        $database = new Database($config['database']);

        $this->user = new User($database->getConnection());
    }

    public function handle(): void
    {
        $userId = filter_var(
            $_SESSION['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($userId === false) {
            Response::error('Please log in first.', 401);
        }

        $user = $this->user->findById($userId);

        if ($user === null) {
            Response::error('Please log in again.', 401);
        }

        if ((int) $user['is_active'] !== 1) {
            Response::error('Your account is inactive.', 403);
        }

        if ($user['role'] !== 'admin') {
            Response::error('Admin access required.', 403);
        }
    }
}