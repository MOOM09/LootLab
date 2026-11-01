<?php

class CartController extends Controller
{
    private CartInterface $cart;
    private PDO $db;

    public function __construct(array $config)
    {
        // Connect to the database
        $database = new Database($config['database']);

        $this->db = $database->getConnection();

        // Create the Cart object
        $this->cart = new Cart($database);
    }

    //? Returns the logged-in user ID, or null for a guest.
    private function getUserId(): ?int
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $userId = filter_var(
            $_SESSION['user_id'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($userId === false) {
            unset($_SESSION['user_id']);
            return null;
        }

        return $userId;
    }

    //? Get the user's cart, or the guest cart kept in the session.
    public function index(): void
    {
        $userId = $this->getUserId();

if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html')) {
            $this->view('cart/index');
            return;
        }

        if ($userId === null) {
            Response::success([
                'items' => $this->guestItems(),
                'totals' => $this->guestTotals(),
                'csrf_token' => $this->csrfToken(),
            ]);
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

        if ($designId !== null && $designId < 1) {
            $designId = null;
        }

        $userId = $this->getUserId();

        if ($userId === null) {
            $this->addGuestItem($productId, $quantity, $designId);
            return;
        }

        //? A custom design may only be attached by its owner.
        if ($designId !== null && !$this->ownsDesign($designId, $userId)) {
            $this->json(403, ['error' => 'That custom design does not belong to you.']);
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

        //? Guest item ids are strings like "12:3"; database item ids are integers.
        $itemId = $input['item_id'] ?? null;
        $quantity = (int) ($input['quantity'] ?? 0);

        if ($quantity < 1) {
            $this->json(400, ['error' => 'Invalid item or quantity.']);
        }

        $userId = $this->getUserId();

        if ($userId === null) {
            if (!is_string($itemId) || $itemId === '') {
                $this->json(400, ['error' => 'Invalid item or quantity.']);
            }

            $this->updateGuestItem($itemId, $quantity);
            return;
        }

        $itemId = (int) $itemId;

        if ($itemId < 1) {
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

        //? Guest item ids are strings like "12:3"; database item ids are integers.
        $itemId = $input['item_id'] ?? null;

        $userId = $this->getUserId();

        if ($userId === null) {
            if (!is_string($itemId) || $itemId === '') {
                $this->json(400, ['error' => 'Invalid item ID.']);
            }

            $this->removeGuestItem($itemId);
            return;
        }

        $itemId = (int) $itemId;

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

        if ($userId === null) {
            unset($_SESSION['guest_cart']);

            $this->json(200, ['success' => true]);
        }

        $cartId = $this->cart->getOrCreateCartId($userId);
        $success = $this->cart->clear($cartId);

        $this->json(200, ['success' => $success]);
    }

    //? Adds to the session guest cart, capped by stock.
    private function addGuestItem(int $productId, int $quantity, ?int $designId): void
    {
        $product = $this->findActiveProduct($productId);

        if ($product === null) {
            $this->json(404, ['error' => 'Product not found or unavailable.']);
        }

        $guestCart = $this->guestCart();
        $key = $this->guestKey($productId, $designId);

        $current = $guestCart[$key]['quantity'] ?? 0;
        $allowed = (int) $product['stock_quantity'] - $current;

        if ($allowed < 1) {
            $this->json(409, ['error' => 'Not enough stock available.']);
        }

        $quantity = min($quantity, $allowed);

        $guestCart[$key] = [
            'product_id' => $productId,
            'quantity'   => $current + $quantity,
            'design_id'  => $designId,
        ];

        $_SESSION['guest_cart'] = $guestCart;

        $this->json(201, [
            'success' => true,
            'item_id' => $key,
            'quantity' => $guestCart[$key]['quantity'],
        ]);
    }

    private function updateGuestItem(string $itemId, int $quantity): void
    {
        $guestCart = $this->guestCart();

        if (!isset($guestCart[$itemId])) {
            $this->json(404, ['error' => 'Cart item not found.']);
        }

        $product = $this->findActiveProduct((int) $guestCart[$itemId]['product_id']);

        if ($product === null) {
            $this->json(404, ['error' => 'Product not found or unavailable.']);
        }

        if ($quantity > (int) $product['stock_quantity']) {
            $this->json(409, ['error' => 'Not enough stock available.']);
        }

        $guestCart[$itemId]['quantity'] = $quantity;

        $_SESSION['guest_cart'] = $guestCart;

        $this->json(200, ['success' => true]);
    }

    private function removeGuestItem(string $itemId): void
    {
        $guestCart = $this->guestCart();

        if (!isset($guestCart[$itemId])) {
            $this->json(404, ['error' => 'Cart item not found.']);
        }

        unset($guestCart[$itemId]);

        $_SESSION['guest_cart'] = $guestCart;

        $this->json(200, ['success' => true]);
    }

    //? Guest items as an array, shaped like Cart::getItems() output.
    private function guestItems(): array
    {
        $items = [];

        foreach ($this->guestCart() as $key => $guestItem) {
            $product = $this->findActiveProduct((int) $guestItem['product_id']);

            if ($product === null) {
                continue;
            }

            $quantity = (int) $guestItem['quantity'];

            $items[] = [
                'id'               => $key,
                'product_id'       => (int) $product['id'],
                'quantity'         => $quantity,
                'custom_design_id' => $guestItem['design_id'] ?? null,
                'name'             => $product['name'],
                'price'            => (string) $product['price'],
                'line_total'       => (string) round((float) $product['price'] * $quantity, 2),
            ];
        }

        return $items;
    }

    //? Guest totals, shaped like Cart::getTotals() output (no promo for guests).
    private function guestTotals(): array
    {
        $subtotal = 0.0;

        foreach ($this->guestItems() as $item) {
            $subtotal += (float) $item['line_total'];
        }

        return [
            'subtotal' => $subtotal,
            'discount' => 0.0,
            'total'    => $subtotal,
        ];
    }

    private function guestCart(): array
    {
        $guestCart = $_SESSION['guest_cart'] ?? [];

        return is_array($guestCart) ? $guestCart : [];
    }

    private function guestKey(int $productId, ?int $designId): string
    {
        return $productId . ':' . ($designId ?? 0);
    }

    private function ownsDesign(int $designId, int $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT id FROM custom_designs WHERE id = :design_id AND user_id = :user_id LIMIT 1'
        );

        $statement->execute(['design_id' => $designId, 'user_id' => $userId]);

        return $statement->fetchColumn() !== false;
    }

    private function findActiveProduct(int $productId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, price, stock_quantity
             FROM products
             WHERE id = :product_id AND is_active = 1
             LIMIT 1'
        );

        $statement->execute(['product_id' => $productId]);

        $product = $statement->fetch(PDO::FETCH_ASSOC);

        return $product === false ? null : $product;
    }
}
