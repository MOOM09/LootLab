<?php

class Cart implements CartInterface
{
    private PDO $db;
    private CartItem $items;
    private PromoCode $promos;

    public function __construct(Database $database)
    {
        $this->db     = $database->getConnection();
        $this->items  = new CartItem($database);
        $this->promos = new PromoCode($database);
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

        $added = $this->items->add($cartId, $productId, $quantity, $designId);

        //? The subtotal may have changed, so the stored discount is recalculated.
        if ($added) {
            $this->refreshPromo($cartId);
        }

        return $added;
    }

    public function updateQuantity(int $cartId, int $itemId, int $quantity): bool
    {
        if ($quantity < 1) {
            $removed = $this->items->remove($cartId, $itemId);

            if ($removed) {
                $this->refreshPromo($cartId);
            }

            return $removed;
        }

        $updated = $this->items->updateQuantity($cartId, $itemId, $quantity);

        if ($updated) {
            $this->refreshPromo($cartId);
        }

        return $updated;
    }

    public function removeItem(int $cartId, int $itemId): bool
    {
        $removed = $this->items->remove($cartId, $itemId);

        if ($removed) {
            $this->refreshPromo($cartId);
        }

        return $removed;
    }

    public function clear(int $cartId): bool
    {
        $cleared = $this->items->clear($cartId);

        //? An empty cart cannot hold a discount.
        if ($cleared) {
            $this->refreshPromo($cartId);
        }

        return $cleared;
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

    //? Stores a validated promo code and the discount calculated from the current subtotal.
    //? An invalid, expired or unknown code clears any promo currently applied.
    public function applyPromoByCode(int $cartId, string $code): bool
    {
        $promo = $this->promos->findValidByCode($code);

        if ($promo === null) {
            return $this->removePromo($cartId);
        }

        $subtotal = $this->subtotal($cartId);
        $discount = $this->promoDiscount($promo, $subtotal);

        return $this->applyPromo($cartId, (int) $promo['promo_id'], $discount);
    }

    public function applyPromo(int $cartId, ?int $promoId, float $discount): bool
    {
        $subtotal = $this->subtotal($cartId);

        $statement = $this->db->prepare(
            'UPDATE carts
             SET promo_code_id = :promo_id, promo_discount = :discount
             WHERE id = :cart_id'
        );

        return $statement->execute([
            'promo_id' => $promoId,
            'discount' => max(0, min($discount, $subtotal)),
            'cart_id'  => $cartId,
        ]);
    }

    //? Clears the applied promo code and its discount.
    public function removePromo(int $cartId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE carts
             SET promo_code_id = NULL, promo_discount = 0
             WHERE id = :cart_id'
        );

        return $statement->execute(['cart_id' => $cartId]);
    }

    //? Recalculates the stored discount from the current subtotal.
    //? The promo is cleared when it no longer exists, has expired or is no longer valid.
    public function refreshPromo(int $cartId): bool
    {
        $statement = $this->db->prepare(
            'SELECT promo_code_id
             FROM carts
             WHERE id = :cart_id'
        );

        $statement->execute(['cart_id' => $cartId]);

        $promoId = $statement->fetchColumn();

        if ($promoId === false) {
            return false;
        }

        if ($promoId === null) {
            return true;
        }

        $promoId = (int) $promoId;
        $promo = $this->promos->findValidByCode($this->promoCodeString($promoId));

        if ($promo === null) {
            return $this->removePromo($cartId);
        }

        $discount = $this->promoDiscount($promo, $this->subtotal($cartId));

        if ($discount <= 0) {
            return $this->removePromo($cartId);
        }

        return $this->applyPromo($cartId, $promoId, $discount);
    }

    //? Sum of quantity x price for every item currently in the cart.
    private function subtotal(int $cartId): float
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(cart_items.quantity * products.price), 0)
             FROM cart_items
             JOIN products ON products.id = cart_items.product_id
             WHERE cart_items.cart_id = :cart_id'
        );

        $statement->execute(['cart_id' => $cartId]);

        return (float) $statement->fetchColumn();
    }

    //? Percentage: subtotal x value / 100. Fixed: at most the subtotal.
    //? The result is never negative and never above the subtotal.
    private function promoDiscount(array $promo, float $subtotal): float
    {
        $value = (float) $promo['discount_value'];

        $discount = $promo['discount_type'] === 'percentage'
            ? $subtotal * $value / 100
            : $value;

        return round(max(0, min($discount, $subtotal)), 2);
    }

    //? Text of a stored promo code id, so it can be re-checked by PromoCode::findValidByCode().
    private function promoCodeString(int $promoId): string
    {
        $statement = $this->db->prepare(
            'SELECT code
             FROM promo_codes
             WHERE promo_id = :promo_id'
        );

        $statement->execute(['promo_id' => $promoId]);

        $code = $statement->fetchColumn();

        return $code === false ? '' : (string) $code;
    }

    //? Adds a quantity to a product already in this cart (new row if it is not).
    //? Used when merging a guest cart, where quantities must be added together.
    public function mergeItem(int $cartId, int $productId, int $quantity, ?int $designId = null): bool
    {
        if ($quantity < 1) {
            return false;
        }

        return $this->items->merge($cartId, $productId, $quantity, $designId);
    }
}