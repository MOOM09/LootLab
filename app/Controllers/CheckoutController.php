<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Interfaces/cartInterface.php';
require_once __DIR__ . '/../Models/CartItem.php';
require_once __DIR__ . '/../Models/PromoCode.php';
require_once __DIR__ . '/../Models/Cart.php';
require_once __DIR__ . '/../Services/CheckoutService.php';
require_once __DIR__ . '/../Services/StripeService.php';
require_once __DIR__ . '/../Services/TextBeeService.php';

class CheckoutController extends Controller
{
   private CheckoutService $checkout;
private Cart $cart;
    private ?StripeService $stripe = null;
    private PDO $db;
    private string $appUrl;
    private const STRIPE_CURRENCY = 'usd';
    private const DEFAULT_JOD_TO_USD_RATE = 1.41;
    private const OTP_TTL = 300;
    private const OTP_VERIFIED_TTL = 600;
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_RESEND_COOLDOWN = 60;
    private const OTP_MAX_SENDS_PER_15_MIN = 3;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $database = new Database($config['database']);
        $this->db = $database->getConnection();
        $this->checkout = new CheckoutService($database);
        $this->cart = new Cart($database);
        $this->appUrl = rtrim($config['app_url'], '/');
    }

    private function stripe(): StripeService
    {
        if ($this->stripe === null) $this->stripe = new StripeService();
        return $this->stripe;
    }

    private function jodToUsdRate(): float
    {
        $rate = (float) ($_ENV['STRIPE_JOD_TO_USD_RATE'] ?? self::DEFAULT_JOD_TO_USD_RATE);
        return $rate > 0 ? $rate : self::DEFAULT_JOD_TO_USD_RATE;
    }

    private function jodToStripeAmount(float $jodTotal): int
    {
        return (int) round($jodTotal * $this->jodToUsdRate() * 100);
    }

    private function stripeAmountLabel(float $jodTotal, int $amount): string
    {
        return strtoupper(self::STRIPE_CURRENCY) . ' ' . number_format($amount / 100, 2, '.', '')
            . ' (approx. ' . number_format($jodTotal, 3, '.', '') . ' JOD)';
    }

    private function renderCheckoutError(string $message, array $old = []): void
{
    $userId = $this->getUserId();

    $this->view('checkout/index', [
        'error' => $message,
        'notice' => null,
        'promoNotice' => null,
        'promoError' => null,
        'old' => $old,
        'appUrl' => $this->appUrl,
        'csrfToken' => $this->csrfToken(),
        ...$this->checkoutSummary($userId)
    ]);
}
    

    private function getUserId(): int
    {
        if (empty($_SESSION['user_id'])) $this->redirect($this->appUrl . '/login');
        return (int) $_SESSION['user_id'];
    }

    private function checkoutSummary(int $userId): array
{
    $cartId = $this->cart->getOrCreateCartId($userId);

    $this->cart->refreshPromo($cartId);

    $totals = $this->cart->getTotals($cartId);

    $stmt = $this->db->prepare(
        'SELECT p.code
         FROM carts c
         LEFT JOIN promo_codes p
             ON p.promo_id = c.promo_code_id
         WHERE c.id = :cart_id
           AND c.user_id = :user_id'
    );

    $stmt->execute([
        'cart_id' => $cartId,
        'user_id' => $userId
    ]);

    return [
        'totals' => $totals,
        'appliedPromoCode' => (string) ($stmt->fetchColumn() ?: '')
    ];
}

    public function index(): void
{
    $userId = $this->getUserId();

    $notice = ($_GET['payment'] ?? '') === 'cancelled'
        ? 'Your card payment was cancelled. Your order was not charged.'
        : null;

    $promoNotice = $_SESSION['checkout_promo_notice'] ?? null;
    $promoError = $_SESSION['checkout_promo_error'] ?? null;

    unset(
        $_SESSION['checkout_promo_notice'],
        $_SESSION['checkout_promo_error']
    );

    $this->view('checkout/index', [
        'error' => null,
        'notice' => $notice,
        'promoNotice' => $promoNotice,
        'promoError' => $promoError,
        'old' => [],
        'appUrl' => $this->appUrl,
        'csrfToken' => $this->csrfToken(),
        ...$this->checkoutSummary($userId)
    ]);
}
public function applyPromo(): void
{
    $userId = $this->getUserId();

    $input = $this->requestData();
    $this->validateCsrf($input);

    try {
        $code = trim((string) ($input['promo_code'] ?? ''));

        if ($code === '') {
            throw new InvalidArgumentException('Please enter a promo code.');
        }

        $cartId = $this->cart->getOrCreateCartId($userId);

        if (empty($this->cart->getItems($cartId))) {
            throw new RuntimeException('Your cart is empty.');
        }

        $config = require __DIR__ . '/../../config/config.php';
        $database = new Database($config['database']);

        $promoModel = new PromoCode($database);
        $promo = $promoModel->findValidByCode($code);

        if ($promo === null) {
            throw new InvalidArgumentException(
                'Invalid or expired promo code.'
            );
        }

        if (!$this->cart->applyPromoByCode($cartId, $code)) {
            throw new RuntimeException('Could not apply promo code.');
        }

        $_SESSION['checkout_promo_notice'] =
            'Promo code applied successfully.';

    } catch (Throwable $e) {
        $_SESSION['checkout_promo_error'] = $e->getMessage();
    }

    $this->redirect($this->appUrl . '/checkout');
}

