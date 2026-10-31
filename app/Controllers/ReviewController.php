<?php
class ReviewController extends Controller
{
    private Review $review;

    public function __construct(array $config)
    {
        $database = new Database($config['database']);
        $this->review = new Review($database->getConnection());
    }

    public function index(): void
    {
        $productId = $this->positiveId($_GET['product_id'] ?? null);

        if (!$this->review->productExists($productId)) {
            Response::error('Product not found.', 404);
        }

        Response::success([
            'reviews' => $this->review->findByProduct($productId),
            'product_id' => $productId,
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $userId = $this->requireUser();
        $productId = $this->positiveId($input['product_id'] ?? null);
        $ratingInput = $input['rating'] ?? null;
        $rating = (is_string($ratingInput) || is_int($ratingInput))
            ? filter_var(
                $ratingInput,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 5]]
            )
            : false;
        $comment = $input['comment'] ?? '';

        if (!is_string($comment)) {
            Response::error('Invalid comment.', 422);
        }

        $comment = trim($comment);

        if ($rating === false || $comment === '' || strlen($comment) > 5000) {
            Response::error(
                'Provide a rating from 1 to 5 and a comment of up to 5000 bytes.',
                422
            );
        }

        if (!$this->review->productExists($productId)) {
            Response::error('Product not found.', 404);
        }

        $this->review->create($userId, $productId, $rating, $comment);

        Response::success(
            null,
            201,
            'Review created successfully.'
        );
    }

    public function destroy(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $userId = $this->requireUser();
        $reviewId = $this->positiveId($input['review_id'] ?? null);
        $productId = $this->positiveId($input['product_id'] ?? null);

        $deleted = $this->review->deleteOwned(
            $reviewId,
            $userId,
            $productId
        );

        if (!$deleted) {
            Response::error('Review not found or does not belong to you.', 404);
        }

        Response::success(
            null,
            200,
            'Review deleted successfully.'
        );
    }

    private function positiveId(mixed $value): int
    {
        $id = (is_string($value) || is_int($value))
            ? filter_var(
                $value,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            )
            : false;

        if ($id === false) {
            Response::error('Invalid ID.', 422);
        }

        return $id;
    }

    private function requireUser(): int
    {
        if (!isset($_SESSION['user_id'])) {
            Response::error('Authentication is required.', 401);
        }

        return $this->positiveId($_SESSION['user_id']);
    }
}
