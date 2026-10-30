<?php
// Todo: Call validateCsrf CSRF[Cross-Site Request Forgery] in all actions with create, update, delete, plus login and logout.
// Todo: call csrfToken() whenever you display a form that needs CSRF protection.
class Controller
{
    protected function requestData(): array
    {
        $contentType = strtolower(trim(
            explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]
        ));

        if ($contentType === 'application/json') {
            try {
                $data = json_decode(
                    file_get_contents('php://input'),
                    false,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (JsonException $exception) {
                Response::error('Invalid JSON.', 400);
            }

            if (!$data instanceof stdClass) {
                Response::error('JSON body must be an object.', 400);
            }

            return (array) $data;
        }

        return $_POST;
    }
    //? Creat csrfToken
    protected function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token']))
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return $_SESSION['csrf_token'];
    }

    protected function validateCsrf(array $data): void
    {
        $token = $data['csrf_token'] ?? null;
        $savedToken = $_SESSION['csrf_token'] ?? null;

        if (
            !is_string($token) ||
            !is_string($savedToken) ||
            !hash_equals($savedToken, $token)
        ) {
            Response::error('Invalid form token.', 403);
        }
    }

    protected function view(string $view, array $data = []): void
    {
        // Create variables from the keys in array
        extract($data, EXTR_SKIP);

        require_once __DIR__ . '/../Views/' . $view . '.php';
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}


// ? How to use?
//===> class AuthController extends Controller {}

//? data may contain the errors you want to send to that view
// $this->view('auth/login', [
//     'error' => 'Wrong email or password.',
// ]);

//? Why redirect 
// After successful login → redirect to the home page.
// After logout → redirect to the login page.
// If a guest opens a protected page → redirect to login.
//* Example
// $_SESSION['user_id'] = $user['id'];
// $this->redirect('/projects/LootLab/public/');