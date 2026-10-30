<?php

namespace App\Controllers\Admin;

use Controller;
use Database;
use Response;
use User;

class UserController extends Controller
{
    private User $user;

    public function __construct(array $config)
    {
        $database = new Database($config['database']);

        $this->user = new User($database->getConnection());
    }

    public function index(): void
    {
        Response::success($this->user->findAll());
    }

    public function ban(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $id = filter_var(
            $input['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false) {
            Response::error('Invalid user ID.', 422);
        }

        if ($id === (int) $_SESSION['user_id']) {
            Response::error('You cannot ban your own account.', 403);
        }

        $user = $this->user->findById($id);

        if ($user === null) {
            Response::error('User not found.', 404);
        }

        if ((int) $user['is_active'] === 0) {
            Response::error('User is already banned.', 409);
        }

        //? This call the model and its ban function and return boolean.
        if (!$this->user->ban($id)) {
            Response::error('User status changed. Refresh and try again.', 409);
        }

        Response::success(null, 200, 'User banned successfully.');
    }

    public function unban(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $id = filter_var(
            $input['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false) {
            Response::error('Invalid user ID.', 422);
        }

        $user = $this->user->findById($id);

        if ($user === null) {
            Response::error('User not found.', 404);
        }

        if ((int) $user['is_active'] === 1) {
            Response::error('User is already active.', 409);
        }

        if (!$this->user->unban($id)) {
            Response::error('User status changed. Refresh and try again.', 409);
        }

        Response::success(null, 200, 'User unbanned successfully.');
    }
}
