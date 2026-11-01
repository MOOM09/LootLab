<?php

class OrderItem {
    private PDO $conn;

    public function __construct(PDO|Database $connection) {
        $this->conn = $connection instanceof Database
            ? $connection->getConnection()
            : $connection;
    }

    function getAll() {
        $sql = "SELECT * FROM order_items";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function getById(int $id) {
        $sql = "SELECT * FROM order_items WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    function create(int $order_id, int $product_id, int $quantity, float $unit_price) {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$order_id, $product_id, $quantity, $unit_price]);
    }

    function update(int $id, int $order_id, int $product_id, int $quantity, float $unit_price) {
        $sql = "UPDATE order_items
                SET order_id=?, product_id=?, quantity=?, unit_price=?
                WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$order_id, $product_id, $quantity, $unit_price, $id]);
    }

    function delete(int $id) {
        $sql = "DELETE FROM order_items WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
    }
}
