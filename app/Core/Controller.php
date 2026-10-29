<?php
class Controller {
    protected function view (string $view, array $data = []) : void
    {
        extract($data, EXTR_SKIP);

        require_once __DIR__ . '/../Views/' . $view . '.php';   
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

        //* method JSON BY ASEM
        protected function json(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
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