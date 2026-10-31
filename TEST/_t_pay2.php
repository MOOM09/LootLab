<?php
// Complete the existing Checkout Session in TEST mode via its PaymentIntent,
// using the official test card token tok_visa (4242 4242 4242 4242). No real charge.
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Services/StripeService.php';

$state = json_decode(file_get_contents(__DIR__ . '/_e2e_state.json'), true);
preg_match('#/c/pay/(cs_test_[A-Za-z0-9]+)#', $state['stripeUrl'], $m);
$sessionId = $m[1] ?? null;
echo 'session: ' . $sessionId . PHP_EOL;

$svc = new StripeService();
$r = new ReflectionProperty('StripeService', 'stripe');
$r->setAccessible(true);
$stripe = $r->getValue($svc);

$s = $stripe->checkout->sessions->retrieve($sessionId);
echo 'before: status=' . $s->status . ' payment_status=' . $s->payment_status
    . ' amount=' . $s->amount_total . ' ' . $s->currency
    . ' pi=' . var_export($s->payment_intent, true) . PHP_EOL;

$piId = is_string($s->payment_intent) ? $s->payment_intent : ($s->payment_intent->id ?? null);
if (!$piId) {
    echo 'NO PAYMENT INTENT — session not in a confirmable state' . PHP_EOL;
    exit;
}

try {
    $pi = $stripe->paymentIntents->retrieve($piId);
    echo 'payment intent: ' . $pi->id . ' status=' . $pi->status . ' amount=' . $pi->amount . ' ' . $pi->currency . PHP_EOL;

    // Test-mode confirmation with Stripe's official test card token.
    $pm = $stripe->paymentMethods->create(['type' => 'card', 'card' => ['token' => 'tok_visa']]);
    echo 'test card: ' . $pm->id . ' ' . $pm->card->brand . ' ****' . $pm->card->last4 . PHP_EOL;

    $confirmed = $stripe->paymentIntents->confirm($piId, ['payment_method' => $pm->id]);
    echo 'confirmed PI status=' . $confirmed->status . PHP_EOL;

    sleep(3);
    $after = $stripe->checkout->sessions->retrieve($sessionId);
    echo 'after: status=' . $after->status . ' payment_status=' . $after->payment_status
        . ' amount_total=' . $after->amount_total . ' ' . $after->currency . PHP_EOL;
} catch (Throwable $e) {
    echo 'FAILED: ' . get_class($e) . ': ' . substr($e->getMessage(), 0, 300) . PHP_EOL;
}
