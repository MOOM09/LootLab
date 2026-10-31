<?php

require_once dirname(__DIR__) . '/Models/Product.php';

class AiProductCatalog
{
    private Product $product;

    public function __construct(PDO $connection)
    {
        $this->product = new Product($connection);
    }

    public function getProducts(): array
    {
        return array_map(
            static function (array $product): array {
                return [
                    'id' => (int) $product['id'],
                    'name' => $product['name'],
                    'description' => $product['description'] ?? '',
                    'category' => $product['category_name'],
                    'subcategory' => $product['subcategory_name'],
                    'price' => (float) (
                        $product['discount_price'] ?? $product['price']
                    ),
                    'stock_quantity' => (int) $product['stock_quantity'],
                ];
            },
            $this->product->getAll()
        );
    }
}