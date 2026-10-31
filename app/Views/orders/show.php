<?php

/**
 * Expects: $order (order row), $appUrl (string).
 */
$appUrl = rtrim($appUrl ?? '', '/');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= (int) $order['id'] ?> - LootLab</title>
</head>

<body>
    <h1>Order #<?= (int) $order['id'] ?></h1>

    <p>
        <a href="<?= htmlspecialchars($appUrl . '/orders', ENT_QUOTES, 'UTF-8') ?>">Back to orders</a>
    </p>

    <dl>
        <dt>Status</dt>
        <dd><?= htmlspecialchars((string) $order['status'], ENT_QUOTES, 'UTF-8') ?></dd>

        <dt>Total</dt>
        <dd><?= htmlspecialchars((string) $order['total_amount'], ENT_QUOTES, 'UTF-8') ?></dd>

        <dt>Shipping address</dt>
        <dd><?= nl2br(htmlspecialchars((string) $order['shipping_address'], ENT_QUOTES, 'UTF-8')) ?></dd>

        <dt>Phone</dt>
        <dd><?= htmlspecialchars((string) $order['phone'], ENT_QUOTES, 'UTF-8') ?></dd>

        <dt>Placed</dt>
        <dd><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>
</body>

</html>