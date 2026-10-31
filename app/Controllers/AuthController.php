<?php

require_once dirname(__DIR__) . '/Services/CartMergeService.php';
require_once dirname(__DIR__) . '/Models/Cart.php';
require_once dirname(__DIR__) . '/Models/CartItem.php';

// TODO: In frontend JavaScript, redirect to result.data.redirect
// after a successful login or registration response.
class AuthController extends Controller
{
    private User $user;
    private string $appUrl;
    private Database $database;
    private GoogleAuthService $googleAuth;

    public function __construct(array $config)
    {
        $this->appUrl = $config['app_url'];

        $this->database = new Database($config['database']);

        $this->user = new User($this->database->getConnection());

        $this->googleAuth = new GoogleAuthService($config['google']);
    }

    public function showLogin(): void
    {
        $this->view('auth/login', [
            'appUrl' => $this->appUrl,
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function showRegister(): void
    {
        $this->view('auth/register', [
            'appUrl' => $this->appUrl,
            'csrfToken' => $this->csrfToken(),
        ]);
    }


    public function register(): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::error('Log out before using another account.', 409);
        }

        $input = $this->requestData();

        $this->validateCsrf($input);

        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $passwordConfirmation = $input['password_confirmation'] ?? '';

        if (
            !is_string($name) ||
            !is_string($email) ||
            !is_string($password) ||
            !is_string($passwordConfirmation)
        ) {
            Response::error('Invalid input.', 422);
        }

        $name = trim($name);
        $email = trim($email);

        $errors = $this->validateRegister(
            $name,
            $email,
            $password,
            $passwordConfirmation
        );

        if ($errors !== []) {
            Response::error('Validation failed.', 422, $errors);
        }

        if ($this->user->findByEmail($email) !== null) {
            Response::error('Email is already registered.', 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $userId = $this->user->create($name, $email, $passwordHash);
        } catch (PDOException $exception) {
            // Handle duplicate email if another request registered it first.
            if (
                $exception->getCode() === '23000' &&
                (int) ($exception->errorInfo[1] ?? 0) === 1062
            ) {
                Response::error('Email is already registered.', 409);
            }

            throw $exception;
        }

        $this->mergeGuestCart((int) $userId);

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $userId;

        Response::success(
            [
                'id' => $userId,
                'redirect' => $this->appUrl . '/products',
            ],
            201,
            'Account created and logged in successfully.'
        );
    }

    // ? Validate registration fields and return errors
    private function validateRegister(
        string $name,
        string $email,
        string $password,
        string $passwordConfirmation
    ): array {
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Name must not exceed 100 characters.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (
            strlen($email) > 255 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must contain at least 8 bytes.';
        } elseif (strlen($password) > 72) {
            $errors['password'] = 'Password must not exceed 72 bytes.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must include an uppercase letter, a lowercase letter, and a digit.';
        } elseif (str_contains($password, "\0")) {
            $errors['password'] = 'Password contains an invalid character.';
        }

        if ($passwordConfirmation === '') {
            $errors['password_confirmation'] = 'Confirm your password.';
        } elseif ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        return $errors;
    }

    // ? Validate login fields and return errors
    private function validateLogin(
        string $email,
        string $password
    ): array {
        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        return $errors;
    }

    public function login(): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::error('Log out before using another account.', 409);
        }

        $input = $this->requestData();

        $this->validateCsrf($input);

        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!is_string($email) || !is_string($password)) {
            Response::error('Invalid input.', 422);
        }

        $email = trim($email);

        $errors = $this->validateLogin($email, $password);

        if ($errors !== []) {
            Response::error('Validation failed.', 422, $errors);
        }

        $user = $this->user->findByEmail($email);

        //? Password check
        if (
            !$user ||
            !is_string($user['password']) ||
            !password_verify($password, $user['password'])
        ) {
            Response::error('Invalid email or password.', 401);
        }

        //? Account status check
        if ((int) $user['is_active'] !== 1) {
            Response::error('Your account is inactive.', 403);
        }

