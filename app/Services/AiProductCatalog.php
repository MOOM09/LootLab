<?php

class AiProductCatalog
{
    public function getProducts(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'مج ناروتو باللبس البرتقالي الأساسي',
                'price' => 7,
                'stock_quantity' => 5
            ],
            [
                'id' => 2,
                'name' => 'بوستر ناروتو وساسكي',
                'price' => 4,
                'stock_quantity' => 10
            ],
            [
                'id' => 3,
                'name' => 'بلوزة إيتاتشي سوداء',
                'price' => 15,
                'stock_quantity' => 0
            ],
            [
                'id' => 4,
                'name' => 'ميدالية Batman من عالم DC',
                'price' => 3,
                'stock_quantity' => 8
            ],
            [
                'id' => 5,
                'name' => 'مج Spider-Man من عالم Marvel',
                'price' => 8,
                'stock_quantity' => 6
            ],
            [
                'id' => 6,
                'name' => 'بوستر God of War لشخصية Kratos',
                'price' => 5,
                'stock_quantity' => 4
            ]
        ];
    }
}