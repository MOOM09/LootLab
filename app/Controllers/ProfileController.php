<?php
require_once dirname(__DIR__) . '/Models/CustomDesign.php';
require_once dirname(__DIR__) . '/Services/DesignImageStorage.php';
class ProfileController extends Controller
{
    private User $user;
    private string $appUrl;
    private CustomDesign $designs;

    public function __construct(array $config)
    {
        $this->appUrl = $config['app_url'];
        $database = new Database($config['database']);

        $this->user = new User($database->getConnection());
    
    $this->designs = new CustomDesign(
    $database->getConnection()
);
    
        }
    

    public function index(): void
    {
        $user = $this->user->findById(
            (int) $_SESSION['user_id']
        );

        if ($user === null) {
            Response::error('User not found.', 404);
        }

        $this->view('profile/index', [
    'user' => $user,
    'appUrl' => $this->appUrl,
    'csrfToken' => $this->csrfToken(),
    'designs' => $this->designs->findByUser(
        (int) $_SESSION['user_id']
    ),
]);
    }

    private function validateProfile(string $name, string $phone): array
    {
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Name must not exceed 100 characters.';
        }

        if (
            $phone !== '' &&
            (
                strlen($phone) > 25 ||
                !preg_match('/^\+?[0-9 ()-]+$/', $phone) ||
                !preg_match('/[0-9]/', $phone)
            )
        ) {
            $errors['phone'] = 'Enter a valid phone number.';
        }

        return $errors;
    }

    public function update(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $name = $input['name'] ?? '';
        $phone = $input['phone'] ?? '';

        if (!is_string($name) || !is_string($phone)) {
            Response::error('Invalid input.', 422);
        }

        $name = trim($name);
        $phone = trim($phone);

        $errors = $this->validateProfile($name, $phone);

        if ($errors !== []) {
            Response::error('Validation failed.', 422, $errors);
        }

        $userId = (int) $_SESSION['user_id'];

        if (!$this->user->updateProfile($userId, $name, $phone)) {
            Response::error('Unable to update profile.', 500);
        }

        Response::success(null, 200, 'Profile updated successfully.');
    }

    public function deleteAccount(): void
    {

        $input = $this->requestData();
        $this->validateCsrf($input);

        $password = $input['password'] ?? '';

        if (!is_string($password) || $password === '')
            Response::error('Password is requiered', 422);

        $userId = (int) $_SESSION['user_id'];
        $profile = $this->user->findById($userId);

        if ($profile === null)
            Response::error("User not found", 404);

        $user = $this->user->findByEmail($profile['email']);

        if ($user === null || (int) $user['id'] !== $userId)
            Response::error('Account changed. Please log in again.', 409);

        // If logged in by google
        if (!is_string($user['password'])) {
            Response::error(
                'Google accounts require Google identity confirmation before deletion.',
                409
            );
        }

        if (!password_verify($password, $user['password']))
            Response::error('Incorrect password.', 403);

        try {
            $deleted = $this->user->delete($userId);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                Response::error(
                    'Account has linked orders or custom designs and cannot be deleted.',
                    409
                );
            }

            throw $e;
        }

        if (!$deleted)
            Response::error('User not found.', 404);

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }

        session_destroy();

        Response::success(null, 200, 'Account deleted successfully.');
    }
    public function designImage(): void
{
    $designId = filter_var(
        $_GET['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($designId === false) {
        Response::error('Invalid design ID.', 422);
        return;
    }

    $design = $this->designs->findForUser(
        $designId,
        (int) $_SESSION['user_id']
    );

    if (
        $design === null ||
        !is_string($design['result_image'])
    ) {
        Response::error('Design not found.', 404);
        return;
    }

    try {
        $storage = new DesignImageStorage();
        $path = $storage->pathFor($design['result_image']);
    } catch (RuntimeException $exception) {
        Response::error('Design image not found.', 404);
        return;
    }

    if (!is_file($path) || !is_readable($path)) {
        Response::error('Design image not found.', 404);
        return;
    }
// إزالة أي نص أو مسافات خرجت قبل بيانات الصورة.
while (ob_get_level() > 0) {
    if (!ob_end_clean()) {
        break;
    }
}
    header('Content-Type: image/png');
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    header(
        'Content-Disposition: inline; filename="design-'
        . $designId . '.png"'
    );

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    readfile($path);
    exit;
}
}
