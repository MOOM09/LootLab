<?php
    require_once __DIR__ . "/../Core/Database.php";
    class Review{
        private PDO $conn;
        function __construct(){
            $db = new Database();
            $this->conn = $db->connect();
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