
<?php


class OrderController extends Controller
{
    private Order $order;
    private string $appUrl;

    public function __construct(array $config)
    {

        $database = new Database($config['database']);
        $this->order = new Order();
        $this->appUrl = $config['app_url'];
    }

    // Display all orders
    public function index(): void
    {
        $orders = $this->order->getAll();

        $this->view('orders/index', [
            'orders' => $orders,
            'appUrl' => $this->appUrl,
        ]);
    }

    // Display one order
    public function show(): void
    {
        $id = $this->positiveId($_GET['id'] ?? null);
        $order = $this->order->getById($id);

        if (!$order) {
            http_response_code(404);
            exit('Order not found.');
        }

        $this->view('orders/show', [
            'order' => $order,
            'appUrl' => $this->appUrl,
        ]);
    }

    // Create an order
    public function store(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $userId = $this->requireUser();

        $promoCodeId = $input['promo_code_id'] ?? null;

        if ($promoCodeId === '') {
            $promoCodeId = null;
        } elseif ($promoCodeId !== null) {
            $promoCodeId = $this->positiveId($promoCodeId);
        }

        $promoDiscount = filter_var(
            $input['promo_discount'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $totalAmount = filter_var(
            $input['total_amount'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $status = $input['status'] ?? '';
        $shippingAddress = $input['shipping_address'] ?? '';
        $shippingAddress = is_string($shippingAddress) ? trim($shippingAddress) : $shippingAddress;
        $phone = $input['phone'] ?? '';
        $phone = is_string($phone) ? trim($phone) : $phone;

        if (
            $promoDiscount === false ||
            $totalAmount === false ||
            $promoDiscount < 0 ||
            $totalAmount < 0 ||
            !is_string($status) ||
            !is_string($shippingAddress) ||
            !is_string($phone) ||
            $status === '' ||
            $shippingAddress === '' ||
            $phone === ''
        ) {
            http_response_code(422);
            exit('Invalid order data.');
        }

        $this->order->create(
            $userId,
            $promoCodeId,
            $promoDiscount,
            $totalAmount,
            $status,
            $shippingAddress,
            $phone
        );

        $this->redirect($this->appUrl . '/orders');
    }

    // Update an order
    public function update(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $this->requireUser();

        $id = $this->positiveId($input['id'] ?? null);

        $promoDiscount = filter_var(
            $input['promo_discount'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $totalAmount = filter_var(
            $input['total_amount'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $status = $input['status'] ?? '';
        $shippingAddress = $input['shipping_address'] ?? '';
        $shippingAddress = is_string($shippingAddress) ? trim($shippingAddress) : $shippingAddress;
        $phone = $input['phone'] ?? '';
        $phone = is_string($phone) ? trim($phone) : $phone;

        if (
            $promoDiscount === false ||
            $totalAmount === false ||
            $promoDiscount < 0 ||
            $totalAmount < 0 ||
            !is_string($status) ||
            !is_string($shippingAddress) ||
            !is_string($phone) ||
            $status === '' ||
            $shippingAddress === '' ||
            $phone === ''
        ) {
            http_response_code(422);
            exit('Invalid order data.');
        }

        if (!$this->order->getById($id)) {
            http_response_code(404);
            exit('Order not found.');
        }

        $this->order->update(
            $id,
            $promoDiscount,
            $totalAmount,
            $status,
            $shippingAddress,
            $phone
        );

        $this->redirect($this->appUrl . '/orders');
    }

    // Delete an order
    public function destroy(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $this->requireUser();

        $id = $this->positiveId($input['id'] ?? null);

        if (!$this->order->getById($id)) {
            http_response_code(404);
            exit('Order not found.');
        }

        $this->order->delete($id);

        $this->redirect($this->appUrl . '/orders');
    }

    private function positiveId(mixed $value): int
    {
        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false) {
            http_response_code(422);
            exit('Invalid ID.');
        }

        return $id;
    }

    private function requireUser(): int
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect($this->appUrl . '/login');
        }

        return $this->positiveId($_SESSION['user_id']);
    }
}
