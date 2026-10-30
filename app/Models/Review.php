<?php
    require_once __DIR__ . "/../Core/Database.php";
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
?>