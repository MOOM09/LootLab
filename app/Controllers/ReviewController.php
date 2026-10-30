<?php
class ReviewController extends Controller
{
    private Review $review;
    private string $appUrl;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';

        $database = new Database($config['database']);

        $this->review = new Review($database->getConnection());
        $this->appUrl = $config['app_url'];
    }

    // ? Display a product's reviews
    
    public function index(): void
    {
        $productId = $this->positiveId($_GET['product_id'] ?? null);

        if (!$this->review->productExists($productId)) {
            http_response_code(404);
            exit('Product not found.');
        }

        // A CSRF token protects forms from unwanted external submissions.
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->view('reviews/index', [
            'reviews' => $this->review->findByProduct($productId),
            'productId' => $productId,
            'appUrl' => $this->appUrl,
            'csrfToken' => $_SESSION['csrf_token'],
        ]);
    }

    // ? Process the submitted review form
    public function store(): void
    {
        $userId = $this->requireUser();
        $this->checkCsrf();

        $productId = $this->positiveId($_POST['product_id'] ?? null);

        $rating = filter_var(
            $_POST['rating'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 5]]
        );

        $comment = $_POST['comment'] ?? '';

        if (!is_string($comment)) {
            http_response_code(422);
            exit('Invalid comment.');
        }

        $comment = trim($comment);

        if ($rating === false || $comment === '' || strlen($comment) > 5000) {
            http_response_code(422);
            exit('Provide a rating from 1 to 5 and a comment of up to 5000 bytes.');
        }

        if (!$this->review->productExists($productId)) {
            http_response_code(404);
            exit('Product not found.');
        }

        // The user ID comes from the session, not from the form.
        $this->review->create($userId, $productId, $rating, $comment);

        $this->redirect(
            $this->appUrl . '/reviews?product_id=' . $productId
        );
    }

    // ? Delete the logged-in user's review
    public function destroy(): void
    {
        $userId = $this->requireUser();
        $this->checkCsrf();

        $reviewId = $this->positiveId($_POST['review_id'] ?? null);
        $productId = $this->positiveId($_POST['product_id'] ?? null);

        $deleted = $this->review->deleteOwned(
            $reviewId,
            $userId,
            $productId
        );

        if (!$deleted) {
            http_response_code(404);
            exit('Review not found or does not belong to you.');
        }

        $this->redirect(
            $this->appUrl . '/reviews?product_id=' . $productId
        );
    }

    // ? Reject missing or invalid IDs
    private function positiveId(mixed $value): int
    {
        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false) {
            http_response_code(422);
            exit('Invalid ID.');
        }

        return $id;
    }

    // ? Require login until your shared AuthMiddleware is implemented
    private function requireUser(): int
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect($this->appUrl . '/login');
        }

        return $this->positiveId($_SESSION['user_id']);
    }

    // ? Check that the submitted form contains the session's token
    private function checkCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? null;
        $savedToken = $_SESSION['csrf_token'] ?? null;

        if (
            !is_string($token) ||
            !is_string($savedToken) ||
            !hash_equals($savedToken, $token)
        ) {
            http_response_code(403);
            exit('Invalid form token.');
        }
    }
}