public function removePromo(): void
{
    $userId = $this->getUserId();

    $input = $this->requestData();
    $this->validateCsrf($input);

    $cartId = $this->cart->getOrCreateCartId($userId);

    if ($this->cart->removePromo($cartId)) {
        $_SESSION['checkout_promo_notice'] =
            'Promo code removed.';
    } else {
        $_SESSION['checkout_promo_error'] =
            'Could not remove promo code.';
    }

    $this->redirect($this->appUrl . '/checkout');
}
    /** Normalize Jordanian phone numbers into +9627XXXXXXXX. */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-()]/', '', $phone);
        if (!preg_match('/^(?:\+962|00962|962|0)?(7[789]\d{7})$/', $phone, $matches)) {
            throw new InvalidArgumentException('Enter a valid Jordanian mobile number, e.g. 0791234567.');
        }
        return '+962' . $matches[1];
    }

    private function validatedInput(array $input): array
    {
        $address = trim((string) ($input['shipping_address'] ?? ''));
        $phone = $this->normalizePhone(trim((string) ($input['phone'] ?? '')));
        $lat = filter_var($input['delivery_lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($input['delivery_lng'] ?? null, FILTER_VALIDATE_FLOAT);
        $label = trim((string) ($input['meeting_point_label'] ?? ''));
        if ($address === '' || strlen($address) > 3000) {
            throw new InvalidArgumentException('Enter a shipping address (up to 3000 bytes).');
        }
        if ($lat === false || $lng === false || !is_finite((float) $lat) || !is_finite((float) $lng)
            || $lat < 29.12 || $lat > 33.45 || $lng < 34.86 || $lng > 39.35) {
            throw new InvalidArgumentException('Please pin a delivery meeting point inside Jordan on the map.');
        }
        if ($label === '' || (function_exists('mb_strlen') ? mb_strlen($label, 'UTF-8') : strlen($label)) > 255) {
            throw new InvalidArgumentException('Please confirm your meeting point on the map.');
        }
        return [
            'shipping_address' => $address, 'phone' => $phone,
            'delivery_lat' => (float) $lat, 'delivery_lng' => (float) $lng,
            'meeting_point_label' => $label,
        ];
    }

    private function hasVerifiedPhone(int $userId, string $phone): bool
    {
        $verified = $_SESSION['checkout_phone_verified'] ?? null;
        return is_array($verified)
            && ($verified['user_id'] ?? null) === $userId
            && ($verified['phone'] ?? null) === $phone
            && ($verified['expires_at'] ?? 0) > time();
    }

    private function sendCode(int $userId, array $pending): void
    {
        $now = time();
        $history = array_values(array_filter(
            $_SESSION['checkout_otp_send_history'] ?? [],
            static fn($t) => is_int($t) && $t > $now - 900
        ));
        if (count($history) >= self::OTP_MAX_SENDS_PER_15_MIN) {
            throw new RuntimeException('Too many SMS requests. Try again after 15 minutes.');
        }
        if ($history && end($history) > $now - self::OTP_RESEND_COOLDOWN) {
            throw new RuntimeException('Please wait 60 seconds before requesting another code.');
        }
        $code = (string) random_int(100000, 999999);
        (new TextBeeService())->sendOtp($pending['input']['phone'], $code);
        $history[] = $now;
        $_SESSION['checkout_otp_send_history'] = $history;
        $pending['code_hash'] = password_hash($code, PASSWORD_DEFAULT);
        $pending['expires_at'] = $now + self::OTP_TTL;
        $pending['attempts'] = 0;
        $pending['user_id'] = $userId;
        $_SESSION['checkout_otp_pending'] = $pending;
    }

    /** Called by both checkout actions. Never create an order until phone is verified. */
    private function beginCheckout(string $method): void
    {
        $userId = $this->getUserId();
        $input = $this->requestData();
        $this->validateCsrf($input);
        try {
            $data = $this->validatedInput($input);
            if ($this->hasVerifiedPhone($userId, $data['phone'])) {
                if ($method === 'stripe') $this->processStripe($userId, $data);
                else $this->processCod($userId, $data);
                return;
            }
            $this->sendCode($userId, ['method' => $method, 'input' => $data]);
            $this->redirect($this->appUrl . '/checkout/verify');
        } catch (Throwable $e) {
            error_log('Checkout SMS/validation: ' . $e->getMessage());
            $this->renderCheckoutError($e->getMessage(), $input);
        }
    }

    public function placeOrder(): void { $this->beginCheckout('cod'); }
    public function payWithStripe(): void { $this->beginCheckout('stripe'); }

    private function otpView(?string $error = null, ?string $notice = null): void
    {
        $pending = $_SESSION['checkout_otp_pending'] ?? [];
        $phone = (string) ($pending['input']['phone'] ?? '');
        $masked = strlen($phone) > 4 ? substr($phone, 0, 5) . '••••' . substr($phone, -3) : 'your phone';
        $this->view('checkout/verify', [
            'appUrl' => $this->appUrl, 'csrfToken' => $this->csrfToken(),
            'maskedPhone' => $masked, 'error' => $error, 'notice' => $notice,
            'expiresAt' => $pending['expires_at'] ?? 0,
        ]);
    }

    private function pendingForUser(): array
    {
        $userId = $this->getUserId();
        $pending = $_SESSION['checkout_otp_pending'] ?? null;
        if (!is_array($pending) || ($pending['user_id'] ?? null) !== $userId) {
            $this->redirect($this->appUrl . '/checkout');
        }
        return $pending;
    }

    public function showOtp(): void
    {
        $pending = $this->pendingForUser();
        $this->otpView(
            ($pending['expires_at'] ?? 0) <= time() ? 'The code has expired. Request a new code.' : null
        );
    }

    public function verifyOtp(): void
    {
        $pending = $this->pendingForUser();
        $input = $this->requestData();
        $this->validateCsrf($input);
        $code = trim((string) ($input['otp'] ?? ''));
        if (($pending['expires_at'] ?? 0) <= time()) {
            $this->otpView('This code has expired. Please request a new code.');
            return;
        }
        if (($pending['attempts'] ?? 0) >= self::OTP_MAX_ATTEMPTS) {
            $this->otpView('Too many incorrect attempts. Request a new code.');
            return;
        }
        if (!preg_match('/^\d{6}$/', $code) || !password_verify($code, $pending['code_hash'] ?? '')) {
            $_SESSION['checkout_otp_pending']['attempts'] = ($pending['attempts'] ?? 0) + 1;
            $this->otpView('Incorrect verification code. Please try again.');
            return;
        }
        $userId = $this->getUserId();
        $_SESSION['checkout_phone_verified'] = [
            'user_id' => $userId,
            'phone' => $pending['input']['phone'],
            'expires_at' => time() + self::OTP_VERIFIED_TTL,
        ];
        unset($_SESSION['checkout_otp_pending']);
        // The verified server-side pending input is used; never trust hidden fields on OTP page.
        if ($pending['method'] === 'stripe') $this->processStripe($userId, $pending['input']);
        else $this->processCod($userId, $pending['input']);
    }

    public function resendOtp(): void
    {
        $pending = $this->pendingForUser();
        $this->validateCsrf($this->requestData());
        try {
            $this->sendCode($this->getUserId(), $pending);
            $this->otpView(null, 'A new verification code was requested.');
        } catch (Throwable $e) {
            error_log('OTP resend: ' . $e->getMessage());
            $this->otpView($e->getMessage());
        }
    }

    private function processCod(int $userId, array $input): void
    {
        try {
            $orderId = $this->checkout->createOrder(
                $userId, $input['shipping_address'], $input['phone'],
                $input['delivery_lat'], $input['delivery_lng'], $input['meeting_point_label']
            );
            $_SESSION['last_order_id'] = $orderId;
            $this->redirect($this->appUrl . '/checkout/success');
        } catch (Throwable $e) {
            error_log('COD order failed: ' . $e->getMessage());
            $this->renderCheckoutError($e->getMessage(), $input);
        }
    }

    private function processStripe(int $userId, array $input): void
    {
        try {
            $orderId = $this->checkout->createStripeOrder(
                $userId, $input['shipping_address'], $input['phone'],
                $input['delivery_lat'], $input['delivery_lng'], $input['meeting_point_label']
            );
            $statement = $this->db->prepare('SELECT total_amount FROM orders WHERE id = :order_id AND user_id = :user_id');
            $statement->execute(['order_id' => $orderId, 'user_id' => $userId]);
            $order = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$order) throw new RuntimeException('Order not found.');
            $jodTotal = (float) $order['total_amount'];
            $amount = $this->jodToStripeAmount($jodTotal);
            if ($amount < 50) throw new RuntimeException('Order amount is too small to pay by card.');
            $session = $this->stripe()->createCheckoutSession(
                [[
                    'price_data' => [
                        'currency' => self::STRIPE_CURRENCY,
                        'product_data' => [
                            'name' => 'LootLab Order #' . $orderId,
                            'description' => $this->stripeAmountLabel($jodTotal, $amount),
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
                $this->appUrl . '/checkout/stripe-success?session_id={CHECKOUT_SESSION_ID}',
                $this->appUrl . '/checkout?payment=cancelled',
                $orderId
            );
            $_SESSION['stripe_order_id'] = $orderId;
            $this->redirect($session->url);
        } catch (Throwable $e) {
            error_log('Stripe checkout session failed: ' . $e->getMessage());
            $this->renderCheckoutError(
                'We could not start the card payment. Please try again or use Cash on Delivery.', $input
            );
        }
    }

    public function success(): void
    {
        $this->getUserId();
        $orderId = $_SESSION['last_order_id'] ?? null;
        if (!$orderId) $this->redirect($this->appUrl . '/checkout');
        $this->view('checkout/success', [
            'orderId' => $orderId, 'appUrl' => $this->appUrl, 'paymentMethod' => 'cod',
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
            $session = $this->stripe()->retrieveCheckoutSession($sessionId);

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
