<?php

class OrderController extends Controller
{
    private Order $order;

    public function __construct(array $config)
    {
        $database = new Database($config['database']);
        $conn = $database->getConnection();
        $this->order = new Order($conn);
    }

    public function index(): void
    {
        Response::success($this->order->getAll());
    }

    public function show(): void
    {
        $id = $this->positiveId($_GET['id'] ?? null);
        $order = $this->order->getById($id);

        if (!$order) {
            Response::error('Order not found.', 404);
        }

        Response::success($order);
    }

    public function store(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $userId = $this->requireUser();
        $data = $this->validateOrderData($input, true);

        $this->order->create(
            $userId,
            $data['promo_code_id'],
            $data['promo_discount'],
            $data['total_amount'],
            $data['status'],
            $data['shipping_address'],
            $data['phone']
        );

        Response::success(null, 201, 'Order created successfully.');
    }

    public function update(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $this->requireUser();
        $id = $this->positiveId($input['id'] ?? null);
        $data = $this->validateOrderData($input);

        if (!$this->order->getById($id)) {
            Response::error('Order not found.', 404);
        }

        $this->order->update(
            $id,
            $data['promo_discount'],
            $data['total_amount'],
            $data['status'],
            $data['shipping_address'],
            $data['phone']
        );

        Response::success(null, 200, 'Order updated successfully.');
    }

    public function destroy(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $this->requireUser();
        $id = $this->positiveId($input['id'] ?? null);

        if (!$this->order->getById($id)) {
            Response::error('Order not found.', 404);
        }

        $this->order->delete($id);

        Response::success(null, 200, 'Order deleted successfully.');
    }

    private function validateOrderData(array $input, bool $includePromoCodeId = false): array
    {
        $promoDiscountInput = $input['promo_discount'] ?? null;
        $totalAmountInput = $input['total_amount'] ?? null;
        $promoDiscount = is_scalar($promoDiscountInput)
            ? filter_var($promoDiscountInput, FILTER_VALIDATE_FLOAT)
            : false;
        $totalAmount = is_scalar($totalAmountInput)
            ? filter_var($totalAmountInput, FILTER_VALIDATE_FLOAT)
            : false;

        $status = $input['status'] ?? '';
        $shippingAddress = $input['shipping_address'] ?? '';
        $phone = $input['phone'] ?? '';

        if (
            $promoDiscount === false ||
            $totalAmount === false ||
            $promoDiscount < 0 ||
            $totalAmount < 0 ||
            !is_string($status) ||
            !is_string($shippingAddress) ||
            !is_string($phone) ||
            trim($status) === '' ||
            trim($shippingAddress) === '' ||
            trim($phone) === ''
        ) {
            Response::error('Invalid order data.', 422);
        }

        $promoCodeId = null;
        if ($includePromoCodeId) {
            $promoCodeIdInput = $input['promo_code_id'] ?? null;

            if ($promoCodeIdInput !== null && $promoCodeIdInput !== '') {
                $promoCodeId = $this->positiveId($promoCodeIdInput);
            }
        }

        return [
            'promo_code_id' => $promoCodeId,
            'promo_discount' => (float) $promoDiscount,
            'total_amount' => (float) $totalAmount,
            'status' => trim($status),
            'shipping_address' => trim($shippingAddress),
            'phone' => trim($phone),
        ];
    }

    private function positiveId(mixed $value): int
    {
        $id = (is_string($value) || is_int($value))
            ? filter_var(
                $value,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            )
            : false;

        if ($id === false) {
            Response::error('Invalid ID.', 422);
        }

        return $id;
    }

    private function requireUser(): int
    {
        if (!isset($_SESSION['user_id'])) {
            Response::error('Authentication is required.', 401);
        }

        return $this->positiveId($_SESSION['user_id']);
    }
}
