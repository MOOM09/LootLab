
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
        $cartId = $this->cart->getOrCreateCartId($userId);

        $this->json(200, [
            'items' => $this->cart->getItems($cartId),
            'totals' => $this->cart->getTotals($cartId),
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
            $this->json(400, ['error' => 'Invalid product or quantity.']);
        }

        $cartId = $this->cart->getOrCreateCartId($userId);

        $success = $this->cart->addItem(
            $cartId,
            $productId,
            $quantity,
            $designId
        );

        $this->json($success ? 201 : 400, [
            'success' => $success,
        ]);
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

        $this->json(200, ['success' => $success]);
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

        $this->json(200, ['success' => $success]);
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
