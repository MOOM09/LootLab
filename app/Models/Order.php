<?php

class Order {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    function getAll() {
        $sql = "SELECT * FROM orders";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getForUser(int $userId): array
    {
        $stmt = $this->conn->prepare('SELECT id, total_amount, status, promo_discount, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdForUser(int $id, int $userId): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function getItemsForUserOrder(int $id, int $userId): array
    {
        $stmt = $this->conn->prepare('SELECT oi.product_id, p.name, oi.quantity, oi.unit_price FROM order_items oi INNER JOIN products p ON p.id = oi.product_id INNER JOIN orders o ON o.id = oi.order_id WHERE o.id = ? AND o.user_id = ? ORDER BY oi.id');
        $stmt->execute([$id, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getById(int $id) {
        $sql = "SELECT * FROM orders WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    function create(
        int $user_id,
        ?int $promo_code_id,
        float $promo_discount,
        float $total_amount,
        string $status,
        string $shipping_address,
        string $phone
    ) {
        $sql = "INSERT INTO orders
                (user_id, promo_code_id, promo_discount, total_amount, status, shipping_address, phone)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $user_id,
            $promo_code_id,
            $promo_discount,
            $total_amount,
            $status,
            $shipping_address,
            $phone
        ]);
    }

    function update(
        int $id,
        float $promo_discount,
        float $total_amount,
        string $status,
        string $shipping_address,
        string $phone
    ) {
        $sql = "UPDATE orders
                SET promo_discount=?, total_amount=?, status=?, shipping_address=?, phone=?
                WHERE id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $promo_discount,
            $total_amount,
            $status,
            $shipping_address,
            $phone,
            $id
        ]);
    }

    function delete(int $id) {
        $sql = "DELETE FROM orders WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
    }
}
