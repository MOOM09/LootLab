<?php
    class con{
        private products $pro;
        function __construct(){
            $this->pro = new products;
        }
        function index():void 
        {
            $proud = $this->pro->getData();
        }
    }
    require_once "../Views/products.php";
?>