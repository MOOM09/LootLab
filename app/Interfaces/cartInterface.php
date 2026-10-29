<?php

interface CartInterface
{
    /** Returns the user's cart id, creating the cart if it doesn't exist. */
    public function getOrCreateCartId(int $userId): int;

    /** Returns all items in the cart with product name, price and line total. */
    public function getItems(int $cartId): array;

    /** Adds a product (optionally with a custom design). Merges quantity if the same product/design already exists. */
    public function addItem(int $cartId, int $productId, int $quantity = 1, ?int $designId = null): bool;

    /** Sets a new quantity. A quantity below 1 removes the item. */
    public function updateQuantity(int $cartId, int $itemId, int $quantity): bool;

    /** Removes one item from the cart. */
    public function removeItem(int $cartId, int $itemId): bool;

    /** Removes all items from the cart. */
    public function clear(int $cartId): bool;

    /** Returns ['subtotal' => float, 'discount' => float, 'total' => float]. */
    public function getTotals(int $cartId): array;

    /** Stores the promo code id and its calculated discount on the cart. */
    public function applyPromo(int $cartId, ?int $promoId, float $discount): bool;
}