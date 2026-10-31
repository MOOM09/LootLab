<?php
class AuthMiddleware
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
            unset($_SESSION['user_id']);
            Response::error('Please log in first.', 401);
        }

        $user = $this->user->findById($userId);

        if ($user === null) {
            unset($_SESSION['user_id']);
            Response::error('Please log in again.', 401);
        }

        if ((int) $user['is_active'] !== 1) {
            unset($_SESSION['user_id']);
            Response::error('Your account is inactive.', 403);
        }

        // Reject sessions created before the account's session version changed.
        if (
            (int) ($_SESSION['session_version'] ?? 0)
            !== (int) $user['session_version']
        ) {
            unset(
                $_SESSION['user_id'],
                $_SESSION['session_version']
            );

            Response::error(
                'Your session has expired. Please log in again.',
                401
            );
        }
    }
}
