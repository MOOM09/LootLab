<?php

class CartItem
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function findByCart(int $cartId): array
    {
        $statement = $this->db->prepare(
            'SELECT cart_items.id,
                    cart_items.product_id,
                    cart_items.custom_design_id,
                    cart_items.quantity,
                    products.name,
                    products.image,
                    products.price,
                    (cart_items.quantity * products.price) AS line_total
             FROM cart_items
             JOIN products ON products.id = cart_items.product_id
             WHERE cart_items.cart_id = :cart_id
             ORDER BY cart_items.created_at'
        );
        $statement->execute(['cart_id' => $cartId]);

        $items = $statement->fetchAll();
        require_once __DIR__ . '/../Services/ContentTranslationService.php';
        $products = array_map(static fn(array $item): array => [
            'id' => (int)$item['product_id'], 'name' => (string)$item['name']
        ], $items);
        $localized = (new ContentTranslationService($this->db))->localizeProducts($products);
        foreach ($localized as $index => $product) $items[$index]['name'] = $product['name'];
        return $items;
    }

    public function add(int $cartId, int $productId, int $quantity, ?int $designId): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO cart_items (cart_id, product_id, quantity, custom_design_id)
             VALUES (:cart_id, :product_id, :quantity, :design_id)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
        );

        return $statement->execute([
            'cart_id'    => $cartId,
            'product_id' => $productId,
            'quantity'   => $quantity,
            'design_id' => $designId,
        ]);
    }

    //? Adds to an existing quantity instead of setting it (guest cart merge).
    //? The stored column is written as cart_items.quantity + the incoming quantity.
    public function merge(int $cartId, int $productId, int $quantity, ?int $designId): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO cart_items (cart_id, product_id, quantity, custom_design_id)
             VALUES (:cart_id, :product_id, :quantity, :design_id)
             ON DUPLICATE KEY UPDATE quantity = cart_items.quantity + :add_quantity'
        );

        return $statement->execute([
            'cart_id'       => $cartId,
            'product_id'    => $productId,
            'quantity'      => $quantity,
            'design_id'     => $designId,
            'add_quantity'  => $quantity,
        ]);
    }

    public function updateQuantity(int $cartId, int $itemId, int $quantity): bool
    {
        $statement = $this->db->prepare(
            'UPDATE cart_items
             SET quantity = :quantity
             WHERE id = :item_id AND cart_id = :cart_id'
        );

        $statement->execute([
            'quantity' => $quantity,
            'item_id'  => $itemId,
            'cart_id'  => $cartId,
        ]);

        if ($statement->rowCount() > 0) {
            return true;
        }

        $exists = $this->db->prepare(
            'SELECT 1 FROM cart_items WHERE id = :item_id AND cart_id = :cart_id'
        );
        $exists->execute([
            'item_id' => $itemId,
            'cart_id' => $cartId,
        ]);

        return $exists->fetchColumn() !== false;
    }

    public function remove(int $cartId, int $itemId): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM cart_items
             WHERE id = :item_id AND cart_id = :cart_id'
        );

        $statement->execute([
            'item_id' => $itemId,
            'cart_id' => $cartId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function clear(int $cartId): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM cart_items WHERE cart_id = :cart_id'
        );

        return $statement->execute(['cart_id' => $cartId]);
    }
}