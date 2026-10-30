<?php

class FavoriteController extends Controller
{
    private Favorite $favorite;
    private Product $product;
    private Cart $cart;
    private PDO $connection;
    private User $user;


    public function __construct(array $config)
    {
        $database = new Database($config['database']);

        $this->connection = $database->getConnection();

        $this->favorite = new Favorite($this->connection);
        $this->product = new Product($this->connection);
        $this->user = new User($this->connection);
        $this->cart = new Cart($database);
    }

    public function index(): void
    {
        $favoriteId = $this->resolveFavoriteId();

        $items = $favoriteId === null
            ? []
            : $this->favorite->getItems($favoriteId);

        Response::success([
            'items' => $items,
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $productId = filter_var(
            $input['product_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($productId === false) {
            Response::error('Invalid product ID.', 422);
        }

        $product = $this->product->getById($productId);

        if ($product === null) {
            Response::error('Product not found or inactive.', 404);
        }

        $favoriteId = $this->resolveFavoriteId(true);

        if ($this->favorite->contains($favoriteId, $productId)) {
            Response::error('Product is already in favorites.', 409);
        }

        try {
            $this->favorite->addItem($favoriteId, $productId);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                Response::error('Product is already in favorites.', 409);
            }

            throw $exception;
        }

        Response::success(null, 201, 'Product added to favorites.');
    }

    public function destroy(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $productId = filter_var(
            $input['product_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($productId === false) {
            Response::error('Invalid product ID.', 422);
        }

        $favoriteId = $this->resolveFavoriteId();

        if (
            $favoriteId === null ||
            !$this->favorite->removeItem($favoriteId, $productId)
        ) {
            Response::error('Product not found in your favorites.', 404);
        }

        Response::success(null, 200, 'Product removed from favorites.');
    }

    // ? Coordinate with the Cart model; remove the favorite only after adding succeeds.
    public function moveToCart(): void
    {
        // ? Read input and validate CSRF.
        // This route must use AuthMiddleware.
        $input = $this->requestData();
        $this->validateCsrf($input);

        $productId = filter_var(
            $input['product_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($productId === false) {
            Response::error('Invalid product ID.', 422);
        }

        // ? Find only the logged-in user's favorites collection.
        $favoriteId = $this->resolveFavoriteId();

        if (
            $favoriteId === null ||
            !$this->favorite->contains($favoriteId, $productId)
        ) {
            Response::error('Product not found in your favorites.', 404);
        }

        // ? Check that the product is available.
        $product = $this->product->getById($productId);

        if ($product === null) {
            Response::error('Product not found or inactive.', 404);
        }

        // ? Both changes must succeed together.
        $this->connection->beginTransaction();

        try {
            // ? Find or create the account's cart.
            $cartId = $this->cart->getOrCreateCartId(
                (int) $_SESSION['user_id']
            );

            // ? Count this product's quantity already in the cart.
            $existingQuantity = 0;

            foreach ($this->cart->getItems($cartId) as $item) {
                if ((int) $item['product_id'] === $productId) {
                    $existingQuantity += (int) $item['quantity'];
                }
            }

            // ? We are moving one additional unit.
            if ($existingQuantity + 1 > (int) $product['stock_quantity']) {
                throw new DomainException(
                    'Not enough stock to add another item.',
                    409
                );
            }

            // ? Remove the favorite inside the transaction.
            // If adding to the cart fails, rollback restores this favorite.
            // Checking the result also prevents moving the same favorite twice.
            if (!$this->favorite->removeItem($favoriteId, $productId)) {
                throw new DomainException(
                    'Product is no longer in your favorites.',
                    404
                );
            }

            // ? Add one unit using your teammate's Cart model.
            if (!$this->cart->addItem($cartId, $productId, 1)) {
                throw new RuntimeException('Unable to add item to cart.');
            }

            // ? Save the cart addition and favorite removal together.
            $this->connection->commit();
        } catch (DomainException $exception) {
            // ? Undo changes before sending an expected error.
            $this->connection->rollBack();

            Response::error(
                $exception->getMessage(),
                $exception->getCode()
            );
        } catch (Throwable $exception) {
            // ? Undo changes and pass unexpected errors to error handling.
            $this->connection->rollBack();

            throw $exception;
        }

        // ? Respond only after the transaction has finished.
        Response::success(null, 200, 'Product moved to cart.');
    }

    // ? Use the logged-in user ID or the server's current session ID for a guest.
    // TODO: Validate any logged-in account's current existence and active status.
    // Never accept user_id, session_id, or a collection ID from the request as ownership proof.
    private function resolveFavoriteId(bool $createIfMissing = false): ?int
    {

        if (isset($_SESSION['user_id'])) {
            $userId = filter_var(
                $_SESSION['user_id'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($userId === false) {
                unset($_SESSION['user_id']);
                Response::error('Please log in again.', 401);
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
            $favorite = $this->favorite->findByUser($userId);

            if ($favorite !== null) {
                return (int) $favorite['fav_id'];
            }

            if ($createIfMissing) {
                return $this->favorite->createForUser($userId);
            }

            return null;
        } else {
            $sessionId = session_id();

            $favorite = $this->favorite->findBySession($sessionId);

            if ($favorite !== null) {
                return (int) $favorite['fav_id'];
            }

            if ($createIfMissing) {
                return $this->favorite->createForSession($sessionId);
            }

            return null;
        }
    }
}
