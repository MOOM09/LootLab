
<?php

use Stripe\StripeClient;
use Stripe\Checkout\Session;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $secretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';

        if ($secretKey === '') {
            $secretKey = getenv('STRIPE_SECRET_KEY') ?: '';
        }

        if ($secretKey === '') {
            throw new Exception('Stripe secret key is missing.');
        }

        $this->stripe = new StripeClient($secretKey);
    }

    public function createCheckoutSession(
        array $lineItems,
        string $successUrl,
        string $cancelUrl,
        int $orderId
    ): Session {
        return $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            //? payment_method_types is rejected by the SDK's pinned API version
            //? (2026-10-28.endive). Card payments are configured in the Stripe Dashboard.
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $orderId,
            'metadata' => [
                'order_id' => (string) $orderId
            ]
        ]);
    }

    // Retrieve a Checkout Session from Stripe.
    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId);
    }
}
