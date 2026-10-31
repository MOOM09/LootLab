<?php
// Verifies the sandbox JOD->USD->cents conversion in CheckoutController.
require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/app/Core/Controller.php';
require __DIR__ . '/app/Core/Database.php';
require __DIR__ . '/app/Interfaces/cartInterface.php';
require __DIR__ . '/app/Models/CartItem.php';
require __DIR__ . '/app/Models/Cart.php';
require __DIR__ . '/app/Services/CheckoutService.php';
require __DIR__ . '/app/Services/StripeService.php';
require __DIR__ . '/app/Controllers/CheckoutController.php';

$c = new ReflectionClass('CheckoutController');
$ctrl = $c->newInstanceWithoutConstructor();

$mRate = $c->getMethod('jodToUsdRate');
$mRate->setAccessible(true);
$mAmount = $c->getMethod('jodToStripeAmount');
$mAmount->setAccessible(true);
$mLabel = $c->getMethod('stripeAmountLabel');
$mLabel->setAccessible(true);

$rate = $mRate->invoke($ctrl);
echo 'rate used: ' . $rate . ' USD per JOD' . PHP_EOL;

$cases = [[1.00, 141], [8.96, 1263], [12.50, 1763], [12.000, 1692], [0.10, 14], [0.00, 0]];
foreach ($cases as [$jod, $expected]) {
    $got = $mAmount->invoke($ctrl, (float) $jod);
    echo ($got === $expected ? 'PASS ' : 'FAIL ') . $jod . ' JOD -> ' . $got . ' cents (expected ' . $expected . ')' . PHP_EOL;
}

echo PHP_EOL . 'label example: ' . $mLabel->invoke($ctrl, 8.96, 1263) . PHP_EOL;

// Round-trip: the label must equal what Stripe will show (cents / 100).
$cents = $mAmount->invoke($ctrl, 8.96);
echo 'cents/100 rendered by Stripe: ' . number_format($cents / 100, 2, '.', '') . ' USD' . PHP_EOL;