        $this->mergeGuestCart((int) $user['id']);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        //? Calling view() directly would display the page
        // while the browser remains on the POST /login request,
        //  without running the product controller.
        Response::success(
            ['redirect' => $this->appUrl . '/products'],
            200,
            'Login successful.'
        );
    }

    // session_destroy: Deletes the session data on the server, but doesn’t delete that browser cookie.
    public function logout(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

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

        Response::success(
            ['redirect' => $this->appUrl . '/login'],
            200,
            'Logged out successfully.'
        );
    }

    // ? Redirect the visitor to Google
    public function redirectToGoogle(): void
    {
        // Guest check
        if (isset($_SESSION['user_id'])) {
            Response::error('Log out before using another account.', 409);
        }

        // Generate a security value and save it for the callback.
        $state = bin2hex(random_bytes(32));

        $_SESSION['google_oauth'] = [
            'state' => $state,
            'created_at' => time(),
        ];

        // Send the browser to Google's login page.
        $url = $this->googleAuth->getAuthorizationUrl($state);

        $this->redirect($url);
    }

    // ? Handle Google's response and log the user in
    public function googleCallback(): void
    {
        $this->validateGoogleState();

        if (isset($_SESSION['user_id'])) {
            Response::error('You are already logged in.', 409);
        }

        $identity = $this->getGoogleIdentity();
        $user = $this->findOrCreateGoogleUser($identity);

        if ((int) $user['is_active'] !== 1) {
            Response::error('Your account is inactive.', 403);
        }

        $this->completeGoogleLogin($user);
    }

    // ? Validate and consume the security value sent to Google.
    private function validateGoogleState(): void
    {
        $state = $_GET['state'] ?? null;
        $saved = $_SESSION['google_oauth'] ?? null;

        if (
            !is_string($state) ||
            !is_array($saved) ||
            !isset($saved['state'], $saved['created_at']) ||
            !is_string($saved['state']) ||
            !is_int($saved['created_at']) ||
            !hash_equals($saved['state'], $state)
        ) {
            Response::error('Invalid Google login request.', 403);
        }

        unset($_SESSION['google_oauth']);

        if (time() - $saved['created_at'] > 600) {
            Response::error('Google login expired. Please try again.', 403);
        }
    }

    // ? Exchange Google's authorization code and verify the identity.
    private function getGoogleIdentity(): array
    {
        if (isset($_GET['error'])) {
            Response::error('Google login was cancelled or denied.', 400);
        }

        // Authorization code
        $code = $_GET['code'] ?? null;

        if (!is_string($code) || $code === '') {
            Response::error('Missing Google authorization code.', 400);
        }

        try {
            $tokens = $this->googleAuth->exchangeCode($code);

            return $this->googleAuth->verifyIdToken(
                $tokens['id_token']
            );
        } catch (Throwable $exception) {
            Response::error(
                'Unable to verify Google login. Please try again.',
                401
            );

            exit;
        }
    }

    // ? Find the Google account or create a new LootLab account.
    private function findOrCreateGoogleUser(array $identity): array
    {
        $googleId = $identity['sub'];
        $email = $identity['email'];

        if (
            strlen($googleId) > 255 ||
            strlen($email) > 255 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            Response::error('Invalid Google account information.', 422);
        }

        $user = $this->user->findByGoogleId($googleId);

        if ($user === null) {
            // Existing accounts must not be linked automatically by email.
            if ($this->user->findByEmail($email) !== null) {
                Response::error(
                    'This email already has an account. Sign in using your existing method.',
                    409
                );
            }

            $name = $identity['name'] ?? 'Google user';

            if (!is_string($name) || trim($name) === '') {
                $name = 'Google user';
            }

            $name = mb_substr(trim($name), 0, 100);

            try {
                $userId = $this->user->createFromGoogle(
                    $name,
                    $email,
                    $googleId
                );
            } catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                    Response::error(
                        'An account was registered already. Please try signing in again.',
                        409
                    );
                }

                throw $exception;
            }

            $user = $this->user->findById($userId);
        }

        if ($user === null) {
            Response::error('Unable to load your account.', 500);
        }

        return $user;
    }

    // ? Preserve guest favorites and establish the login session.
    private function completeGoogleLogin(array $user): void
    {
        $favorite = new Favorite($this->database->getConnection());

        $favorite->mergeGuestIntoUser(
            session_id(),
            (int) $user['id']
        );

        $this->mergeGuestCart((int) $user['id']);

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];

        $this->redirect($this->appUrl . '/products');
    }

    //? Move the session guest cart into the user's database cart.
    //? Must run before session_regenerate_id(), while the guest cart is still in the session.
    //? Failures are logged and the guest cart is kept, so the merge can be retried.
    private function mergeGuestCart(int $userId): void
    {
        if (empty($_SESSION['guest_cart'])) {
            return;
        }

        try {
            $merge = new CartMergeService($this->database);

            $merge->mergeGuestCart($userId);

            //? Clear the guest cart only after a successful merge.
            unset($_SESSION['guest_cart']);
        } catch (Throwable $exception) {
            error_log(
                'Guest cart merge failed for user ' . $userId . ': '
                . $exception->getMessage()
            );
        }
    }
}
