<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Interfaces/cartInterface.php';
require_once __DIR__ . '/../Models/CartItem.php';
require_once __DIR__ . '/../Models/Cart.php';
require_once __DIR__ . '/../Services/CheckoutService.php';
require_once __DIR__ . '/../Services/StripeService.php';
class CheckoutController extends Controller
{
    private CheckoutService $checkout;
    private StripeService $stripe;
    private PDO $db;
    private string $appUrl;

    //? Sandbox only: the Stripe account used for testing does not support JOD,
    //? so order totals (stored in JOD) are converted to USD for Stripe payments.
    //? Stored order totals are never changed.
    private const STRIPE_CURRENCY = 'usd';
    private const DEFAULT_JOD_TO_USD_RATE = 1.41;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';

        $database = new Database($config['database']);

        $this->db = $database->getConnection();
        $this->checkout = new CheckoutService($database);
        $this->stripe = new StripeService();
        $this->appUrl = rtrim($config['app_url'], '/');
    }

    //? Fixed sandbox exchange rate (1 JOD = N USD), configurable through .env.
    private function jodToUsdRate(): float
    {
        $rate = (float) ($_ENV['STRIPE_JOD_TO_USD_RATE'] ?? self::DEFAULT_JOD_TO_USD_RATE);

        return $rate > 0 ? $rate : self::DEFAULT_JOD_TO_USD_RATE;
    }

    //? Converts a stored JOD total into the smallest unit of the Stripe currency (USD cents).
    private function jodToStripeAmount(float $jodTotal): int
    {
        return (int) round($jodTotal * $this->jodToUsdRate() * 100);
    }

    //? Human readable amount for the Stripe line item, e.g. "12.64 USD (approx. 8.96 JOD)".
    private function stripeAmountLabel(float $jodTotal, int $amount): string
    {
        return strtoupper(self::STRIPE_CURRENCY)
            . ' ' . number_format($amount / 100, 2, '.', '')
            . ' (approx. ' . number_format($jodTotal, 3, '.', '') . ' JOD)';
    }

    //? Re-render the checkout form after an error, keeping the action and a valid CSRF token.
    private function renderCheckoutError(string $message, array $old = []): void
    {
        $this->view('checkout/index', [
            'error' => $message,
            'old' => $old,
            'appUrl' => $this->appUrl,
            'csrfToken' => $this->csrfToken(),
        ]);
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

        //? Stripe sends the customer back here when they cancel on the hosted page.
        $notice = ($_GET['payment'] ?? '') === 'cancelled'
            ? 'Your card payment was cancelled. Your order was not charged.'
            : null;

        $this->view('checkout/index', [
            'error' => null,
            'notice' => $notice,
            'old' => [],
            'appUrl' => $this->appUrl,
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    // Process COD order. Route: POST /checkout.
    public function placeOrder(): void
    {
        $userId = $this->getUserId();

        $input = $this->requestData();
        $this->validateCsrf($input);

        $address = trim((string) ($input['shipping_address'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));

        if ($address === '' || $phone === '') {
            $this->view('checkout/index', [
                'error' => 'Please fill in all required fields.',
                'old' => $input,
                'appUrl' => $this->appUrl,
                'csrfToken' => $this->csrfToken(),
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
                'old' => $input,
                'appUrl' => $this->appUrl,
                'csrfToken' => $this->csrfToken(),
            ]);
        }
    }

    // Process Stripe payment.
    public function payWithStripe(): void
    {
        $userId = $this->getUserId();

        $input = $this->requestData();
        $this->validateCsrf($input);

        $address = trim((string) ($input['shipping_address'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));

        if ($address === '' || $phone === '') {
            $this->view('checkout/index', [
                'error' => 'Please fill in all required fields.',
                'old' => $input,
                'appUrl' => $this->appUrl,
                'csrfToken' => $this->csrfToken(),
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

            $jodTotal = (float) $order['total_amount'];

            //? Sandbox conversion: JOD total -> USD cents (see the class constants).
            $amount = $this->jodToStripeAmount($jodTotal);

            if ($amount < 50) { // Stripe requires at least 0.50 USD for card payments.
                throw new Exception('Order amount is too small to pay by card.');
            }

            $session = $this->stripe->createCheckoutSession(
                [
                    [
                        'price_data' => [
                            'currency' => self::STRIPE_CURRENCY,
                            'product_data' => [
                                'name' => 'LootLab Order #' . $orderId,
                                'description' => $this->stripeAmountLabel($jodTotal, $amount)
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

            // Remember the order for this browser session (the Stripe session also carries it).
            $_SESSION['stripe_order_id'] = $orderId;

            $this->redirect($session->url);

        } catch (Throwable $e) {
            error_log('Stripe checkout session failed: ' . $e->getMessage());

            $this->renderCheckoutError(
                'We could not start the card payment. Please try again or use Cash on Delivery.',
                $input
            );
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
            'orderId' => $orderId,
            'appUrl' => $this->appUrl,
            'paymentMethod' => 'cod',
        ]);
    }



    // Verify successful Stripe payment.
    public function stripeSuccess(): void
    {
        $userId = $this->getUserId();

        $sessionId = $_GET['session_id'] ?? '';

        if (!is_string($sessionId) || $sessionId === '') {
            $this->redirect($this->appUrl . '/checkout');
            return;
        }

        try {
            $session = $this->stripe->retrieveCheckoutSession($sessionId);

            //? The Stripe session itself identifies the order; the session copy is only a fallback.
            $orderId = (string) $session->client_reference_id;
            $metadataOrderId = (string) ($session->metadata->order_id ?? '');
            $sessionOrderId = $_SESSION['stripe_order_id'] ?? null;

            if (
                $orderId === '' ||
                ($metadataOrderId !== '' && $metadataOrderId !== $orderId) ||
                ($sessionOrderId !== null && (string) $sessionOrderId !== $orderId)
            ) {
                throw new RuntimeException('Stripe session reference mismatch.');
            }

            $orderId = (int) $orderId;

            // Verify payment state with Stripe.
            if ($session->status !== 'complete' || $session->payment_status !== 'paid') {
                throw new RuntimeException('Stripe session is not paid.');
            }

            // Verify ownership and the expected amount/currency.
            $statement = $this->db->prepare(
                'SELECT id, total_amount FROM orders
                 WHERE id = :order_id AND user_id = :user_id'
            );

            $statement->execute([
                'order_id' => $orderId,
                'user_id' => $userId
            ]);

            $order = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new RuntimeException('Order does not belong to this user.');
            }

            $expectedAmount = $this->jodToStripeAmount((float) $order['total_amount']);

            if (
                (int) $session->amount_total !== $expectedAmount ||
                (string) $session->currency !== self::STRIPE_CURRENCY
            ) {
                throw new RuntimeException(
                    'Stripe amount mismatch: expected '
                    . $expectedAmount . ' ' . self::STRIPE_CURRENCY
                    . ', got ' . (int) $session->amount_total . ' ' . (string) $session->currency
                );
            }

            //? Clear the cart only once per order; a refresh must not wipe newly added items.
            if (($_SESSION['stripe_paid_order_id'] ?? null) !== $orderId) {
                $cart = new Cart(new Database(
                    (require __DIR__ . '/../../config/config.php')['database']
                ));

                $cartId = $cart->getOrCreateCartId($userId);
                $cart->clear($cartId);
            }

            $_SESSION['stripe_paid_order_id'] = $orderId;
            $_SESSION['last_order_id'] = $orderId;
            unset($_SESSION['stripe_order_id']);

            $this->view('checkout/success', [
                'orderId' => $orderId,
                'appUrl' => $this->appUrl,
                'paymentMethod' => 'stripe',
                'paidAmount' => $this->stripeAmountLabel((float) $order['total_amount'], $expectedAmount),
            ]);

        } catch (Throwable $e) {
            error_log('Stripe payment verification failed: ' . $e->getMessage());

            $this->renderCheckoutError(
                'We could not verify your card payment. If you were charged, please contact support with your order reference.'
            );
        }
    }
}
