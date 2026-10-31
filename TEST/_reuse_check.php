<?php
// Read-only: is the Order #11 Stripe session still open?
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Services/StripeService.php';

$sessionId = 'cs_test_a1ojstnY6TQkr1s2UDAff9F2vfbXeb0WEiJuvsmfGLFLxXU6sFpaZoy5T3';

$svc = new StripeService();
$r = new ReflectionProperty('StripeService', 'stripe');
$r->setAccessible(true);
$stripe = $r->getValue($svc);

try {
    $s = $stripe->checkout->sessions->retrieve($sessionId);
    echo 'status=' . $s->status . PHP_EOL;
    echo 'payment_status=' . $s->payment_status . PHP_EOL;
    echo 'amount=' . $s->amount_total . ' ' . $s->currency . PHP_EOL;
    echo 'client_reference_id=' . $s->client_reference_id . PHP_EOL;
    echo 'reusable=' . var_export($s->status === 'open', true) . PHP_EOL;
    echo 'url=' . substr((string) $s->url, 0, 80) . PHP_EOL;
    file_put_contents(__DIR__ . '/_reuse_url.txt', (string) $s->url);
} catch (Throwable $e) {
    echo 'retrieve failed: ' . get_class($e) . ': ' . substr($e->getMessage(), 0, 200) . PHP_EOL;
}
