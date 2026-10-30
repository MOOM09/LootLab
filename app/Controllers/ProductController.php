<?php

class ProductController extends Controller
{
    private Product $product;

    public function __construct()
    {
        $this->product = new Product();
    }

    public function index(): void
    {
        $id = $this->getOptionalProductId();

        if ($id !== null) {
            $this->showProduct($id);
            return;
        }

        $products = $this->product->getAll();

        $this->view('products/index', [
            'products' => $products,
        ]);
    }

    public function show(): void
    {
        $this->showProduct($this->getProductId());
    }

    private function showProduct(int $id): void
    {
        $product = $this->product->getById($id);

        if ($product === null) {
            http_response_code(404);
            $this->view('errors/404', [
                'message' => 'Product not found.',
            ]);
            return;
        }

        $this->view('products/show', [
            'product' => $product,
        ]);
    }

    public function store(): void
    {
        try {
            $data = $this->validateProduct($_POST);
            $this->product->create($data);

            $this->redirect('/products');
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $this->view('products/create', [
                'errors' => [$e->getMessage()],
                'data' => $_POST,
            ]);
        }
    }

    public function update(): void
    {
        $id = $this->getProductId();

        if ($this->product->getById($id) === null) {
            http_response_code(404);
            $this->view('errors/404', [
                'message' => 'Product not found.',
            ]);
            return;
        }

        try {
            $data = $this->validateProduct($_POST);
            $this->product->update($id, $data);

            $this->redirect('/products?id=' . $id);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $this->view('products/edit', [
                'errors' => [$e->getMessage()],
                'data' => $_POST,
                'productId' => $id,
            ]);
        }
    }

    public function destroy(): void
    {
        $id = $this->getProductId();

        if ($this->product->getById($id) === null) {
            http_response_code(404);
            $this->view('errors/404', [
                'message' => 'Product not found.',
            ]);
            return;
        }

        $this->product->delete($id);
        $this->redirect('/products');
    }

    private function getProductId(): int
    {
        $id = $this->getOptionalProductId();

        if ($id === null) {
            http_response_code(400);
            throw new InvalidArgumentException('A valid product ID is required.');
        }

        return $id;
    }

    private function getOptionalProductId(): ?int
    {
        $value = $_GET['id'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false) {
            http_response_code(400);
            throw new InvalidArgumentException('A valid product ID is required.');
        }

        return $id;
    }

    private function validateProduct(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $price = $data['price'] ?? null;
        $stockQuantity = $data['stock_quantity'] ?? null;

        if ($name === '') {
            throw new InvalidArgumentException('Product name is required.');
        }

        if (!is_numeric($price) || (float) $price < 0) {
            throw new InvalidArgumentException('A valid price is required.');
        }

        if (
            filter_var($stockQuantity, FILTER_VALIDATE_INT) === false
            || (int) $stockQuantity < 0
        ) {
            throw new InvalidArgumentException(
                'A valid non-negative stock quantity is required.'
            );
        }

        $subcategoryId = filter_var(
            $data['subcategory_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($subcategoryId === false) {
            throw new InvalidArgumentException('A valid subcategory is required.');
        }

        $discountPrice = $this->getDiscountPrice(
            $data['discount_price'] ?? null
        );

        if ($discountPrice !== null && $discountPrice >= (float) $price) {
            throw new InvalidArgumentException(
                'The discount price must be lower than the product price.'
            );
        }

        return [
            'name' => $name,
            'description' => $description,
            'price' => (float) $price,
            'stock_quantity' => (int) $stockQuantity,
            'subcategory_id' => $subcategoryId,
            'discount_price' => $discountPrice,
            'image' => trim((string) ($data['image'] ?? '')),
        ];
    }

    private function getDiscountPrice(mixed $discountPrice): ?float
    {
        if ($discountPrice === null || $discountPrice === '') {
            return null;
        }

        if (!is_numeric($discountPrice) || (float) $discountPrice < 0) {
            throw new InvalidArgumentException(
                'A valid discount price is required.'
            );
        }

        return (float) $discountPrice;
    }
}