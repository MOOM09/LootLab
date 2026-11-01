<?php
class AuthMiddleware
{
    private User $user;
    private string $appUrl;

    public function __construct(array $config)
    {
        $this->appUrl = rtrim((string) ($config['app_url'] ?? ''), '/');
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
            $this->reject('Please log in first.', 401);
        }

        $user = $this->user->findById($userId);

        if ($user === null) {
            unset($_SESSION['user_id']);
            $this->reject('Please log in again.', 401);
        }

        if ((int) $user['is_active'] !== 1) {
            unset($_SESSION['user_id']);
            $this->reject('Your account is inactive.', 403);
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

            $this->reject('Your session has expired. Please log in again.', 401);
        }
    }
    private function reject(string $message, int $status): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' &&
            str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'text/html')) {
            header('Location: ' . $this->appUrl . '/login?notice=signin', true, 303);
            exit;
        }
        Response::error($message, $status);
    }
}
