
<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Services/CheckoutService.php';
require_once __DIR__ . '/../Services/StripeService.php';
require_once __DIR__ . '/../Models/Cart.php';
class CheckoutController extends Controller
{
    private CheckoutService $checkout;
    private StripeService $stripe;
    private PDO $db;
    private string $appUrl;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';

        $database = new Database($config['database']);

        $this->db = $database->getConnection();
        $this->checkout = new CheckoutService($database);
        $this->stripe = new StripeService();
        $this->appUrl = rtrim($config['app_url'], '/');
    }

    private function getUserId(): int
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect($this->appUrl . '/login');
        }

        return (int) $_SESSION['user_id'];
    }

    // Display checkout form.
    public function index(): void
    {
        $this->getUserId();

        $this->view('checkout/index', [
            'error' => null,
            'old' => []
        ]);
    }

    // Process COD order.
    public function placeOrder(): void
    {
        $userId = $this->getUserId();

        $address = trim($_POST['shipping_address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($address === '' || $phone === '') {
            $this->view('checkout/index', [
                'error' => 'Please fill in all required fields.',
                'old' => $_POST
            ]);
            return;
        }

        try {
            $orderId = $this->checkout->createOrder(
                $userId,
                $address,
                $phone
            );

            $_SESSION['last_order_id'] = $orderId;

            $this->redirect($this->appUrl . '/checkout/success');

        } catch (Throwable $e) {
            $this->view('checkout/index', [
                'error' => $e->getMessage(),
                'old' => $_POST
            ]);
        }
    }

    // Process Stripe payment.
    public function payWithStripe(): void
    {
        $userId = $this->getUserId();

        $address = trim($_POST['shipping_address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($address === '' || $phone === '') {
            $this->view('checkout/index', [
                'error' => 'Please fill in all required fields.',
                'old' => $_POST
            ]);
            return;
        }

        try {
            // Create order without clearing the cart.
            $orderId = $this->checkout->createStripeOrder(
                $userId,
                $address,
                $phone
            );

            // Get the order total.
            $statement = $this->db->prepare(
                'SELECT total_amount FROM orders
                 WHERE id = :order_id AND user_id = :user_id'
            );

            $statement->execute([
                'order_id' => $orderId,
                'user_id' => $userId
            ]);

            $order = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception('Order not found.');
            }

            // Convert JOD to fils.
            $amount = (int) round((float) $order['total_amount'] * 1000);

            if ($amount < 1) {
                throw new Exception('Invalid order amount.');
            }

            $session = $this->stripe->createCheckoutSession(
                [
                    [
                        'price_data' => [
                            'currency' => 'jod',
                            'product_data' => [
                                'name' => 'LootLab Order #' . $orderId
                            ],
                            'unit_amount' => $amount
                        ],
                        'quantity' => 1
                    ]
                ],
                $this->appUrl . '/checkout/stripe-success?session_id={CHECKOUT_SESSION_ID}',
                $this->appUrl . '/checkout?payment=cancelled',
                $orderId
            );

            // Remember the order for this browser session.
            $_SESSION['stripe_order_id'] = $orderId;

            $this->redirect($session->url);

        } catch (Throwable $e) {
            $this->view('checkout/index', [
                'error' => $e->getMessage(),
                'old' => $_POST
            ]);
        }
    }

    // Display successful COD order.
    public function success(): void
    {
        $this->getUserId();

        $orderId = $_SESSION['last_order_id'] ?? null;

        if (!$orderId) {
            $this->redirect($this->appUrl . '/checkout');
        }

        $this->view('checkout/success', [
            'orderId' => $orderId
        ]);
    }



    // Verify successful Stripe payment.
    public function stripeSuccess(): void
    {
        $userId = $this->getUserId();

        $sessionId = $_GET['session_id'] ?? '';
        $orderId = $_SESSION['stripe_order_id'] ?? null;

        if ($sessionId === '' || !$orderId) {
            $this->redirect($this->appUrl . '/checkout');
            return;
        }

        try {
            $session = $this->stripe->retrieveCheckoutSession($sessionId);

            // Verify payment and order ownership.
            if (
                $session->status !== 'complete' ||
                $session->payment_status !== 'paid' ||
                (string) $session->client_reference_id !== (string) $orderId
            ) {
                throw new Exception('Payment could not be verified.');
            }

            // Verify that the order belongs to this user.
            $statement = $this->db->prepare(
                'SELECT id FROM orders
                 WHERE id = :order_id AND user_id = :user_id'
            );

            $statement->execute([
                'order_id' => $orderId,
                'user_id' => $userId
            ]);

            if (!$statement->fetch()) {
                throw new Exception('Order not found.');
            }

            // Clear the cart after verified payment.
            $cart = new Cart(new Database(
                (require __DIR__ . '/../../config/config.php')['database']
            ));

            $cartId = $cart->getOrCreateCartId($userId);
            $cart->clear($cartId);

            $_SESSION['last_order_id'] = $orderId;
            unset($_SESSION['stripe_order_id']);

            $this->view('checkout/success', [
                'orderId' => $orderId
            ]);

        } catch (Throwable $e) {
            $this->view('checkout/index', [
                'error' => $e->getMessage(),
                'old' => []
            ]);
        }
    }
}
