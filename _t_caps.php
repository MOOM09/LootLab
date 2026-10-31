<?php
// Read-only capability probe (no writes).
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';

$key = $_ENV['STRIPE_SECRET_KEY'] ?? '';
echo 'using: ' . (str_starts_with($key, 'sk_test_') ? 'TEST key' : 'NOT a test key') . PHP_EOL;
$client = new Stripe\StripeClient($key);
unset($key);

echo 'SessionService methods: ' . implode(', ', array_filter(get_class_methods($client->checkout->sessions), fn($m) => !str_starts_with($m, '__'))) . PHP_EOL;

try {
    $methods = $client->paymentMethods->all(['limit' => 5]);
    echo 'paymentMethods.all(): OK (' . count($methods->data) . ' listed)' . PHP_EOL;
} catch (Throwable $e) {
    echo 'paymentMethods.all(): ' . get_class($e) . ': ' . substr($e->getMessage(), 0, 120) . PHP_EOL;
}

foreach (['PaymentIntentService', 'paymentIntents'] as $svc) {
    if (property_exists($client, $svc) || isset($client->{$svc})) {
        echo "client->$svc available" . PHP_EOL;
    }
}
echo 'PaymentIntentService file: ' . (is_file(__DIR__ . '/vendor/stripe/stripe-php/lib/Service/PaymentIntentService.php') ? 'yes' : 'no') . PHP_EOL;
echo 'PaymentIntentService methods: ' . implode(', ', array_filter(get_class_methods($client->paymentIntents), fn($m) => !str_starts_with($m, '__'))) . PHP_EOL;
