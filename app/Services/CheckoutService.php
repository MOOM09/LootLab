
<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Interfaces/cartInterface.php';
require_once __DIR__ . '/../Models/CartItem.php';
require_once __DIR__ . '/../Models/Cart.php';

class CheckoutService
{
    private PDO $db;
    private Cart $cart;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
        $this->cart = new Cart($database);
    }

    // Existing COD checkout: create order and clear cart.
    public function createOrder(
        int $userId,
        string $shippingAddress,
        string $phone
    ): int {
        return $this->createOrderRecord(
            $userId,
            $shippingAddress,
            $phone,
            true
        );
    }

    // Stripe checkout: create order but keep cart.
    public function createStripeOrder(
        int $userId,
        string $shippingAddress,
        string $phone
    ): int {
        return $this->createOrderRecord(
            $userId,
            $shippingAddress,
            $phone,
            false
        );
    }

    private function createOrderRecord(
        int $userId,
        string $shippingAddress,
        string $phone,
        bool $clearCart
    ): int {

        $cartId = $this->cart->getOrCreateCartId($userId);

        // Verify cart ownership.
        $statement = $this->db->prepare(
            'SELECT id FROM carts
             WHERE id = :cart_id AND user_id = :user_id'
        );

        $statement->execute([
            'cart_id' => $cartId,
            'user_id' => $userId
        ]);

        if (!$statement->fetch()) {
            throw new Exception('Cart not found.');
        }

        $items = $this->cart->getItems($cartId);

        if (empty($items)) {
            throw new Exception('Your cart is empty.');
        }

        $this->db->beginTransaction();

        try {
            $total = 0;
            $validatedItems = [];

            // Validate products and calculate total.
            foreach ($items as $item) {

                $statement = $this->db->prepare(
                    'SELECT price, stock_quantity, is_active
                     FROM products
                     WHERE id = :product_id
                     FOR UPDATE'
                );

                $statement->execute([
                    'product_id' => $item['product_id']
                ]);

                $product = $statement->fetch(PDO::FETCH_ASSOC);

                if (!$product || !$product['is_active']) {
                    throw new Exception('A product is unavailable.');
                }

                if ($product['stock_quantity'] < $item['quantity']) {
                    throw new Exception('Insufficient product stock.');
                }

                $price = (float) $product['price'];

                $total += $price * $item['quantity'];

                // Save the validated price for the order item.
                $validatedItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'custom_design_id' => $item['custom_design_id'] ?? null
                ];
            }

            // Create order.
            $statement = $this->db->prepare(
                'INSERT INTO orders
                 (user_id, total_amount, status, shipping_address, phone)
                 VALUES
                 (:user_id, :total, :status, :address, :phone)'
            );

            $statement->execute([
                'user_id' => $userId,
                'total' => round($total, 2),
                'status' => 'pending',
                'address' => $shippingAddress,
                'phone' => $phone
            ]);

            $orderId = (int) $this->db->lastInsertId();

            // Insert order items.
            $statement = $this->db->prepare(
                'INSERT INTO order_items
                 (order_id, product_id, quantity, unit_price, custom_design_id)
                 VALUES
                 (:order_id, :product_id, :quantity, :price, :design_id)'
            );

            foreach ($validatedItems as $item) {
                $statement->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'design_id' => $item['custom_design_id']
                ]);
            }

            $this->db->commit();

            // Only clear the cart when requested.
            if ($clearCart) {
                $this->cart->clear($cartId);
            }

            return $orderId;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
