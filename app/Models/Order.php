<?php

class Order {
    private PDO $conn;

    public function __construct(Database $database) {
        $this->conn = $database->getConnection();
    }

    function getAll() {
        $sql = "SELECT * FROM orders";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
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
?>     