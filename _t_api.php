<?php
// Phase 3 (test mode only): inspect the session via the API and confirm payment with
// Stripe's official test card using a test PaymentMethod. No real card, no real charge.
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Services/StripeService.php';

$state = json_decode(file_get_contents(__DIR__ . '/_e2e_state.json'), true);
$sessionId = null;
if (preg_match('#/c/pay/(cs_test_[A-Za-z0-9]+)#', $state['stripeUrl'], $m)) {
    $sessionId = $m[1];
}
echo 'session from redirect: ' . ($sessionId ?? 'not found') . PHP_EOL;

$svc = new StripeService();
$r = new ReflectionProperty('StripeService', 'stripe');
$r->setAccessible(true);
$stripe = $r->getValue($svc);

$s = $stripe->checkout->sessions->retrieve($sessionId);
echo 'currency=' . $s->currency . ' amount_total=' . $s->amount_total . ' status=' . $s->status
    . ' payment_status=' . $s->payment_status . PHP_EOL;
echo 'client_reference_id=' . $s->client_reference_id . ' metadata.order_id=' . ($s->metadata->order_id ?? '-') . PHP_EOL;

// What the customer sees on the Stripe page == amount_total/100. 1263 -> 12.63 USD.
echo 'displayed amount: ' . number_format($s->amount_total / 100, 2, '.', '') . ' ' . strtoupper($s->currency) . PHP_EOL;
echo 'matches 12.63 USD: ' . var_export($s->amount_total === 1263 && $s->currency === 'usd', true) . PHP_EOL;
echo 'payment_method_types: ' . (isset($s->payment_method_types) && is_array($s->payment_method_types) ? implode(',', $s->payment_method_types) : '(not returned)') . PHP_EOL;

// Pay with the official test card using a PaymentMethod (test mode).
try {
    $pm = $stripe->paymentMethods->create([
        'type' => 'card',
        'card' => ['token' => 'tok_visa'], // Stripe test token: Visa 4242 4242 4242 4242
    ]);
    echo 'test payment method created: ' . $pm->id . ' brand=' . $pm->card->brand . ' last4=' . $pm->card->last4 . PHP_EOL;

    $confirmed = $stripe->checkout->sessions->confirm($sessionId, [
        'payment_method' => $pm->id,
    ]);
    echo 'after confirm: status=' . $confirmed->status . ' payment_status=' . $confirmed->payment_status . PHP_EOL;
} catch (Throwable $e) {
    echo 'CONFIRM FAILED: ' . get_class($e) . ': ' . substr($e->getMessage(), 0, 250) . PHP_EOL;
}
