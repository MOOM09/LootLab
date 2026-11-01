<?php
    class products{
            private PDO $conn; 
    function __construct(PDO $conn){
            $this->conn= $conn;
        }
            function getData(){
        $sql = "SELECT * FROM products";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    }
?>