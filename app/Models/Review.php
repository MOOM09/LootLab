<?php
    class Review{
        private PDO $conn;
        function __construct(PDO $conn){
            // $conn = new Database();
            // $this->conn = $conn->connect();
            $this->conn = $conn;
        }
        function getAll(){
            $sql = "SELECT * FROM reviews";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        function getById(int $id){
            $sql = "SELECT * FROM reviews WHERE id=?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        }
        public function productExists(int $productId): bool
        {
            $sql = "SELECT id FROM products WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$productId]);

            return $stmt->fetchColumn() !== false;
        }

        public function findByProduct(int $productId): array
        {
            $sql = "SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$productId]);

            return $stmt->fetchAll();
        }

        public function deleteOwned(int $reviewId, int $userId, int $productId): bool
        {
            $sql = "DELETE FROM reviews WHERE id = ? AND user_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$reviewId, $userId, $productId]);

            return $stmt->rowCount() > 0;
        }

        public function hasPurchased(int $userId, int $productId): bool
        {
            $sql = "SELECT 1 FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.status IN ('completed', 'shipped') LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $productId]);
            return $stmt->fetchColumn() !== false;
        }

        public function isInVerifiedStripeOrder(int $userId, int $productId, int $orderId): bool
        {
            if ($orderId <= 0) return false;
            $stmt = $this->conn->prepare('SELECT 1 FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.id=? AND o.user_id=? AND oi.product_id=? LIMIT 1');
            $stmt->execute([$orderId, $userId, $productId]);
            return $stmt->fetchColumn() !== false;
        }

        function create(int $user_id ,int $product_id,int $rating,string $comment){
            $sql = "INSERT INTO reviews (user_id,product_id,rating,comment) 
                    VALUES(?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$user_id,$product_id,$rating,$comment]);
        }
        function update(int $id ,int $rating,string $comment){
                $sql = ("UPDATE reviews SET rating=?,comment=? WHERE id=? ");
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$rating,$comment,$id]);
        }
        function delete(int $id){
                $sql =("DELETE FROM reviews WHERE id=?");
                $stmt = $this->conn->prepare($sql);
                 $stmt->execute([$id]);
        }
    }
