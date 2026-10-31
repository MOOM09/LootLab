<?php
// Expects: $orderId (int); optional $appUrl, $paymentMethod ('cod'|'stripe'), $paidAmount.
$orderId = (int) ($orderId ?? 0);
$appUrl = rtrim($appUrl ?? '', '/');
$paymentMethod = $paymentMethod ?? 'cod';
$paidAmount = $paidAmount ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order placed - LootLab</title>
</head>

<body>
    <h1>Thank you!</h1>

    <p>Your order <strong>#<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8') ?></strong> was placed successfully.</p>

    <?php if ($paymentMethod === 'stripe'): ?>
        <p>Payment received by card.</p>
        <?php if ($paidAmount !== null): ?>
            <p>Amount charged: <strong><?= htmlspecialchars((string) $paidAmount, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <?php endif; ?>
    <?php else: ?>
        <p>Payment method: Cash on Delivery. You pay when your order arrives.</p>
    <?php endif; ?>

    <?php if ($appUrl !== ''): ?>
        <p>
            <a href="<?= htmlspecialchars($appUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Continue shopping</a>
        </p>
    <?php endif; ?>
</body>

</html>