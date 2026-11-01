<?php

require_once dirname(__DIR__) . '/Models/CartItem.php';
require_once dirname(__DIR__) . '/Models/Cart.php';

//? Moves the guest cart stored in $_SESSION['guest_cart'] into the user's database cart.
class CartMergeService
{
    private PDO $db;
    private Cart $cart;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
        $this->cart = new Cart($database);
    }

    //? Returns the number of items merged. Throws when the merge fails.
    public function mergeGuestCart(int $userId): int
    {
        $guestItems = $_SESSION['guest_cart'] ?? [];

        if (!is_array($guestItems) || $guestItems === []) {
            return 0;
        }

        $cartId = $this->cart->getOrCreateCartId($userId);

        //? Read the current quantities once, so stock can be checked per product and design.
        $existing = [];

        foreach ($this->cart->getItems($cartId) as $item) {
            $key = $this->itemKey(
                (int) $item['product_id'],
                $this->designId($item['custom_design_id'] ?? null)
            );

            $existing[$key] = (int) $item['quantity'];
        }



        $this->db->beginTransaction();

        try {
            $merged = 0;

            foreach ($guestItems as $guestItem) {
                $productId = (int) ($guestItem['product_id'] ?? 0);
                $quantity = (int) ($guestItem['quantity'] ?? 0);
                $designId = $this->designId($guestItem['design_id'] ?? null);

                if ($productId < 1 || $quantity < 1) {
                    continue;
                }

                //? Only active products can enter the user's cart.
                $product = $this->findActiveProduct($productId);

                if ($product === null) {
                    continue;
                }

                //? A custom design may only be attached by its owner.
                if ($designId !== null && !$this->ownsDesign($designId, $userId)) {
                    throw new RuntimeException('A custom design does not belong to this user.');
                }

                $key = $this->itemKey($productId, $designId);
                $inCart = $existing[$key] ?? 0;

                //? The guest quantity is capped so the combined total never exceeds stock.
                $quantity = min($quantity, (int) $product['stock_quantity'] - $inCart);

                if ($quantity < 1) {
                    continue;
                }


                $this->cart->mergeItem($cartId, $productId, $quantity, $designId);

                $existing[$key] = $inCart + $quantity;
                $merged++;
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }

        return $merged;
    }

    private function findActiveProduct(int $productId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, stock_quantity
             FROM products
             WHERE id = :product_id AND is_active = 1
             LIMIT 1'
        );

        $statement->execute(['product_id' => $productId]);

        $product = $statement->fetch(PDO::FETCH_ASSOC);

        return $product === false ? null : $product;
    }

    private function ownsDesign(int $designId, int $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT id FROM custom_designs WHERE id = :design_id AND user_id = :user_id LIMIT 1'
        );

        $statement->execute(['design_id' => $designId, 'user_id' => $userId]);

        return $statement->fetchColumn() !== false;
    }

    private function itemKey(int $productId, ?int $designId): string
    {
        return $productId . ':' . ($designId ?? 0);
    }

    private function designId(mixed $value): ?int
    {
        $designId = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        return $designId === false ? null : $designId;
    }
}
