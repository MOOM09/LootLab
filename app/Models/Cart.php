<?php

class Cart implements CartInterface
{
    private PDO $db;
    private CartItem $items;

    public function __construct(Database $database)
    {
        $this->db    = $database->getConnection();
        $this->items = new CartItem($database);
    }

    public function getOrCreateCartId(int $userId): int
    {
        //? this is to create the cart id or to get the existing one
        //? and setting id the value of the last inserted id
        $statement = $this->db->prepare(
            'INSERT INTO carts (user_id) VALUES (:user_id)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $this->db->lastInsertId();
    }

    public function getItems(int $cartId): array
    {
        return $this->items->findByCart($cartId);
    }

    public function addItem(int $cartId, int $productId, int $quantity = 1, ?int $designId = null): bool
    {
        if ($quantity < 1) {
            return false;
        }

        return $this->items->add($cartId, $productId, $quantity, $designId);
    }

    public function updateQuantity(int $cartId, int $itemId, int $quantity): bool
    {
        if ($quantity < 1) {
            return $this->items->remove($cartId, $itemId);
        }

        return $this->items->updateQuantity($cartId, $itemId, $quantity);
    }

    public function removeItem(int $cartId, int $itemId): bool
    {
        return $this->items->remove($cartId, $itemId);
    }

    public function clear(int $cartId): bool
    {
        return $this->items->clear($cartId);
    }

    public function getTotals(int $cartId): array
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(cart_items.quantity * products.price), 0) AS subtotal,
                    carts.promo_discount
             FROM carts
             LEFT JOIN cart_items ON cart_items.cart_id = carts.id
             LEFT JOIN products   ON products.id = cart_items.product_id
             WHERE carts.id = :cart_id
             GROUP BY carts.id, carts.promo_discount'
        );
        $statement->execute(['cart_id' => $cartId]);

        $row = $statement->fetch() ?: ['subtotal' => 0, 'promo_discount' => 0];

        $subtotal = (float) $row['subtotal'];
        $discount = min((float) $row['promo_discount'], $subtotal);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total'    => $subtotal - $discount,
        ];
    }

    public function applyPromo(int $cartId, ?int $promoId, float $discount): bool
    {
        $statement = $this->db->prepare(
            'UPDATE carts
             SET promo_code_id = :promo_id, promo_discount = :discount
             WHERE id = :cart_id'
        );

        return $statement->execute([
            'promo_id' => $promoId,
            'discount' => max(0, $discount),
            'cart_id'  => $cartId,
        ]);
    }
}