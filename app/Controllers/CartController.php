
<?php


class CartController extends Controller
{
    private CartInterface $cart;

    public function __construct(array $config)
    {
        // Connect to the database
        $database = new Database($config['database']);

        // Create the Cart object
        $this->cart = new Cart($database);
    }

    // Get the logged-in user's ID
    private function getUserId(): int
    {
        if (empty($_SESSION['user_id'])) {
            $this->json(401, ['error' => 'Please log in first.']);
        }

        return (int) $_SESSION['user_id'];
    }

    // Get the user's cart
    public function index(): void
    {
        $userId = $this->getUserId();

        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html')) {
            $this->view('cart/index');
            return;
        }

        $cartId = $this->cart->getOrCreateCartId($userId);

        Response::success([
            'items' => $this->cart->getItems($cartId),
            'totals' => $this->cart->getTotals($cartId),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    // Add a product to the cart
    public function add(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $userId = $this->getUserId();

        $productId = (int) ($input['product_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 1);
        $designId = isset($input['custom_design_id'])
            ? (int) $input['custom_design_id']
            : null;

        if ($productId < 1 || $quantity < 1) {
            Response::error('Invalid product or quantity.', 400);
        }

        if ($designId !== null) {
            Response::error(
                'Custom designs are not supported by the current cart schema.',
                422
            );
        }

        $cartId = $this->cart->getOrCreateCartId($userId);

        if (!$this->cart->addItem(
            $cartId,
            $productId,
            $quantity,
            null
        )) {
            Response::error('The product could not be added to your cart.', 400);
        }

        Response::success(null, 201, 'Product added to cart.');
    }

    // Update a cart item's quantity
    public function update(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $userId = $this->getUserId();

        $itemId = (int) ($input['item_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 0);

        if ($itemId < 1 || $quantity < 1) {
            $this->json(400, ['error' => 'Invalid item or quantity.']);
        }

        $cartId = $this->cart->getOrCreateCartId($userId);

        $success = $this->cart->updateQuantity(
            $cartId,
            $itemId,
            $quantity
        );

        if (!$success) {
            Response::error('Cart item not found.', 404);
        }

        Response::success(null, 200, 'Cart quantity updated.');
    }

    // Remove one item from the cart
    public function remove(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $userId = $this->getUserId();

        $itemId = (int) ($input['item_id'] ?? 0);

        if ($itemId < 1) {
            $this->json(400, ['error' => 'Invalid item ID.']);
        }

        $cartId = $this->cart->getOrCreateCartId($userId);
        $success = $this->cart->removeItem($cartId, $itemId);

        if (!$success) {
            Response::error('Cart item not found.', 404);
        }

        Response::success(null, 200, 'Cart item removed.');
    }

    // Remove all items from the cart
    public function clear(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $userId = $this->getUserId();
        $cartId = $this->cart->getOrCreateCartId($userId);

        $success = $this->cart->clear($cartId);

        $this->json(200, ['success' => $success]);
    }
}
